<script>
    // THE ANTI-FLASHBANG PROTOCOL 
    if (localStorage.getItem('theme') === null) {
        localStorage.setItem('theme', 'dark'); 
    }
    if (localStorage.getItem('theme') === 'dark') {
        document.documentElement.classList.add('dark-mode');
    }
</script>

<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);

// 1. Kunin ang Session Info ni LSA
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_name = $_SESSION['admin_name'] ?? 'LSA Officer';
$admin_role = $_SESSION['admin_role'] ?? 'LSA';

// Default profile picture
$profile_img = 'default_avatar.png';

// 2. Kunin ang Profile Picture sa lsa_users table
if (isset($pdo) && $admin_id > 0) {
    $stmt = $pdo->prepare("SELECT profile_pic FROM lsa_users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data && !empty($user_data['profile_pic'])) {
        $profile_img = $user_data['profile_pic'];
    }
} elseif (isset($conn) && $admin_id > 0) {
    $stmt = $conn->prepare("SELECT profile_pic FROM lsa_users WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        if (!empty($row['profile_pic'])) {
            $profile_img = $row['profile_pic'];
        }
    }
}

// 3. Kunin ang Badge Counts (Hybrid PDO & MySQLi para iwas error)
$newAppCount = 0;
$restructureCount = 0;

if (isset($pdo)) {
    $pendingResult = $pdo->query("SELECT COUNT(*) as total FROM loan_applications WHERE status = 'PENDING'");
    $newAppCount = $pendingResult ? $pendingResult->fetch(PDO::FETCH_ASSOC)['total'] : 0;

    $restructureResult = $pdo->query("SELECT COUNT(*) as total FROM loan_applications WHERE (loan_purpose LIKE '%Restructure%' OR loan_purpose LIKE '%Extension%') AND status = 'PENDING'");
    $restructureCount = $restructureResult ? $restructureResult->fetch(PDO::FETCH_ASSOC)['total'] : 0;
} elseif (isset($conn)) {
    $pendingQuery = "SELECT COUNT(*) as total FROM loan_applications WHERE status = 'PENDING'";
    $pendingResult = $conn->query($pendingQuery);
    $newAppCount = ($pendingResult) ? $pendingResult->fetch_assoc()['total'] : 0;

    $restructureQuery = "SELECT COUNT(*) as total FROM loan_applications WHERE (loan_purpose LIKE '%Restructure%' OR loan_purpose LIKE '%Extension%') AND status = 'PENDING'";
    $restructureResult = $conn->query($restructureQuery);
    $restructureCount = ($restructureResult) ? $restructureResult->fetch_assoc()['total'] : 0;
}
?>

<link rel="stylesheet" href="assets/css/base-style.css">
<script src="https://unpkg.com/lucide@latest"></script>

<style>
    /* --- THE "ANTI-THICC" & LAYOUT HOTFIX --- */
    
    .logo-text {
        white-space: nowrap;
        overflow: hidden;
    }
    
    .sidebar.collapsed .logo-text {
        display: none; 
    }

    .header-actions {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .datetime-display {
        text-align: center;
        font-size: 11px;
        color: var(--text-tertiary);
        padding-bottom: 12px;
        white-space: nowrap;
        transition: all 0.2s ease;
        overflow: hidden;
        font-weight: 500;
    }

    .sidebar.collapsed .datetime-display {
        opacity: 0;
        height: 0;
        padding: 0;
        margin: 0;
        display: none;
    }

    .nav-badge {
        margin-left: auto; 
        background: #8b5cf6; 
        color: #fff; 
        font-size: 10px; 
        padding: 2px 8px; 
        border-radius: 12px; 
        font-weight: 700;
        transition: all 0.2s ease;
    }

    .sidebar.collapsed .nav-badge {
        display: none !important;
        opacity: 0;
    }

    /* --- DROPDOWN TOGGLE SWITCH STYLES --- */
    .toggle-switch {
        width: 34px;
        height: 20px;
        background: var(--text-tertiary);
        border-radius: 12px;
        position: relative;
        transition: background 0.3s ease;
        flex-shrink: 0;
    }
    
    .toggle-switch::after {
        content: '';
        position: absolute;
        top: 2px;
        left: 2px;
        width: 16px;
        height: 16px;
        background: #fff;
        border-radius: 50%;
        transition: transform 0.3s cubic-bezier(0.4, 0.0, 0.2, 1);
        box-shadow: 0 1px 2px rgba(0,0,0,0.2);
    }

    .dark-mode .toggle-switch {
        background: #10b981; 
    }
    
    .dark-mode .toggle-switch::after {
        transform: translateX(14px);
    }
</style>



<aside class="sidebar" id="sidebar">
    <script>
        // THE ANTI-SIDEBAR-DANCE PROTOCOL
        if (window.innerWidth > 768 && localStorage.getItem("sidebarCollapsed") === "true") {
            document.getElementById('sidebar').classList.add('collapsed');
        }
    </script>
    <div class="sidebar-header">
        <div class="logo-container">
            <div class="logo-wrapper">
                <img src="../assets/img/logo.png" alt="Logo" class="logo" onerror="this.src='../assets/img/logo.png';">
            </div>
            <div class="logo-text">
                <h2 class="app-name">Microfinance</h2>
                <span class="app-tagline" style="color: #a78bfa; font-weight: 600;">LSA OFFICER</span>
            </div>
        </div>
        
        <div class="header-actions">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i data-lucide="panel-left-close"></i>
            </button>
        </div>
    </div>

    <nav class="sidebar-nav">
        <div class="nav-section">
            <span class="nav-section-title">LSA MANAGEMENT</span>
            
            <a href="dashboard.php" class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i data-lucide="layout-dashboard"></i>
                <span>Dashboard</span>
            </a>

            <a href="application.php" class="nav-item <?= ($current_page == 'application.php') ? 'active' : '' ?>">
                <i data-lucide="file-plus-2"></i>
                <span>New Applications</span>
                <?php if($newAppCount > 0): ?>
                    <span class="nav-badge" data-count="<?= $newAppCount ?>"><?= $newAppCount ?></span>
                <?php endif; ?>
            </a>

            <a href="forwarded.php" class="nav-item <?= ($current_page == 'forwarded.php') ? 'active' : '' ?>">
                <i data-lucide="arrow-up-right"></i>
                <span>Forwarded Apps</span>
            </a>

            <a href="returned.php" class="nav-item <?= ($current_page == 'returned.php') ? 'active' : '' ?>">
                <i data-lucide="rotate-ccw"></i>
                <span>Returned Apps</span>
            </a>
            
            <a href="restructure.php" class="nav-item <?= ($current_page == 'restructure.php') ? 'active' : '' ?>">
                <i data-lucide="refresh-cw"></i>
                <span>Restructure Req.</span>
                <?php if($restructureCount > 0): ?>
                    <span class="nav-badge" data-count="<?= $restructureCount ?>"><?= $restructureCount ?></span>
                <?php endif; ?>
            </a>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div id="live-datetime" class="datetime-display"></div>
        
        <div class="user-profile">
            <div class="user-avatar">
                <img src="../client/uploads/profiles/<?= htmlspecialchars($profile_img) ?>" alt="Profile" onerror="this.src='../client/uploads/profiles/default_avatar.png';">
            </div>
            <div class="user-info">
                <span class="user-name"><?= htmlspecialchars($admin_name) ?></span>
                <span class="user-role"><?= htmlspecialchars($admin_role) ?></span>
            </div>
            <button class="user-menu-btn" id="userMenuBtn">
                <i data-lucide="more-vertical"></i>
            </button>
            
            <div class="user-menu-dropdown" id="userMenuDropdown">
                <div class="umd-header">
                    <div class="umd-avatar" id="umdAvatar"><?= strtoupper(substr($admin_name, 0, 1)) ?></div>
                    <div class="umd-info">
                        <span class="umd-signed">Signed in as</span>
                        <span class="umd-name" id="umdName"><?= htmlspecialchars($admin_name) ?></span>
                        <span class="umd-role" id="umdRole"><?= htmlspecialchars($admin_role) ?></span>
                    </div>
                </div>
                
                <div class="umd-divider"></div>
                
                <a href="profile.php" class="umd-item"><i data-lucide="user-round"></i><span>Profile</span></a>
                
                <button class="umd-item" id="themeToggleBtn" style="width: 100%; border: none; background: transparent; cursor: pointer; font-family: inherit; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="moon"></i>
                        <span>Dark Mode</span>
                    </div>
                    <div class="toggle-switch"></div>
                </button>

                <div class="umd-divider"></div>
                
                <a href="../login/logout.php" class="umd-item umd-item-danger umd-sign-out"><i data-lucide="log-out"></i><span>Sign Out</span></a>
            </div>
        </div>
    </div>
</aside>

<div class="mobile-overlay" id="mobileOverlay"></div>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // 1. Init Icons
    if (typeof lucide !== "undefined") lucide.createIcons();

    // 2. Sync body tag with the documentElement theme
    if (document.documentElement.classList.contains('dark-mode')) {
        document.body.classList.add('dark-mode');
    }

    // 3. Theme Toggle Logic
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation(); 
            
            document.documentElement.classList.toggle('dark-mode');
            document.body.classList.toggle('dark-mode');
            
            const isDark = document.documentElement.classList.contains('dark-mode');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');
        });
    }

    // 4. Live Time & Date Logic
    function updateDateTime() {
        const now = new Date();
        const options = { weekday: 'short', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' };
        const timeString = now.toLocaleDateString('en-US', options);
        const dtElement = document.getElementById('live-datetime');
        if(dtElement) dtElement.textContent = timeString;
    }
    setInterval(updateDateTime, 1000);
    updateDateTime(); 

    // 5. Sidebar Logic
    const sidebar = document.getElementById("sidebar");
    const sidebarToggle = document.getElementById("sidebarToggle");
    const mobileOverlay = document.getElementById("mobileOverlay");
    const mobileMenuBtn = document.getElementById("mobileMenuBtn");

    if (sidebarToggle) {
        sidebarToggle.addEventListener("click", () => {
            sidebar.classList.toggle("collapsed");
            if (window.innerWidth > 768) {
                localStorage.setItem("sidebarCollapsed", sidebar.classList.contains("collapsed"));
            }
        });
    }

    if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener("click", () => {
            sidebar.classList.add("mobile-open");
            if (mobileOverlay) mobileOverlay.classList.add("active");
            document.body.style.overflow = "hidden";
        });
    }

    if (mobileOverlay) {
        mobileOverlay.addEventListener("click", () => {
            sidebar.classList.remove("mobile-open");
            mobileOverlay.classList.remove("active");
            document.body.style.overflow = "";
        });
    }

    // 6. User Dropdown Logic
    const userBtn = document.getElementById('userMenuBtn');
    const userMenu = document.getElementById('userMenuDropdown');
    if (userBtn && userMenu) {
        userBtn.addEventListener('click', e => { 
            e.stopPropagation(); 
            userMenu.classList.toggle('umd-open'); 
        });
        document.addEventListener('click', e => { 
            if (!userMenu.contains(e.target) && e.target !== userBtn) {
                userMenu.classList.remove('umd-open');
            }
        });
    }

    // 7. Function to update badges without refreshing (LSA AJAX)
    function refreshBadges() {
        fetch('get_badge_counts.php')
            .then(response => response.json())
            .then(data => {
                // Update New Applications Badge
                const appBadge = document.querySelector('a[href="application.php"] .nav-badge');
                if (appBadge) {
                    appBadge.textContent = data.new_apps;
                    appBadge.style.display = data.new_apps > 0 ? 'inline-block' : 'none';
                }

                // Update Restructure Badge
                const restBadge = document.querySelector('a[href="restructure.php"] .nav-badge');
                if (restBadge) {
                    restBadge.textContent = data.restructure;
                    restBadge.style.display = data.restructure > 0 ? 'inline-block' : 'none';
                }
            })
            .catch(error => console.error("Error fetching badges:", error));
    }

    // Refresh every 30 seconds
    setInterval(refreshBadges, 30000);
});
</script>