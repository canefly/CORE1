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

// Fetch wallet with new balance fields
$wallet = getOrCreateWallet($conn, $user_id);

$walletId        = (int) ($wallet['id'] ?? 0);
$accountNumber   = (string) ($wallet['account_number'] ?? '');
$balance         = (float) ($wallet['balance'] ?? 0);
$loanPrincipal   = (float) ($wallet['loan_wallet_balance'] ?? 0); // KUKUHA NA SA DATABASE
$walletStatus    = (string) ($wallet['status'] ?? 'ACTIVE');

$fullName        = getUserFullName($conn, $user_id);

$loanContext     = getEffectiveLoanContext($conn, $user_id);
$hasLoan         = (bool) ($loanContext['has_loan'] ?? false);
$loanType        = (string) ($loanContext['loan_type'] ?? '');
// ... [REST OF ORIGINAL Logic and UI IN wallet.php REMAINS INTACT] ...
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
    <title>Wallet Management</title>

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

        * { box-sizing: border-box; }
        body { background: var(--bg-main); font-family: 'Inter', sans-serif; margin: 0; }
        .main-content { padding: 28px; color: var(--text-main); }
        .wallet-shell { max-width: 1400px; margin: 0 auto; }
        .page-header { margin-bottom: 24px; }
        .page-header h1 { margin: 0; font-size: 2rem; font-weight: 800; letter-spacing: -0.03em; }
        .page-header p { margin: 8px 0 0; color: var(--text-soft); }

        /* Alert Styles */
        .alert { border-radius: 16px; padding: 14px 18px; margin-bottom: 18px; font-weight: 600; display: flex; align-items: center; gap: 10px; border: 1px solid transparent; }
        .alert-success { background: rgba(14, 116, 88, 0.18); color: #d1fae5; border-color: rgba(16, 185, 129, 0.30); }
        .alert-error { background: rgba(127, 29, 29, 0.22); color: #fee2e2; border-color: rgba(239, 68, 68, 0.25); }

        /* Wallet Grid */
        .wallet-top-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 22px; margin-bottom: 22px; }
        .wallet-balance-card { 
            background: var(--card-bg); 
            border: 1px solid var(--card-border); 
            border-radius: var(--radius-lg); 
            padding: 28px; 
            min-height: 280px; 
            display: flex; 
            flex-direction: column; 
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            box-shadow: var(--shadow);
        }

        .wallet-balance-label { color: var(--text-soft); font-size: 0.95rem; margin-bottom: 8px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .wallet-balance-value { font-size: clamp(2rem, 3vw, 3.2rem); line-height: 1; font-weight: 900; letter-spacing: -0.05em; margin: 0; }
        
        .wallet-owner-block { margin-top: 15px; }
        .wallet-owner-name { font-size: 1.05rem; font-weight: 700; }
        .wallet-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 999px; font-size: 0.75rem; font-weight: 800; margin-top: 10px; }
        
        /* Actions */
        .wallet-actions { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 25px; }
        .btn-action { display: inline-flex; align-items: center; justify-content: center; gap: 8px; text-decoration: none; border-radius: 12px; padding: 12px 16px; font-weight: 700; transition: all 0.2s; font-size: 0.9rem; flex: 1; min-width: 120px; }
        
        .btn-savings { background: rgba(255,255,255,0.06); color: #fff; border: 1px solid rgba(255,255,255,0.1); }
        .btn-savings:hover { background: rgba(255,255,255,0.12); transform: translateY(-2px); }
        
        .btn-loan-out { background: linear-gradient(135deg, var(--warning) 0%, #e5b045 100%); color: #332400; box-shadow: 0 8px 20px rgba(255, 200, 87, 0.2); }
        .btn-loan-out:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(255, 200, 87, 0.3); }

        .btn-pay-now { background: var(--accent); color: #052b1f; width: 100%; margin-top: 10px; }

        /* Secondary Stats */
        .wallet-stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 22px; }
        .stat-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-md); padding: 18px; }
        .stat-card-label { font-size: 0.8rem; color: var(--text-soft); margin-bottom: 8px; }
        .stat-card-value { font-size: 1.2rem; font-weight: 800; color: var(--text-main); }

        /* Sections */
        .section-grid { display: grid; grid-template-columns: 0.9fr 1.4fr; gap: 22px; }
        .section-card { background: var(--card-bg); border: 1px solid var(--card-border); border-radius: var(--radius-lg); padding: 24px; }
        .section-title { margin: 0 0 18px; font-size: 1.2rem; font-weight: 800; }

        /* Tables & Lists */
        .info-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .tx-table { width: 100%; border-collapse: collapse; font-size: 0.9rem; }
        .tx-table th { text-align: left; color: var(--text-soft); padding: 12px; border-bottom: 1px solid var(--card-border); }
        .tx-table td { padding: 12px; border-bottom: 1px solid rgba(255,255,255,0.05); }
        .amount-in { color: var(--accent); }
        .amount-out { color: var(--warning); }

        @media (max-width: 1100px) { .wallet-top-grid, .section-grid, .wallet-stats-grid { grid-template-columns: 1fr; } .wallet-stats-grid { grid-template-columns: 1fr 1fr; } }
    </style>
</head>
<body>

<?php include __DIR__ . '/include/sidebar.php'; ?>

<div class="main-content">
    <div class="wallet-shell">
        <div class="page-header">
            <h1>Digital Wallet Center</h1>
            <p>Monitor your savings and manage your loan disbursements separately.</p>
        </div>

        <?php if ($successMessage !== ''): ?>
            <div class="alert alert-success"><i class="bi bi-check-circle-fill"></i> <?php echo e($successMessage); ?></div>
        <?php endif; ?>

        <?php if ($errorMessage !== ''): ?>
            <div class="alert alert-error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo e($errorMessage); ?></div>
        <?php endif; ?>

        <div class="wallet-top-grid">
            
            <div class="wallet-balance-card" style="border-top: 4px solid var(--accent);">
                <div>
                    <div class="wallet-balance-label">Savings Wallet</div>
                    <h2 class="wallet-balance-value">₱ <?php echo number_format($balance, 2); ?></h2>
                    
                    <div class="wallet-owner-block">
                        <div class="wallet-owner-name"><?php echo e($fullName); ?></div>
                        <div class="wallet-badge" style="background: var(--accent-soft); color: var(--accent); border: 1px solid var(--accent);">
                            <i class="bi bi-piggy-bank-fill"></i> ACTIVE SAVINGS
                        </div>
                    </div>
                </div>

                <div class="wallet-actions">
                    <a href="cash_in.php" class="btn-action btn-savings">
                        <i class="bi bi-plus-lg"></i> Cash In
                    </a>
                    <a href="cash_out.php" class="btn-action btn-savings">
                        <i class="bi bi-dash-lg"></i> Cash Out
                    </a>
                    <?php if ($hasLoan): ?>
                        <a href="pay_loan_using_wallet.php" class="btn-action btn-pay-now">
                            <i class="bi bi-credit-card-2-back"></i> Pay Loan Dues
                        </a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="wallet-balance-card" style="border-top: 4px solid var(--warning); background: rgba(255, 200, 87, 0.03);">
                <div>
                    <div class="wallet-balance-label">Loan Wallet (Principal)</div>
                    <h2 class="wallet-balance-value" style="color: var(--warning);">₱ <?php echo number_format($loanPrincipal, 2); ?></h2>
                    
                    <div class="wallet-owner-block">
                        <div class="wallet-owner-name"><?php echo $hasLoan ? e($loanType) : 'No Disbursed Loan'; ?></div>
                        <div class="wallet-badge" style="background: rgba(255, 107, 107, 0.1); color: var(--danger); border: 1px solid var(--danger);">
                            <i class="bi bi-shield-lock-fill"></i> WITHDRAWAL ONLY
                        </div>
                    </div>
                </div>

                <div class="wallet-actions">
                    <?php if ($loanPrincipal > 0): ?>
                        <a href="cash_out_loan.php" class="btn-action btn-loan-out" style="flex: 2;">
                            <i class="bi bi-box-arrow-up-right"></i> Cash Out Principal Amount
                        </a>
                    <?php else: ?>
                        <p style="color: var(--text-muted); font-size: 0.85rem; font-style: italic;">
                            No principal amount available for cash out at this moment.
                        </p>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div class="wallet-stats-grid">
            <div class="stat-card">
                <div class="stat-card-label">Reserved (Savings)</div>
                <div class="stat-card-value">₱ <?php echo number_format($reservedAmount, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Withdrawable (Savings)</div>
                <div class="stat-card-value">₱ <?php echo number_format($withdrawable, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Current Monthly Due</div>
                <div class="stat-card-value" style="color: var(--danger);">₱ <?php echo number_format($monthlyDue, 2); ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-card-label">Next Due Date</div>
                <div class="stat-card-value"><?php echo $hasLoan ? e($nextPaymentDisplay) : 'N/A'; ?></div>
            </div>
        </div>

        <div class="section-grid">
            <div class="section-card">
                <h3 class="section-title">Loan Summary</h3>
                <?php if ($hasLoan): ?>
                    <div class="info-row"><span>Status</span><span style="color: var(--accent); font-weight: bold;"><?php echo e($loanStatus); ?></span></div>
                    <div class="info-row"><span>Remaining Balance</span><span>₱ <?php echo number_format($outstanding, 2); ?></span></div>
                    <div class="info-row"><span>Account No.</span><span><?php echo e($accountNumber); ?></span></div>
                    <?php if ($loanId): ?> <div class="info-row"><span>Loan Ref</span><span>#<?php echo e($loanId); ?></span></div> <?php endif; ?>
                <?php else: ?>
                    <p style="color: var(--text-muted);">You currently have no active loan records.</p>
                <?php endif; ?>
            </div>

            <div class="section-card">
                <h3 class="section-title">Recent Activities</h3>
                <div style="overflow-x: auto;">
                    <table class="tx-table">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Amount</th>
                                <th>Balance</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recentTransactions)): ?>
                                <?php foreach ($recentTransactions as $tx): ?>
                                    <?php 
                                        $type = strtoupper((string)$tx['transaction_type']);
                                        $isIn = in_array($type, ['CASH_IN', 'ADJUSTMENT']);
                                    ?>
                                    <tr>
                                        <td style="font-weight: 700;"><?php echo e($type); ?></td>
                                        <td class="<?php echo $isIn ? 'amount-in' : 'amount-out'; ?>">
                                            <?php echo $isIn ? '+' : '-'; ?> ₱ <?php echo number_format($tx['amount'], 2); ?>
                                        </td>
                                        <td>₱ <?php echo number_format($tx['running_balance'], 2); ?></td>
                                        <td style="color: var(--text-muted); font-size: 0.8rem;">
                                            <?php echo date('M d, Y', strtotime($tx['created_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align: center; color: var(--text-muted); padding: 20px;">No records found.</td></tr>
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