<!--C:\xampp\htdocs\CORE1\client\pay_loan_using_wallet.php-->
<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/session_checker.php";
require_once __DIR__ . "/include/wallet_helper.php";
require_once __DIR__ . "/send_payment_to_financial.php";
require_once __DIR__ . "/send_wallet_payment_to_core2.php";
require_once __DIR__ . "/receipt_image_generator.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = (int) $_SESSION['user_id'];

$fullName = getUserFullName($conn, $user_id);
$wallet   = getOrCreateWallet($conn, $user_id);

$walletId        = (int) ($wallet['id'] ?? 0);
$accountNumber   = (string) ($wallet['account_number'] ?? '');
$walletStatus    = (string) ($wallet['status'] ?? 'ACTIVE');
$balance         = (float) ($wallet['balance'] ?? 0);

$loanContext     = getEffectiveLoanContext($conn, $user_id);
$hasLoan         = (bool) ($loanContext['has_loan'] ?? false);
$loanType        = (string) ($loanContext['loan_type'] ?? '');
$loanId          = $loanContext['loan_id'] ?? null;
$restructuredId  = $loanContext['restructured_loan_id'] ?? null;
$monthlyDue      = (float) ($loanContext['monthly_due'] ?? 0);
$outstanding     = (float) ($loanContext['outstanding'] ?? 0);
$nextPaymentRaw  = $loanContext['next_payment'] ?? null;
$loanStatus      = (string) ($loanContext['status'] ?? '');

$nextPaymentDisplay = formatWalletDate($nextPaymentRaw);

$errorMessage = '';
$successMessage = '';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function walletPayLog(string $message): void
{
    $logFile = __DIR__ . '/debug_wallet_pay.log';
    @file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function resolveWalletPaymentBreakdown(
    mysqli $conn,
    string $source,
    array $loan,
    int $loan_id,
    int $restructured_loan_id,
    float $amount
): array {
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
            'monthly_due'      => $monthlyDue,
            'payment_type'     => 'restructured_wallet'
        ];
    }

    $interestRate = 0.00;
    $loanOutstanding = 0.00;

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
        $loanOutstanding = round((float)($loanRow['outstanding'] ?? 0), 2);
    }

    if ($interestRate > 0 && $loanOutstanding > 0) {
        $interestAmount = round($loanOutstanding * ($interestRate / 100), 2);
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
        'monthly_due'      => $monthlyDue,
        'payment_type'     => 'wallet'
    ];
}

function hasExistingPaidPending(
    mysqli $conn,
    int $userId,
    int $loanId,
    int $restructuredLoanId,
    string $source
): bool {
    if ($source === 'restructured') {
        $stmt = $conn->prepare("
            SELECT id
            FROM transactions
            WHERE user_id = ?
              AND restructured_loan_id = ?
              AND status = 'PAID_PENDING'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $userId, $restructuredLoanId);
    } else {
        $stmt = $conn->prepare("
            SELECT id
            FROM transactions
            WHERE user_id = ?
              AND loan_id = ?
              AND status = 'PAID_PENDING'
            LIMIT 1
        ");
        $stmt->bind_param("ii", $userId, $loanId);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $exists = $res && $res->num_rows > 0;
    $stmt->close();

    return $exists;
}

function fetchUserRow(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare("SELECT fullname, email, phone FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? ($res->fetch_assoc() ?: []) : [];
    $stmt->close();

    return $row;
}

function fetchLoanApplicationExtras(mysqli $conn, int $loanId): array
{
    $default = [
        'source_of_income' => '',
        'estimated_monthly_income' => 0
    ];

    $loanStmt = $conn->prepare("
        SELECT application_id
        FROM loans
        WHERE id = ?
        LIMIT 1
    ");
    if (!$loanStmt) {
        return $default;
    }

    $loanStmt->bind_param("i", $loanId);
    $loanStmt->execute();
    $loanRes = $loanStmt->get_result();
    $loanRow = $loanRes ? $loanRes->fetch_assoc() : null;
    $loanStmt->close();

    $applicationId = (int)($loanRow['application_id'] ?? 0);
    if ($applicationId <= 0) {
        return $default;
    }

    $appStmt = $conn->prepare("
        SELECT source_of_income, estimated_monthly_income
        FROM loan_applications
        WHERE id = ?
        LIMIT 1
    ");
    if (!$appStmt) {
        return $default;
    }

    $appStmt->bind_param("i", $applicationId);
    $appStmt->execute();
    $appRes = $appStmt->get_result();
    $appRow = $appRes ? ($appRes->fetch_assoc() ?: []) : [];
    $appStmt->close();

    return [
        'source_of_income' => (string)($appRow['source_of_income'] ?? ''),
        'estimated_monthly_income' => (float)($appRow['estimated_monthly_income'] ?? 0)
    ];
}

function updateWalletTransactionSync(
    mysqli $conn,
    int $walletTransactionId,
    string $syncStatus,
    ?string $syncError = null
): void {
    $stmt = $conn->prepare("
        UPDATE wallet_transactions
        SET sync_status = ?, sync_error = ?
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("ssi", $syncStatus, $syncError, $walletTransactionId);
    $stmt->execute();
    $stmt->close();
}

function updateTransactionProviderMethod(
    mysqli $conn,
    int $transactionId,
    string $providerMethod
): void {
    $stmt = $conn->prepare("
        UPDATE transactions
        SET provider_method = ?
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("si", $providerMethod, $transactionId);
    $stmt->execute();
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$hasLoan) {
        $errorMessage = 'No active or restructured loan found.';
    } elseif ($monthlyDue <= 0) {
        $errorMessage = 'Invalid monthly due amount.';
    } elseif ($outstanding <= 0) {
        $errorMessage = 'This loan has no outstanding balance.';
    } elseif ($balance < $monthlyDue) {
        $errorMessage = 'Insufficient wallet balance to pay the current due.';
    } else {
        $source = ($loanType === 'RESTRUCTURED' && !empty($restructuredId)) ? 'restructured' : 'original';
        $finalLoanId = $loanId ? (int)$loanId : 0;
        $finalRestructuredId = $restructuredId ? (int)$restructuredId : 0;

        if ($source === 'restructured' && $finalLoanId <= 0) {
            $errorMessage = 'Invalid original loan mapping for restructured loan.';
        } elseif (hasExistingPaidPending($conn, $user_id, $finalLoanId, $finalRestructuredId, $source)) {
            $errorMessage = 'You still have a payment pending verification for this loan.';
        } else {
            $conn->begin_transaction();

            $walletTransactionId = 0;
            $transactionId = 0;

            try {
                $wallet = getOrCreateWallet($conn, $user_id);
                $walletId = (int)($wallet['id'] ?? 0);
                $currentBalance = (float)($wallet['balance'] ?? 0);

                $loanContext = getEffectiveLoanContext($conn, $user_id);
                if (empty($loanContext['has_loan'])) {
                    throw new Exception('No active or restructured loan found.');
                }

                $loanType = (string)($loanContext['loan_type'] ?? '');
                $source = ($loanType === 'RESTRUCTURED' && !empty($loanContext['restructured_loan_id'])) ? 'restructured' : 'original';

                $finalLoanId = (int)($loanContext['loan_id'] ?? 0);
                $finalRestructuredId = (int)($loanContext['restructured_loan_id'] ?? 0);
                $paymentAmount = (float)($loanContext['monthly_due'] ?? 0);

                if ($paymentAmount <= 0) {
                    throw new Exception('Invalid monthly due amount.');
                }

                if ($currentBalance < $paymentAmount) {
                    throw new Exception('Insufficient wallet balance to pay the current due.');
                }

                if ($source === 'restructured') {
                    $loanRow = [
                        'outstanding'   => (float)($loanContext['outstanding'] ?? 0),
                        'interest_rate' => 0.00
                    ];

                    $rStmt = $conn->prepare("
                        SELECT outstanding, interest_rate
                        FROM restructured_loans
                        WHERE id = ? AND user_id = ?
                        LIMIT 1
                    ");
                    $rStmt->bind_param("ii", $finalRestructuredId, $user_id);
                    $rStmt->execute();
                    $rRes = $rStmt->get_result();
                    $rData = $rRes ? $rRes->fetch_assoc() : null;
                    $rStmt->close();

                    if (!$rData) {
                        throw new Exception('Restructured loan not found.');
                    }

                    $loanRow = $rData;
                } else {
                    $loanRow = [
                        'outstanding' => (float)($loanContext['outstanding'] ?? 0)
                    ];

                    $lStmt = $conn->prepare("
                        SELECT id, outstanding, monthly_due, status
                        FROM loans
                        WHERE id = ? AND user_id = ?
                        LIMIT 1
                    ");
                    $lStmt->bind_param("ii", $finalLoanId, $user_id);
                    $lStmt->execute();
                    $lRes = $lStmt->get_result();
                    $lData = $lRes ? $lRes->fetch_assoc() : null;
                    $lStmt->close();

                    if (!$lData) {
                        throw new Exception('Loan not found.');
                    }

                    $loanRow = $lData;
                }

                $breakdown = resolveWalletPaymentBreakdown(
                    $conn,
                    $source,
                    $loanRow,
                    $finalLoanId,
                    $finalRestructuredId,
                    $paymentAmount
                );

                $principal_amount = (float)$breakdown['principal_amount'];
                $interest_amount  = (float)$breakdown['interest_amount'];
                $penalty_amount   = (float)$breakdown['penalty_amount'];
                $monthly_due      = (float)$breakdown['monthly_due'];
                $payment_type     = (string)$breakdown['payment_type'];

                $newBalance = $currentBalance - $paymentAmount;
                if ($newBalance < 0) {
                    throw new Exception('Invalid wallet balance after deduction.');
                }

                $txLoanId = $finalLoanId > 0 ? $finalLoanId : null;
                $txRestructuredId = $finalRestructuredId > 0 ? $finalRestructuredId : null;

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
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PAID_PENDING', NOW(), 'WALLET')
                ");
                $ins->bind_param(
                    "iiiddddd",
                    $user_id,
                    $txLoanId,
                    $txRestructuredId,
                    $paymentAmount,
                    $principal_amount,
                    $interest_amount,
                    $penalty_amount,
                    $monthly_due
                );

                if (!$ins->execute()) {
                    throw new Exception("Failed to insert wallet payment transaction: " . $ins->error);
                }

                $transactionId = (int)$conn->insert_id;
                $ins->close();

                $receiptNumber = "RCPT-" . date("Ymd") . "-" . str_pad((string)$transactionId, 6, "0", STR_PAD_LEFT);

                $u0 = $conn->prepare("UPDATE transactions SET receipt_number=? WHERE id=?");
                $u0->bind_param("si", $receiptNumber, $transactionId);
                $u0->execute();
                $u0->close();

                if (!updateWalletBalance($conn, $walletId, $newBalance)) {
                    throw new Exception('Failed to update wallet balance.');
                }

                $walletTransactionId = recordWalletTransaction($conn, [
                    'wallet_account_id'    => $walletId,
                    'user_id'              => $user_id,
                    'loan_id'              => $finalLoanId > 0 ? $finalLoanId : null,
                    'restructured_loan_id' => $finalRestructuredId > 0 ? $finalRestructuredId : null,
                    'transaction_type'     => $source === 'restructured' ? 'RESTRUCTURED_PAYMENT' : 'LOAN_PAYMENT',
                    'amount'               => $paymentAmount,
                    'running_balance'      => $newBalance,
                    'reference_no'         => $receiptNumber,
                    'remarks'              => $source === 'restructured'
                        ? 'Wallet payment for restructured loan (pending verification)'
                        : 'Wallet payment for active loan (pending verification)',
                    'status'               => 'SUCCESS',
                    'sync_status'          => 'PENDING',
                    'sync_error'           => null
                ]);

                $txRow = [
                    "id" => $transactionId,
                    "loan_id" => $txLoanId,
                    "restructured_loan_id" => $txRestructuredId,
                    "amount" => $paymentAmount,
                    "principal_amount" => $principal_amount,
                    "interest_amount" => $interest_amount,
                    "penalty_amount" => $penalty_amount,
                    "monthly_due" => $monthly_due,
                    "status" => "PAID_PENDING",
                    "trans_date" => date("Y-m-d H:i:s"),
                    "provider_method" => "WALLET",
                    "paymongo_payment_id" => null,
                    "paymongo_checkout_id" => null,
                    "receipt_number" => $receiptNumber
                ];

                if (defined('PUBLIC_BASE_URL')) {
                    $receiptUrl = rtrim(PUBLIC_BASE_URL, '/') . "/CORE1/client/receipt.php?tx_id=" . $transactionId;
                    $dir = ensure_receipt_dir();
                    $filenamePending = $receiptNumber . "-PENDING.png";
                    $filePathPending = $dir . "/" . $filenamePending;

                    $okImg = generate_receipt_png($txRow, $receiptUrl, $filePathPending);
                    if ($okImg && file_exists($filePathPending)) {
                        $pendingUrl = "receipts/" . $filenamePending;
                        $u2 = $conn->prepare("UPDATE transactions SET receipt_image_pending_url=? WHERE id=?");
                        $u2->bind_param("si", $pendingUrl, $transactionId);
                        $u2->execute();
                        $u2->close();
                    }
                }

                $conn->commit();

                walletPayLog("Committed wallet payment tx_id={$transactionId}, wallet_tx_id={$walletTransactionId}, source={$source}");

                try {
                    $userRow = fetchUserRow($conn, $user_id);
                    $loanAppRow = fetchLoanApplicationExtras($conn, $finalLoanId);

                    $paymentPayload = [
                        "transaction_id" => $transactionId,
                        "user_id" => $user_id,
                        "loan_id" => $finalLoanId > 0 ? $finalLoanId : null,
                        "restructured_loan_id" => $finalRestructuredId > 0 ? $finalRestructuredId : null,
                        "amount" => $paymentAmount,
                        "status" => "PAID_PENDING",
                        "payment_date" => $txRow["trans_date"],
                        "provider_method" => "WALLET",
                        "paymongo_payment_id" => "",
                        "paymongo_checkout_id" => "",
                        "receipt_number" => $receiptNumber,

                        "principal_amount" => $principal_amount,
                        "interest_amount" => $interest_amount,
                        "penalty_amount" => $penalty_amount,
                        "payment_type" => $payment_type,

                        "client_full_name" => (string)($userRow["fullname"] ?? ''),
                        "client_email" => (string)($userRow["email"] ?? ''),
                        "client_phone" => (string)($userRow["phone"] ?? ''),
                        "client_occupation" => (string)($loanAppRow["source_of_income"] ?? ''),
                        "client_monthly_income" => (float)($loanAppRow["estimated_monthly_income"] ?? 0)
                    ];

                    walletPayLog("Sending wallet payment to FINANCIAL payload=" . json_encode($paymentPayload));

                    $financialResponse = sendPaymentToFinancial($paymentPayload);
walletPayLog("FINANCIAL response=" . json_encode($financialResponse));

$core2Response = sendWalletPaymentToCore2($paymentPayload);
walletPayLog("CORE2 wallet response=" . json_encode($core2Response));

$financialOk = !empty($financialResponse['success']);
$core2Ok = !empty($core2Response['success']);

if ($financialOk && $core2Ok) {
    updateWalletTransactionSync($conn, $walletTransactionId, 'SYNCED', null);
} else {
    $errors = [];

    if (!$financialOk) {
        $errors[] = 'FINANCIAL: ' . (string)($financialResponse['message'] ?? 'Unknown FINANCIAL error');
    }

    if (!$core2Ok) {
        $errors[] = 'CORE2: ' . (string)($core2Response['message'] ?? 'Unknown CORE2 error');
    }

    updateWalletTransactionSync($conn, $walletTransactionId, 'FAILED', implode(' | ', $errors));
}
                } catch (Throwable $syncEx) {
                    walletPayLog("Financial send failed for tx_id={$transactionId}: " . $syncEx->getMessage());
                    updateWalletTransactionSync($conn, $walletTransactionId, 'FAILED', $syncEx->getMessage());
                }

                header("Location: receipt.php?tx_id=" . $transactionId);
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                walletPayLog("Wallet payment failed: " . $e->getMessage());
                $errorMessage = 'Loan payment failed: ' . $e->getMessage();
            }
        }
    }

    $wallet   = getOrCreateWallet($conn, $user_id);
    $walletId = (int) ($wallet['id'] ?? 0);
    $balance  = (float) ($wallet['balance'] ?? 0);

    $loanContext     = getEffectiveLoanContext($conn, $user_id);
    $hasLoan         = (bool) ($loanContext['has_loan'] ?? false);
    $loanType        = (string) ($loanContext['loan_type'] ?? '');
    $loanId          = $loanContext['loan_id'] ?? null;
    $restructuredId  = $loanContext['restructured_loan_id'] ?? null;
    $monthlyDue      = (float) ($loanContext['monthly_due'] ?? 0);
    $outstanding     = (float) ($loanContext['outstanding'] ?? 0);
    $nextPaymentRaw  = $loanContext['next_payment'] ?? null;
    $loanStatus      = (string) ($loanContext['status'] ?? '');
    $nextPaymentDisplay = formatWalletDate($nextPaymentRaw);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pay Loan Using Wallet</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root{
            --bg-main: linear-gradient(135deg, #06152f 0%, #071a3b 50%, #041127 100%);
            --card-bg: rgba(11, 26, 57, 0.92);
            --card-border: rgba(255,255,255,0.08);
            --text-main: #ffffff;
            --text-soft: #a9b8d4;
            --accent: #16e0a0;
            --accent-dark: #13c48c;
            --shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
            --radius-lg: 24px;
        }

        * { box-sizing: border-box; }

        html, body {
            margin: 0;
            min-height: 100%;
            background: var(--bg-main);
            color: var(--text-main);
            font-family: Arial, sans-serif;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
            padding: 28px;
        }

        .shell {
            width: 100%;
            max-width: 1100px;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0 0 8px;
            font-size: 2rem;
            font-weight: 800;
        }

        .page-header p {
            margin: 0;
            color: var(--text-soft);
        }

        .grid {
            display: grid;
            grid-template-columns: minmax(320px, 1fr) minmax(320px, 1fr);
            gap: 22px;
            align-items: start;
        }

        .card {
            width: 100%;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 24px;
            overflow: hidden;
        }

        .label {
            color: var(--text-soft);
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .amount {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 18px;
            word-break: break-word;
        }

        .meta {
            display: grid;
            gap: 12px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }

        .meta-row:last-child {
            border-bottom: 0;
        }

        .meta-row span:first-child {
            color: var(--text-soft);
            flex: 1;
        }

        .meta-row span:last-child {
            font-weight: 700;
            text-align: right;
            flex: 1;
            word-break: break-word;
        }

        .form-title {
            margin: 0 0 18px;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .form-actions {
            display: flex;
            gap: 12px;
            margin-top: 18px;
            flex-wrap: wrap;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            border: 0;
            border-radius: 14px;
            padding: 13px 18px;
            text-decoration: none;
            cursor: pointer;
            font-weight: 700;
            min-width: 170px;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: #052b1f;
        }

        .alert {
            border-radius: 14px;
            padding: 14px 16px;
            margin-bottom: 18px;
            font-weight: 600;
        }

        .alert-error {
            background: rgba(127, 29, 29, 0.22);
            color: #fee2e2;
            border: 1px solid rgba(239, 68, 68, 0.25);
        }

        .info-box {
            margin-top: 18px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
            line-height: 1.7;
            color: var(--text-soft);
        }

        @media (max-width: 1100px) {
            .main-content {
                margin-left: 250px;
                padding: 22px;
            }

            .grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 18px;
                padding-top: 90px;
            }

            .form-actions .btn {
                width: 100%;
            }

            .amount {
                font-size: 2.4rem;
            }
        }

        @media (max-width: 520px) {
            .meta-row {
                flex-direction: column;
            }

            .meta-row span:last-child {
                text-align: left;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/include/sidebar.php'; ?>

<div class="main-content">
    <div class="shell">
        <div class="page-header">
            <h1>Pay Loan Using Wallet</h1>
            <p>Use your wallet balance to pay your current due securely.</p>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error"><?= e($errorMessage) ?></div>
        <?php endif; ?>

        <div class="grid">
            <div class="card">
                <div class="label">Available Wallet Balance</div>
                <div class="amount">₱<?= number_format($balance, 2) ?></div>

                <div class="meta">
                    <div class="meta-row">
                        <span>Account Number</span>
                        <span><?= e($accountNumber) ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Wallet Status</span>
                        <span><?= e($walletStatus) ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Account Holder</span>
                        <span><?= e($fullName) ?></span>
                    </div>
                </div>
            </div>

            <div class="card">
                <h2 class="form-title">Loan Payment Summary</h2>

                <div class="meta">
                    <div class="meta-row">
                        <span>Loan Type</span>
                        <span><?= e($loanType ?: 'N/A') ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Monthly Due</span>
                        <span>₱<?= number_format($monthlyDue, 2) ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Outstanding Balance</span>
                        <span>₱<?= number_format($outstanding, 2) ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Next Payment Date</span>
                        <span><?= e($nextPaymentDisplay ?: 'N/A') ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Loan Status</span>
                        <span><?= e($loanStatus ?: 'N/A') ?></span>
                    </div>
                </div>

                <div class="info-box">
                    Once you confirm, the wallet amount will be deducted immediately and the payment
                    will be sent for verification and collection syncing.
                </div>

                <form method="POST" class="form-actions">
                    <a href="wallet.php" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i>
                        Back to Wallet
                    </a>

                    <button type="submit" class="btn btn-primary" <?= (!$hasLoan || $monthlyDue <= 0 || $outstanding <= 0 || $balance < $monthlyDue) ? 'disabled' : '' ?>>
                        <i class="bi bi-wallet2"></i>
                        Pay Using Wallet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>