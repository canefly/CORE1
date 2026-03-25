<?php
session_start();

// Saktong path base sa binigay mo: C:\xampp\htdocs\CORE1\client\include\
require_once __DIR__ . "/../include/config.php"; 
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

// Realistic retrieval ng wallet data
$wallet = getOrCreateWallet($conn, $user_id);

$walletId        = (int) ($wallet['id'] ?? 0);
$loanPrincipal   = (float) ($wallet['loan_wallet_balance'] ?? 0); // Principal balance mula sa database

$error = '';
$success = '';

// Realistic Cash Out Process with Transaction Logic
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cash_out_amount'])) {
    $amount = (float) $_POST['cash_out_amount'];

    if ($amount <= 0) {
        $error = "Mangyaring maglagay ng tamang halaga.";
    } elseif ($amount > $loanPrincipal) {
        $error = "Kulang ang iyong Loan Wallet balance.";
    } else {
        // Simulan ang transaction para sa data integrity
        mysqli_begin_transaction($conn);

        try {
            // 1. Bawasan ang loan_wallet_balance sa wallet_accounts table
            $stmtUpdate = $conn->prepare("UPDATE wallet_accounts SET loan_wallet_balance = loan_wallet_balance - ?, updated_at = NOW() WHERE id = ?");
            $stmtUpdate->bind_param("di", $amount, $walletId);
            $stmtUpdate->execute();
            $stmtUpdate->close();

            // 2. Realistic Audit Trail: I-record sa wallet_transactions
            $reference = 'WDL-LOAN-' . date('YmdHis') . '-' . $user_id . '-' . rand(1000, 9999);
            $newRunningBalance = $loanPrincipal - $amount;
            $remarks = "Loan Principal Withdrawal via Dashboard";
            $status = 'SUCCESS';
            $txType = 'CASH_OUT';

            $stmtTx = $conn->prepare("
                INSERT INTO wallet_transactions (
                    wallet_account_id, user_id, transaction_type, amount, 
                    running_balance, reference_no, remarks, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmtTx->bind_param("iisddsss", $walletId, $user_id, $txType, $amount, $newRunningBalance, $reference, $remarks, $status);
            $stmtTx->execute();
            $stmtTx->close();

            $uStmt = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $uStmt->bind_param("i", $user_id);
            $uStmt->execute();
            $uRes = $uStmt->get_result();
            $userProfile = $uRes ? $uRes->fetch_assoc() : null;
            $uStmt->close();

            $syncPayload = [
                'wallet_account_id'    => $walletId,
                'user_id'              => $user_id,
                'loan_id'              => null,
                'restructured_loan_id' => null,
                'transaction_type'     => $txType,
                'amount'               => $amount,
                'running_balance'      => $newRunningBalance,
                'reference_no'         => $reference,
                'remarks'              => $remarks,
                'status'               => $status,
                'sync_status'          => 'PENDING',
                'sync_error'           => null,
                'user_profile'         => $userProfile
            ];

            $syncResponse = sendWalletSyncToCore2($syncPayload);

            if (!$syncResponse['success']) {
                echo '<script>console.error("🔥 CORE 2 SYNC FATAL ERROR: " . addslashes($syncResponse["message"]));</script>';
            }

            mysqli_commit($conn);
            
            // Redirect pabalik sa wallet dashboard pagkatapos ng matagumpay na withdrawal
            header("Location: wallet.php?success=cashout");
            exit;

        } catch (Exception $e) {
            mysqli_rollback($conn);
            $error = "Nagkaroon ng problema sa transaction: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cash Out Loan Principal</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        :root { --bg: #06152f; --card: rgba(11, 26, 57, 0.95); --accent: #ffc857; }
        body { background: var(--bg); color: white; font-family: 'Inter', sans-serif; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .withdraw-card { background: var(--card); padding: 40px; border-radius: 24px; border: 1px solid rgba(255,255,255,0.1); width: 100%; max-width: 450px; box-shadow: 0 20px 60px rgba(0,0,0,0.4); }
        .balance-box { background: rgba(255, 200, 87, 0.1); padding: 20px; border-radius: 16px; margin: 25px 0; border: 1px solid rgba(255, 200, 87, 0.2); text-align: center; }
        input { width: 100%; padding: 15px; margin: 15px 0; border-radius: 12px; border: 1px solid #334155; background: #0f172a; color: white; font-size: 1.2rem; box-sizing: border-box; text-align: center; }
        .btn-submit { width: 100%; padding: 16px; border: none; border-radius: 12px; cursor: pointer; font-weight: 800; background: var(--accent); color: #332400; transition: 0.3s; }
        .btn-submit:hover { transform: translateY(-3px); box-shadow: 0 10px 20px rgba(255, 200, 87, 0.2); }
        .error-box { background: rgba(255, 107, 107, 0.1); color: #ff6b6b; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 1px solid rgba(255, 107, 107, 0.2); font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="withdraw-card">
        <h2 style="margin:0; color:var(--accent);"><i class="bi bi-bank2"></i> Cash Out</h2>
        <p style="color: #a9b8d4; margin: 10px 0 0;">I-withdraw ang iyong approved loan principal.</p>

        <div class="balance-box">
            <small style="color: var(--accent); text-transform: uppercase;">Available Loan Principal</small>
            <div style="font-size: 2.5rem; font-weight: 900; margin-top: 5px;">₱ <?php echo number_format($loanPrincipal, 2); ?></div>
        </div>

        <?php if ($error): ?> 
            <div class="error-box"><i class="bi bi-exclamation-triangle"></i> <?php echo $error; ?></div> 
        <?php endif; ?>

        <form method="POST">
            <label style="font-size: 0.9rem; color: #a9b8d4;">Halaga na i-wiwithdraw (PHP):</label>
            <input type="number" name="cash_out_amount" step="0.01" max="<?php echo $loanPrincipal; ?>" placeholder="0.00" required autofocus>
            
            <button type="submit" class="btn-submit">KUMPIRMAHIN ANG WITHDRAWAL</button>
        </form>

        <a href="wallet.php" style="display:block; text-align:center; margin-top: 20px; color: #a9b8d4; text-decoration: none; font-size: 0.9rem;"><i class="bi bi-arrow-left"></i> Bumalik sa Wallet</a>
    </div>
</body>
</html>