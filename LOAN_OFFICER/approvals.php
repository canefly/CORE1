<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Approvals</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Approvals.css">
    
    </head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Pending Approvals</h1>
            <p>Review applications verified by LSA and make final loan decisions.</p>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <button class="btn-filter active">All Queue</button>
                <button class="btn-filter">Low Risk</button>
                <button class="btn-filter">Medium Risk</button>
                <button class="btn-filter">High Risk</button>
            </div>
            
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search borrower...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Borrower Name</th>
                        <th>Loan Amount</th>
                        <th>Term</th>
                        <th>Risk Level</th>
                        <th>Verified Date</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1015</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">GT</div>
                                <span>Gary Thompson</span>
                            </div>
                        </td>
                        <td>₱25,000</td>
                        <td><span class="term-text">6 MOS</span></td>
                        <td><span class="risk-badge risk-low">Low Risk</span></td>
                        <td>Oct 20, 2025</td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1015" class="btn-review">
                                Review <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1012</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">LK</div>
                                <span>Lisa Kudrow</span>
                            </div>
                        </td>
                        <td>₱50,000</td>
                        <td><span class="term-text">12 MOS</span></td>
                        <td><span class="risk-badge risk-med">Medium Risk</span></td>
                        <td>Oct 19, 2025</td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1012" class="btn-review">
                                Review <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1010</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">RS</div>
                                <span>Richard Smith</span>
                            </div>
                        </td>
                        <td>₱150,000</td>
                        <td><span class="term-text">24 MOS</span></td>
                        <td><span class="risk-badge risk-high">High Risk</span></td>
                        <td>Oct 18, 2025</td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1010" class="btn-review">
                                Review <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1008</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">JM</div>
                                <span>John Mayer</span>
                            </div>
                        </td>
                        <td>₱10,000</td>
                        <td><span class="term-text">3 MOS</span></td>
                        <td><span class="risk-badge risk-low">Low Risk</span></td>
                        <td>Oct 17, 2025</td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1008" class="btn-review">
                                Review <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1005</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">TS</div>
                                <span>Tony Stark</span>
                            </div>
                        </td>
                        <td>₱200,000</td>
                        <td><span class="term-text">18 MOS</span></td>
                        <td><span class="risk-badge risk-med">Medium Risk</span></td>
                        <td>Oct 15, 2025</td>
                        <td style="text-align:center;">
                            <a href="review_loan.php?id=1005" class="btn-review">
                                Review <i class="bi bi-arrow-right-short"></i>
                            </a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

</body>
</html>