<?php
session_start();

/*
    Adjust mo ito depende sa actual auth/config mo.
    Halimbawa kung meron kang auth file:
    require_once __DIR__ . "/../../config/auth.php";
*/
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Disbursement</title>
    <link rel="stylesheet" href="../../assets/css/loan-disbursement.css?v=1.0">
    <script src="../../assets/js/loan-disbursement.js?v=1.0"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="icon" type="image/png" href="../../img/logo.png">
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo-container">
                <div class="logo-wrapper">
                    <img src="../../img/logo.png" alt="Logo" class="logo">
                </div>
                <div class="logo-text">
                    <h2 class="app-name">Microfinance</h2>
                    <span class="app-tagline">Financial</span>
                </div>
            </div>
            <button class="sidebar-toggle" id="sidebarToggle">
                <i data-lucide="panel-left-close"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <span class="nav-section-title">MAIN MENU</span>

                <a href="../dashboard.php" class="nav-item">
                    <i data-lucide="layout-dashboard"></i>
                    <span>Dashboard</span>
                </a>

                <a href="../Approvalq.php" class="nav-item">
                    <i data-lucide="check-circle"></i>
                    <span>Approval Queue</span>
                </a>

                <a href="../financeapproval.php" class="nav-item">
                    <i data-lucide="user-check"></i>
                    <span>Finance Approval</span>
                </a>

                <a href="../gl.php" class="nav-item">
                    <i data-lucide="receipt-text"></i>
                    <span>General Ledger</span>
                </a>

                <a href="../ap.php" class="nav-item">
                    <i data-lucide="scale"></i>
                    <span>AP Management</span>
                </a>

                <a href="../simulationapproval.php" class="nav-item">
                    <i data-lucide="calculator"></i>
                    <span>Simulation Approval</span>
                </a>

                <a href="loan-disbursement.php" class="nav-item active">
                    <i data-lucide="banknote-arrow-up"></i>
                    <span>Loan Disbursement</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">SETTINGS</span>

                <a href="#" class="nav-item">
                    <i data-lucide="settings"></i>
                    <span>Configuration</span>
                </a>

                <a href="#" class="nav-item">
                    <i data-lucide="shield"></i>
                    <span>Security</span>
                </a>
            </div>
        </nav>

        <div class="sidebar-footer">
            <div class="user-profile">
                <div class="user-avatar">
                    <img src="../../img/profile.png" alt="User">
                </div>
                <div class="user-info">
                    <span class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></span>
                    <span class="user-role"><?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Administrator'); ?></span>
                </div>
                <button class="user-menu-btn" id="userMenuBtn">
                    <i data-lucide="more-vertical"></i>
                </button>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <div class="header-left">
                <button class="mobile-menu-btn" id="mobileMenuBtn">
                    <i data-lucide="menu"></i>
                </button>
                <div class="header-title">
                    <h1>Loan Disbursement</h1>
                    <p>Manage approved loans waiting for release and move them to active loans once disbursed.</p>
                </div>
            </div>

            <div class="header-right">
                <div class="header-clock">
                    <span id="realTimeClock"></span>
                </div>

                <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                    <i data-lucide="sun" class="sun-icon"></i>
                    <i data-lucide="moon" class="moon-icon"></i>
                </button>

                <button class="icon-btn">
                    <i data-lucide="bell"></i>
                </button>
            </div>
        </header>

        <div class="content-wrapper">

            <div class="stats-grid">
                <div class="stat-card-minimal">
                    <div class="stat-icon text-brand-green">
                        <i data-lucide="wallet"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Pending Release</span>
                        <span class="stat-value" id="statPendingAmount">₱0.00</span>
                    </div>
                </div>

                <div class="stat-card-minimal">
                    <div class="stat-icon text-accent-blue">
                        <i data-lucide="check-circle-2"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Disbursed Today</span>
                        <span class="stat-value" id="statDisbursedToday">₱0.00</span>
                    </div>
                </div>

                <div class="stat-card-minimal">
                    <div class="stat-icon text-accent-purple">
                        <i data-lucide="history"></i>
                    </div>
                    <div class="stat-info">
                        <span class="stat-label">Waiting Count</span>
                        <span class="stat-value" id="statWaitingCount">0</span>
                    </div>
                </div>
            </div>

            <nav class="tabs-nav">
                <button class="tab-link active" data-tab="pending">
                    <i data-lucide="layers"></i> Pending Disbursement
                </button>
                <button class="tab-link" data-tab="history">
                    <i data-lucide="file-text"></i> Disbursement History
                </button>
            </nav>

            <div id="pendingTab" class="tab-pane active">
                <div class="premium-container">
                    <div class="section-header">
                        <div class="section-title">
                            <i data-lucide="banknote-arrow-up"></i>
                            <span>Pending Loan Releases</span>
                        </div>
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="search" placeholder="Search client / application..." id="searchPending">
                        </div>
                    </div>

                    <div class="payroll-table-container">
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Application ID</th>
                                    <th>Borrower</th>
                                    <th>Principal</th>
                                    <th>Term</th>
                                    <th>Monthly Due</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="pendingDisbursementBody">
                                <tr>
                                    <td colspan="7" class="td-loading-state">
                                        <div class="loading-sync">
                                            <i data-lucide="refresh-cw" class="spin icon-loading-spin"></i>
                                            <p class="text-sync-msg">Loading pending disbursements...</p>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div id="historyTab" class="tab-pane">
                <div class="premium-container">
                    <div class="section-header">
                        <div class="section-title">
                            <i data-lucide="history"></i>
                            <span>Disbursement History</span>
                        </div>
                        <div class="search-box">
                            <i data-lucide="search"></i>
                            <input type="search" placeholder="Search disbursed loans..." id="searchHistory">
                        </div>
                    </div>

                    <div class="payroll-table-container">
                        <table class="payroll-table">
                            <thead>
                                <tr>
                                    <th>Application ID</th>
                                    <th>Borrower</th>
                                    <th>Principal</th>
                                    <th>Term</th>
                                    <th>Monthly Due</th>
                                    <th>Disbursed At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="historyDisbursementBody">
                                <tr>
                                    <td colspan="7" class="td-empty-state">No history found.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </main>

    <div id="detailModal" class="modal-overlay">
        <div class="modal-premium">
            <div class="modal-header-premium">
                <div class="section-title">
                    <i data-lucide="file-text" class="text-brand-green"></i>
                    <span>Loan Disbursement Details</span>
                </div>
                <div class="flex-center-gap">
                    <button class="btn-close-modal" id="btnCloseModal">
                        <i data-lucide="x"></i>
                    </button>
                </div>
            </div>

            <div class="modal-body-premium" id="detailContent">
                <div class="loading-sync">
                    <i data-lucide="refresh-cw" class="spin icon-loading-spin"></i>
                    <p class="text-sync-msg">Loading details...</p>
                </div>
            </div>
        </div>
    </div>

 
</body>
</html>