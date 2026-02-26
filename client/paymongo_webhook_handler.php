<?php
// C:\xampp\htdocs\client\paymongo_webhook_handler.php

// Load DB + PayMongo config (ensure $conn and constants exist)
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";
require_once __DIR__ . "/receipt_image_generator.php";

// If included from paymongo_webhook.php, raw may already exist
if (!isset($raw)) {
  $raw = file_get_contents("php://input");
}
if (!$raw) { http_response_code(400); exit("No payload"); }

// =========================
// Signature Verification
// =========================
$headers = function_exists("getallheaders") ? getallheaders() : [];

$sigHeader =
  ($headers["Paymongo-Signature"] ?? $headers["paymongo-signature"] ?? null) ?:
  ($_SERVER['HTTP_PAYMONGO_SIGNATURE'] ?? null);

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

// ✅ Replace this with your real webhook secret (keep it in paymongo_config.php ideally)
$webhookSecret = defined('PAYMONGO_WEBHOOK_SECRET')
  ? PAYMONGO_WEBHOOK_SECRET
  : 'whsk_qpbMXZ8uqjRvETy8NKzs8bYf';

$expected = hash_hmac('sha256', $t . "." . $raw, $webhookSecret);

// PayMongo sends te=... (li can be blank)
$given = $te ?: $li;

if (!$given || !hash_equals($expected, $given)) {
  http_response_code(401);
  exit("Invalid signature");
}

// =========================
// Parse payload
// =========================
$payload = json_decode($raw, true);
if (!is_array($payload)) { http_response_code(400); exit("Invalid JSON"); }

$eventType = $payload['data']['attributes']['type'] ?? '';
$dataObj   = $payload['data']['attributes']['data'] ?? null;

if (!$eventType || !is_array($dataObj)) { http_response_code(400); exit("Invalid event"); }

// We only handle paid events
if ($eventType !== 'checkout_session.payment.paid' && $eventType !== 'payment.paid') {
  http_response_code(200);
  exit("Ignored");
}

$attrs = $dataObj['attributes'] ?? [];
if (!is_array($attrs)) $attrs = [];

// =========================
// Helpers
// =========================
function arr_get($a, $path, $default = null) {
  $keys = explode('.', $path);
  foreach ($keys as $k) {
    if (!is_array($a) || !array_key_exists($k, $a)) return $default;
    $a = $a[$k];
  }
  return $a;
}

function normalize_method($raw) {
  $m = strtoupper((string)$raw);
  if ($m === '') return 'PAID';
  // common mappings
  if ($m === 'GCASH') return 'GCASH';
  if ($m === 'PAYMAYA' || $m === 'MAYA') return 'MAYA';
  if ($m === 'CARD') return 'CARD';
  return $m;
}

// =========================
// Extract metadata (tx_id, loan_id) from multiple possible locations
// =========================
$metadata = [];

// typical: attributes.metadata
if (isset($attrs['metadata']) && is_array($attrs['metadata'])) {
  $metadata = $attrs['metadata'];
}

// sometimes in payment_intent
if (!$metadata && isset($attrs['payment_intent']['attributes']['metadata']) && is_array($attrs['payment_intent']['attributes']['metadata'])) {
  $metadata = $attrs['payment_intent']['attributes']['metadata'];
}

// sometimes in payments[0].attributes.metadata
if (!$metadata && isset($attrs['payments'][0]['attributes']['metadata']) && is_array($attrs['payments'][0]['attributes']['metadata'])) {
  $metadata = $attrs['payments'][0]['attributes']['metadata'];
}

// Sometimes metadata is nested deeper (best-effort)
if (!$metadata) {
  $maybe = arr_get($payload, 'data.attributes.data.attributes.metadata', null);
  if (is_array($maybe)) $metadata = $maybe;
}

$tx_id   = (int)($metadata['tx_id'] ?? 0);
$loan_id = (int)($metadata['loan_id'] ?? 0);

// IDs
$objectId = $dataObj['id'] ?? null; // could be cs_... or pay_... depending on event
$checkoutId = null;
$paymentId  = null;

// For checkout_session.payment.paid:
// - dataObj id is checkout_session id (cs_...)
// - payments array holds payment id (pay_...)
if ($eventType === 'checkout_session.payment.paid') {
  $checkoutId = $objectId;
  $paymentId  = $attrs['payments'][0]['id'] ?? null;
}

// For payment.paid:
// - dataObj id is payment id (pay_...)
// - checkout id may not be present; we can still update payment id
if ($eventType === 'payment.paid') {
  $paymentId = $objectId;
}

// =========================
// Extract payment method (source.type) from multiple locations
// =========================
$methodRaw = null;

// Most common in payment object: attributes.source.type
$methodRaw = $methodRaw ?: ($attrs['source']['type'] ?? null);

// For checkout_session events, method might be inside first payment object
if (!$methodRaw && isset($attrs['payments'][0]['attributes']['source']['type'])) {
  $methodRaw = $attrs['payments'][0]['attributes']['source']['type'];
}

// For payment.paid event, source is usually in payment.attributes.source.type (already in $attrs)
if (!$methodRaw) {
  $methodRaw = arr_get($payload, 'data.attributes.data.attributes.source.type', null);
}

$provider_method = normalize_method($methodRaw);

// =========================
// If metadata missing, fallback: map tx_id via checkoutId (since you stored paymongo_checkout_id)
// =========================
if ($tx_id <= 0 && $checkoutId) {
  $s = $conn->prepare("SELECT id, loan_id FROM transactions WHERE paymongo_checkout_id=? LIMIT 1");
  $s->bind_param("s", $checkoutId);
  $s->execute();
  $r = $s->get_result();
  if ($r && $r->num_rows === 1) {
    $row = $r->fetch_assoc();
    $tx_id = (int)$row['id'];
    if ($loan_id <= 0) $loan_id = (int)$row['loan_id'];
  }
  $s->close();
}

// If still missing, we can't proceed safely
if ($tx_id <= 0) {
  http_response_code(200);
  exit("Missing tx_id");
}

// =========================
// Process transaction atomically
// =========================
$conn->begin_transaction();
try {
  // Lock tx row
  $q = $conn->prepare("SELECT * FROM transactions WHERE id=? FOR UPDATE");
  $q->bind_param("i", $tx_id);
  $q->execute();
  $r = $q->get_result();
  if (!$r || $r->num_rows !== 1) throw new Exception("TX not found");
  $tx = $r->fetch_assoc();
  $q->close();

  // If already success, ignore (idempotent)
  if (strtoupper((string)($tx['status'] ?? '')) === 'SUCCESS') {
    $conn->commit();
    http_response_code(200);
    exit("Already processed");
  }

  // Determine loan id if missing
  if ($loan_id <= 0) {
    $loan_id = (int)($tx['loan_id'] ?? 0);
  }

  $amount = (float)($tx['amount'] ?? 0);
  $paidAt = date("Y-m-d H:i:s");

  // Update tx SUCCESS + method + refs
  $u = $conn->prepare("
    UPDATE transactions
    SET status='SUCCESS',
        provider_method=?,
        trans_date=?,
        paymongo_checkout_id=COALESCE(?, paymongo_checkout_id),
        paymongo_payment_id=COALESCE(?, paymongo_payment_id)
    WHERE id=?
  ");
  $u->bind_param("ssssi", $provider_method, $paidAt, $checkoutId, $paymentId, $tx_id);
  $u->execute();
  $u->close();

  // Update loan if available
  if ($loan_id > 0 && $amount > 0) {
    // Deduct outstanding + move next_payment
    $l = $conn->prepare("
      UPDATE loans
      SET outstanding = GREATEST(0, outstanding - ?),
          next_payment = DATE_ADD(next_payment, INTERVAL 1 MONTH)
      WHERE id=?
    ");
    $l->bind_param("di", $amount, $loan_id);
    $l->execute();
    $l->close();

    // If fully paid => completed
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
  }

  // =========================
  // Generate FINAL receipt image and save URL
  // =========================
  $receiptUrl = PUBLIC_BASE_URL . "/client/receipt.php?tx_id=" . $tx_id;

  $dir = ensure_receipt_dir();
  $rcptNo = (string)($tx['receipt_number'] ?? '');
  if ($rcptNo === '') {
    $rcptNo = "RCPT-" . date("Ymd") . "-" . str_pad((string)$tx_id, 6, "0", STR_PAD_LEFT);
    // ensure receipt_number exists
    $u3a = $conn->prepare("UPDATE transactions SET receipt_number=? WHERE id=?");
    $u3a->bind_param("si", $rcptNo, $tx_id);
    $u3a->execute();
    $u3a->close();
  }

  $finalFilename = $rcptNo . "-FINAL.png";
  $finalPath = $dir . "/" . $finalFilename;

  // refresh tx data for method/proof text on the image
  $s2 = $conn->prepare("SELECT * FROM transactions WHERE id=? LIMIT 1");
  $s2->bind_param("i", $tx_id);
  $s2->execute();
  $txFresh = $s2->get_result()->fetch_assoc();
  $s2->close();

  $okImg = generate_receipt_png($txFresh ?: $tx, $receiptUrl, $finalPath);
  if ($okImg && file_exists($finalPath)) {
    // store relative URL; receipt.php can render it directly
    $finalUrl = "receipts/" . $finalFilename;

    $u4 = $conn->prepare("UPDATE transactions SET receipt_image_final_url=? WHERE id=?");
    $u4->bind_param("si", $finalUrl, $tx_id);
    $u4->execute();
    $u4->close();
  } else {
    error_log("FINAL receipt image failed: tx_id={$tx_id} path={$finalPath}");
  }

  $conn->commit();

  http_response_code(200);
  echo "OK";

} catch (Exception $e) {
  $conn->rollback();
  http_response_code(500);
  echo "Error: " . $e->getMessage();
}