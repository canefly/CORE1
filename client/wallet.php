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

$wallet = getOrCreateWallet($conn, $user_id);

$walletId        = (int) ($wallet['id'] ?? 0);
$accountNumber   = (string) ($wallet['account_number'] ?? '');
$balance         = (float) ($wallet['balance'] ?? 0);
$walletStatus    = (string) ($wallet['status'] ?? 'ACTIVE');

$fullName        = getUserFullName($conn, $user_id);

$loanContext     = getEffectiveLoanContext($conn, $user_id);
$hasLoan         = (bool) ($loanContext['has_loan'] ?? false);
$loanType        = (string) ($loanContext['loan_type'] ?? '');
$loanId          = $loanContext['loan_id'] ?? null;
$restructuredId  = $loanContext['restructured_loan_id'] ?? null;
$monthlyDue      = (float) ($loanContext['monthly_due'] ?? 0);
$outstanding     = (float) ($loanContext['outstanding'] ?? 0);
$nextPaymentRaw  = $loanContext['next_payment'] ?? null;
$loanStatus      = (string) ($loanContext['status'] ?? '');

$reservedAmount  = getReservedAmount($conn, $user_id);
$withdrawable    = getWithdrawableAmount($conn, $user_id);

$recentTransactions = getRecentWalletTransactions($conn, $user_id, 10);

$nextPaymentDisplay = formatWalletDate($nextPaymentRaw);

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$successMessage = '';
$errorMessage   = '';

if (isset($_GET['success'])) {
    switch ($_GET['success']) {
        case 'cashin':
            $successMessage = 'Cash in successful.';
            break;
        case 'cashout':
            $successMessage = 'Cash out successful.';
            break;
        case 'loanpaid':
            $successMessage = 'Loan payment using wallet successful.';
            break;
    }
}

if (isset($_GET['error'])) {
    switch ($_GET['error']) {
        case 'insufficient_balance':
            $errorMessage = 'Insufficient wallet balance.';
            break;
        case 'withdraw_limit':
            $errorMessage = 'Requested cash out exceeds your withdrawable balance.';
            break;
        case 'no_active_loan':
            $errorMessage = 'No active or restructured loan found.';
            break;
        default:
            $errorMessage = urldecode((string)$_GET['error']);
            break;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wallet</title>

    <link rel="stylesheet" href="assets/css/wallet.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        :root{
            --bg-main: linear-gradient(135deg, #06152f 0%, #071a3b 50%, #041127 100%);
            --card-bg: rgba(11, 26, 57, 0.92);
            --card-border: rgba(255,255,255,0.08);
            --text-main: #ffffff;
            --text-soft: #a9b8d4;
            --text-muted: #7f92b3;
            --accent: #16e0a0;
            --accent-dark: #13c48c;
            --accent-soft: rgba(22, 224, 160, 0.12);
            --danger: #ff6b6b;
            --warning: #ffc857;
            --shadow: 0 18px 50px rgba(0, 0, 0, 0.25);
            --radius-lg: 24px;
            --radius-md: 18px;
            --radius-sm: 14px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: var(--bg-main);
        }

        .main-content {
            padding: 28px;
            color: var(--text-main);
        }

        .wallet-shell {
            max-width: 1400px;
            margin: 0 auto;
        }

        .page-header {
            margin-bottom: 24px;
        }

        .page-header h1 {
            margin: 0;
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.03em;
            color: var(--text-main);
        }

        .page-header p {
            margin: 8px 0 0;
            color: var(--text-soft);
            font-size: 0.98rem;
        }

        .alert {
            border-radius: 16px;
            padding: 14px 18px;
            margin-bottom: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            border: 1px solid transparent;
        }

        .alert-success {
            background: rgba(14, 116, 88, 0.18);
            color: #d1fae5;
            border-color: rgba(16, 185, 129, 0.30);
        }

        .alert-error {
            background: rgba(127, 29, 29, 0.22);
            color: #fee2e2;
            border-color: rgba(239, 68, 68, 0.25);
        }

        .wallet-top-grid {
            display: grid;
            grid-template-columns: 1.5fr 1fr;
            gap: 22px;
            margin-bottom: 22px;
        }

        .wallet-balance-card,
        .stat-card,
        .section-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            position: relative;
            overflow: hidden;
        }

        .wallet-balance-card {
            padding: 28px;
            min-height: 270px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .wallet-balance-card::before {
            content: "";
            position: absolute;
            width: 260px;
            height: 260px;
            border-radius: 50%;
            top: -90px;
            right: -70px;
            background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, rgba(255,255,255,0.02) 50%, transparent 70%);
            pointer-events: none;
        }

        .wallet-balance-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 18px;
            position: relative;
            z-index: 1;
        }

        .wallet-balance-label {
            color: var(--text-soft);
            font-size: 0.95rem;
            margin-bottom: 10px;
            font-weight: 500;
        }

        .wallet-balance-value {
            font-size: clamp(2.3rem, 4vw, 4rem);
            line-height: 1;
            font-weight: 900;
            letter-spacing: -0.05em;
            margin: 0;
            color: var(--text-main);
        }

        .wallet-owner-block {
            margin-top: 18px;
        }

        .wallet-owner-name {
            font-size: 1.08rem;
            font-weight: 700;
            margin-bottom: 4px;
            word-break: break-word;
        }

        .wallet-owner-meta {
            color: var(--text-soft);
            font-size: 0.92rem;
            word-break: break-word;
        }

        .wallet-badge {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: var(--accent-soft);
            color: #d7fff3;
            border: 1px solid rgba(22, 224, 160, 0.28);
            padding: 9px 14px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 0.88rem;
            white-space: nowrap;
        }

        .wallet-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 26px;
            position: relative;
            z-index: 1;
        }

        .btn-secondary,
        .btn-pay {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-decoration: none;
            border: 0;
            border-radius: 14px;
            padding: 13px 18px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.18s ease;
            min-width: 150px;
        }

        .btn-secondary {
            background: rgba(255,255,255,0.06);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.08);
        }

        .btn-secondary:hover {
            transform: translateY(-1px);
            background: rgba(255,255,255,0.10);
        }

        .btn-pay {
            background: linear-gradient(135deg, var(--accent) 0%, var(--accent-dark) 100%);
            color: #052b1f;
            box-shadow: 0 10px 24px rgba(22, 224, 160, 0.28);
        }

        .btn-pay:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 30px rgba(22, 224, 160, 0.34);
        }

        .wallet-stats-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }

        .stat-card {
            padding: 20px;
            min-height: 127px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card-label {
            font-size: 0.88rem;
            color: var(--text-soft);
            margin-bottom: 10px;
        }

        .stat-card-value {
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.1;
            color: var(--text-main);
            word-break: break-word;
        }

        .stat-card-value.small-text {
            font-size: 1.25rem;
        }

        .section-grid {
            display: grid;
            grid-template-columns: 0.95fr 1.35fr;
            gap: 22px;
        }

        .section-card {
            padding: 24px;
        }

        .section-title {
            margin: 0 0 18px;
            font-size: 1.28rem;
            font-weight: 800;
            color: var(--text-main);
        }

        .loan-pill {
            display: inline-block;
            background: var(--accent-soft);
            color: #d7fff3;
            border: 1px solid rgba(22, 224, 160, 0.26);
            border-radius: 999px;
            padding: 8px 12px;
            font-size: 0.8rem;
            font-weight: 800;
            margin-bottom: 18px;
        }

        .loan-pill.muted {
            background: rgba(255,255,255,0.06);
            color: #e5e7eb;
            border-color: rgba(255,255,255,0.08);
        }

        .info-list {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 16px;
            padding: 14px 0;
            border-bottom: 1px solid rgba(255,255,255,0.07);
        }

        .info-row:last-child {
            border-bottom: 0;
        }

        .info-row span:first-child {
            color: var(--text-soft);
            font-size: 0.95rem;
        }

        .info-row span:last-child {
            color: var(--text-main);
            font-weight: 700;
            text-align: right;
        }

        .empty-note {
            color: var(--text-soft);
            line-height: 1.7;
            margin: 0;
        }

        .table-wrap {
            overflow-x: auto;
        }

        .tx-table {
            width: 100%;
            border-collapse: collapse;
        }

        .tx-table th,
        .tx-table td {
            text-align: left;
            padding: 14px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.07);
            vertical-align: middle;
            white-space: nowrap;
        }

        .tx-table th {
            color: var(--text-soft);
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .tx-table td {
            color: var(--text-main);
            font-size: 0.93rem;
        }

        .tx-type {
            font-weight: 800;
        }

        .amount-in {
            color: #37e2a8;
            font-weight: 800;
        }

        .amount-out {
            color: #ffc857;
            font-weight: 800;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            padding: 7px 10px;
            border-radius: 999px;
            font-size: 0.77rem;
            font-weight: 800;
            background: rgba(255,255,255,0.06);
            color: #fff;
            border: 1px solid rgba(255,255,255,0.07);
        }

        .tx-empty {
            text-align: center;
            color: var(--text-soft);
            padding: 22px 0;
        }

        @media (max-width: 1180px) {
            .wallet-top-grid,
            .section-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 720px) {
            .main-content {
                padding: 18px;
            }

            .wallet-stats-grid {
                grid-template-columns: 1fr 1fr;
            }

            .wallet-balance-card,
            .section-card,
            .stat-card {
                border-radius: 18px;
            }

            .wallet-balance-card {
                padding: 22px;
                min-height: auto;
            }

            .wallet-balance-top {
                flex-direction: column;
                align-items: flex-start;
            }

            .wallet-actions {
                flex-direction: column;
            }

            .btn-secondary,
            .btn-pay {
                width: 100%;
            }

            .tx-table th,
            .tx-table td {
                padding: 12px 10px;
            }
        }

        @media (max-width: 520px) {
            .wallet-stats-grid {
                grid-template-columns: 1fr;
            }

            .info-row {
                flex-direction: column;
                align-items: flex-start;
            }

            .info-row span:last-child {
                text-align: left;
            }
        }
    </style>
</head>
<body>

<?php include __DIR__ . '/include/sidebar.php'; ?>

<div class="main-content">
    <div class="wallet-shell">
        <div class="page-header">
            <h1>Digital Wallet</h1>
            <p>Manage your savings, cash in, cash out, and loan payments in one place.</p>
        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle-fill"></i>
                <?php echo e($successMessage); ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo e($errorMessage); ?>
            </div>
        <?php endif; ?>

        <div class="wallet-top-grid">
            <div class="wallet-balance-card">
                <div>
                    <div class="wallet-balance-top">
                        <div>
                            <div class="wallet-balance-label">Available Wallet Balance</div>
                            <h2 class="wallet-balance-value">₱ <?php echo number_format($balance, 2); ?></h2>

                            <div class="wallet-owner-block">
                                <div class="wallet-owner-name"><?php echo e($fullName); ?></div>
                                <div class="wallet-owner-meta"><?php echo e($accountNumber); ?></div>
                            </div>
                        </div>

                        <div class="wallet-badge">
                            <i class="bi bi-wallet2"></i>
                            <?php echo e($walletStatus); ?>
                        </div>
                    </div>
                </div>

                <div class="wallet-actions">
                    <a href="cash_in.php" class="btn-secondary">
                        <i class="bi bi-plus-circle-fill"></i> Cash In
                    </a>

                    <a href="cash_out.php" class="btn-secondary">
                        <i class="bi bi-dash-circle-fill"></i> Cash Out
                    </a>

                    <?php if ($hasLoan): ?>
                        <a href="pay_loan_using_wallet.php" class="btn-pay">
                            <i class="bi bi-credit-card-fill"></i> Pay Loan Using Wallet
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="wallet-stats-grid">
                <div class="stat-card">
                    <div class="stat-card-label">Reserved Amount</div>
                    <div class="stat-card-value">₱ <?php echo number_format($reservedAmount, 2); ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-label">Withdrawable Amount</div>
                    <div class="stat-card-value">₱ <?php echo number_format($withdrawable, 2); ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-label">Loan Access</div>
                    <div class="stat-card-value small-text">
                        <?php echo $hasLoan ? e($loanType) : 'No Active Loan'; ?>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-card-label">Next Payment</div>
                    <div class="stat-card-value small-text">
                        <?php echo $hasLoan ? e($nextPaymentDisplay) : 'Not Available'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-grid">
            <div class="section-card">
                <h3 class="section-title">Loan Information</h3>

                <?php if ($hasLoan): ?>
                    <div class="loan-pill"><?php echo e($loanType); ?></div>

                    <div class="info-list">
                        <div class="info-row">
                            <span>Status</span>
                            <span><?php echo e($loanStatus); ?></span>
                        </div>
                        <div class="info-row">
                            <span>Monthly Due</span>
                            <span>₱ <?php echo number_format($monthlyDue, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span>Outstanding</span>
                            <span>₱ <?php echo number_format($outstanding, 2); ?></span>
                        </div>
                        <div class="info-row">
                            <span>Next Payment</span>
                            <span><?php echo e($nextPaymentDisplay); ?></span>
                        </div>
                        <?php if ($loanId): ?>
                            <div class="info-row">
                                <span>Loan ID</span>
                                <span><?php echo e($loanId); ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if ($restructuredId): ?>
                            <div class="info-row">
                                <span>Restructured Loan ID</span>
                                <span><?php echo e($restructuredId); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="loan-pill muted">No Active Loan</div>
                    <p class="empty-note">
                        Your wallet is active and ready for savings, cash in, and cash out. Loan payment options will appear here once you have an active or restructured loan.
                    </p>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <h3 class="section-title">Recent Wallet Transactions</h3>

                <div class="table-wrap">
                    <table class="tx-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Running Balance</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentTransactions)): ?>
                                <?php foreach ($recentTransactions as $tx): ?>
                                    <?php
                                        $type = strtoupper((string)($tx['transaction_type'] ?? ''));
                                        $amount = (float)($tx['amount'] ?? 0);
                                        $runningBalance = (float)($tx['running_balance'] ?? 0);
                                        $status = (string)($tx['status'] ?? '');
                                        $reference = (string)($tx['reference_no'] ?? '');
                                        $createdAt = (string)($tx['created_at'] ?? '');
                                        $isIn = in_array($type, ['CASH_IN', 'ADJUSTMENT'], true);
                                    ?>
                                    <tr>
                                        <td class="tx-type"><?php echo e($type); ?></td>
                                        <td><?php echo e($reference ?: 'N/A'); ?></td>
                                        <td class="<?php echo $isIn ? 'amount-in' : 'amount-out'; ?>">
                                            <?php echo $isIn ? '+' : '-'; ?> ₱ <?php echo number_format($amount, 2); ?>
                                        </td>
                                        <td>₱ <?php echo number_format($runningBalance, 2); ?></td>
                                        <td>
                                            <span class="status-pill"><?php echo e($status); ?></span>
                                        </td>
                                        <td>
                                            <?php echo $createdAt ? e(date('M d, Y h:i A', strtotime($createdAt))) : 'N/A'; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="tx-empty">No wallet transactions yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>