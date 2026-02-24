<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Master Ledger</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/ledger.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Master Loan Ledger</h1>
                <p>Complete record of all active and closed loan accounts.</p>
            </div>
        </div>

        <div class="toolbar">
            <div class="filter-group">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search client, Loan ID...">
                </div>
                
                <select class="select-filter">
                    <option value="all">All Status</option>
                    <option value="active" selected>Active</option>
                    <option value="paid">Fully Paid</option>
                    <option value="overdue">Overdue</option>
                </select>

                <select class="select-filter">
                    <option value="this_month">This Month</option>
                    <option value="last_month">Last Month</option>
                    <option value="year_2025">2025</option>
                </select>
            </div>

            <button class="btn-export">
                <i class="bi bi-download"></i> Export CSV
            </button>
        </div>

        <div class="ledger-container">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Client Name</th>
                            <th>Release Date</th>
                            <th class="text-right">Principal</th>
                            <th class="text-right">Total Interest</th>
                            <th class="text-right">Total Paid</th>
                            <th class="text-right">Balance</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th style="text-align:center;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        
                        <tr>
                            <td style="color:#fbbf24; font-weight:700;">#LN-2025-001</td>
                            <td>Maria Clara</td>
                            <td>Oct 01, 2025</td>
                            <td class="text-right font-mono">15,000.00</td>
                            <td class="text-right font-mono">1,500.00</td>
                            <td class="text-right font-mono" style="color:#34d399;">5,500.00</td>
                            <td class="text-right font-mono" style="color:#fff;">11,000.00</td>
                            <td>
                                <span class="progress-text">33% Paid</span>
                                <div class="progress-wrapper">
                                    <div class="progress-fill" style="width: 33%; background:#34d399;"></div>
                                </div>
                            </td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td style="text-align:center;">
                                <a href="#" class="btn-view">OPEN</a>
                            </td>
                        </tr>

                        <tr>
                            <td style="color:#fbbf24; font-weight:700;">#LN-2025-004</td>
                            <td>Juan Dela Cruz</td>
                            <td>Sep 15, 2025</td>
                            <td class="text-right font-mono">50,000.00</td>
                            <td class="text-right font-mono">5,000.00</td>
                            <td class="text-right font-mono" style="color:#34d399;">10,000.00</td>
                            <td class="text-right font-mono" style="color:#f87171;">45,000.00</td>
                            <td>
                                <span class="progress-text">18% Paid</span>
                                <div class="progress-wrapper">
                                    <div class="progress-fill" style="width: 18%; background:#f87171;"></div>
                                </div>
                            </td>
                            <td><span class="status-badge status-overdue">Overdue</span></td>
                            <td style="text-align:center;">
                                <a href="#" class="btn-view">OPEN</a>
                            </td>
                        </tr>

                        <tr>
                            <td style="color:#fbbf24; font-weight:700;">#LN-2025-008</td>
                            <td>Gary Thompson</td>
                            <td>Oct 20, 2025</td>
                            <td class="text-right font-mono">25,000.00</td>
                            <td class="text-right font-mono">2,500.00</td>
                            <td class="text-right font-mono" style="color:#34d399;">0.00</td>
                            <td class="text-right font-mono" style="color:#fff;">27,500.00</td>
                            <td>
                                <span class="progress-text">0% Paid</span>
                                <div class="progress-wrapper">
                                    <div class="progress-fill" style="width: 0%;"></div>
                                </div>
                            </td>
                            <td><span class="status-badge status-active">Active</span></td>
                            <td style="text-align:center;">
                                <a href="#" class="btn-view">OPEN</a>
                            </td>
                        </tr>

                        <tr>
                            <td style="color:#fbbf24; font-weight:700;">#LN-2024-899</td>
                            <td>Jose Rizal</td>
                            <td>Jan 10, 2024</td>
                            <td class="text-right font-mono">100,000.00</td>
                            <td class="text-right font-mono">10,000.00</td>
                            <td class="text-right font-mono" style="color:#34d399;">110,000.00</td>
                            <td class="text-right font-mono" style="color:#94a3b8;">0.00</td>
                            <td>
                                <span class="progress-text">100% Paid</span>
                                <div class="progress-wrapper">
                                    <div class="progress-fill" style="width: 100%; background:#10b981;"></div>
                                </div>
                            </td>
                            <td><span class="status-badge status-paid">Closed</span></td>
                            <td style="text-align:center;">
                                <a href="#" class="btn-view">OPEN</a>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

</body>
</html>