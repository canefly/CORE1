<?php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/session_checker.php";

// Kick out if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// Fetch all transactions for the logged-in user
$query = "SELECT * FROM transactions WHERE user_id = ? ORDER BY trans_date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$transactions = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/transactions.css">
</head>
<body>

    <?php include 'include/sidebar.php'; ?>
    <?php include 'include/theme_toggle.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Transaction History</h1>
                <p>View your payment records and official receipts.</p>
            </div>
        </div>

        <div class="history-card">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Reference No.</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($transactions)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 30px; color: #9ca3af;">No transactions found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($transactions as $tx): 
                            // Format the date and time
                            $dateObj = new DateTime($tx['trans_date']);
                            $dateStr = $dateObj->format('M d, Y');
                            $timeStr = $dateObj->format('h:i A');
                            
                            // Determine styles based on status
                            $status = strtoupper($tx['status']);
                            if ($status === 'SUCCESS') {
                                $statusClass = 'status-verified';
                                $statusText = 'Verified';
                            } elseif ($status === 'FAILED') {
                                $statusClass = 'status-posted'; // Repurposing your CSS for errors
                                $statusText = 'Failed';
                            } else {
                                $statusClass = 'status-pending';
                                $statusText = 'Pending Review';
                            }

                            // Clean up reference numbers and methods
                            $refCode = !empty($tx['receipt_number']) ? $tx['receipt_number'] : (!empty($tx['paymongo_payment_id']) ? $tx['paymongo_payment_id'] : 'SYS-GEN');
                            $method = !empty($tx['provider_method']) ? $tx['provider_method'] : 'System';
                        ?>
                        <tr>
                            <td>
                                <div style="font-weight:600; color:#fff;"><?php echo $dateStr; ?></div>
                                <div style="font-size:12px; color:#9ca3af;"><?php echo $timeStr; ?></div>
                            </td>
                            <td><span class="ref-code"><?php echo htmlspecialchars($refCode); ?></span></td>
                            <td>Payment via <?php echo htmlspecialchars($method); ?></td>
                            <td class="amount-credit">+ ₱ <?php echo number_format($tx['amount'], 2); ?></td>
                            <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span></td>
                            <td>
                                <?php if (!empty($tx['receipt_image_final_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($tx['receipt_image_final_url']); ?>" target="_blank" style="color:#60a5fa; font-size:13px; text-decoration:none;">Download OR</a>
                                <?php elseif (!empty($tx['receipt_image_pending_url'])): ?>
                                    <a href="<?php echo htmlspecialchars($tx['receipt_image_pending_url']); ?>" target="_blank" style="color:#60a5fa; font-size:13px; text-decoration:none;">View Image</a>
                                <?php else: ?>
                                    <span style="color:#6b7280; font-size:13px;">System Generated</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

</body>
</html>