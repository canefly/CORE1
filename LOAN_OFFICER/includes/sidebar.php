
<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>


<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<style>
    /* --- SIDEBAR CONTAINER --- */
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
        padding: 24px; /* Increased padding for better spacing */
        z-index: 1000;
    }

    /* --- BRAND HEADER --- */
    .brand {
        display: flex;
        align-items: flex-start; /* Aligns icon with the top of text */
        gap: 12px;
        padding-bottom: 24px;
        border-bottom: 1px solid #374151;
        margin-bottom: 24px;
    }

    .brand i {
        font-size: 28px;
        color: #10b981;
        line-height: 1; /* Fixes vertical alignment issues */
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
        /* REMOVED margin-left: 36px (This was causing the misalignment) */
    }

    /* --- USER PROFILE CARD --- */
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

    /* --- NAVIGATION --- */
    .nav-links {
        list-style: none;
        display: flex;
        flex-direction: column;
        gap: 8px; /* Spacing between links */
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
    }

    .nav-item:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #fff;
    }

    /* Active State - Matches your reference image (Green Button Style) */
    .nav-item.active {
        background-color: #064e3b; /* Dark Green Background */
        color: #fff; /* White Text */
        border: 1px solid #059669; /* Subtle border definition */
    }
    
    /* Highlight the icon in active state */
    .nav-item.active i {
        color: #34d399; 
    }

    .nav-item i {
        font-size: 18px;
        color: #6b7280; /* Default Icon Color */
    }
    
    /* Badge Style */
    .nav-badge {
        margin-left: auto;
        background: #ef4444;
        color: #fff;
        font-size: 10px;
        padding: 2px 8px;
        border-radius: 12px;
        font-weight: 700;
    }

    /* --- LOGOUT --- */
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
        <i class="bi bi-bank2"></i>
        <h2>MicroFinance <span style="font-size:10px; color:#a78bfa; display:block;">LOAN OFFICER</span></h2>
    </div>

    <div class="user-profile">
        <div class="avatar" style="background-color:#4338ca; color:#fff;">LO</div>
        <div class="user-info">
            <h4>Sarah Connor</h4>
            <span>Senior Approver</span>
        </div>
    </div>

    <ul class="nav-links">
        
        <li>
            <a href="dashboard.php" class="nav-item <?php echo ($current_page == 'dashboard.php') ? 'active' : ''; ?>">
                <i class="bi bi-speedometer2"></i> LO Dashboard
            </a>
        </li>

        <li>
            <a href="approvals.php" class="nav-item <?php echo ($current_page == 'approvals.php') ? 'active' : ''; ?>">
                <i class="bi bi-check-square-fill"></i> For Approval
                <span class="nav-badge" style="background:#8b5cf6;">5</span>
            </a>
        </li>

        <li>
            <a href="approved.php" class="nav-item <?php echo ($current_page == 'approved.php') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-check"></i> Approved Loans
            </a>
        </li>

        <li>
            <a href="rejected.php" class="nav-item <?php echo ($current_page == 'rejected.php') ? 'active' : ''; ?>">
                <i class="bi bi-file-earmark-x"></i> Rejected Loans
            </a>
        </li>
        
        <li>
            <a href="restructure.php" class="nav-item <?php echo ($current_page == 'restructure.php') ? 'active' : ''; ?>">
                <i class="bi bi-arrow-repeat"></i> Restructure Req.
            </a>
        </li>

    </ul>

    <div class="logout-btn">
        <a href="../index.php" class="nav-item logout-link">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</div>