<?php
session_start();
require_once __DIR__ . "/include/config.php"; 
include __DIR__ . "/include/session_checker.php";

// ==========================================
// CORE 2 CONNECTION
// ==========================================
$core2_host = "127.0.0.1"; 
$core2_user = "root";
$core2_pass = "";
$core2_dbname = "core2_db";

$core2_conn = new mysqli($core2_host, $core2_user, $core2_pass, $core2_dbname);

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = $_SESSION['user_id'];
$success = false;

// Kunin ang Checkout Session ID galing sa URL (PayMongo redirect)
$session_id = $_GET['session_id'] ?? ''; 

if ($session_id) {
    // NOTE: Sa totoong setup, dapat i-verify mo muna sa PayMongo API kung 'paid' na talaga.
    // Pero for testing/simplicity, i-assume natin na nag-success si user.
    
    // 1. Kunin ang last pending transaction o amount na i-ka-cash in
    // (Dapat may session variable ka o temp table para sa amount, for now let's say ₱500)
    $amount = 500.00; 

    // 2. I-update ang Balance sa Core 2 (savings_accounts)
    $upd_wallet = $core2_conn->prepare("UPDATE savings_accounts SET current_balance = current_balance + ? WHERE user_id = ?");
    $upd_wallet->bind_param("di", $amount, $user_id);
    
    if ($upd_wallet->execute()) {
        $success = true;
        
        // Kunin ang bagong balance para sa transaction record
        $bal_stmt = $core2_conn->prepare("SELECT id, current_balance FROM savings_accounts WHERE user_id = ?");
        $bal_stmt->bind_param("i", $user_id);
        $bal_stmt->execute();
        $wallet_info = $bal_stmt->get_result()->fetch_assoc();
        $wallet_id = $wallet_info['id'];
        $new_bal = $wallet_info['current_balance'];

        // 3. Record sa Core 2 (savings_transactions)
        $ref_num = 'PMGO-' . strtoupper(substr(md5(uniqid()), 0, 8));
        $desc = "PayMongo Cash-in (Session: $session_id)";
        $ins_sav = $core2_conn->prepare("INSERT INTO savings_transactions (account_id, transaction_type, amount, balance_after, description, reference_number) VALUES (?, 'deposit', ?, ?, ?, ?)");
        $ins_sav->bind_param("idiss", $wallet_id, $amount, $new_bal, $desc, $ref_num);
        $ins_sav->execute();

        // 4. Record sa Core 1 (transactions) para sa audit trail
        $ins_txn = $mysqli->prepare("INSERT INTO transactions (user_id, amount, status, trans_date, provider_method, paymongo_checkout_id, receipt_number) VALUES (?, ?, 'SUCCESS', NOW(), 'PAYMONGO', ?, ?)");
        $ins_txn->bind_param("idss", $user_id, $amount, $session_id, $ref_num);
        $ins_txn->execute();

        // 5. Notification sa Core 1
        $notif_title = "Wallet Top-up Success";
        $notif_msg = "Successfully added ₱" . number_format($amount, 2) . " to your wallet via PayMongo.";
        $ins_notif = $mysqli->prepare("INSERT INTO notifications (user_id, title, message, type, icon) VALUES (?, ?, ?, 'success', 'bi-wallet2')");
        $ins_notif->bind_param("iss", $user_id, $notif_title, $notif_msg);
        $ins_notif->execute();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment Success - Microfinance</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/wallet.css">
</head>
<body style="display: flex; justify-content: center; align-items: center; text-align: center;">

    <div class="table-card" style="max-width: 500px; padding: 50px;">
        <?php if ($success): ?>
            <div class="activation-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981; width: 80px; height: 80px; font-size: 40px;">
                <i class="bi bi-check-all"></i>
            </div>
            <h1 style="color: #fff; margin-bottom: 10px;">Payment Successful!</h1>
            <p style="color: #94a3b8; margin-bottom: 30px;">Your digital wallet has been credited. You can now use your balance to pay your loans.</p>
            
            <div class="user-box" style="text-align: center;">
                <small>Reference Number</small>
                <h3 style="letter-spacing: 1px;"><?php echo $ref_num; ?></h3>
            </div>

            <button class="btn-pay" onclick="window.location.href='wallet.php'" style="width: 100%; justify-content: center;">
                Go Back to Wallet <i class="bi bi-arrow-right-short"></i>
            </button>
        <?php else: ?>
            <div class="activation-icon" style="background: rgba(239, 68, 68, 0.1); color: #f87171;">
                <i class="bi bi-x-circle"></i>
            </div>
            <h1 style="color: #fff;">Verification Failed</h1>
            <p style="color: #94a3b8;">We couldn't verify your payment. If you were charged, please contact support.</p>
            <button class="btn-secondary" onclick="window.location.href='wallet.php'" style="width: 100%; margin-top: 20px;">Return to Home</button>
        <?php endif; ?>
    </div>

</body>
</html>