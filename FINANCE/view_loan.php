<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';
if (file_exists($connection_file)) {
    require_once $connection_file;
} else {
    die("Error: Connection file not found.");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No Loan ID provided.");
}

$loan_id = $_GET['id'];

try {
    // 2. Fetch the specific loan and user details
    $stmt = $pdo->prepare("
        SELECT l.*, u.fullname, u.phone, u.email,
        COALESCE(la.total_interest, (l.loan_amount * (l.interest_rate / 100) * l.term_months)) as total_interest,
        la.loan_purpose
        FROM loans l
        JOIN users u ON l.user_id = u.id
        LEFT JOIN loan_applications la ON l.application_id = la.id
        WHERE l.id = ?
    ");
    $stmt->execute([$loan_id]);
    $loan = $stmt->fetch();

    if (!$loan) {
        die("Loan record not found.");
    }

    // Math for SOA summary
    $principal = $loan['loan_amount'];
    $interest = $loan['total_interest'];
    $total_payable = $principal + $interest;
    $balance = $loan['outstanding'];
    $total_paid = $total_payable - $balance;
    if ($total_paid < 0) $total_paid = 0;
    
    // Safety display catch
    $displayStatus = $loan['status'];
    if ($balance <= 0) $displayStatus = 'PAID';

    // 3. Fetch Payment History (Transactions)
    $stmtTx = $pdo->prepare("SELECT * FROM transactions WHERE loan_id = ? ORDER BY trans_date DESC");
    $stmtTx->execute([$loan_id]);
    $transactions = $stmtTx->fetchAll();

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Statement of Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/view_loan.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Statement of Account</h1>
                <p>Detailed payment history and loan breakdown.</p>
            </div>
            <div>
                <a href="ledger.php" style="color: #94a3b8; text-decoration: none; margin-right: 15px;"><i class="bi bi-arrow-left"></i> Back to Ledger</a>
                <button class="btn-export" onclick="window.print()"><i class="bi bi-printer"></i> Print SOA</button>
            </div>
        </div>

        <div class="soa-header">
            <div class="soa-box">
                <h4>Client Details</h4>
                <p><?php echo htmlspecialchars($loan['fullname']); ?></p>
                <p style="font-size: 13px; color: #94a3b8;"><?php echo htmlspecialchars($loan['phone'] ?? 'No Phone provided'); ?></p>
            </div>
            
            <div class="soa-box">
                <h4>Loan Overview</h4>
                <p>ID: #LN-<?php echo str_pad($loan['id'], 4, '0', STR_PAD_LEFT); ?></p>
                <p>Status: <strong style="color: <?php echo ($displayStatus=='PAID')?'#34d399':'#fbbf24'; ?>;"><?php echo $displayStatus; ?></strong></p>
                <p style="font-size: 13px; color: #94a3b8;">Purpose: <?php echo htmlspecialchars($loan['loan_purpose'] ?? 'General'); ?></p>
            </div>
            
            <div class="soa-box" style="border-left: 1px dashed #334155; padding-left: 20px;">
                <h4>Outstanding Balance</h4>
                <span class="highlight">₱ <?php echo number_format($balance, 2); ?></span>
                <p style="font-size: 14px;">Principal: <strong>₱ <?php echo number_format($principal, 2); ?></strong></p>
                <p style="font-size: 14px;">Total Paid: <span class="good">₱ <?php echo number_format($total_paid, 2); ?></span></p>
            </div>
        </div>

        <div class="ledger-container">
            <div class="section-head">
                <h3>Payment History</h3>
            </div>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Receipt / Ref No.</th>
                            <th>Date & Time</th>
                            <th>Payment Method</th>
                            <th class="text-right">Amount Paid</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transactions)): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 20px;">No payments recorded yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($transactions as $tx): ?>
                            <tr>
                                <td style="color:#60a5fa; font-weight: 600;"><?php echo htmlspecialchars($tx['receipt_number'] ?? 'TRX-'.$tx['id']); ?></td>
                                <td><?php echo date("M d, Y - g:i A", strtotime($tx['trans_date'])); ?></td>
                                <td><?php echo htmlspecialchars($tx['provider_method'] ?? 'OTC'); ?></td>
                                <td class="text-right font-mono" style="color:#34d399; font-weight: bold;">+ ₱ <?php echo number_format($tx['amount'], 2); ?></td>
                                <td><span class="status-badge status-paid"><?php echo htmlspecialchars($tx['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>