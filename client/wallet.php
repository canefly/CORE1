<?php
session_start();
require_once __DIR__ . "/include/config.php"; 
include __DIR__ . "/include/session_checker.php";

if (isset($conn)) {
    $mysqli = $conn;
} else {
    die("Error: Variable \$conn is not defined in config.php. Please check your connection file.");
}

// CORE 2 CONNECTION change ip adress na lang lods
$core2_host = "127.0.0.1"; 
$core2_user = "root";
$core2_pass = "";
$core2_dbname = "core2_db";

$core2_conn = new mysqli($core2_host, $core2_user, $core2_pass, $core2_dbname);

if ($core2_conn->connect_error) {
    die("Core2 DB Connection failed: " . $core2_conn->connect_error);
}

// Kick out if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = "Unknown User"; 
$loan_id = null;
$monthly_due = 0;
$next_payment = "N/A";
$has_active_loan = false;
$wallet_account_id = null;
$wallet_balance = 0.00;
$wallet_account_num = "";

// 1. GET USER INFO (CORE 1 - $mysqli)
$user_stmt = $mysqli->prepare("SELECT fullname FROM users WHERE id = ?");
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_res = $user_stmt->get_result();
if($u_row = $user_res->fetch_assoc()) {
    $full_name = $u_row['fullname'];
}

// 2. CHECK FOR ACTIVE LOAN (CORE 1 - $mysqli)
$loan_stmt = $mysqli->prepare("SELECT id, monthly_due, next_payment FROM loans WHERE user_id = ? AND status IN ('ACTIVE', 'RESTRUCTURED') LIMIT 1");
$loan_stmt->bind_param("i", $user_id);
$loan_stmt->execute();
$loan_res = $loan_stmt->get_result();
if($l_row = $loan_res->fetch_assoc()) {
    $has_active_loan = true;
    $loan_id = $l_row['id'];
    $monthly_due = $l_row['monthly_due'];
    $next_payment = date('M d, Y', strtotime($l_row['next_payment']));
}

// 3. CHECK FOR WALLET ACCOUNT (CORE 2 - $core2_conn) => PINALITAN KO DITO
$wallet_stmt = $core2_conn->prepare("SELECT id, account_number, current_balance FROM savings_accounts WHERE user_id = ? LIMIT 1");
$wallet_stmt->bind_param("i", $user_id);
$wallet_stmt->execute();
$wallet_res = $wallet_stmt->get_result();
$has_wallet = false;

if($w_row = $wallet_res->fetch_assoc()) {
    $has_wallet = true;
    $wallet_account_id = $w_row['id'];
    $wallet_balance = $w_row['current_balance'];
    $wallet_account_num = $w_row['account_number'];
}

// ==========================================
// HANDLE POST REQUESTS (Activation & Payment)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    
    // ACTION: ACTIVATE WALLET
    if (isset($_POST['action']) && $_POST['action'] == 'activate') {
        if ($has_active_loan && !$has_wallet) {
            $new_acct_num = 'WAL-' . date('Y') . '-' . str_pad($user_id, 4, '0', STR_PAD_LEFT);
            // CORE 2 - $core2_conn => PINALITAN KO DITO
            $insert_wallet = $core2_conn->prepare("INSERT INTO savings_accounts (user_id, account_number, account_type, current_balance, status) VALUES (?, ?, 'Digital Wallet', 0.00, 'active')");
            $insert_wallet->bind_param("is", $user_id, $new_acct_num);
            if ($insert_wallet->execute()) {
                header("Location: wallet.php?success=activated");
                exit;
            }
        }
    }

    // ACTION: PAY LOAN
    if (isset($_POST['action']) && $_POST['action'] == 'pay_loan') {
        if ($has_active_loan && $has_wallet && $wallet_balance >= $monthly_due) {
            
            // 1. Deduct from Core 2 (savings_accounts) - $core2_conn
            $new_balance = $wallet_balance - $monthly_due;
            $upd_wallet = $core2_conn->prepare("UPDATE savings_accounts SET current_balance = ? WHERE id = ?");
            $upd_wallet->bind_param("di", $new_balance, $wallet_account_id);
            $upd_wallet->execute();

            // 2. Record Transaction in Core 2 (savings_transactions) - $core2_conn
            $ref_num = 'PAY-' . date('Ymd') . '-' . rand(1000,9999);
            $desc = "Paid Monthly Loan Due (Loan ID: $loan_id)";
            $ins_sav_txn = $core2_conn->prepare("INSERT INTO savings_transactions (account_id, transaction_type, amount, balance_after, description, reference_number) VALUES (?, 'loan_payment', ?, ?, ?, ?)");
            $ins_sav_txn->bind_param("idiss", $wallet_account_id, $monthly_due, $new_balance, $desc, $ref_num);
            $ins_sav_txn->execute();

            // 3. Update Core 1 (loans outstanding) - $mysqli
            $upd_loan = $mysqli->prepare("UPDATE loans SET outstanding = outstanding - ? WHERE id = ?");
            $upd_loan->bind_param("di", $monthly_due, $loan_id);
            $upd_loan->execute();

            // 4. Record Transaction in Core 1 (transactions) - $mysqli
            $ins_txn = $mysqli->prepare("INSERT INTO transactions (user_id, loan_id, amount, status, trans_date, provider_method, receipt_number) VALUES (?, ?, ?, 'SUCCESS', NOW(), 'WALLET', ?)");
            $ins_txn->bind_param("iids", $user_id, $loan_id, $monthly_due, $ref_num);
            $ins_txn->execute();

            header("Location: wallet.php?success=paid");
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Digital Wallet - Microfinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/wallet.css">
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>

<div class="main-content">

    <?php if (isset($_GET['success']) && $_GET['success'] == 'paid'): ?>
        <div style="background: #065f46; color: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <i class="bi bi-check-circle-fill"></i> Loan payment successful! Deducted from your digital wallet.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'cashin'): ?>
        <div style="background: #065f46; color: #fff; padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
            <i class="bi bi-check-circle-fill"></i> Cash In successful! Your wallet balance has been updated.
        </div>
    <?php endif; ?>

    <?php 
    // ==========================================
    // SCREEN 1: NO ACTIVE LOAN (Cannot use wallet)
    // ==========================================
    if (!$has_active_loan): 
    ?>
    <div class="activation-wrapper">
        <div class="table-card text-center" style="max-width: 400px; margin: 0 auto; padding: 40px 30px;">
            <div class="activation-icon" style="color: #f87171; background: rgba(239, 68, 68, 0.15);">
                <i class="bi bi-lock-fill"></i>
            </div>
            <h2 style="color: #fff; margin-bottom: 10px; font-size: 22px;">Wallet Locked</h2>
            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6;">
                You need an approved and active loan to activate the Digital Wallet feature. Apply for a loan first.
            </p>
        </div>
    </div>

    <?php 
    // ==========================================
    // SCREEN 2: HAS LOAN, BUT NO WALLET YET (Activation)
    // ==========================================
    elseif ($has_active_loan && !$has_wallet): 
    ?>
    <div id="activationScreen" class="activation-wrapper">
        <div class="table-card text-center" style="max-width: 400px; margin: 0 auto; padding: 40px 30px;">
            <div class="activation-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <h2 style="color: #fff; margin-bottom: 10px; font-size: 22px;">Activate Wallet</h2>
            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
                Enable your digital wallet to manage funds and pay your loans easily.
            </p>
            
            <div class="user-box">
                <small>Account Owner</small>
                <h3><?php echo htmlspecialchars($full_name); ?></h3>
            </div>

            <ul class="wallet-rules">
                <li><i class="bi bi-check-circle-fill text-green"></i> Real-time Loan Repayment</li>
                <li><i class="bi bi-check-circle-fill text-green"></i> Secure Savings Tracking</li>
                <li class="strict-rule"><i class="bi bi-exclamation-triangle-fill"></i> <strong>STRICTLY NO CASHOUT.</strong><br>Funds are for loan repayment only.</li>
            </ul>

            <form method="POST">
                <input type="hidden" name="action" value="activate">
                <button type="submit" class="btn-pay" style="width: 100%; justify-content: center; margin-top: 20px;">
                    Activate Wallet Now <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <?php 
    // ==========================================
    // SCREEN 3: HAS LOAN AND WALLET IS ACTIVE
    // ==========================================
    else: 
    ?>
    <div id="mainWalletScreen" style="animation: fadeIn 0.4s ease-in-out;">
        <div class="page-header">
            <h1>Digital Wallet</h1>
            <p>Manage your funds for loan repayments</p>
        </div>

        <div class="hero-card">
            <div class="hero-info">
                <h2>Available Balance</h2>
                <div class="amount">₱ <?php echo number_format($wallet_balance, 2); ?></div>
                <div class="due-date">
                    <i class="bi bi-person-badge"></i> <?php echo htmlspecialchars($full_name); ?> | <?php echo htmlspecialchars($wallet_account_num); ?>
                </div>
            </div>
            <div class="hero-actions wallet-mobile-actions">
                <button class="btn-secondary" onclick="window.location.href='cash_in.php'">
                    <i class="bi bi-plus-circle-fill"></i> Add Balance
                </button>
                <button class="btn-pay" onclick="checkPaymentLogic()">
                    <i class="bi bi-credit-card-fill"></i> Pay Loan
                </button>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head">
                <h3>Recent Activity</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Fetch history specific to this wallet
                        $history_stmt = $core2_conn->prepare("SELECT transaction_type, amount, created_at FROM savings_transactions WHERE account_id = ? ORDER BY created_at DESC LIMIT 5");                        $history_stmt->execute();
                        $history_res = $history_stmt->get_result();

                        if($history_res->num_rows > 0):
                            while($hist = $history_res->fetch_assoc()):
                                $is_deposit = ($hist['transaction_type'] == 'deposit');
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <?php if($is_deposit): ?>
                                        <i class="bi bi-arrow-down-left-circle-fill" style="color: #34d399; font-size: 18px;"></i>
                                        <strong style="color: #fff;">Cash In</strong>
                                    <?php else: ?>
                                        <i class="bi bi-arrow-up-right-circle-fill" style="color: #fbbf24; font-size: 18px;"></i>
                                        <strong style="color: #fff;">Loan Payment</strong>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y • h:i A', strtotime($hist['created_at'])); ?></td>
                            <td style="color: <?php echo $is_deposit ? '#34d399' : '#fff'; ?>; font-weight: 700;">
                                <?php echo $is_deposit ? '+' : '-'; ?> ₱ <?php echo number_format($hist['amount'], 2); ?>
                            </td>
                            <td><span class="badge bg-green">Completed</span></td>
                        </tr>
                        <?php 
                            endwhile;
                        else:
                        ?>
                        <tr><td colspan="4" style="text-align:center;">No recent transactions.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php endif; ?>

</div>

<div id="modalPayNow" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-icon icon-success"><i class="bi bi-check-lg"></i></div>
        <h3>Ready to Pay?</h3>
        <p>You have enough balance to pay your upcoming due of <strong>₱<?php echo number_format($monthly_due, 2); ?></strong> for <strong><?php echo $next_payment; ?></strong>.</p>
        <form method="POST">
            <input type="hidden" name="action" value="pay_loan">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('modalPayNow')">Cancel</button>
                <button type="submit" class="btn-pay">Pay Now</button>
            </div>
        </form>
    </div>
</div>

<div id="modalInsufficient" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-icon icon-error"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <h3>Insufficient Balance</h3>
        <p>Your balance is only <strong>₱<?php echo number_format($wallet_balance, 2); ?></strong>. Your monthly due is <strong>₱<?php echo number_format($monthly_due, 2); ?></strong>. Please Add Balance first.</p>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeModal('modalInsufficient')" style="width:100%;">Close</button>
        </div>
    </div>
</div>

<script>
    // Variables from PHP passed to JS
    const walletBalance = <?php echo $wallet_balance; ?>;
    const monthlyDue = <?php echo $monthly_due; ?>;

    function checkPaymentLogic() {
        if (walletBalance >= monthlyDue) {
            document.getElementById('modalPayNow').style.display = 'flex';
        } else {
            document.getElementById('modalInsufficient').style.display = 'flex';
        }
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
</script>

</body>
</html>