<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$current_page = basename($_SERVER['PHP_SELF']);

// 1. Kunin ang Session Info ni LO
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_name = $_SESSION['admin_name'] ?? 'Loan Officer';
$admin_role = $_SESSION['admin_role'] ?? 'Loan Officer';

// Default picture
$profile_img = 'default_avatar.png';

// 2. Kunin ang Profile Picture sa lo_users table
if (isset($pdo) && $admin_id > 0) {
    $stmt = $pdo->prepare("SELECT profile_pic FROM lo_users WHERE id = ?");
    $stmt->execute([$admin_id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_data && !empty($user_data['profile_pic'])) {
        $profile_img = $user_data['profile_pic'];
    }
}
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
        padding: 24px; 
        z-index: 1000;
    }

    /* --- BRAND HEADER --- */
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
        color: #10b981;
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
        object-fit: cover; /* Para hindi ma-stretch ang image */
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
    }

    .nav-item:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #fff;
    }

    .nav-item.active {
        background-color: #064e3b; 
        color: #fff; 
        border: 1px solid #059669; 
    }
    
    .nav-item.active i {
        color: #34d399; 
    }

    .nav-item i {
        font-size: 18px;
        color: #6b7280; 
    }
    
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
        <img src="../client/uploads/profiles/<?= htmlspecialchars($profile_img) ?>" alt="Profile" class="avatar" onerror="this.src='../client/uploads/profiles/default_avatar.png';">
        <div class="user-info">
            <h4><?= htmlspecialchars($admin_name) ?></h4>
            <span><?= htmlspecialchars($admin_role) ?></span>
        </div>
    </div>

    <ul class="nav-links">
        
        <li>
            <a href="dashboard.php" class="nav-item <?= ($current_page == 'dashboard.php') ? 'active' : '' ?>">
                <i class="bi bi-speedometer2"></i> LO Dashboard
            </a>
        </li>

        <li>
            <a href="approvals.php" class="nav-item <?= ($current_page == 'approvals.php') ? 'active' : '' ?>">
                <i class="bi bi-check-square-fill"></i> For Approval
                <span class="nav-badge" style="background:#8b5cf6;">5</span>
            </a>
        </li>

        <li>
            <a href="approved.php" class="nav-item <?= ($current_page == 'approved.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-check"></i> Approved Loans
            </a>
        </li>

        <li>
            <a href="rejected.php" class="nav-item <?= ($current_page == 'rejected.php') ? 'active' : '' ?>">
                <i class="bi bi-file-earmark-x"></i> Rejected Loans
            </a>
        </li>
        
        <li>
            <a href="restructure.php" class="nav-item <?= ($current_page == 'restructure.php') ? 'active' : '' ?>">
                <i class="bi bi-arrow-repeat"></i> Restructure Req.
            </a>
        </li>

    </ul>

    <div class="logout-btn">
        <a href="../logout.php" class="nav-item logout-link">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</div>