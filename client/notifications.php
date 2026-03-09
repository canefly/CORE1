<?php
session_start();
require_once __DIR__ . "/include/config.php"; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

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

// --- FETCH ALL NOTIFICATIONS FROM DATABASE ---
$notifications = [];
$notif_q = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$notif_q->bind_param("i", $user_id);
$notif_q->execute();
$notif_res = $notif_q->get_result();

while ($row = $notif_res->fetch_assoc()) {
    $notifications[] = $row;
}
$notif_q->close();
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

</body>
</html>