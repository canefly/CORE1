<link rel="stylesheet" href="assets/css/Sidebar.css">

<?php $cp = basename($_SERVER['PHP_SELF']); ?>

<style>
    /* =========================================
   SIDEBAR COMPONENT STYLES (Global)
   ========================================= */

/* Sidebar Container */
.sidebar {
    width: 260px;
    height: 100vh;
    background-color: #1f2937;
    border-right: 1px solid #374151;
    position: fixed;
    top: 0;
    left: 0;
    display: flex;
    flex-direction: column;
    padding: 24px;
    z-index: 1000;
}

/* Brand / Logo Section */
.brand {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding-bottom: 24px;
    border-bottom: 1px solid #374151;
    margin-bottom: 24px;
}

.brand i {
    font-size: 28px;
    color: #10b981; /* Default Green Logo */
    line-height: 1;
    margin-top: 2px;
}

.brand-text {
    display: flex;
    flex-direction: column;
}

.brand h2 {
    color: #fff;
    font-size: 20px;
    font-weight: 700;
    margin: 0;
    line-height: 1.2;
}

.datetime {
    color: #9ca3af;
    font-size: 11px;
    margin-top: 4px;
}

/* User Profile Section */
.user-profile {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background-color: #111827;
    border-radius: 12px;
    margin-bottom: 30px;
    border: 1px solid #374151;
}

.avatar {
    width: 40px;
    height: 40px;
    background-color: #064e3b;
    color: #10b981;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 14px;
    flex-shrink: 0;
}

.user-info {
    display: flex;
    flex-direction: column;
}

.user-info h4 {
    color: #fff;
    font-size: 14px;
    font-weight: 600;
    margin: 0;
}

.user-info span {
    color: #9ca3af;
    font-size: 11px;
    text-transform: uppercase;
    margin-top: 2px;
}

/* Navigation Links */
.nav-links {
    list-style: none;
    display: flex;
    flex-direction: column;
    gap: 8px;
    padding: 0;
    margin: 0;
    flex-grow: 1;
}

.nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    border-radius: 8px;
    color: #9ca3af;
    text-decoration: none;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s ease;
    cursor: pointer;
}

.nav-item:hover {
    background-color: rgba(255, 255, 255, 0.05);
    color: #fff;
}

/* Active Link State */
.nav-item.active {
    background-color: #064e3b; /* Green Background */
    color: #fff;
    border: 1px solid #059669;
}

.nav-item.active i {
    color: #34d399; /* Light Green Icon */
}

.nav-item i {
    font-size: 18px;
    color: #6b7280;
}

/* Badges */
.nav-badge {
    margin-left: auto;
    background: #ef4444; /* Default Red */
    color: #fff;
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 700;
}

/* Amber Badge (For Warnings/Finance) */
.nav-badge.amber {
    background-color: #f59e0b;
    color: #0f172a;
}

/* Logout Button */
.logout-btn {
    border-top: 1px solid #374151;
    padding-top: 20px;
    margin-top: auto;
}

.logout-link {
    color: #ef4444;
}

.logout-link:hover {
    background-color: rgba(239, 68, 68, 0.1);
    color: #fca5a5;
}

.logout-link i {
    color: #ef4444;
}
</style>


<div class="sidebar">
    <div class="brand">
        <i class="bi bi-wallet-fill" style="color:#fbbf24;"></i> <div class="brand-text">
            <h2>Finance Dept.</h2>
            <div class="datetime" id="datetime"></div>
        </div>
    </div>

    <div class="user-profile">
        <div class="avatar" style="background:#b45309; color:#fff;">FA</div>
        <div class="user-info">
            <h4>Alex Morgan</h4>
            <span>Finance Head</span>
        </div>
    </div>

    <ul class="nav-links">
        <li>
            <a href="dashboard.php" class="nav-item <?= ($cp == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-grid-1x2-fill"></i> Dashboard
            </a>
        </li>
        <li>
            <a href="ledger.php" class="nav-item <?= ($cp == 'ledger.php') ? 'active' : '' ?>">
                <i class="bi bi-journal-text"></i> Master Loan Ledger
            </a>
        </li>
        <li>
            <a href="payments.php" class="nav-item <?= ($cp == 'payments.php') ? 'active' : '' ?>">
                <i class="bi bi-cash-coin"></i> Payment Monitoring
                <span class="nav-badge amber">5</span> 
            </a>
        </li>
        <li>
            <a href="disbursement.php" class="nav-item <?= ($cp == 'disbursement.php') ? 'active' : '' ?>">
                <i class="bi bi-box-arrow-right"></i> Disbursement
            </a>
        </li>
        
        <li style="margin-top:20px; border-top:1px solid #374151; padding-top:10px;">
            <span style="font-size:10px; color:#6b7280; padding-left:16px; text-transform:uppercase; font-weight:700;">Admin Controls</span>
        </li>
        <li>
            <a href="settings.php" class="nav-item <?= ($cp == 'settings.php') ? 'active' : '' ?>">
                <i class="bi bi-sliders"></i> Interest & Rates
            </a>
        </li>
    </ul>

    <div class="logout-btn">
        <a href="../index.php" class="nav-item logout-link">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</div>

<script>
    function updateDateTime() {
        const now = new Date();
        document.getElementById('datetime').textContent = now.toLocaleDateString('en-US', { 
            month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' 
        });
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);
</script>