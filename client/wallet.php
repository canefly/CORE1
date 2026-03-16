<?php
session_start();
require_once __DIR__ . "/include/config.php";
include __DIR__ . "/include/session_checker.php";

// Kick out if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
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
                <h3>John Vergel Espayos</h3>
            </div>

            <ul class="wallet-rules">
                <li><i class="bi bi-check-circle-fill text-green"></i> Real-time Loan Repayment</li>
                <li><i class="bi bi-check-circle-fill text-green"></i> Secure Savings Tracking</li>
                <li class="strict-rule"><i class="bi bi-exclamation-triangle-fill"></i> <strong>STRICTLY NO CASHOUT.</strong><br>Funds are for loan repayment only.</li>
            </ul>

            <button class="btn-pay" style="width: 100%; justify-content: center; margin-top: 20px;" onclick="activateWallet()">
                Activate Wallet Now <i class="bi bi-arrow-right"></i>
            </button>
        </div>
    </div>


    <div id="mainWalletScreen" style="display: none;">
        
        <div class="page-header">
            <h1>Digital Wallet</h1>
            <p>Manage your funds for loan repayments</p>
        </div>

        <div class="hero-card">
            <div class="hero-info">
                <h2>Available Balance</h2>
                <div class="amount">₱ 5,000.00</div>
                <div class="due-date">
                    <i class="bi bi-person-badge"></i> John Vergel Espayos | WAL-2026-0001
                </div>
            </div>
            <div class="hero-actions wallet-mobile-actions">
                <button class="btn-pay">
                    <i class="bi bi-plus-circle-fill"></i> Add Balance
                </button>
                <button class="btn-secondary">
                    <i class="bi bi-credit-card-fill"></i> Pay Loan
                </button>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head">
                <h3>Recent Activity</h3>
                <a href="#" class="link-all">View All</a>
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
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="bi bi-arrow-down-left-circle-fill" style="color: #34d399; font-size: 18px;"></i>
                                    <strong style="color: #fff;">Cash In - GCash</strong>
                                </div>
                            </td>
                            <td>Mar 14, 2026 • 10:30 AM</td>
                            <td style="color: #34d399; font-weight: 700;">+ ₱ 5,000.00</td>
                            <td><span class="badge bg-green">Completed</span></td>
                        </tr>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <i class="bi bi-arrow-up-right-circle-fill" style="color: #fbbf24; font-size: 18px;"></i>
                                    <strong style="color: #fff;">Loan Payment (LN-15)</strong>
                                </div>
                            </td>
                            <td>Mar 01, 2026 • 02:15 PM</td>
                            <td style="color: #fff; font-weight: 700;">- ₱ 2,016.67</td>
                            <td><span class="badge bg-green">Completed</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<script>
    function activateWallet() {
        // Hide activation, show wallet dashboard smoothly
        document.getElementById('activationScreen').style.display = 'none';
        
        const mainScreen = document.getElementById('mainWalletScreen');
        mainScreen.style.display = 'block';
        mainScreen.style.animation = 'fadeIn 0.4s ease-in-out';
    }
</script>

</body>
</html>