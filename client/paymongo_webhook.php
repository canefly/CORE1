<?php
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  header("Content-Type: text/plain; charset=utf-8");
  exit("Webhook endpoint is LIVE ✅ (PayMongo will POST JSON here)");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Method not allowed");
}

$raw = file_get_contents("php://input");
$headers = function_exists("getallheaders") ? getallheaders() : [];

$method = $_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN';
$ct = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
$cl = $_SERVER['CONTENT_LENGTH'] ?? ($_SERVER['HTTP_CONTENT_LENGTH'] ?? '');

file_put_contents(
  __DIR__ . "/paymongo_webhook_log.txt",
  "----\n" . date("c") . "\n" .
  "METHOD: {$method}\nCONTENT_TYPE: {$ct}\nCONTENT_LENGTH: {$cl}\n" .
  "RAW:\n{$raw}\n\n",
  FILE_APPEND
);

if (!$raw) { http_response_code(400); exit("Empty body"); }

// --- Verify signature ---
$sigHeader = $headers["Paymongo-Signature"] ?? $headers["paymongo-signature"] ?? null;
if ($sigHeader) {
  $parts = [];
  foreach (explode(",", $sigHeader) as $kv) {
    $kv = trim($kv);
    if (strpos($kv, "=") !== false) {
      [$k, $v] = array_map("trim", explode("=", $kv, 2));
      $parts[$k] = $v;
    }
  }

  $t = $parts["t"] ?? null;
  $sig = $parts["te"] ?? ($parts["li"] ?? null);

  if ($t && $sig) {
    $expected = hash_hmac("sha256", $t . "." . $raw, PAYMONGO_WEBHOOK_SECRET);
    if (!hash_equals($expected, $sig)) {
      http_response_code(400);
      exit("Invalid signature");
    }
  }
}

$event = json_decode($raw, true);
if (!is_array($event)) { http_response_code(400); exit("Invalid JSON"); }

$eventType = $event["data"]["attributes"]["type"] ?? "";
$payloadData = $event["data"]["attributes"]["data"] ?? null;
if (!$payloadData) { http_response_code(200); exit("No data"); }

$isPaid   = ($eventType === "payment.paid" || $eventType === "checkout_session.payment.paid");
$isFailed = ($eventType === "payment.failed" || $eventType === "checkout_session.payment.failed");

// ✅ Smart metadata extraction
$meta = [];
if (isset($payloadData["attributes"]["metadata"]) && is_array($payloadData["attributes"]["metadata"])) {
  $meta = $payloadData["attributes"]["metadata"];
}
if (!$meta && isset($payloadData["attributes"]["payment_intent"]["attributes"]["metadata"])
    && is_array($payloadData["attributes"]["payment_intent"]["attributes"]["metadata"])) {
  $meta = $payloadData["attributes"]["payment_intent"]["attributes"]["metadata"];
}
if (!$meta && isset($payloadData["attributes"]["payments"][0]["attributes"]["metadata"])
    && is_array($payloadData["attributes"]["payments"][0]["attributes"]["metadata"])) {
  $meta = $payloadData["attributes"]["payments"][0]["attributes"]["metadata"];
}

$tx_id = (int)($meta["tx_id"] ?? 0);
$loan_id = (int)($meta["loan_id"] ?? 0);

if ($tx_id <= 0 || $loan_id <= 0) {
  http_response_code(200);
  exit("Missing metadata");
}

$conn->begin_transaction();

try {
  if ($isPaid) {
    $q = $conn->prepare("SELECT amount, status FROM transactions WHERE id=? FOR UPDATE");
    $q->bind_param("i", $tx_id);
    $q->execute();
    $txRow = $q->get_result()->fetch_assoc();
    $q->close();

    if (!$txRow) throw new Exception("Transaction not found.");

    if (($txRow["status"] ?? "") === "SUCCESS") {
      $conn->commit();
      http_response_code(200);
      exit("Already processed");
    }

    $amount = (float)$txRow["amount"];

    $u = $conn->prepare("UPDATE transactions SET status='SUCCESS' WHERE id=?");
    $u->bind_param("i", $tx_id);
    $u->execute();
    $u->close();

    $u1 = $conn->prepare("UPDATE loans
                          SET outstanding = GREATEST(0, outstanding - ?),
                              next_payment = DATE_ADD(next_payment, INTERVAL 1 MONTH)
                          WHERE id=?");
    $u1->bind_param("di", $amount, $loan_id);
    $u1->execute();
    $u1->close();

    $c = $conn->prepare("SELECT outstanding FROM loans WHERE id=?");
    $c->bind_param("i", $loan_id);
    $c->execute();
    $out = $c->get_result()->fetch_assoc();
    $c->close();

    if ($out && (float)$out["outstanding"] <= 0.00001) {
      $u2 = $conn->prepare("UPDATE loans SET status='COMPLETED' WHERE id=?");
      $u2->bind_param("i", $loan_id);
      $u2->execute();
      $u2->close();
    }

  } elseif ($isFailed) {
    $u = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
    $u->bind_param("i", $tx_id);
    $u->execute();
    $u->close();
  }

  $conn->commit();
  http_response_code(200);
  echo "OK";
} catch (Exception $e) {
  $conn->rollback();
  http_response_code(500);
  echo "ERR: " . $e->getMessage();
}