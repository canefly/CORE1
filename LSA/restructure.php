<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Restructure Requests</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Sidebar.css">
    <link rel="stylesheet" href="assets/css/Restructure.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Restructure Requests</h1>
            <p>Incoming requests from clients to modify existing loan terms.</p>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <button class="btn-filter active">New Requests</button>
                <button class="btn-filter">Pending Docs</button>
                <button class="btn-filter">Forwarded</button>
            </div>
            
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search client name...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Client Name</th>
                        <th>Request Type</th>
                        <th>Proposed Change</th>
                        <th>Reason</th>
                        <th>Supporting Docs</th> <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#fbbf24; font-weight:700;">#RQ-201</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">JD</div>
                                <span>Juan Dela Cruz</span>
                            </div>
                        </td>
                        <td>Term Extension</td>
                        <td>
                            <div class="comparison-box">
                                <span class="val-old">6 Mos</span>
                                <i class="bi bi-arrow-right arrow-icon"></i>
                                <span class="val-new">12 Mos</span>
                            </div>
                        </td>
                        <td style="font-size:13px; color:#cbd5e1;">Financial hardship</td>
                        
                        <td>
                            <a href="#" class="doc-link" title="View Document">
                                <i class="bi bi-file-earmark-medical"></i> Hospital Bill.pdf
                            </a>
                        </td>

                        <td><span class="badge bg-amber">New Request</span></td>
                        <td style="text-align:center;">
                            <a href="#" class="btn-assess">
                                Assess <i class="bi bi-clipboard-check"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#fbbf24; font-weight:700;">#RQ-202</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">MC</div>
                                <span>Maria Clara</span>
                            </div>
                        </td>
                        <td>Payment Reduction</td>
                        <td>
                            <div class="comparison-box">
                                <span class="val-old">₱5k/mo</span>
                                <i class="bi bi-arrow-right arrow-icon"></i>
                                <span class="val-new">₱3k/mo</span>
                            </div>
                        </td>
                        <td style="font-size:13px; color:#cbd5e1;">Medical emergency</td>

                        <td>
                            <a href="#" class="doc-link" title="View Document">
                                <i class="bi bi-file-earmark-text"></i> Medical Cert.jpg
                            </a>
                        </td>

                        <td><span class="badge bg-amber">New Request</span></td>
                        <td style="text-align:center;">
                            <a href="#" class="btn-assess">
                                Assess <i class="bi bi-clipboard-check"></i>
                            </a>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#fbbf24; font-weight:700;">#RQ-203</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">PP</div>
                                <span>Pedro Penduko</span>
                            </div>
                        </td>
                        <td>Restructure</td>
                        <td>
                            <div class="comparison-box">
                                <span class="val-old">Active</span>
                                <i class="bi bi-arrow-right arrow-icon"></i>
                                <span class="val-new">Paused</span>
                            </div>
                        </td>
                        <td style="font-size:13px; color:#cbd5e1;">Job loss</td>

                        <td>
                            <a href="#" class="doc-link" title="View Document">
                                <i class="bi bi-file-earmark-person"></i> Termination Letter.pdf
                            </a>
                        </td>

                        <td><span class="badge bg-amber">New Request</span></td>
                        <td style="text-align:center;">
                            <a href="#" class="btn-assess">
                                Assess <i class="bi bi-clipboard-check"></i>
                            </a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
        
    </div>
</body>
</html>