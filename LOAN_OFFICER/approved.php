<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Approved Loans</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Approved.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Approved Loans History</h1>
            <p>Archive of all successfully approved loan applications.</p>
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
                        <th>Approved Amount</th>
                        <th>Interest</th>
                        <th>Approval Date</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#34d399; font-weight:700;">#LA-1002</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">BW</div>
                                <span>Bruce Wayne</span>
                            </div>
                        </td>
                        <td>₱1,000,000</td>
                        <td>3.5%</td>
                        <td>Oct 10, 2025</td>
                        <td>
                            <span class="badge-approved"><i class="bi bi-check2"></i> Approved</span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                <button class="btn-icon" title="View Details"><i class="bi bi-eye"></i></button>
                                <button class="btn-icon btn-print" title="Print Contract"><i class="bi bi-printer"></i></button>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#34d399; font-weight:700;">#LA-0998</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">CK</div>
                                <span>Clark Kent</span>
                            </div>
                        </td>
                        <td>₱50,000</td>
                        <td>5.0%</td>
                        <td>Oct 08, 2025</td>
                        <td>
                            <span class="badge-approved"><i class="bi bi-check2"></i> Approved</span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                <button class="btn-icon" title="View Details"><i class="bi bi-eye"></i></button>
                                <button class="btn-icon btn-print" title="Print Contract"><i class="bi bi-printer"></i></button>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#34d399; font-weight:700;">#LA-0995</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">DP</div>
                                <span>Diana Prince</span>
                            </div>
                        </td>
                        <td>₱150,000</td>
                        <td>4.2%</td>
                        <td>Oct 05, 2025</td>
                        <td>
                            <span class="badge-approved"><i class="bi bi-check2"></i> Approved</span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                <button class="btn-icon" title="View Details"><i class="bi bi-eye"></i></button>
                                <button class="btn-icon btn-print" title="Print Contract"><i class="bi bi-printer"></i></button>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#34d399; font-weight:700;">#LA-0990</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">BA</div>
                                <span>Barry Allen</span>
                            </div>
                        </td>
                        <td>₱20,000</td>
                        <td>6.0%</td>
                        <td>Oct 01, 2025</td>
                        <td>
                            <span class="badge-approved"><i class="bi bi-check2"></i> Approved</span>
                        </td>
                        <td style="text-align:center;">
                            <div class="action-btn-group">
                                <button class="btn-icon" title="View Details"><i class="bi bi-eye"></i></button>
                                <button class="btn-icon btn-print" title="Print Contract"><i class="bi bi-printer"></i></button>
                            </div>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        
    </div>
</body>
</html>