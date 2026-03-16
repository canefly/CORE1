<?php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/API/api_vault.php";
require_once __DIR__ . "/receipt_image_generator.php";

date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

$tx_id = (int)($_GET['tx_id'] ?? 0);
$ok = isset($_GET['ok']);

if (!$ok || $tx_id <= 0) {
  header("Location: myloans.php");
  exit;
}

$stmt = $conn->prepare("SELECT * FROM transactions WHERE id=? LIMIT 1");
$stmt->bind_param("i", $tx_id);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tx) {
  exit("Transaction not found.");
}

$checkoutId = $tx['paymongo_checkout_id'] ?? '';
if (!$checkoutId) {
  header("Location: receipt.php?tx_id=" . $tx_id);
  exit;
}

// Grabbing the pre-encoded auth string from the vault
$auth = $paymongo_auth_base64;
$ch = curl_init("https://api.paymongo.com/v1/checkout_sessions/" . urlencode($checkoutId));
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_HTTPHEADER => [
    "Authorization: Basic {$auth}",
    "Accept: application/json"
  ],
  CURLOPT_TIMEOUT => 30,
]);
$response = curl_exec($ch);
$http = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);

if ($http < 200 || $http >= 300 || !is_array($data)) {
  header("Location: receipt.php?tx_id=" . $tx_id);
  exit;
}

$attrs = $data['data']['attributes'] ?? [];
$payments = $attrs['payments'] ?? [];

// If payment exists, mark only as PAID_PENDING
if (!empty($payments) && isset($payments[0]['id'])) {
  $paymentId = $payments[0]['id'];
  $methodRaw = $payments[0]['attributes']['source']['type'] ?? '';
  $method = strtoupper($methodRaw ?: 'PAID');

  $paidAt = date("Y-m-d H:i:s");

  $u = $conn->prepare("
    UPDATE transactions
    SET status='PAID_PENDING',
        provider_method=?,
        paymongo_payment_id=?,
        trans_date=?
    WHERE id=? AND status='PENDING'
  ");
  $u->bind_param("sssi", $method, $paymentId, $paidAt, $tx_id);
  $u->execute();
  $u->close();
}

header("Location: receipt.php?tx_id=" . $tx_id);
exit;