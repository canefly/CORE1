<?php
$currentPage = basename($_SERVER['PHP_SELF']);

// Default values
$sidebar_name = "Client";
$sidebar_initial = "C";
$profile_pic = null;
$notifications = [];

if (isset($_SESSION['user_id']) && isset($conn)) {
    $sid = (int)$_SESSION['user_id'];
    
    // 1. Kunin ang User Info (kasama ang profile_pic)
    $s_query = $conn->prepare("SELECT fullname, profile_pic FROM users WHERE id = ?");
    $s_query->bind_param("i", $sid);
    $s_query->execute();
    $s_res = $s_query->get_result();
    if ($s_row = $s_res->fetch_assoc()) {
        $sidebar_name = $s_row['fullname'];
        $sidebar_initial = strtoupper(substr($sidebar_name, 0, 1));
        $profile_pic = $s_row['profile_pic'];
    }
    $s_query->close();

    // 2. Kunin ang Notifications (OLA Style Alerts)
    
    // Alert A: Active Loan Due Date
    $loan_q = $conn->prepare("SELECT monthly_due, next_payment FROM loans WHERE user_id = ? AND status = 'ACTIVE' LIMIT 1");
    $loan_q->bind_param("i", $sid);
    $loan_q->execute();
    $loan_res = $loan_q->get_result();
    if ($loan_row = $loan_res->fetch_assoc()) {
        $notifications[] = "<strong>Due Soon:</strong> Pay ₱" . number_format($loan_row['monthly_due'], 2) . " on " . date('M d', strtotime($loan_row['next_payment']));
    }
    $loan_q->close();

    // Alert B: Loan Application Status
    $app_q = $conn->prepare("SELECT id, status FROM loan_applications WHERE user_id = ? ORDER BY updated_at DESC LIMIT 1");
    $app_q->bind_param("i", $sid);
    $app_q->execute();
    $app_res = $app_q->get_result();
    if ($app_row = $app_res->fetch_assoc()) {
        if ($app_row['status'] == 'APPROVED') {
            $notifications[] = "Your application #LA-{$app_row['id']} is Approved!";
        } elseif ($app_row['status'] == 'REJECTED') {
            $notifications[] = "Application #LA-{$app_row['id']} was returned. Please check.";
        }
    }
    $app_q->close();
}
?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap');

    .sidebar { width: 260px; height: 100vh; background-color: #1f2937; border-right: 1px solid #374151; position: fixed; top: 0; left: 0; display: flex; flex-direction: column; padding: 24px; z-index: 1000; font-family: 'Google Sans', sans-serif; transition: transform 0.3s ease-in-out; overflow-y: auto; -ms-overflow-style: none; scrollbar-width: none; }
    .sidebar::-webkit-scrollbar { display: none; }
    .brand { display: flex; align-items: center; gap: 12px; padding-bottom: 24px; border-bottom: 1px solid #374151; margin-bottom: 24px; }
    .brand i { font-size: 28px; color: #10b981; }
    .brand h2 { color: #fff; font-size: 20px; font-weight: 700; margin: 0; }
    .user-profile { display: flex; align-items: center; gap: 12px; padding: 12px; background-color: #111827; border-radius: 12px; margin-bottom: 30px; border: 1px solid #374151; position: relative; }
    
    /* Na-update para sa image fitting */
    .avatar { width: 35px; height: 35px; background-color: #374151; color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; border: 2px solid #10b981; flex-shrink: 0; overflow: hidden; }
    .avatar img { width: 100%; height: 100%; object-fit: cover; }
    
    .user-info { flex-grow: 1; overflow: hidden; }
    .user-info h4 { color: #fff; font-size: 13px; font-weight: 600; margin: 0; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .verified-badge { color: #10b981; font-size: 10px; text-transform: uppercase; font-weight: 700; display: flex; align-items: center; gap: 3px; }
    .notif-btn { background: transparent; border: none; color: #9ca3af; cursor: pointer; position: relative; padding: 5px; transition: 0.2s; }
    .notif-btn:hover { color: #fff; }
    .notif-btn i { font-size: 18px; }
    .red-dot { position: absolute; top: 2px; right: 2px; width: 8px; height: 8px; background: #ef4444; border-radius: 50%; border: 1px solid #111827; }
    .notif-dropdown { display: none; position: absolute; top: 60px; right: 0; left: 0; background: #1f2937; border: 1px solid #374151; border-radius: 8px; box-shadow: 0 10px 15px rgba(0,0,0,0.5); z-index: 2000; padding: 10px; }
    .notif-dropdown.show { display: block; animation: fadeIn 0.2s; }
    .dropdown-header { font-size: 11px; color: #9ca3af; text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
    .dropdown-item { font-size: 12px; color: #cbd5e1; padding: 8px; border-bottom: 1px solid #374151; }
    .btn-see-all { display: block; width: 100%; text-align: center; background: #10b981; color: #064e3b; text-decoration: none; font-size: 11px; font-weight: 700; padding: 8px; border-radius: 4px; margin-top: 8px; }
    .nav-links { list-style: none; display: flex; flex-direction: column; gap: 8px; padding: 0; margin: 0; flex-grow: 1; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; color: #9ca3af; text-decoration: none; font-size: 14px; font-weight: 500; transition: all 0.2s ease; }
    .nav-item:hover { background-color: rgba(255, 255, 255, 0.05); color: #fff; }
    .nav-item.active { background-color: #10b981; color: #064e3b; }
    .nav-item i { font-size: 18px; }
    .logout-btn { border-top: 1px solid #374151; padding-top: 20px; margin-top: auto; }
    .logout-link { color: #ef4444; }
    .logout-link:hover { background-color: rgba(239, 68, 68, 0.1); color: #fca5a5; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    .mobile-menu-btn { display: none; position: fixed; top: 25px; left: 20px; z-index: 3000; background: transparent; color: #10b981; border: none; padding: 5px; font-size: 28px; cursor: pointer; box-shadow: none; transition: transform 0.3s ease-in-out, color 0.3s; }
    .mobile-menu-btn.active { transform: translateX(180px); background: transparent; color: #ef4444; }
    .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999; backdrop-filter: blur(2px); }
    @media (max-width: 992px) { .mobile-menu-btn { display: block; } .sidebar { transform: translateX(-100%); box-shadow: none; } .sidebar.active { transform: translateX(0); box-shadow: 5px 0 15px rgba(0,0,0,0.5); } .sidebar-overlay.active { display: block; } .main-content { padding-top: 80px !important; } }
</style>

<button class="mobile-menu-btn" onclick="toggleSidebar()"><i class="bi bi-list"></i></button>
<div class="sidebar-overlay" onclick="toggleSidebar()"></div>

<div class="sidebar" id="mySidebar">
    <div class="brand">
        <i class="bi bi-wallet2"></i>
        <h2>MicroFinance</h2>
    </div>

    <div class="user-profile">
        <div class="avatar">
            <?php if (!empty($profile_pic)): ?>
                <img src="uploads/profiles/<?= htmlspecialchars($profile_pic) ?>" alt="Profile">
            <?php else: ?>
                <?= htmlspecialchars($sidebar_initial) ?>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <h4><?php echo htmlspecialchars($sidebar_name); ?></h4>
            <span class="verified-badge"><i class="bi bi-patch-check-fill"></i> Verified</span>
        </div>
        
        <button class="notif-btn" onclick="toggleDropdown()">
            <i class="bi bi-bell-fill"></i>
            <?php if(count($notifications) > 0): ?>
                <span class="red-dot"></span>
            <?php endif; ?>
        </button>
        <div class="notif-dropdown" id="notifDropdown">
            <div class="dropdown-header">Recent Alerts</div>
            <?php if(count($notifications) > 0): ?>
                <?php foreach($notifications as $notif): ?>
                    <div class="dropdown-item"><?= $notif ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="dropdown-item" style="text-align:center; color:#6b7280;">No new alerts.</div>
            <?php endif; ?>
            <a href="#" class="btn-see-all">See All</a>
        </div>
    </div>

    <ul class="nav-links">
        <li><a href="dashboard.php" class="nav-item <?= ($currentPage == 'dashboard.php') ? 'active' : '' ?>"><i class="bi bi-grid-fill"></i> Dashboard</a></li>
        <li><a href="apply_loan.php" class="nav-item <?= ($currentPage == 'apply_loan.php') ? 'active' : '' ?>"><i class="bi bi-plus-circle-fill"></i> Apply Loan</a></li>
        <li><a href="myloans.php" class="nav-item <?= ($currentPage == 'myloans.php') ? 'active' : '' ?>"><i class="bi bi-file-earmark-text-fill"></i> My Loans</a></li>
        <li><a href="transactions.php" class="nav-item <?= ($currentPage == 'transactions.php') ? 'active' : '' ?>"><i class="bi bi-clock-history"></i> Transactions</a></li>
        <li><a href="restructure.php" class="nav-item <?= ($currentPage == 'restructure.php') ? 'active' : '' ?>"><i class="bi bi-arrow-repeat"></i> Restructure</a></li>
        <li><a href="profile.php" class="nav-item <?= ($currentPage == 'profile.php') ? 'active' : '' ?>"><i class="bi bi-person-fill"></i> Profile</a></li>
    </ul>

    <div class="logout-btn">
        <a href="logout.php" class="nav-item logout-link"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        window.toggleDropdown = function() {
            const dropdown = document.getElementById('notifDropdown');
            if(dropdown) dropdown.classList.toggle('show');
        }

        window.toggleSidebar = function() {
            const sidebar = document.querySelector('.sidebar');
            const overlay = document.querySelector('.sidebar-overlay');
            const btn = document.querySelector('.mobile-menu-btn');
            const icon = btn.querySelector('i');

            if(sidebar && overlay && btn) {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                btn.classList.toggle('active');

                if (sidebar.classList.contains('active')) {
                    icon.classList.remove('bi-list');
                    icon.classList.add('bi-x-lg');
                } else {
                    icon.classList.add('bi-list');
                    icon.classList.remove('bi-x-lg');
                }
            }
        }

        window.addEventListener('click', function(e) {
            const dropdown = document.getElementById('notifDropdown');
            const notifBtn = document.querySelector('.notif-btn');
            if (dropdown && notifBtn && !dropdown.contains(e.target) && !notifBtn.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    });
</script>