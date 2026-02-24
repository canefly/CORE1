<?php
session_start();
include __DIR__ . "/include/config.php";

// Redirect if not logged in
if (!isset($_SESSION['user_email'])) {
    header("Location: index.html");
    exit();
}

// Fetch user info
$email = $_SESSION['user_email'];
$userQuery = $conn->prepare("SELECT * FROM users WHERE email = ?");
$userQuery->bind_param("s", $email);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult->fetch_assoc();

// Fetch latest loan
$loanQuery = $conn->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$loanQuery->bind_param("i", $user['id']);
$loanQuery->execute();
$loanResult = $loanQuery->get_result();
$loan = $loanResult->fetch_assoc();

// Fetch recent transactions
$transQuery = $conn->prepare("SELECT * FROM transactions WHERE loan_id = ? ORDER BY trans_date DESC LIMIT 5");
$transQuery->bind_param("i", $loan['id']);
$transQuery->execute();
$transResult = $transQuery->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

    <?php include 'include/sidebar.php'; ?>
    <?php include 'include/theme_toggle.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Overview</h1>
            <p>Welcome back, <strong>Juan</strong>! Here is your financial summary.</p>
        </div>

        <div class="hero-card">
            <div class="hero-info">
                <h2>Next Payment Due</h2>
                <div class="amount">₱ 3,500.00</div>
                <div class="due-date">
                    <i class="bi bi-calendar-event"></i> Due on <strong>Oct 25, 2025</strong> (3 Days Left)
                </div>
            </div>
            <div class="hero-actions">
                <a href="transactions.php?action=report" class="btn-pay">
                    <i class="bi bi-qr-code-scan"></i> Pay Now
                </a>
                <a href="my_loans.php" class="btn-secondary">
                    View Details
                </a>
            </div>
        </div>

        <div class="stats-grid">
            
            <div class="stat-box">
                <h4>Outstanding Balance</h4>
                <div class="val">₱ 15,000.00</div>
            </div>

            <div class="stat-box">
                <h4>Total Amount Paid</h4>
                <div class="val text-green">₱ 10,500.00</div>
            </div>

            <div class="stat-box">
                <h4>Loan Status</h4>
                <div class="val text-blue">Active</div>
            </div>

            <div class="stat-box score-box">
                <div class="score-ring">
                    <span class="score-text">720</span>
                </div>
                <div class="score-label">
                    <h4>Credit Score</h4>
                    <span class="score-desc">Excellent <i class="bi bi-graph-up-arrow"></i></span>
                </div>
            </div>

        </div>

        <div class="table-card">
            <div class="section-head">
                <h3>Recent Transactions</h3>
                <a href="transactions.php" class="link-all">View All</a>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Activity</th>
                        <th>Method</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Oct 20, 2025</td>
                        <td>Payment Submitted</td>
                        <td>GCash (Ref: 9982)</td>
                        <td style="font-weight:700;">₱ 3,500.00</td>
                        <td><span class="badge bg-yellow">Pending Review</span></td>
                    </tr>
                    <tr>
                        <td>Sep 25, 2025</td>
                        <td>Monthly Amortization</td>
                        <td>Cash / OTC</td>
                        <td style="font-weight:700;">₱ 3,500.00</td>
                        <td><span class="badge bg-green">Verified</span></td>
                    </tr>
                    <tr>
                        <td>Aug 25, 2025</td>
                        <td>Loan Disbursement</td>
                        <td>Bank Transfer</td>
                        <td style="font-weight:700; color:#60a5fa;">+ ₱ 25,000.00</td>
                        <td><span class="badge bg-green">Success</span></td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>