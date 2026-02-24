<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Rejected Loans</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Rejected.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Rejected Applications</h1>
            <p>History of declined loan requests and decision rationale.</p>
        </div>

        <div class="filter-bar">
            <div class="date-filter">
                <span style="font-size:12px; color:#94a3b8;">Filter by Date:</span>
                <input type="date" class="date-input">
            </div>
            
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search by name or App ID...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Borrower</th>
                        <th>Requested Amount</th>
                        <th>Rejection Reason</th>
                        <th>Date Rejected</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#f87171; font-weight:700;">#LA-1011</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">JD</div>
                                <span>John Doe</span>
                            </div>
                        </td>
                        <td>₱500,000</td>
                        <td><span class="reason-pill">Credit Score Too Low</span></td>
                        <td>Oct 20, 2025</td>
                        <td>
                            <span class="badge-rejected"><i class="bi bi-x-circle"></i> Rejected</span>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-view" title="View Full Report"><i class="bi bi-file-text"></i></button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#f87171; font-weight:700;">#LA-1004</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">AS</div>
                                <span>Alice Smith</span>
                            </div>
                        </td>
                        <td>₱50,000</td>
                        <td><span class="reason-pill">High Debt-to-Income</span></td>
                        <td>Oct 18, 2025</td>
                        <td>
                            <span class="badge-rejected"><i class="bi bi-x-circle"></i> Rejected</span>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-view" title="View Full Report"><i class="bi bi-file-text"></i></button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#f87171; font-weight:700;">#LA-0992</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">BO</div>
                                <span>Bob OdenCharliekirk</span>
                            </div>
                        </td>
                        <td>₱1,000,000</td>
                        <td><span class="reason-pill">Collateral Insufficient</span></td>
                        <td>Oct 15, 2025</td>
                        <td>
                            <span class="badge-rejected"><i class="bi bi-x-circle"></i> Rejected</span>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-view" title="View Full Report"><i class="bi bi-file-text"></i></button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#f87171; font-weight:700;">#LA-0985</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">WW</div>
                                <span>Walter White</span>
                            </div>
                        </td>
                        <td>₱2,500,000</td>
                        <td><span class="reason-pill">Unverifiable Income</span></td>
                        <td>Oct 10, 2025</td>
                        <td>
                            <span class="badge-rejected"><i class="bi bi-x-circle"></i> Rejected</span>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-view" title="View Full Report"><i class="bi bi-file-text"></i></button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        
    </div>
</body>
</html>