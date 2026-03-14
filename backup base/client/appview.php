<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Application | MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/appview.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <a href="dashboard.php" class="back-link">
            <i class="bi bi-arrow-left"></i> Back to Dashboard
        </a>

        <div class="page-header">
            <h1>Application Status</h1>
            <p>Track the progress of your loan request.</p>
        </div>

        <div class="app-grid">
            
            <div class="col-left">
                
                <div class="status-card">
                    <div class="card-header">
                        <div>
                            <div class="app-id">Application #APP-2025-001</div>
                            <div class="app-date">Submitted on Oct 20, 2025</div>
                        </div>
                        <span class="status-pill">Under Review</span>
                    </div>

                    <div class="timeline">
                        <div class="timeline-item completed">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Application Submitted</h4>
                                <p>Oct 20, 2025 • 09:30 AM</p>
                            </div>
                        </div>

                        <div class="timeline-item active">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>LSA Document Verification</h4>
                                <p>In Progress • Assigned to: Staff Monitor</p>
                                <p style="color:#60a5fa; margin-top:5px; font-size:11px;">
                                    <i class="bi bi-info-circle"></i> We are currently checking your submitted IDs.
                                </p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Loan Officer Approval</h4>
                                <p>Pending</p>
                            </div>
                        </div>

                        <div class="timeline-item">
                            <div class="timeline-dot"></div>
                            <div class="timeline-content">
                                <h4>Disbursement</h4>
                                <p>Pending</p>
                            </div>
                        </div>
                    </div>
                </div>

                </div>

            <div class="col-right">
                
                <div class="status-card">
                    <h4 style="color:#fff; margin-bottom:15px; font-size:14px;">Loan Details</h4>
                    
                    <div class="detail-row">
                        <span class="label">Amount Requested</span>
                        <span class="value" style="color:#10b981;">₱ 10,000.00</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Term</span>
                        <span class="value">6 Months</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Interest Rate</span>
                        <span class="value">3.5% / mo</span>
                    </div>
                    <div class="detail-row">
                        <span class="label">Est. Monthly</span>
                        <span class="value">₱ 1,816.67</span>
                    </div>
                </div>

                <div class="status-card">
                    <h4 style="color:#fff; margin-bottom:15px; font-size:14px;">Documents</h4>
                    
                    <div class="doc-preview">
                        <div class="doc-left">
                            <i class="bi bi-person-vcard"></i>
                            <span class="doc-name">Valid ID.jpg</span>
                        </div>
                        <i class="bi bi-check-circle-fill doc-status"></i>
                    </div>

                    <div class="doc-preview">
                        <div class="doc-left">
                            <i class="bi bi-file-earmark-text"></i>
                            <span class="doc-name">Proof of Income.pdf</span>
                        </div>
                        <i class="bi bi-hourglass-split" style="color:#fbbf24; font-size:11px;"></i>
                    </div>
                </div>

            </div>

        </div>

    </div>

</body>
</html>