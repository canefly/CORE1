<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Restructure Requests</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Restructure.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Restructure Requests</h1>
            <p>Manage loan modification proposals and negotiations.</p>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <button class="btn-filter active">All Pending</button>
                <button class="btn-filter">Client Requested</button>
                <button class="btn-filter">Counter-Offers</button>
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
                        <th>Type</th>
                        <th>Proposed Changes</th>
                        <th>Date Requested</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#fbbf24; font-weight:700;">#LA-1014</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">EP</div>
                                <span>Elvis Presley</span>
                            </div>
                        </td>
                        <td><span style="font-size:12px; color:#cbd5e1;">Amount Change</span></td>
                        <td>
                            <div class="change-box">
                                <span class="val-old">₱50,000</span>
                                <i class="bi bi-arrow-right arrow-icon"></i>
                                <span class="val-new">₱40,000</span>
                            </div>
                        </td>
                        <td>Oct 20, 2025</td>
                        <td><span class="badge-pending">Waiting for Client</span></td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1014" class="btn-review">
                                View <i class="bi bi-eye"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#fbbf24; font-weight:700;">#LA-1009</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">MM</div>
                                <span>Marilyn Monroe</span>
                            </div>
                        </td>
                        <td><span style="font-size:12px; color:#cbd5e1;">Term Extension</span></td>
                        <td>
                            <div class="change-box">
                                <span class="val-old">12 Months</span>
                                <i class="bi bi-arrow-right arrow-icon"></i>
                                <span class="val-new">18 Months</span>
                            </div>
                        </td>
                        <td>Oct 19, 2025</td>
                        <td><span class="badge-pending">Approval Required</span></td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1009" class="btn-review">
                                Decide <i class="bi bi-check-circle"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#fbbf24; font-weight:700;">#LA-0997</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">CD</div>
                                <span>Celine Dion</span>
                            </div>
                        </td>
                        <td><span style="font-size:12px; color:#cbd5e1;">Rate Adjustment</span></td>
                        <td>
                            <div class="change-box">
                                <span class="val-old">5.0%</span>
                                <i class="bi bi-arrow-right arrow-icon"></i>
                                <span class="val-new">4.5%</span>
                            </div>
                        </td>
                        <td>Oct 18, 2025</td>
                        <td><span class="badge-pending">Approval Required</span></td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=0997" class="btn-review">
                                Decide <i class="bi bi-check-circle"></i>
                            </a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        
    </div>
</body>
</html>