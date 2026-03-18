<?php
// CLIENT/create_checkout.php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

file_put_contents(__DIR__ . "/debug_payments.log",
  "[" . date("Y-m-d H:i:s") . "] HIT create_checkout.php\n",
  FILE_APPEND
);

require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/API/api_vault.php";
require_once __DIR__ . "/receipt_image_generator.php";

function resolveTransactionBreakdown(mysqli $conn, string $source, array $loan, int $loan_id, ?int $restructured_loan_id, float $amount): array
{
  $principalAmount = 0.00;
  $interestAmount  = 0.00;
  $penaltyAmount   = 0.00;
  $monthlyDue      = round($amount, 2);

  if ($source === 'restructured') {
    $outstanding  = round((float)($loan['outstanding'] ?? 0), 2);
    $interestRate = round((float)($loan['interest_rate'] ?? 0), 2);

    if ($interestRate > 0 && $outstanding > 0) {
      $interestAmount = round($outstanding * ($interestRate / 100), 2);
      if ($interestAmount > $amount) {
        $interestAmount = 0.00;
      }
    }

    $principalAmount = round($amount - $interestAmount - $penaltyAmount, 2);

    if ($principalAmount < 0) {
      $principalAmount = 0.00;
    }

    return [
      'principal_amount' => $principalAmount,
      'interest_amount'  => $interestAmount,
      'penalty_amount'   => $penaltyAmount,
      'monthly_due'      => $monthlyDue
    ];
  }

  $interestRate = 0.00;
  $outstanding  = 0.00;

  $loanStmt = $conn->prepare("
    SELECT interest_rate, outstanding
    FROM loans
    WHERE id = ?
    LIMIT 1
  ");
  $loanStmt->bind_param("i", $loan_id);
  $loanStmt->execute();
  $loanRes = $loanStmt->get_result();
  $loanRow = $loanRes ? $loanRes->fetch_assoc() : null;
  $loanStmt->close();

  if ($loanRow) {
    $interestRate = round((float)($loanRow['interest_rate'] ?? 0), 2);
    $outstanding  = round((float)($loanRow['outstanding'] ?? 0), 2);
  }

  if ($interestRate > 0 && $outstanding > 0) {
    $interestAmount = round($outstanding * ($interestRate / 100), 2);
    if ($interestAmount > $amount) {
      $interestAmount = 0.00;
    }
  }

  $principalAmount = round($amount - $interestAmount - $penaltyAmount, 2);

  if ($principalAmount < 0) {
    $principalAmount = 0.00;
  }

  return [
    'principal_amount' => $principalAmount,
    'interest_amount'  => $interestAmount,
    'penalty_amount'   => $penaltyAmount,
    'monthly_due'      => $monthlyDue
  ];
}

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$source  = trim((string)($_GET['source'] ?? 'original'));

$loan_id = 0;
$restructured_loan_id = null;
$amount = 0.00;
$loanLabel = '';
$loan = [];

if ($source === 'restructured') {
  $restructured_loan_id = (int)($_GET['restructured_loan_id'] ?? 0);
  if ($restructured_loan_id <= 0) {
    http_response_code(400);
    exit("Invalid restructured_loan_id");
  }

  $stmt = $conn->prepare("
    SELECT id, original_loan_id, principal_amount, monthly_due, outstanding, interest_rate, status
    FROM restructured_loans
    WHERE id = ? AND user_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("ii", $restructured_loan_id, $user_id);
  $stmt->execute();
  $res = $stmt->get_result();

  if (!$res || $res->num_rows !== 1) {
    http_response_code(404);
    exit("Restructured loan not found");
  }

  $loan = $res->fetch_assoc();
  $stmt->close();

  $loan_id = (int)($loan['original_loan_id'] ?? 0);
  if ($loan_id <= 0) {
    http_response_code(400);
    exit("Invalid original loan mapping for restructured loan.");
  }

  if (strtoupper((string)$loan['status']) !== 'ACTIVE') {
    http_response_code(400);
    exit("Restructured loan is not ACTIVE.");
  }

  if ((float)$loan['outstanding'] <= 0) {
    http_response_code(400);
    exit("Restructured loan has no outstanding balance.");
  }

  $amount = (float)$loan['monthly_due'];
  if ($amount <= 0) {
    http_response_code(400);
    exit("Invalid monthly_due");
  }

  $loanLabel = "Restructured Loan Payment (RL #{$restructured_loan_id})";
} else {
  $loan_id = (int)($_GET['loan_id'] ?? 0);
  $restructured_loan_id = null;

  if ($loan_id <= 0) {
    http_response_code(400);
    exit("Invalid loan_id");
  }

  $stmt = $conn->prepare("
    SELECT id, monthly_due, outstanding, status
    FROM loans
    WHERE id = ? AND user_id = ?
    LIMIT 1
  ");
  $stmt->bind_param("ii", $loan_id, $user_id);
  $stmt->execute();
  $res = $stmt->get_result();

  if (!$res || $res->num_rows !== 1) {
    http_response_code(404);
    exit("Loan not found");
  }

  $loan = $res->fetch_assoc();
  $stmt->close();

  if (strtoupper((string)$loan['status']) !== 'ACTIVE') {
    http_response_code(400);
    exit("Loan is not ACTIVE.");
  }

  if ((float)$loan['outstanding'] <= 0) {
    http_response_code(400);
    exit("Loan has no outstanding balance.");
  }

  $amount = (float)$loan['monthly_due'];
  if ($amount <= 0) {
    http_response_code(400);
    exit("Invalid monthly_due");
  }

  $loanLabel = "Loan Payment (Loan #{$loan_id})";
}

if ($loan_id <= 0) {
  http_response_code(400);
  exit("Invalid loan_id for transaction.");
}

// extra FK-safe validation for restructured transactions only
if ($source === 'restructured') {
  if ($restructured_loan_id === null || $restructured_loan_id <= 0) {
    http_response_code(400);
    exit("Invalid restructured_loan_id for transaction.");
  }

  $fkCheck = $conn->prepare("
    SELECT id
    FROM restructured_loans
    WHERE id = ?
    LIMIT 1
  ");
  $fkCheck->bind_param("i", $restructured_loan_id);
  $fkCheck->execute();
  $fkRes = $fkCheck->get_result();
  $fkRow = $fkRes ? $fkRes->fetch_assoc() : null;
  $fkCheck->close();

  if (!$fkRow) {
    http_response_code(400);
    exit("Restructured loan reference does not exist.");
  }
}

$amountCentavos = (int) round($amount * 100);

// optional blocker: huwag payagan kung may existing PAID_PENDING
if ($source === 'restructured') {
  $chk = $conn->prepare("
    SELECT id
    FROM transactions
    WHERE user_id = ?
      AND restructured_loan_id = ?
      AND status = 'PAID_PENDING'
    LIMIT 1
  ");
  $chk->bind_param("ii", $user_id, $restructured_loan_id);
} else {
  $chk = $conn->prepare("
    SELECT id
    FROM transactions
    WHERE user_id = ?
      AND loan_id = ?
      AND status = 'PAID_PENDING'
    LIMIT 1
  ");
  $chk->bind_param("ii", $user_id, $loan_id);
}
$chk->execute();
$chkRes = $chk->get_result();
if ($chkRes && $chkRes->num_rows > 0) {
  http_response_code(400);
  exit("You still have a payment pending verification for this loan.");
}
$chk->close();

file_put_contents(__DIR__ . "/debug_payments.log",
  "[" . date("Y-m-d H:i:s") . "] BEFORE INSERT source={$source} loan_id={$loan_id} restructured_loan_id=" . ($restructured_loan_id === null ? 'NULL' : $restructured_loan_id) . " user_id={$user_id} amount={$amount}\n",
  FILE_APPEND
);

$breakdown = resolveTransactionBreakdown(
  $conn,
  $source,
  $loan,
  $loan_id,
  $restructured_loan_id,
  (float)$amount
);

$principal_amount = (float)$breakdown['principal_amount'];
$interest_amount  = (float)$breakdown['interest_amount'];
$penalty_amount   = (float)$breakdown['penalty_amount'];
$monthly_due      = (float)$breakdown['monthly_due'];

file_put_contents(__DIR__ . "/debug_payments.log",
  "[" . date("Y-m-d H:i:s") . "] BREAKDOWN tx source={$source} loan_id={$loan_id} restructured_loan_id=" . ($restructured_loan_id === null ? 'NULL' : $restructured_loan_id) . " principal={$principal_amount} interest={$interest_amount} penalty={$penalty_amount} monthly_due={$monthly_due}\n",
  FILE_APPEND
);

// 1) Create PENDING transaction
$ins = $conn->prepare("
  INSERT INTO transactions (
    user_id,
    loan_id,
    restructured_loan_id,
    amount,
    principal_amount,
    interest_amount,
    penalty_amount,
    monthly_due,
    status,
    trans_date,
    provider_method
  )
  VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING', NOW(), '')
");
$ins->bind_param(
  "iiiddddd",
  $user_id,
  $loan_id,
  $restructured_loan_id,
  $amount,
  $principal_amount,
  $interest_amount,
  $penalty_amount,
  $monthly_due
);

if (!$ins->execute()) {
  http_response_code(500);
  exit("Insert tx failed: " . htmlspecialchars($ins->error));
}
$tx_id = (int)$conn->insert_id;
$ins->close();

file_put_contents(__DIR__ . "/debug_payments.log",
  "[" . date("Y-m-d H:i:s") . "] INSERT OK tx_id={$tx_id} source={$source} loan_id={$loan_id} restructured_loan_id=" . ($restructured_loan_id === null ? 'NULL' : $restructured_loan_id) . "\n",
  FILE_APPEND
);

// 2) Receipt number now
$receiptNumber = "RCPT-" . date("Ymd") . "-" . str_pad((string)$tx_id, 6, "0", STR_PAD_LEFT);
$u0 = $conn->prepare("UPDATE transactions SET receipt_number=? WHERE id=?");
$u0->bind_param("si", $receiptNumber, $tx_id);
$u0->execute();
$u0->close();

// 3) Create PayMongo checkout session
// Grabbing the pre-encoded auth string from the vault
$auth = $paymongo_auth_base64;

$payload = [
  "data" => [
    "attributes" => [
      "line_items" => [[
        "name" => $loanLabel,
        "quantity" => 1,
        "amount" => $amountCentavos,
        "currency" => "PHP"
      ]],
      "payment_method_types" => ["gcash", "paymaya", "card"],
      "success_url" => PUBLIC_BASE_URL . "/CORE1/client/payment_return.php?ok=1&tx_id={$tx_id}",
      "cancel_url"  => PUBLIC_BASE_URL . "/CORE1/client/payment_return.php?cancel=1&tx_id={$tx_id}",
      "description" => "Monthly loan payment",
      "metadata" => [
        "tx_id" => (string)$tx_id,
        "source" => $source,
        "loan_id" => (string)$loan_id,
        "restructured_loan_id" => $restructured_loan_id === null ? "" : (string)$restructured_loan_id,
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

// 4) Save checkout id
$u1 = $conn->prepare("UPDATE transactions SET paymongo_checkout_id=? WHERE id=?");
$u1->bind_param("si", $checkoutId, $tx_id);
$u1->execute();
$u1->close();

// 5) Generate pending receipt image
$receiptUrl = PUBLIC_BASE_URL . "/CORE1/client/receipt.php?tx_id=" . $tx_id;

$dir = ensure_receipt_dir();
$filenamePending = $receiptNumber . "-PENDING.png";
$filePathPending = $dir . "/" . $filenamePending;

$txRow = [
  "id" => $tx_id,
  "loan_id" => $loan_id,
  "restructured_loan_id" => $restructured_loan_id,
  "amount" => $amount,
  "principal_amount" => $principal_amount,
  "interest_amount" => $interest_amount,
  "penalty_amount" => $penalty_amount,
  "monthly_due" => $monthly_due,
  "status" => "PENDING",
  "trans_date" => date("Y-m-d H:i:s"),
  "provider_method" => "TO_BE_CONFIRMED",
  "paymongo_payment_id" => null,
  "paymongo_checkout_id" => $checkoutId,
  "receipt_number" => $receiptNumber
];

$okImg = generate_receipt_png($txRow, $receiptUrl, $filePathPending);
if (!$okImg) {
  http_response_code(500);
  exit("Receipt image generation failed. Enable GD extension and ensure /client/receipts is writable.");
}

if ($okImg && file_exists($filePathPending)) {
  $pendingUrl = "receipts/" . $filenamePending;
  $u2 = $conn->prepare("UPDATE transactions SET receipt_image_pending_url=? WHERE id=?");
  $u2->bind_param("si", $pendingUrl, $tx_id);
  $u2->execute();
  $u2->close();
}

header("Location: " . $checkoutUrl);
exit;