<?php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/session_checker.php";
require_once __DIR__ . "/include/wallet_helper.php";
require_once __DIR__ . "/send_wallet_sync_to_core2.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

$user_id = (int) $_SESSION['user_id'];
$fullName = getUserFullName($conn, $user_id);
$wallet = getOrCreateWallet($conn, $user_id);

$walletId      = (int) ($wallet['id'] ?? 0);
$accountNumber = (string) ($wallet['account_number'] ?? '');
$balance       = (float) ($wallet['balance'] ?? 0);

$errorMessage = '';
$successMessage = '';

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0;
    $remarks = trim((string)($_POST['remarks'] ?? 'Cash in to wallet'));

    if ($amount <= 0) {
        $errorMessage = 'Please enter a valid cash in amount.';
    } else {
        $conn->begin_transaction();

        try {
            $wallet = getOrCreateWallet($conn, $user_id);

            $walletId = (int)($wallet['id'] ?? 0);
            $currentBalance = (float)($wallet['balance'] ?? 0);
            $newBalance = $currentBalance + $amount;

            if (!updateWalletBalance($conn, $walletId, $newBalance)) {
                throw new Exception('Failed to update wallet balance.');
            }

            $referenceNo = generateWalletReference('CIN');

            recordWalletTransaction($conn, [
                'wallet_account_id'    => $walletId,
                'user_id'              => $user_id,
                'loan_id'              => null,
                'restructured_loan_id' => null,
                'transaction_type'     => 'CASH_IN',
                'amount'               => $amount,
                'running_balance'      => $newBalance,
                'reference_no'         => $referenceNo,
                'remarks'              => $remarks !== '' ? $remarks : 'Cash in to wallet',
                'status'               => 'SUCCESS',
                'sync_status'          => 'PENDING',
                'sync_error'           => null
            ]);

            $conn->commit();

            $syncPayload = [
                'wallet_account_id'    => $walletId,
                'user_id'              => $user_id,
                'loan_id'              => null,
                'restructured_loan_id' => null,
                'transaction_type'     => 'CASH_IN',
                'amount'               => $amount,
                'running_balance'      => $newBalance,
                'reference_no'         => $referenceNo,
                'remarks'              => $remarks !== '' ? $remarks : 'Cash in to wallet',
                'status'               => 'SUCCESS',
                'sync_status'          => 'PENDING',
                'sync_error'           => null
            ];
            
            $syncResponse = sendWalletSyncToCore2($syncPayload);
            
            if (!empty($syncResponse['success'])) {
                $upd = $conn->prepare("UPDATE wallet_transactions SET sync_status = 'SYNCED' WHERE reference_no = ?");
                $upd->bind_param("s", $referenceNo);
                $upd->execute();
                $upd->close();
            } else {
                $err = $syncResponse['message'] ?? 'Unknown error connecting to CORE2';
                $upd = $conn->prepare("UPDATE wallet_transactions SET sync_status = 'FAILED', sync_error = ? WHERE reference_no = ?");
                $upd->bind_param("ss", $err, $referenceNo);
                $upd->execute();
                $upd->close();
            }

            header("Location: wallet.php?success=cashin");
            exit;
        } catch (Throwable $e) {
            $conn->rollback();
            $errorMessage = 'Cash in failed: ' . $e->getMessage();
        }
    }
}

// refresh current wallet display
$wallet = getOrCreateWallet($conn, $user_id);
$balance = (float)($wallet['balance'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash In Wallet</title>
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
            --radius-md: 18px;
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

        .cashin-shell {
            max-width: 900px;
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

        .alert-success {
            background: rgba(14, 116, 88, 0.18);
            color: #d1fae5;
            border: 1px solid rgba(16, 185, 129, 0.30);
        }

        .hint {
            margin-top: 10px;
            color: var(--text-soft);
            font-size: 0.88rem;
            line-height: 1.6;
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
    <div class="cashin-shell">
        <div class="page-header">
            <h1>Cash In</h1>
            <p>Add funds to your digital wallet balance.</p>
        </div>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php echo e($successMessage); ?>
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
                        <span>Status</span>
                        <span>ACTIVE</span>
                    </div>
                </div>

                <div class="hint">
                    Cash in will increase your wallet balance immediately and create a wallet transaction record.
                </div>
            </div>

            <div class="card">
                <h2 class="form-title">Cash In Form</h2>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="amount">Amount</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
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
                        >Cash in to wallet</textarea>
                    </div>

                    <div class="form-actions">
                        <a href="wallet.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to Wallet
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle-fill"></i> Confirm Cash In
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>