<?php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = (int)$_SESSION['user_id'];
$loan_id = (int)($_GET['loan_id'] ?? 0);
if ($loan_id <= 0) exit("Invalid loan_id");

// Get loan (must belong to logged-in user)
$stmt = $conn->prepare("SELECT id, monthly_due, status FROM loans WHERE id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii", $loan_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) exit("Loan not found.");
$loan = $res->fetch_assoc();
$stmt->close();

if (strtoupper((string)$loan['status']) !== 'ACTIVE') exit("Loan is not ACTIVE.");

$amount = (float)$loan['monthly_due'];
if ($amount <= 0) exit("Invalid monthly_due.");

$amountCentavos = (int) round($amount * 100);

// Create local transaction record as PENDING
$ins = $conn->prepare("
  INSERT INTO transactions (loan_id, amount, method, activity, status, trans_date)
  VALUES (?, ?, 'PAYMONGO', 'PAYMENT', 'PENDING', NOW())
");
$ins->bind_param("id", $loan_id, $amount);
if (!$ins->execute()) exit("Failed to create transaction: " . htmlspecialchars($ins->error));
$tx_id = (int)$conn->insert_id;
$ins->close();

// Call PayMongo: Create Checkout Session
$auth = base64_encode(PAYMONGO_SECRET_KEY . ":");

$payload = [
  "data" => [
    "attributes" => [
      "line_items" => [[
        "name" => "Loan Payment (Loan #{$loan_id})",
        "quantity" => 1,
        "amount" => $amountCentavos,
        "currency" => "PHP"
      ]],
      "payment_method_types" => ["gcash", "paymaya", "card"],
      "success_url" => PUBLIC_BASE_URL . "/client/payment_return.php?ok=1&tx_id={$tx_id}",
      "cancel_url"  => PUBLIC_BASE_URL . "/client/payment_return.php?cancel=1&tx_id={$tx_id}",
      "description" => "Monthly loan payment",
      "metadata" => [
        "tx_id"   => (string)$tx_id,
        "loan_id" => (string)$loan_id,
        "user_id" => (string)$user_id
      ]
    ]
  ]
];

$ch = curl_init("https://api.paymongo.com/v1/checkout_sessions");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POST => true,
  CURLOPT_HTTPHEADER => [
    "Content-Type: application/json",
    "Authorization: Basic {$auth}"
  ],
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_TIMEOUT => 30,
]);

$response = curl_exec($ch);
$curlErr  = curl_error($ch);
$http     = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($response === false) {
  // mark local tx failed
  $up = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
  $up->bind_param("i", $tx_id);
  $up->execute();
  $up->close();

  exit("cURL error: " . htmlspecialchars($curlErr));
}

// Decode JSON safely
$data = json_decode($response, true);
if (!is_array($data)) {
  // mark local tx failed
  $up = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
  $up->bind_param("i", $tx_id);
  $up->execute();
  $up->close();

  exit("PayMongo returned non-JSON (HTTP {$http}):<br><pre>" . htmlspecialchars($response) . "</pre>");
}

// If HTTP not 2xx, show PayMongo errors clearly
if ($http < 200 || $http >= 300) {
  $up = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
  $up->bind_param("i", $tx_id);
  $up->execute();
  $up->close();

  exit("PayMongo API error (HTTP {$http}):<br><pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>");
}

// Extract checkout
$checkoutUrl = $data["data"]["attributes"]["checkout_url"] ?? null;
$checkoutId  = $data["data"]["id"] ?? null;

if (!$checkoutUrl || !$checkoutId) {
  // mark local tx failed
  $up = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
  $up->bind_param("i", $tx_id);
  $up->execute();
  $up->close();

  exit("No checkout_url/checkout_id from PayMongo (HTTP {$http}). Response:<br><pre>" .
       htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>");
}

// Save PayMongo refs (if these columns exist)
$try = $conn->prepare("UPDATE transactions SET paymongo_checkout_id=?, checkout_url=? WHERE id=?");
if ($try) {
  $try->bind_param("ssi", $checkoutId, $checkoutUrl, $tx_id);
  $try->execute();
  $try->close();
}

// Redirect
header("Location: " . $checkoutUrl);
exit;