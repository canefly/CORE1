<?php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/API/api_vault.php";
require_once __DIR__ . "/receipt_image_generator.php";
require_once __DIR__ . "/send_payment_to_financial.php";

date_default_timezone_set('Asia/Manila');
$conn->query("SET time_zone = '+08:00'");

$logFile = __DIR__ . "/debug_payment_return.log";

function dbg_return(string $message): void {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL, FILE_APPEND);
}

/**
 * Added only: payment breakdown resolver
 * No existing logic removed
 */
function resolvePaymentBreakdown(mysqli $conn, array $txRow): array
{
    $loanId = (int)($txRow['loan_id'] ?? 0);
    $restructuredLoanId = (int)($txRow['restructured_loan_id'] ?? 0);
    $amount = round((float)($txRow['amount'] ?? 0), 2);

    $finalLoanId = $loanId > 0 ? $loanId : $restructuredLoanId;

    $principalAmount = $amount;
    $interestAmount = 0.00;
    $penaltyAmount = 0.00;
    $paymentType = 'loan_principal';

    if ($finalLoanId <= 0 || $amount <= 0) {
        return [
            'principal_amount' => $principalAmount,
            'interest_amount' => $interestAmount,
            'penalty_amount' => $penaltyAmount,
            'payment_type' => $paymentType
        ];
    }

    // Try to resolve from loans table
    $loanStmt = $conn->prepare("
        SELECT monthly_due, interest_rate, interest_method, outstanding
        FROM loans
        WHERE id = ?
        LIMIT 1
    ");
    $loanStmt->bind_param("i", $finalLoanId);
    $loanStmt->execute();
    $loanRow = $loanStmt->get_result()->fetch_assoc();
    $loanStmt->close();

    if ($loanRow) {
        $monthlyDue = round((float)($loanRow['monthly_due'] ?? 0), 2);
        $interestRate = (float)($loanRow['interest_rate'] ?? 0);
        $interestMethod = strtoupper((string)($loanRow['interest_method'] ?? ''));
        $outstanding = round((float)($loanRow['outstanding'] ?? 0), 2);

        if ($monthlyDue > 0 && abs($amount - $monthlyDue) <= 0.05) {
            if ($interestRate > 0) {
                if ($interestMethod === 'FLAT') {
                    $interestAmount = round($outstanding * ($interestRate / 100), 2);
                } else {
                    $interestAmount = round($outstanding * ($interestRate / 100), 2);
                }

                if ($interestAmount > $amount) {
                    $interestAmount = 0.00;
                }

                $principalAmount = round($amount - $interestAmount - $penaltyAmount, 2);

                if ($principalAmount < 0) {
                    $principalAmount = $amount;
                    $interestAmount = 0.00;
                }
            }
        }
    }

    return [
        'principal_amount' => round($principalAmount, 2),
        'interest_amount' => round($interestAmount, 2),
        'penalty_amount' => round($penaltyAmount, 2),
        'payment_type' => $paymentType
    ];
}

function buildFinancialPaymentPayloadFromTx(mysqli $conn, array $txRow): array
{
    $userId = (int)$txRow["user_id"];

    $userRow = null;
    $userStmt = $conn->prepare("
        SELECT id, fullname, email, phone
        FROM users
        WHERE id = ?
        LIMIT 1
    ");
    $userStmt->bind_param("i", $userId);
    $userStmt->execute();
    $userRow = $userStmt->get_result()->fetch_assoc();
    $userStmt->close();

    $loanAppRow = null;
    $loanAppStmt = $conn->prepare("
        SELECT source_of_income, estimated_monthly_income
        FROM loan_applications
        WHERE user_id = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    $loanAppStmt->bind_param("i", $userId);
    $loanAppStmt->execute();
    $loanAppRow = $loanAppStmt->get_result()->fetch_assoc();
    $loanAppStmt->close();

    $finalLoanId = (int)$txRow["loan_id"] > 0
        ? (int)$txRow["loan_id"]
        : (int)$txRow["restructured_loan_id"];

    $breakdown = resolvePaymentBreakdown($conn, $txRow);

    return [
        "transaction_id" => (int)$txRow["id"],
        "user_id" => (int)$txRow["user_id"],
        "loan_id" => $finalLoanId,
        "restructured_loan_id" => (int)$txRow["restructured_loan_id"],
        "amount" => (float)$txRow["amount"],
        "status" => (string)$txRow["status"],
        "payment_date" => (string)$txRow["trans_date"],
        "provider_method" => (string)$txRow["provider_method"],
        "paymongo_payment_id" => (string)$txRow["paymongo_payment_id"],
        "paymongo_checkout_id" => (string)$txRow["paymongo_checkout_id"],
        "receipt_number" => (string)$txRow["receipt_number"],

        "principal_amount" => (float)$breakdown["principal_amount"],
        "interest_amount" => (float)$breakdown["interest_amount"],
        "penalty_amount" => (float)$breakdown["penalty_amount"],
        "payment_type" => (string)$breakdown["payment_type"],

        "client_full_name" => (string)($userRow["fullname"] ?? ''),
        "client_email" => (string)($userRow["email"] ?? ''),
        "client_phone" => (string)($userRow["phone"] ?? ''),
        "client_occupation" => (string)($loanAppRow["source_of_income"] ?? ''),
        "client_monthly_income" => (float)($loanAppRow["estimated_monthly_income"] ?? 0)
    ];
}

function refetchTransactionForReturn(mysqli $conn, int $txId): ?array
{
    $refetch = $conn->prepare("
        SELECT id, user_id, loan_id, restructured_loan_id, amount, status, trans_date,
               provider_method, paymongo_payment_id, paymongo_checkout_id, receipt_number,
               receipt_image_pending_url, receipt_image_final_url
        FROM transactions
        WHERE id=?
        LIMIT 1
    ");
    $refetch->bind_param("i", $txId);
    $refetch->execute();
    $txRow = $refetch->get_result()->fetch_assoc();
    $refetch->close();
    return $txRow ?: null;
}

$tx_id = (int)($_GET['tx_id'] ?? 0);
$ok = isset($_GET['ok']);

dbg_return("HIT payment_return.php tx_id={$tx_id} ok=" . ($ok ? '1' : '0') . " raw_get=" . json_encode($_GET));

if (!$ok || $tx_id <= 0) {
    dbg_return("EXIT invalid ok/tx_id");
    header("Location: myloans.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM transactions WHERE id=? LIMIT 1");
$stmt->bind_param("i", $tx_id);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();
$stmt->close();

dbg_return("TX row: " . json_encode($tx));

if (!$tx) {
    dbg_return("EXIT transaction not found");
    exit("Transaction not found.");
}

$checkoutId = $tx['paymongo_checkout_id'] ?? '';
if (!$checkoutId) {
    dbg_return("EXIT missing checkout id, redirect to receipt");
    header("Location: receipt.php?tx_id=" . $tx_id);
    exit;
}

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
$curlErr = curl_error($ch);
curl_close($ch);

dbg_return("PayMongo checkout fetch http={$http} curl_error={$curlErr} raw_response=" . $response);

$data = json_decode($response, true);
$attrs = is_array($data) ? ($data['data']['attributes'] ?? []) : [];
$payments = $attrs['payments'] ?? [];

dbg_return("Parsed payments array: " . json_encode($payments));

if (!empty($payments) && isset($payments[0]['id'])) {
    $paymentId = $payments[0]['id'];
    $methodRaw = $payments[0]['attributes']['source']['type'] ?? '';
    $method = strtoupper($methodRaw ?: 'PAID');
    $paidAt = date("Y-m-d H:i:s");

    dbg_return("Payment found paymentId={$paymentId} method={$method} paidAt={$paidAt}");

    $u = $conn->prepare("
        UPDATE transactions
        SET status='PAID_PENDING',
            provider_method=?,
            paymongo_payment_id=?,
            trans_date=?
        WHERE id=? AND status IN ('PENDING','PAID_PENDING')
    ");
    $u->bind_param("sssi", $method, $paymentId, $paidAt, $tx_id);
    $u->execute();
    $affected = $u->affected_rows;
    $u->close();

    dbg_return("Transaction update affected_rows={$affected}");
} else {
    dbg_return("NO payment found in checkout session. Fallback path will check transaction state.");
}

$txRow = refetchTransactionForReturn($conn, $tx_id);
dbg_return("Refetched txRow: " . json_encode($txRow));

if ($txRow) {
    $txStatusUpper = strtoupper((string)($txRow["status"] ?? ''));

    if ($txStatusUpper === 'PAID_PENDING') {
        $paymentPayload = buildFinancialPaymentPayloadFromTx($conn, $txRow);

        dbg_return("Payment breakdown=" . json_encode([
            "principal_amount" => $paymentPayload["principal_amount"],
            "interest_amount" => $paymentPayload["interest_amount"],
            "penalty_amount" => $paymentPayload["penalty_amount"],
            "payment_type" => $paymentPayload["payment_type"]
        ]));
        dbg_return("Sending to FINANCIAL payload=" . json_encode($paymentPayload));

        $financialResponse = sendPaymentToFinancial($paymentPayload);

        dbg_return("FINANCIAL response=" . json_encode($financialResponse));
    } else {
        dbg_return("Skip fallback send because tx status is {$txStatusUpper}");
    }
} else {
    dbg_return("No txRow found after refetch");
}

header("Location: receipt.php?tx_id=" . $tx_id);
exit;
