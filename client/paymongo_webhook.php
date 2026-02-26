<?php
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";
require_once __DIR__ . "/include/receipt_image_generator.php";

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  header("Content-Type: text/plain; charset=utf-8");
  exit("Webhook endpoint is LIVE ✅");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit("Method not allowed");
}

$raw = file_get_contents("php://input");
if (!$raw) { http_response_code(400); exit("Empty body"); }

// --- signature header ---
$headers = function_exists("getallheaders") ? getallheaders() : [];
$sigHeader = $headers["Paymongo-Signature"] ?? $headers["paymongo-signature"] ?? ($_SERVER["HTTP_PAYMONGO_SIGNATURE"] ?? null);
if (!$sigHeader) { http_response_code(400); exit("Missing Paymongo-Signature"); }

// parse t=..., te=..., li=...
$parts = [];
foreach (explode(",", $sigHeader) as $kv) {
  $kv = trim($kv);
  if (strpos($kv, "=") === false) continue;
  [$k, $v] = explode("=", $kv, 2);
  $parts[trim($k)] = trim($v);
}
$t  = $parts["t"] ?? null;
$te = $parts["te"] ?? null;
$li = $parts["li"] ?? null;
if (!$t) { http_response_code(400); exit("Missing timestamp"); }

$expected = hash_hmac("sha256", $t . "." . $raw, PAYMONGO_WEBHOOK_SECRET);
$given = $te ?: $li;
if (!$given || !hash_equals($expected, $given)) {
  http_response_code(401);
  exit("Invalid signature");
}

$event = json_decode($raw, true);
if (!is_array($event)) { http_response_code(400); exit("Invalid JSON"); }

$eventType   = $event["data"]["attributes"]["type"] ?? "";
$payloadData = $event["data"]["attributes"]["data"] ?? null;
if (!$eventType || !is_array($payloadData)) { http_response_code(400); exit("Invalid event"); }

$isPaid   = in_array($eventType, ["payment.paid", "checkout_session.payment.paid"], true);
$isFailed = in_array($eventType, ["payment.failed", "checkout_session.payment.failed"], true);
if (!$isPaid && !$isFailed) { http_response_code(200); exit("Ignored"); }

// metadata extractor
function extract_meta(array $payloadData): array {
  if (isset($payloadData["attributes"]["metadata"]) && is_array($payloadData["attributes"]["metadata"])) {
    return $payloadData["attributes"]["metadata"];
  }
  if (isset($payloadData["attributes"]["payment_intent"]["attributes"]["metadata"]) &&
      is_array($payloadData["attributes"]["payment_intent"]["attributes"]["metadata"])) {
    return $payloadData["attributes"]["payment_intent"]["attributes"]["metadata"];
  }
  if (isset($payloadData["attributes"]["payments"][0]["attributes"]["metadata"]) &&
      is_array($payloadData["attributes"]["payments"][0]["attributes"]["metadata"])) {
    return $payloadData["attributes"]["payments"][0]["attributes"]["metadata"];
  }
  return [];
}

$meta    = extract_meta($payloadData);
$tx_id   = (int)($meta["tx_id"] ?? 0);
$loan_id = (int)($meta["loan_id"] ?? 0);
$user_id = (int)($meta["user_id"] ?? 0);

if ($tx_id <= 0 || $loan_id <= 0) { http_response_code(200); exit("Missing metadata"); }

// pull method + payment id
$paymongo_payment_id = null;
$provider_method = null;

if (($payloadData["type"] ?? "") === "payment") {
  $paymongo_payment_id = $payloadData["id"] ?? null; // pay_...
  $provider_method = $payloadData["attributes"]["source"]["type"] ?? null; // gcash/paymaya/card/bank
} else {
  $paymongo_payment_id = $payloadData["attributes"]["payments"][0]["id"] ?? null; // pay_...
  $provider_method = $payloadData["attributes"]["payment_method_used"] ?? null;
}

$provider_method = $provider_method ? strtoupper((string)$provider_method) : null;

$conn->begin_transaction();

try {
  // lock tx row
  $q = $conn->prepare("SELECT id, amount, status, receipt_number FROM transactions WHERE id=? FOR UPDATE");
  $q->bind_param("i", $tx_id);
  $q->execute();
  $tx = $q->get_result()->fetch_assoc();
  $q->close();

  if (!$tx) { $conn->commit(); http_response_code(200); exit("TX not found"); }

  $currentStatus = strtoupper((string)($tx["status"] ?? "PENDING"));
  if ($currentStatus === "SUCCESS" || $currentStatus === "FAILED") {
    $conn->commit();
    http_response_code(200);
    exit("Already processed");
  }

  $paidAt = date("Y-m-d H:i:s");

  if ($isFailed) {
    $u = $conn->prepare("UPDATE transactions SET status='FAILED', trans_date=? WHERE id=?");
    $u->bind_param("si", $paidAt, $tx_id);
    $u->execute();
    $u->close();
  } else {
    // SUCCESS
    $receiptNumber = $tx["receipt_number"] ?: ("RCPT-" . date("Ymd") . "-" . str_pad((string)$tx_id, 6, "0", STR_PAD_LEFT));

    $u = $conn->prepare("
      UPDATE transactions
      SET status='SUCCESS',
          trans_date=?,
          user_id = COALESCE(user_id, ?),
          provider_method = COALESCE(?, provider_method),
          paymongo_payment_id = COALESCE(?, paymongo_payment_id),
          receipt_number = COALESCE(?, receipt_number)
      WHERE id=?
    ");
    $u->bind_param("sisssi", $paidAt, $user_id, $provider_method, $paymongo_payment_id, $receiptNumber, $tx_id);
    $u->execute();
    $u->close();

    // deduct loan
    $amount = (float)$tx["amount"];
    $l = $conn->prepare("
      UPDATE loans
      SET outstanding = GREATEST(0, outstanding - ?),
          next_payment = DATE_ADD(next_payment, INTERVAL 1 MONTH)
      WHERE id=?
    ");
    $l->bind_param("di", $amount, $loan_id);
    $l->execute();
    $l->close();
  }

  // Fetch updated tx row
  $txq = $conn->prepare("
    SELECT id, loan_id, amount, status, trans_date, provider_method,
           paymongo_payment_id, paymongo_checkout_id, receipt_number
    FROM transactions
    WHERE id=? LIMIT 1
  ");
  $txq->bind_param("i", $tx_id);
  $txq->execute();
  $txRow = $txq->get_result()->fetch_assoc();
  $txq->close();

  // Generate FINAL image based on new status
  $receiptUrl = PUBLIC_BASE_URL . "/client/receipt.php?tx_id=" . $tx_id;
  $dir = ensure_receipt_dir();
  $status = strtoupper((string)$txRow["status"]);
  $filenameFinal = $txRow["receipt_number"] . "-" . $status . ".png";
  $filePathFinal = $dir . "/" . $filenameFinal;

  generate_receipt_png($txRow, $receiptUrl, $filePathFinal);

  $finalUrl = PUBLIC_BASE_URL . "/client/receipts/" . rawurlencode($filenameFinal);
  $upFinal = $conn->prepare("UPDATE transactions SET receipt_image_final_url=? WHERE id=?");
  $upFinal->bind_param("si", $finalUrl, $tx_id);
  $upFinal->execute();
  $upFinal->close();

  $conn->commit();
  http_response_code(200);
  echo "OK";
} catch (Exception $e) {
  $conn->rollback();
  http_response_code(500);
  echo "ERR: " . $e->getMessage();
}