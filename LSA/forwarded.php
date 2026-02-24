<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Forwarded Applications</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Forwarded.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Approved & Forwarded</h1>
            <p>Applications verified by LSA, currently waiting for Loan Officer approval.</p>
        </div>

        <div class="filter-bar">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search client...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Client Name</th>
                        <th>Loan Amount</th>
                        <th>Date Forwarded</th>
                        <th>Current Status</th>
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
                        <td>Oct 20, 2025</td>
                        <td><span class="badge bg-purple">Waiting for L.O. Approval</span></td>
                        <td style="text-align:center;">
                            <button class="btn-view-only" title="View Details"><i class="bi bi-eye"></i></button>
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
                        <td>Oct 19, 2025</td>
                        <td><span class="badge bg-purple">Waiting for L.O. Approval</span></td>
                        <td style="text-align:center;">
                            <button class="btn-view-only" title="View Details"><i class="bi bi-eye"></i></button>
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
                        <td>₱10,000</td>
                        <td>Oct 18, 2025</td>
                        <td><span class="badge bg-purple">Waiting for L.O. Approval</span></td>
                        <td style="text-align:center;">
                            <button class="btn-view-only" title="View Details"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1008</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">SC</div>
                                <span>Sarah Connor</span>
                            </div>
                        </td>
                        <td>₱100,000</td>
                        <td>Oct 17, 2025</td>
                        <td><span class="badge bg-purple">Waiting for L.O. Approval</span></td>
                        <td style="text-align:center;">
                            <button class="btn-view-only" title="View Details"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#a78bfa; font-weight:700;">#LA-1005</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">MJ</div>
                                <span>Michael Jordan</span>
                            </div>
                        </td>
                        <td>₱75,000</td>
                        <td>Oct 15, 2025</td>
                        <td><span class="badge bg-purple">Waiting for L.O. Approval</span></td>
                        <td style="text-align:center;">
                            <button class="btn-view-only" title="View Details"><i class="bi bi-eye"></i></button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        
    </div>
</body>
</html>