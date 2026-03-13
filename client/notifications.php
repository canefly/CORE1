<?php
session_start();
require_once __DIR__ . "/include/config.php"; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$notifications = [];

// 1. KUNIN ANG MGA DUE DATES (Mula sa active loans)
$loan_q = $conn->prepare("SELECT id, monthly_due, next_payment FROM loans WHERE user_id = ? AND status = 'ACTIVE'");
$loan_q->bind_param("i", $user_id);
$loan_q->execute();
$loan_res = $loan_q->get_result();
while ($loan = $loan_res->fetch_assoc()) {
    $notifications[] = [
        'type' => 'warning',
        'icon' => 'bi-alarm-fill',
        'title' => 'Upcoming Due Date',
        'created_at' => $loan['next_payment'] . ' 00:00:00', // Changed to match DB key
        'display_time' => date('M d, Y', strtotime($loan['next_payment'])),
        'message' => "Friendly reminder: Your monthly amortization of <strong>₱ " . number_format($loan['monthly_due'], 2) . "</strong> is due on <strong>" . date('M d, Y', strtotime($loan['next_payment'])) . "</strong>. Please pay on time to avoid penalties.",
        'link' => 'transactions.php', // Changed to match DB key
        'action_text' => 'Pay Now',
        'is_read' => 0 // Changed to match DB key (0 = unread)
    ];
}
$loan_q->close();

// 2. KUNIN ANG LOAN APPLICATION STATUS (Approved o Returned)
$app_q = $conn->prepare("SELECT id, status, updated_at, remarks FROM loan_applications WHERE user_id = ? ORDER BY updated_at DESC LIMIT 5");
$app_q->bind_param("i", $user_id);
$app_q->execute();
$app_res = $app_q->get_result();
while ($app = $app_res->fetch_assoc()) {
    if ($app['status'] == 'APPROVED') {
        $notifications[] = [
            'type' => 'info',
            'icon' => 'bi-file-earmark-text-fill',
            'title' => 'Loan Application Approved',
            'created_at' => $app['updated_at'],
            'display_time' => date('M d, Y h:i A', strtotime($app['updated_at'])),
            'message' => "Congratulations! Your loan application #LA-" . $app['id'] . " has been approved. The funds have been queued for disbursement.",
            'link' => 'myloans.php',
            'action_text' => 'View Contract',
            'is_read' => 1 // 1 = read
        ];
    } elseif ($app['status'] == 'REJECTED') {
        $notifications[] = [
            'type' => 'danger',
            'icon' => 'bi-exclamation-triangle-fill',
            'title' => 'Application Returned',
            'created_at' => $app['updated_at'],
            'display_time' => date('M d, Y h:i A', strtotime($app['updated_at'])),
            'message' => "Your application #LA-" . $app['id'] . " was returned. Reason: " . htmlspecialchars($app['remarks']),
            'link' => 'apply_loan.php',
            'action_text' => 'Review Application',
            'is_read' => 0
        ];
    }
}
$app_q->close();

// 3. KUNIN ANG SUCCESSFUL PAYMENTS (Mula sa transactions)
$txn_q = $conn->prepare("SELECT id, amount, status, trans_date FROM transactions WHERE user_id = ? AND status = 'SUCCESS' ORDER BY trans_date DESC LIMIT 5");
$txn_q->bind_param("i", $user_id);
$txn_q->execute();
$txn_res = $txn_q->get_result();
while ($txn = $txn_res->fetch_assoc()) {
    $notifications[] = [
        'type' => 'success',
        'icon' => 'bi-patch-check-fill',
        'title' => 'Payment Verified',
        'created_at' => $txn['trans_date'],
        'display_time' => date('M d, Y h:i A', strtotime($txn['trans_date'])),
        'message' => "Your payment of <strong>₱ " . number_format($txn['amount'], 2) . "</strong> has been successfully verified and posted to your account.",
        'link' => 'transactions.php',
        'action_text' => 'View Receipt',
        'is_read' => 1
    ];
}
$txn_q->close();

// --- FETCH ALL NOTIFICATIONS FROM DATABASE (WITHOUT WIPING THE ARRAY) ---
$notif_q = $conn->prepare("SELECT * FROM notifications WHERE user_id = ?");
$notif_q->bind_param("i", $user_id);
$notif_q->execute();
$notif_res = $notif_q->get_result();

while ($row = $notif_res->fetch_assoc()) {
    $notifications[] = $row; // Just add them to the pile
}
$notif_q->close();

// --- SORT EVERYTHING TOGETHER ---
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// --- HANDLE "MARK ALL AS READ" ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_read'])) {
    $update_q = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0");
    $update_q->bind_param("i", $user_id);
    $update_q->execute();
    $update_q->close();
    
    // Refresh page para mawala yung 'unread' highlight at badge
    header("Location: notifications.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications | MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/notifications.css">
</head>
<body>

    <?php include 'include/sidebar.php'; ?>
    <?php include 'include/theme_toggle.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Notifications</h1>
                <p>Stay updated on your loan status and payment reminders.</p>
            </div>
            
            <form method="POST" style="margin: 0;">
                <button type="submit" name="mark_read" class="btn-read-all" style="cursor: pointer;">
                    <i class="bi bi-check-all"></i> Mark all as read
                </button>
            </form>
        </div>

        <div class="notif-container">
            
            <?php if (empty($notifications)): ?>
                <div style="text-align: center; color: #9ca3af; padding: 40px; background: #1f2937; border-radius: 12px; border: 1px solid #374151;">
                    <i class="bi bi-bell-slash" style="font-size: 30px; margin-bottom: 10px; display: block;"></i>
                    No notifications yet.
                </div>
            <?php else: ?>
                <?php foreach ($notifications as $notif): ?>
                    <div class="notif-item <?php echo ($notif['is_read'] == 0) ? 'unread' : ''; ?>">
                        <div class="notif-icon-box type-<?php echo htmlspecialchars($notif['type']); ?>">
                            <i class="bi <?php echo htmlspecialchars($notif['icon']); ?>"></i>
                        </div>
                        <div class="notif-content">
                            <div class="notif-header">
                                <span class="notif-title"><?php echo htmlspecialchars($notif['title']); ?></span>
                                <span class="notif-time"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></span>
                            </div>
                            <div class="notif-body">
                                <?php echo $notif['message']; ?>
                            </div>
                            
                            <?php if (!empty($notif['link']) && $notif['link'] !== '#'): ?>
                                <a href="<?php echo htmlspecialchars($notif['link']); ?>" class="notif-action">
                                    View Details <i class="bi bi-arrow-right"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function markAllRead() {
            const items = document.querySelectorAll('.notif-item');
            items.forEach(item => {
                item.classList.remove('unread');
            });
        }
    </script>

</body>
</html>