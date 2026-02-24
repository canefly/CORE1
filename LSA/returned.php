<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Returned Applications</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Returned.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Returned Applications</h1>
            <p>Applications waiting for client re-submission.</p>
        </div>

        <div class="top-bar">
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
                        <th>Date Returned</th>
                        <th>Rejection Reason</th>
                        <th>Status</th>
                        <th style="text-align:center;">History</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#ef4444; font-weight:700;">#LA-1020</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">JS</div>
                                <span>John Smith</span>
                            </div>
                        </td>
                        <td>Oct 20, 2025</td>
                        <td>
                            <span class="reason-text">ID Expired</span>
                        </td>
                        <td><span class="badge-returned">Waiting for Client</span></td>
                        <td style="text-align:center;">
                            <a href="#" class="btn-view" title="View Details"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#ef4444; font-weight:700;">#LA-1018</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">AJ</div>
                                <span>Alice Johnson</span>
                            </div>
                        </td>
                        <td>Oct 19, 2025</td>
                        <td>
                            <span class="reason-text">Blurred Payslip</span>
                        </td>
                        <td><span class="badge-returned">Waiting for Client</span></td>
                        <td style="text-align:center;">
                            <a href="#" class="btn-view" title="View Details"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

</body>
</html>