<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Dashboard</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Loan Officer Overview</h1>
            <p>Welcome back, Sarah. You have <strong>5 applications</strong> pending your decision today.</p>
        </div>

        <div class="analytics-grid">
            
            <div class="stat-card card-purple">
                <div class="stat-header">
                    <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                </div>
                <div class="stat-value">5</div>
                <div class="stat-label">Pending Approval</div>
                <div class="stat-trend trend-up">
                    <i class="bi bi-arrow-up-short"></i> +2 since yesterday
                </div>
            </div>

            <div class="stat-card card-green">
                <div class="stat-header">
                    <div class="stat-icon"><i class="bi bi-check-lg"></i></div>
                </div>
                <div class="stat-value">24</div>
                <div class="stat-label">Approved (Oct)</div>
                <div class="stat-trend trend-up">
                    <i class="bi bi-graph-up-arrow"></i> 12% vs last month
                </div>
            </div>

            <div class="stat-card card-red">
                <div class="stat-header">
                    <div class="stat-icon"><i class="bi bi-x-lg"></i></div>
                </div>
                <div class="stat-value">8</div>
                <div class="stat-label">Rejected (Oct)</div>
                <div class="stat-trend trend-down">
                    <i class="bi bi-arrow-down-short"></i> Low risk level
                </div>
            </div>

            <div class="stat-card card-blue">
                <div class="stat-header">
                    <div class="stat-icon"><i class="bi bi-wallet2"></i></div>
                </div>
                <div class="stat-value">₱2.4M</div>
                <div class="stat-label">Total Disbursed</div>
                <div class="stat-trend">
                    <span style="color:#94a3b8;">Active Portfolio</span>
                </div>
            </div>

        </div>

        <div class="section-title">
            <span>Priority Applications (Waiting for Decision)</span>
            <a href="approvals.php" class="view-all">View All Queue <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Borrower</th>
                        <th>Loan Amount</th>
                        <th>LSA Verification</th>
                        <th>Risk Assessment</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1015</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">GT</div>
                                <span>Gary Spongebob Thompson</span>
                            </div>
                        </td>
                        <td>₱25,000</td>
                        <td>
                            <i class="bi bi-check-circle-fill" style="color:#10b981; margin-right:5px;"></i> Verified
                        </td>
                        <td><span class="risk-badge risk-low">Low Risk</span></td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1015" class="btn-review">
                                Decide <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1012</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">LK</div>
                                <span>Jella Kudrow</span>
                            </div>
                        </td>
                        <td>₱50,000</td>
                        <td>
                            <i class="bi bi-check-circle-fill" style="color:#10b981; margin-right:5px;"></i> Verified
                        </td>
                        <td><span class="risk-badge risk-med">Medium Risk</span></td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1012" class="btn-review">
                                Decide <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1010</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">RS</div>
                                <span>Richard Dabu</span>
                            </div>
                        </td>
                        <td>₱150,000</td>
                        <td>
                            <i class="bi bi-check-circle-fill" style="color:#10b981; margin-right:5px;"></i> Verified
                        </td>
                        <td><span class="risk-badge risk-high">High Risk</span></td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1010" class="btn-review">
                                Decide <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</body>
</html>