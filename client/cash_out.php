<?php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/session_checker.php";
require_once __DIR__ . "/include/wallet_helper.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

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
$monthlyDue      = (float) ($loanContext['monthly_due'] ?? 0);
$outstanding     = (float) ($loanContext['outstanding'] ?? 0);
$nextPaymentRaw  = $loanContext['next_payment'] ?? null;
$loanStatus      = (string) ($loanContext['status'] ?? '');

$reservedAmount  = getReservedAmount($conn, $user_id);
$withdrawable    = getWithdrawableAmount($conn, $user_id);

$nextPaymentDisplay = formatWalletDate($nextPaymentRaw);

$errorMessage = '';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount  = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    $remarks = trim((string)($_POST['remarks'] ?? 'Cash out from wallet'));

    if ($amount <= 0) {
        $errorMessage = 'Please enter a valid cash out amount.';
    } else {
        $wallet = getOrCreateWallet($conn, $user_id);
        $currentBalance = (float) ($wallet['balance'] ?? 0);
        $currentReserved = getReservedAmount($conn, $user_id);
        $currentWithdrawable = getWithdrawableAmount($conn, $user_id);

        if ($currentBalance <= 0) {
            $errorMessage = 'Your wallet balance is empty.';
        } elseif ($amount > $currentBalance) {
            $errorMessage = 'Cash out amount exceeds your current wallet balance.';
        } elseif ($amount > $currentWithdrawable) {
            $errorMessage = 'Cash out amount exceeds your withdrawable balance.';
        } else {
            $conn->begin_transaction();

            try {
                $walletId = (int)($wallet['id'] ?? 0);
                $newBalance = $currentBalance - $amount;

                if ($newBalance < 0) {
                    throw new Exception('Invalid wallet balance after cash out.');
                }

                // safety check again: after deduction, remaining balance must still protect reserved amount
                if ($newBalance < $currentReserved) {
                    throw new Exception('Cash out would reduce protected loan funds.');
                }

                if (!updateWalletBalance($conn, $walletId, $newBalance)) {
                    throw new Exception('Failed to update wallet balance.');
                }

                $referenceNo = generateWalletReference('COUT');

                recordWalletTransaction($conn, [
                    'wallet_account_id'    => $walletId,
                    'user_id'              => $user_id,
                    'loan_id'              => null,
                    'restructured_loan_id' => null,
                    'transaction_type'     => 'CASH_OUT',
                    'amount'               => $amount,
                    'running_balance'      => $newBalance,
                    'reference_no'         => $referenceNo,
                    'remarks'              => $remarks !== '' ? $remarks : 'Cash out from wallet',
                    'status'               => 'SUCCESS',
                    'sync_status'          => 'PENDING',
                    'sync_error'           => null
                ]);

                $conn->commit();

                header("Location: wallet.php?success=cashout");
                exit;
            } catch (Throwable $e) {
                $conn->rollback();
                $errorMessage = 'Cash out failed: ' . $e->getMessage();
            }
        }
    }

    // refresh latest values after validation/result
    $wallet         = getOrCreateWallet($conn, $user_id);
    $balance        = (float) ($wallet['balance'] ?? 0);
    $reservedAmount = getReservedAmount($conn, $user_id);
    $withdrawable   = getWithdrawableAmount($conn, $user_id);

    $loanContext     = getEffectiveLoanContext($conn, $user_id);
    $hasLoan         = (bool) ($loanContext['has_loan'] ?? false);
    $loanType        = (string) ($loanContext['loan_type'] ?? '');
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
    <title>Cash Out Wallet</title>
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
            --warning: #ffc857;
            --danger: #ff7b7b;
            --shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
            --radius-lg: 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg-main);
            color: var(--text-main);
            font-family: Arial, sans-serif;
        }

        .main-content {
            padding: 28px;
        }

        .cashout-shell {
            max-width: 1100px;
            margin: 0 auto;
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
            grid-template-columns: 1fr 1fr;
            gap: 22px;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            padding: 24px;
        }

        .balance-card .label {
            color: var(--text-soft);
            font-size: 0.95rem;
            margin-bottom: 10px;
        }

        .balance-card .amount {
            font-size: 3rem;
            font-weight: 900;
            line-height: 1;
            margin-bottom: 18px;
        }

        .meta {
            display: grid;
            gap: 12px;
        }

        .meta-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 0;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }

        .meta-row:last-child {
            border-bottom: 0;
        }

        .meta-row span:first-child {
            color: var(--text-soft);
        }

        .meta-row span:last-child {
            font-weight: 700;
            text-align: right;
        }

        .form-title {
            margin: 0 0 18px;
            font-size: 1.3rem;
            font-weight: 800;
        }

        .form-group {
            margin-bottom: 16px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-soft);
            font-size: 0.92rem;
            font-weight: 600;
        }

        .form-control {
            width: 100%;
            background: rgba(255,255,255,0.06);
            border: 1px solid rgba(255,255,255,0.08);
            color: #fff;
            border-radius: 14px;
            padding: 14px 15px;
            font-size: 1rem;
            outline: none;
        }

        .form-control::placeholder {
            color: #90a3c4;
        }

        textarea.form-control {
            min-height: 110px;
            resize: vertical;
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
            min-width: 150px;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.08);
            color: #fff;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--warning) 0%, #ffb020 100%);
            color: #2b1900;
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

        .hint {
            margin-top: 12px;
            color: var(--text-soft);
            font-size: 0.89rem;
            line-height: 1.7;
        }

        .loan-box {
            margin-top: 18px;
            padding: 16px;
            border-radius: 16px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.06);
        }

        .loan-box h3 {
            margin: 0 0 10px;
            font-size: 1rem;
        }

        .loan-box .loan-row {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 8px 0;
        }

        .loan-box .loan-row span:first-child {
            color: var(--text-soft);
        }

        .loan-box .loan-row span:last-child {
            font-weight: 700;
            text-align: right;
        }

        .warning-note {
            margin-top: 14px;
            padding: 14px;
            border-radius: 14px;
            background: rgba(255, 200, 87, 0.12);
            border: 1px solid rgba(255, 200, 87, 0.22);
            color: #ffe8a3;
            line-height: 1.6;
            font-size: 0.9rem;
        }

        @media (max-width: 900px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .main-content {
                padding: 18px;
            }

            .form-actions .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/include/sidebar.php'; ?>

<div class="main-content">
    <div class="cashout-shell">
        <div class="page-header">
            <h1>Cash Out</h1>
            <p>Withdraw available funds from your digital wallet.</p>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="grid">
            <div class="card balance-card">
                <div class="label">Current Wallet Balance</div>
                <div class="amount">₱ <?php echo number_format($balance, 2); ?></div>

                <div class="meta">
                    <div class="meta-row">
                        <span>Account Owner</span>
                        <span><?php echo e($fullName); ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Wallet Number</span>
                        <span><?php echo e($accountNumber); ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Wallet Status</span>
                        <span><?php echo e($walletStatus); ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Reserved Amount</span>
                        <span>₱ <?php echo number_format($reservedAmount, 2); ?></span>
                    </div>
                    <div class="meta-row">
                        <span>Withdrawable Amount</span>
                        <span>₱ <?php echo number_format($withdrawable, 2); ?></span>
                    </div>
                </div>

                <?php if ($hasLoan): ?>
                    <div class="loan-box">
                        <h3>Protected Loan Funds</h3>
                        <div class="loan-row">
                            <span>Loan Type</span>
                            <span><?php echo e($loanType); ?></span>
                        </div>
                        <div class="loan-row">
                            <span>Status</span>
                            <span><?php echo e($loanStatus); ?></span>
                        </div>
                        <div class="loan-row">
                            <span>Monthly Due</span>
                            <span>₱ <?php echo number_format($monthlyDue, 2); ?></span>
                        </div>
                        <div class="loan-row">
                            <span>Outstanding</span>
                            <span>₱ <?php echo number_format($outstanding, 2); ?></span>
                        </div>
                        <div class="loan-row">
                            <span>Next Payment</span>
                            <span><?php echo e($nextPaymentDisplay); ?></span>
                        </div>
                    </div>

                    <div class="warning-note">
                        Part of your wallet balance is protected for loan payment. You can only withdraw the amount above the reserved loan requirement.
                    </div>
                <?php else: ?>
                    <div class="hint">
                        You currently have no active or restructured loan, so your reserved amount is zero and your withdrawable amount is based on your full wallet balance.
                    </div>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2 class="form-title">Cash Out Form</h2>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            max="<?php echo e(number_format($withdrawable, 2, '.', '')); ?>"
                            name="amount"
                            id="amount"
                            class="form-control"
                            placeholder="Enter amount"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="remarks">Remarks</label>
                        <textarea
                            name="remarks"
                            id="remarks"
                            class="form-control"
                            placeholder="Optional remarks"
                        >Cash out from wallet</textarea>
                    </div>

                    <div class="hint">
                        Maximum withdrawable amount: <strong>₱ <?php echo number_format($withdrawable, 2); ?></strong>
                    </div>

                    <div class="form-actions">
                        <a href="wallet.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Wallet
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-cash-stack"></i> Confirm Cash Out
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>