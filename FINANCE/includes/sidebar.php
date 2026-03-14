<link rel="stylesheet" href="assets/css/Sidebar.css">

<?php 
    // 1. Get the current page and directory
    $cp = basename($_SERVER['PHP_SELF']); 
    $current_dir = basename(dirname($_SERVER['SCRIPT_NAME']));

    // 2. Smart Flags: Para malaman ng system kung anong dropdown ang dapat nakabuka (open) by default
    $is_lo = ($current_dir === 'LOAN_OFFICER');
    $is_lsa = ($current_dir === 'LSA');
    $is_admin = ($current_dir !== 'LOAN_OFFICER' && $current_dir !== 'LSA' && $cp === 'settings.php');
    $is_finance_ops = ($current_dir !== 'LOAN_OFFICER' && $current_dir !== 'LSA' && $cp !== 'settings.php');
?>

<style>
    /* BASIC SIDEBAR STYLES */
    .sidebar { width: 260px; height: 100vh; background-color: #1f2937; border-right: 1px solid #374151; position: fixed; top: 0; left: 0; display: flex; flex-direction: column; padding: 24px 15px; z-index: 1000; overflow-y: auto; }
    .brand { display: flex; align-items: flex-start; gap: 12px; padding-bottom: 24px; border-bottom: 1px solid #374151; margin-bottom: 24px; padding-left: 9px; }
    .brand i { font-size: 28px; color: #10b981; line-height: 1; margin-top: 2px; }
    .brand-text { display: flex; flex-direction: column; }
    .brand h2 { color: #fff; font-size: 20px; font-weight: 700; margin: 0; line-height: 1.2; }
    .datetime { color: #9ca3af; font-size: 11px; margin-top: 4px; }
    
    .user-profile { display: flex; align-items: center; gap: 12px; padding: 16px; background-color: #111827; border-radius: 12px; margin-bottom: 20px; border: 1px solid #374151; margin-left: 9px; margin-right: 9px; }
    .avatar { width: 40px; height: 40px; background-color: #064e3b; color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0; }
    .user-info { display: flex; flex-direction: column; }
    .user-info h4 { color: #fff; font-size: 14px; font-weight: 600; margin: 0; }
    .user-info span { color: #9ca3af; font-size: 11px; text-transform: uppercase; margin-top: 2px; }
    
    .nav-links { list-style: none; display: flex; flex-direction: column; padding: 0; margin: 0; flex-grow: 1; }
    .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 16px; border-radius: 8px; color: #9ca3af; text-decoration: none; font-size: 13px; font-weight: 500; transition: all 0.2s ease; cursor: pointer; }
    .nav-item:hover { background-color: rgba(255, 255, 255, 0.05); color: #fff; }
    .nav-item.active { background-color: #064e3b; color: #fff; border: 1px solid #059669; }
    .nav-item.active i { color: #34d399; }
    .nav-item i { font-size: 16px; color: #6b7280; width: 20px; text-align: center; }
    .nav-badge { margin-left: auto; background: #ef4444; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 12px; font-weight: 700; }
    .nav-badge.amber { background-color: #f59e0b; color: #0f172a; }
    
    .logout-btn { border-top: 1px solid #374151; padding-top: 20px; margin-top: 20px; padding-left: 9px; padding-right: 9px;}
    .logout-link { color: #ef4444; }
    .logout-link:hover { background-color: rgba(239, 68, 68, 0.1); color: #fca5a5; }
    .logout-link i { color: #ef4444; }
    
    /* DROPDOWN (ACCORDION) STYLES */
    .nav-group { margin-bottom: 5px; }
    
    .nav-group-title {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 10px 16px;
        color: #9ca3af;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        cursor: pointer;
        border-radius: 8px;
        transition: all 0.2s;
        letter-spacing: 0.5px;
    }
    .nav-group-title:hover { background-color: #111827; color: #fff; }
    .nav-group-title i.caret { font-size: 12px; transition: transform 0.3s ease; }
    
    /* When accordion is open */
    .nav-group-title.open { color: #fff; }
    .nav-group-title.open i.caret { transform: rotate(90deg); }
    
    /* Submenu container */
    .submenu {
        display: none; /* Nakatago by default */
        list-style: none;
        padding-left: 10px; /* Indent para sa ilalim ng dropdown */
        margin: 5px 0 0 0;
        flex-direction: column;
        gap: 2px;
    }
    .submenu.open { display: flex; /* Lalabas kapag may .open class */ }
    
    /* Scrollbar */
    .sidebar::-webkit-scrollbar { width: 5px; }
    .sidebar::-webkit-scrollbar-track { background: transparent; }
    .sidebar::-webkit-scrollbar-thumb { background-color: #4b5563; border-radius: 10px; }
</style>

<div class="sidebar">
    <div class="brand">
        <i class="bi bi-wallet-fill" style="color:#fbbf24;"></i> 
        <div class="brand-text">
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
        
        <li class="nav-group">
            <div class="nav-group-title <?= $is_finance_ops ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>Finance Ops</span>
                <i class="bi bi-chevron-right caret"></i>
            </div>
            <ul class="submenu <?= $is_finance_ops ? 'open' : '' ?>">
                <li>
                    <a href="../FINANCE/dashboard.php" class="nav-item <?= ($is_finance_ops && $cp == 'dashboard.php') ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2-fill"></i> Dashboard
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/ledger.php" class="nav-item <?= ($is_finance_ops && $cp == 'ledger.php') ? 'active' : '' ?>">
                        <i class="bi bi-journal-text"></i> Loan Ledger
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/payments.php" class="nav-item <?= ($is_finance_ops && $cp == 'payments.php') ? 'active' : '' ?>">
                        <i class="bi bi-cash-coin"></i> Payments
                        <span class="nav-badge amber">5</span> 
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/disbursement.php" class="nav-item <?= ($is_finance_ops && $cp == 'disbursement.php') ? 'active' : '' ?>">
                        <i class="bi bi-box-arrow-right"></i> Disbursement
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-group">
            <div class="nav-group-title <?= $is_lo ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>Loan Officer</span>
                <i class="bi bi-chevron-right caret"></i>
            </div>
            <ul class="submenu <?= $is_lo ? 'open' : '' ?>">
                <li>
                    <a href="../LOAN_OFFICER/dashboard.php" class="nav-item <?= ($is_lo && $cp == 'dashboard.php') ? 'active' : '' ?>">
                        <i class="bi bi-speedometer2"></i> LO Dashboard
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/approvals.php" class="nav-item <?= ($is_lo && $cp == 'approvals.php') ? 'active' : '' ?>">
                        <i class="bi bi-check-square-fill"></i> For Approval
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/approved.php" class="nav-item <?= ($is_lo && $cp == 'approved.php') ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-check"></i> Approved Loans
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/rejected.php" class="nav-item <?= ($is_lo && $cp == 'rejected.php') ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-x"></i> Rejected Loans
                    </a>
                </li>
                <li>
                    <a href="../LOAN_OFFICER/restructure.php" class="nav-item <?= ($is_lo && $cp == 'restructure.php') ? 'active' : '' ?>">
                        <i class="bi bi-arrow-repeat"></i> Restructure Req.
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-group">
            <div class="nav-group-title <?= $is_lsa ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>LSA</span>
                <i class="bi bi-chevron-right caret"></i>
            </div>
            <ul class="submenu <?= $is_lsa ? 'open' : '' ?>">
                <li>
                    <a href="../LSA/dashboard.php" class="nav-item <?= ($is_lsa && $cp == 'dashboard.php') ? 'active' : '' ?>">
                        <i class="bi bi-grid-1x2-fill"></i> LSA Dashboard
                    </a>
                </li>
                <li>
                    <a href="../LSA/application.php" class="nav-item <?= ($is_lsa && $cp == 'application.php') ? 'active' : '' ?>">
                        <i class="bi bi-file-earmark-plus-fill"></i> New Apps
                    </a>
                </li>
                <li>
                    <a href="../LSA/forwarded.php" class="nav-item <?= ($is_lsa && $cp == 'forwarded.php') ? 'active' : '' ?>">
                        <i class="bi bi-arrow-up-short"></i> Forwarded Apps
                    </a>
                </li>
                <li>
                    <a href="../LSA/returned.php" class="nav-item <?= ($is_lsa && $cp == 'returned.php') ? 'active' : '' ?>">
                        <i class="bi bi-arrow-counterclockwise"></i> Returned Apps
                    </a>
                </li>
                <li>
                    <a href="../LSA/restructure.php" class="nav-item <?= ($is_lsa && $cp == 'restructure.php') ? 'active' : '' ?>">
                        <i class="bi bi-arrow-repeat"></i> Restructure
                    </a>
                </li>
            </ul>
        </li>

        <li class="nav-group">
            <div class="nav-group-title <?= $is_admin ? 'open' : '' ?>" onclick="toggleDropdown(this)">
                <span>Admin Controls</span>
                <i class="bi bi-chevron-right caret"></i>
            </div>
            <ul class="submenu <?= $is_admin ? 'open' : '' ?>">
                <li>
                    <a href="../FINANCE/settings.php" class="nav-item <?= ($is_admin && $cp == 'settings.php') ? 'active' : '' ?>">
                        <i class="bi bi-sliders"></i> Interest & Rates
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/manage_clients.php" class="nav-item <?= ($is_admin && $cp == 'manage_clients.php') ? 'active' : '' ?>">
                        <i class="bi bi-people-fill"></i> Manage Clients
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/manage_officers.php" class="nav-item <?= ($is_admin && $cp == 'manage_officers.php') ? 'active' : '' ?>">
                        <i class="bi bi-person-badge-fill"></i> Manage Officers
                    </a>
                </li>
                <li>
                    <a href="../FINANCE/logs.php" class="nav-item <?= ($is_admin && $cp == 'logs.php') ? 'active' : '' ?>">
                        <i class="bi bi-list-columns-reverse"></i> Audit Logs
                    </a>
                </li>
            </ul>
        </li>
    </ul>

    <div class="logout-btn">
        <a href="../logout.php" class="nav-item logout-link">
            <i class="bi bi-box-arrow-right"></i> Sign Out
        </a>
    </div>
</div>

<script>
    // Oras
    function updateDateTime() {
        const now = new Date();
        const datetimeElement = document.getElementById('datetime');
        if (datetimeElement) {
            datetimeElement.textContent = now.toLocaleDateString('en-US', { 
                month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' 
            });
        }
    }
    updateDateTime();
    setInterval(updateDateTime, 1000);

    // Accordion / Dropdown Logic
    function toggleDropdown(element) {
        // I-toggle yung ikot ng arrow
        element.classList.toggle('open');
        
        // Kunin yung <ul> na kasunod niya
        var submenu = element.nextElementSibling;
        if (submenu) {
            // I-toggle yung paglabas at pagtago ng menu
            submenu.classList.toggle('open');
        }
    }
</script>