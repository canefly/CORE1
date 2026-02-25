<?php
// CLIENT/create_checkout.php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";
require_once __DIR__ . "/receipt_image_generator.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

$loan_id = (int)($_GET['loan_id'] ?? 0);
if ($loan_id <= 0) { http_response_code(400); exit("Invalid loan_id"); }

// loan must belong to user
$stmt = $conn->prepare("SELECT id, monthly_due, status FROM loans WHERE id=? AND user_id=? LIMIT 1");
$stmt->bind_param("ii", $loan_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) { http_response_code(404); exit("Loan not found"); }
$loan = $res->fetch_assoc();
$stmt->close();

if (strtoupper((string)$loan['status']) !== 'ACTIVE') {
  http_response_code(400);
  exit("Loan is not ACTIVE.");
}

$amount = (float)$loan['monthly_due'];
if ($amount <= 0) { http_response_code(400); exit("Invalid monthly_due"); }
$amountCentavos = (int) round($amount * 100);

// 1) Create PENDING transaction
$ins = $conn->prepare("
  INSERT INTO transactions (user_id, loan_id, amount, status, trans_date, provider_method)
  VALUES (?, ?, ?, 'PENDING', NOW(), 'TO_BE_CONFIRMED')
");
$ins->bind_param("iid", $user_id, $loan_id, $amount);
if (!$ins->execute()) { http_response_code(500); exit("Insert tx failed: " . htmlspecialchars($ins->error)); }
$tx_id = (int)$conn->insert_id;
$ins->close();

// 2) Receipt number now (pending)
$receiptNumber = "RCPT-" . date("Ymd") . "-" . str_pad((string)$tx_id, 6, "0", STR_PAD_LEFT);
$u0 = $conn->prepare("UPDATE transactions SET receipt_number=? WHERE id=?");
$u0->bind_param("si", $receiptNumber, $tx_id);
$u0->execute();
$u0->close();

// 3) Create PayMongo checkout session
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
  $up = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
  $up->bind_param("i", $tx_id);
  $up->execute();
  $up->close();
  http_response_code(500);
  exit("cURL error: " . htmlspecialchars($curlErr));
}

$data = json_decode($response, true);
if (!is_array($data) || $http < 200 || $http >= 300) {
  $up = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
  $up->bind_param("i", $tx_id);
  $up->execute();
  $up->close();
  http_response_code(500);
  exit("PayMongo error (HTTP {$http}):<pre>" . htmlspecialchars($response) . "</pre>");
}

$checkoutUrl = $data["data"]["attributes"]["checkout_url"] ?? null;
$checkoutId  = $data["data"]["id"] ?? null;

if (!$checkoutUrl || !$checkoutId) {
  $up = $conn->prepare("UPDATE transactions SET status='FAILED' WHERE id=?");
  $up->bind_param("i", $tx_id);
  $up->execute();
  $up->close();
  http_response_code(500);
  exit("Missing checkout_url/checkout_id from PayMongo");
}

// 4) Save checkout id (proof while pending)
$u1 = $conn->prepare("UPDATE transactions SET paymongo_checkout_id=? WHERE id=?");
$u1->bind_param("si", $checkoutId, $tx_id);
$u1->execute();
$u1->close();

// 5) Generate + save PENDING receipt image (file)
$receiptUrl = PUBLIC_BASE_URL . "/client/receipt.php?tx_id=" . $tx_id;

$dir = ensure_receipt_dir();
$filenamePending = $receiptNumber . "-PENDING.png";
$filePathPending = $dir . "/" . $filenamePending;

$txRow = [
  "id" => $tx_id,
  "loan_id" => $loan_id,
  "amount" => $amount,
  "status" => "PENDING",
  "trans_date" => date("Y-m-d H:i:s"),
  "provider_method" => "TO_BE_CONFIRMED",
  "paymongo_payment_id" => null,
  "paymongo_checkout_id" => $checkoutId,
  "receipt_number" => $receiptNumber
];

$okImg = generate_receipt_png($txRow, $receiptUrl, $filePathPending);

// Save URL only if file exists (avoid blank/broken images)
if ($okImg && file_exists($filePathPending)) {
  // store relative path (works on localhost & ngrok; receipt.php will render it)
  $pendingUrl = "receipts/" . $filenamePending;

  $u2 = $conn->prepare("UPDATE transactions SET receipt_image_pending_url=? WHERE id=?");
  $u2->bind_param("si", $pendingUrl, $tx_id);
  $u2->execute();
  $u2->close();
} else {
  error_log("Receipt image not generated for tx_id={$tx_id}. Check GD + permissions. path={$filePathPending}");
}

// Redirect user to checkout
header("Location: " . $checkoutUrl);
exit;