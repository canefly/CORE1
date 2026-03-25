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
    // 1. Get the current page and directory
    $cp = basename($_SERVER['PHP_SELF']); 
    $current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));

    // 2. Define page arrays
    $finance_pages = ['dashboard.php', 'ledger.php', 'payments.php', 'disbursement.php'];
    $admin_pages = ['settings.php', 'manage_clients.php', 'manage_officers.php', 'logs.php'];

    // 3. Smart Flags: Para malaman ng system kung anong dropdown ang dapat nakabuka (open) by default
    $is_lo = ($current_dir === 'LOAN_OFFICER');
    $is_lsa = ($current_dir === 'LSA');
    $is_admin = ($current_dir !== 'LOAN_OFFICER' && $current_dir !== 'LSA' && in_array($cp, $admin_pages));
    $is_finance_ops = ($current_dir !== 'LOAN_OFFICER' && $current_dir !== 'LSA' && in_array($cp, $finance_pages));
?>

<link rel="stylesheet" href="../FINANCE/assets/css/global.css">
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
    
    .nav-badge.amber { 
        background-color: #f59e0b; 
        color: #0f172a; 
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
                <i data-lucide="wallet" style="color: #fbbf24; width: 24px; height: 24px;"></i>
            </div>
            <div class="logo-text">
                <h2 class="app-name">Finance Dept.</h2>
                <span class="app-tagline" style="color: #a78bfa; font-weight: 600;">FINANCE HEAD</span>
            </div>
        </div>
        
        <div class="header-actions">
            <button class="sidebar-toggle" id="sidebarToggle">
                <i data-lucide="panel-left-close"></i>
            </button>
        </div>
    </div>

    <nav class="sidebar-nav">
        <!-- FINANCE OPS -->
        <div class="nav-group">
            <div class="nav-group-title <?= $is_finance_ops ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>Finance Ops</span>
                <i data-lucide="chevron-right" class="caret"></i>
            </div>
            <ul class="submenu <?= $is_finance_ops ? 'open' : '' ?>">
                <li>
                    <a href="../FINANCE/dashboard.php" class="nav-item <?= ($is_finance_ops && $cp == 'dashboard.php') ? 'active' : '' ?>">
                        <i data-lucide="layout-dashboard"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/ledger.php" class="nav-item <?= ($is_finance_ops && $cp == 'ledger.php') ? 'active' : '' ?>">
                        <i data-lucide="book-open"></i>
                        <span>Loan Ledger</span>
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/payments.php" class="nav-item <?= ($is_finance_ops && $cp == 'payments.php') ? 'active' : '' ?>">
                        <i data-lucide="coins"></i>
                        <span>Payments</span>
                        <span class="nav-badge amber">5</span>
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/disbursement.php" class="nav-item <?= ($is_finance_ops && $cp == 'disbursement.php') ? 'active' : '' ?>">
                        <i data-lucide="banknote"></i>
                        <span>Disbursement</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- LOAN OFFICER -->
        <div class="nav-group">
            <div class="nav-group-title <?= $is_lo ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>Loan Officer</span>
                <i data-lucide="chevron-right" class="caret"></i>
            </div>
            <ul class="submenu <?= $is_lo ? 'open' : '' ?>">
                <li>
                    <a href="../LOAN_OFFICER/dashboard.php" class="nav-item <?= ($is_lo && $cp == 'dashboard.php') ? 'active' : '' ?>">
                        <i data-lucide="gauge"></i>
                        <span>LO Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/approvals.php" class="nav-item <?= ($is_lo && $cp == 'approvals.php') ? 'active' : '' ?>">
                        <i data-lucide="check-square"></i>
                        <span>For Approval</span>
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/approved.php" class="nav-item <?= ($is_lo && $cp == 'approved.php') ? 'active' : '' ?>">
                        <i data-lucide="file-check"></i>
                        <span>Approved Loans</span>
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/rejected.php" class="nav-item <?= ($is_lo && $cp == 'rejected.php') ? 'active' : '' ?>">
                        <i data-lucide="file-x"></i>
                        <span>Rejected Loans</span>
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/restructure.php" class="nav-item <?= ($is_lo && $cp == 'restructure.php') ? 'active' : '' ?>">
                        <i data-lucide="refresh-cw"></i>
                        <span>Restructure Req.</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- LSA -->
        <div class="nav-group">
            <div class="nav-group-title <?= $is_lsa ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>LSA</span>
                <i data-lucide="chevron-right" class="caret"></i>
            </div>
            <ul class="submenu <?= $is_lsa ? 'open' : '' ?>">
                <li>
                    <a href="../LSA/dashboard.php" class="nav-item <?= ($is_lsa && $cp == 'dashboard.php') ? 'active' : '' ?>">
                        <i data-lucide="layout-dashboard"></i>
                        <span>LSA Dashboard</span>
                    </a>
                </li>
                <li>
                    <a href="../LSA/application.php" class="nav-item <?= ($is_lsa && $cp == 'application.php') ? 'active' : '' ?>">
                        <i data-lucide="file-plus-2"></i>
                        <span>New Apps</span>
                    </a>
                </li>
                <li>
                    <a href="../LSA/forwarded.php" class="nav-item <?= ($is_lsa && $cp == 'forwarded.php') ? 'active' : '' ?>">
                        <i data-lucide="arrow-up-right"></i>
                        <span>Forwarded Apps</span>
                    </a>
                </li>
                <li>
                    <a href="../LSA/returned.php" class="nav-item <?= ($is_lsa && $cp == 'returned.php') ? 'active' : '' ?>">
                        <i data-lucide="rotate-ccw"></i>
                        <span>Returned Apps</span>
                    </a>
                </li>
                <li>
                    <a href="../LSA/restructure.php" class="nav-item <?= ($is_lsa && $cp == 'restructure.php') ? 'active' : '' ?>">
                        <i data-lucide="refresh-cw"></i>
                        <span>Restructure</span>
                    </a>
                </li>
                <li>
                    <a href="../LSA/restructure_archive.php" class="nav-item <?= ($current_page == 'restructure_archive.php') ? 'active' : '' ?>">
                <i data-lucide="archive"></i>
                <span>Restructure Archive</span>   
                    </a>
                </li>
            </ul>
        </div>

        <!-- ADMIN CONTROLS -->
        <div class="nav-group">
            <div class="nav-group-title <?= $is_admin ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>Admin Controls</span>
                <i data-lucide="chevron-right" class="caret"></i>
            </div>
            <ul class="submenu <?= $is_admin ? 'open' : '' ?>">
                <li>
                    <a href="../FINANCE/settings.php" class="nav-item <?= ($is_admin && $cp == 'settings.php') ? 'active' : '' ?>">
                        <i data-lucide="sliders-horizontal"></i>
                        <span>Interest & Rates</span>
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/manage_clients.php" class="nav-item <?= ($is_admin && $cp == 'manage_clients.php') ? 'active' : '' ?>">
                        <i data-lucide="users"></i>
                        <span>Manage Clients</span>
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/manage_officers.php" class="nav-item <?= ($is_admin && $cp == 'manage_officers.php') ? 'active' : '' ?>">
                        <i data-lucide="shield"></i>
                        <span>Manage Officers</span>
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/finance_tickets.php" class="nav-item <?= ($is_admin && $cp == 'finance_tickets.php')? 'active' : '' ?>">
                    <i data-lucide="tickets" ></i>
                    <span>Tickets</span>
                </li>
                <li>
                    <a href="../FINANCE/logs.php" class="nav-item <?= ($is_admin && $cp == 'logs.php') ? 'active' : '' ?>">
                        <i data-lucide="list"></i>
                        <span>Audit Logs</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="sidebar-footer">
        <div id="live-datetime" class="datetime-display"></div>
        
        <div class="user-profile">
            <div class="user-avatar" style="background:#b45309; color:#fff; display:flex; align-items:center; justify-content:center; font-weight:700;">
                FA
            </div>
            <div class="user-info">
                <span class="user-name">Alex Morgan</span>
                <span class="user-role">Finance Head</span>
            </div>
            <button class="user-menu-btn" id="userMenuBtn">
                <i data-lucide="more-vertical"></i>
            </button>
            
            <div class="user-menu-dropdown" id="userMenuDropdown">
                <div class="umd-header">
                    <div class="umd-avatar" id="umdAvatar" style="background:#b45309; color: #fff; font-size: 15px; font-weight: 700; display: flex; align-items: center; justify-content: center;">FA</div>
                    <div class="umd-info">
                        <span class="umd-signed">Signed in as</span>
                        <span class="umd-name" id="umdName">Alex Morgan</span>
                        <span class="umd-role" id="umdRole">Finance Head</span>
                    </div>
                </div>
                
                <div class="umd-divider"></div>
                
                <a href="#" class="umd-item"><i data-lucide="user-round"></i><span>Profile</span></a>
                
                <button class="umd-item" id="themeToggleBtn" style="width: 100%; border: none; background: transparent; cursor: pointer; font-family: inherit; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i data-lucide="moon"></i>
                        <span>Dark Mode</span>
                    </div>
                    <div class="toggle-switch"></div>
                </button>

                <div class="umd-divider"></div>
                
                <a href="../logout.php" class="umd-item umd-item-danger umd-sign-out"><i data-lucide="log-out"></i><span>Sign Out</span></a>
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
});

// Accordion / Dropdown Logic
function toggleDropdown(element) {
    element.classList.toggle('open');
    var submenu = element.nextElementSibling;
    if (submenu) {
        submenu.classList.toggle('open');
    }
}
</script>