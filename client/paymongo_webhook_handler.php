<?php
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";

$raw = file_get_contents("php://input");
if (!$raw) { http_response_code(400); exit("No payload"); }

// Signature header (PayMongo)
$sigHeader = $_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? '';
if (!$sigHeader) { http_response_code(400); exit("Missing Paymongo-Signature"); }

// Parse t=..., te=..., li=...
$parts = [];
foreach (explode(',', $sigHeader) as $kv) {
  $kv = trim($kv);
  if (strpos($kv, '=') === false) continue;
  [$k, $v] = explode('=', $kv, 2);
  $parts[$k] = $v;
}
$t  = $parts['t']  ?? null;
$te = $parts['te'] ?? null;
$li = $parts['li'] ?? null;

if (!$t) { http_response_code(400); exit("Missing timestamp"); }

// Compute expected HMAC
$expected = hash_hmac('sha256', $t . "." . $raw, PAYMONGO_WEBHOOK_SECRET);
$given = $te ?: $li;

if (!$given || !hash_equals($expected, $given)) {
  http_response_code(401);
  exit("Invalid signature");
}

$payload = json_decode($raw, true);
if (!is_array($payload)) { http_response_code(400); exit("Invalid JSON"); }

$eventType = $payload['data']['attributes']['type'] ?? '';
$dataObj   = $payload['data']['attributes']['data'] ?? null;

if (!$eventType || !is_array($dataObj)) { http_response_code(400); exit("Invalid event"); }

if ($eventType !== 'checkout_session.payment.paid') {
  http_response_code(200);
  exit("Ignored");
}

$attrs    = $dataObj['attributes'] ?? [];
$metadata = $attrs['metadata'] ?? [];

$tx_id   = (int)($metadata['tx_id'] ?? 0);
$loan_id = (int)($metadata['loan_id'] ?? 0);
// user_id optional sa update check; but good to keep
$user_id = (int)($metadata['user_id'] ?? 0);

$checkoutId = $dataObj['id'] ?? null; // cs_...
$payments = $attrs['payments'] ?? [];
$paymentId = $payments[0]['id'] ?? null; // pay_...

if ($tx_id <= 0 || $loan_id <= 0 || !$checkoutId) {
  http_response_code(400);
  exit("Missing metadata");
}

$conn->begin_transaction();
try {
  // Lock tx row
  $q = $conn->prepare("SELECT id, amount, status FROM transactions WHERE id=? FOR UPDATE");
  $q->bind_param("i", $tx_id);
  $q->execute();
  $r = $q->get_result();
  if (!$r || $r->num_rows !== 1) throw new Exception("TX not found");
  $tx = $r->fetch_assoc();
  $q->close();

  if ($tx['status'] === 'SUCCESS') {
    $conn->commit();
    http_response_code(200);
    exit("Already processed");
  }

  $amount = (float)$tx['amount'];
  $paidAt = date("Y-m-d H:i:s");

  // Update tx
  $u = $conn->prepare("
    UPDATE transactions
    SET status='SUCCESS',
        trans_date=?,
        paymongo_checkout_id=?,
        paymongo_payment_id=?
    WHERE id=?
  ");
  $u->bind_param("sssi", $paidAt, $checkoutId, $paymentId, $tx_id);
  $u->execute();
  $u->close();

  // Deduct loan outstanding
  $l = $conn->prepare("UPDATE loans SET outstanding = GREATEST(0, outstanding - ?) WHERE id=?");
  $l->bind_param("di", $amount, $loan_id);
  $l->execute();
  $l->close();

  $conn->commit();
  http_response_code(200);
  echo "OK";
} catch (Exception $e) {
  $conn->rollback();
  http_response_code(500);
  echo "Error: " . $e->getMessage();
}