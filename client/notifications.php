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
            <button class="btn-read-all" onclick="markAllRead()">
                <i class="bi bi-check2-all"></i> Mark all as read
            </button>
        </div>

        <div class="notif-container">
            
            <div class="notif-item unread">
                <div class="notif-icon-box type-success">
                    <i class="bi bi-patch-check-fill"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-title">Payment Verified</span>
                        <span class="notif-time">2 hours ago</span>
                    </div>
                    <div class="notif-body">
                        Your payment of <strong>₱ 3,500.00</strong> via GCash has been successfully verified and posted to your account.
                    </div>
                    <a href="transactions.php" class="notif-action">View Receipt <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="notif-item unread">
                <div class="notif-icon-box type-warning">
                    <i class="bi bi-alarm-fill"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-title">Upcoming Due Date</span>
                        <span class="notif-time">Yesterday</span>
                    </div>
                    <div class="notif-body">
                        Friendly reminder: Your monthly amortization of <strong>₱ 3,500.00</strong> is due on <strong>Oct 25, 2025</strong>. Please pay on time to avoid penalties.
                    </div>
                    <a href="transactions.php" class="notif-action">Pay Now <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="notif-item">
                <div class="notif-icon-box type-info">
                    <i class="bi bi-file-earmark-text-fill"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-title">Loan Application Approved</span>
                        <span class="notif-time">Oct 20, 2025</span>
                    </div>
                    <div class="notif-body">
                        Congratulations! Your loan application #LN-1025 has been approved. The funds have been queued for disbursement.
                    </div>
                    <a href="my_loans.php" class="notif-action">View Contract <i class="bi bi-arrow-right"></i></a>
                </div>
            </div>

            <div class="notif-item">
                <div class="notif-icon-box type-danger">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <div class="notif-content">
                    <div class="notif-header">
                        <span class="notif-title">Penalty Applied</span>
                        <span class="notif-time">Sep 26, 2025</span>
                    </div>
                    <div class="notif-body">
                        A late payment penalty of <strong>₱ 250.00</strong> has been added to your account balance due to missed payment deadline.
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        function markAllRead() {
            // Visual logic: Remove 'unread' class from all items
            const items = document.querySelectorAll('.notif-item');
            items.forEach(item => {
                item.classList.remove('unread');
            });
            alert('All notifications marked as read.');
        }
    </script>

</body>
</html>