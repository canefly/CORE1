<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA Dashboard | Analytics</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Dashboard.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>LSA Overview</h1>
            <p>Analytics & Performance Metrics</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-orange">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-info">
                    <h3>12</h3>
                    <span>Pending Review</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-red">
                    <i class="bi bi-file-earmark-x"></i>
                </div>
                <div class="stat-info">
                    <h3>4</h3>
                    <span>Returned Today</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="stat-info">
                    <h3>98%</h3>
                    <span>Accuracy Rate</span>
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-bar-chart-fill"></i> Top Missing Documents</h3>
                    <span class="card-subtitle">Based on returned applications this month</span>
                </div>

                <div class="bar-chart-container">
                    <div class="bar-item">
                        <div class="bar-label">
                            <span>Proof of Income</span>
                            <span>45%</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: 45%;"></div>
                        </div>
                    </div>

                    <div class="bar-item">
                        <div class="bar-label">
                            <span>Valid Gov ID</span>
                            <span>30%</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: 30%;"></div>
                        </div>
                    </div>

                    <div class="bar-item">
                        <div class="bar-label">
                            <span>Barangay Clearance</span>
                            <span>15%</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: 15%;"></div>
                        </div>
                    </div>

                    <div class="bar-item">
                        <div class="bar-label">
                            <span>Utility Bill (Proof of Address)</span>
                            <span>10%</span>
                        </div>
                        <div class="progress-bg">
                            <div class="progress-fill" style="width: 10%;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-clock-history"></i> Recent Activity</h3>
                </div>

                <ul class="activity-list">
                    <li class="activity-item">
                        <div class="dot green"></div>
                        <div>
                            <p>Verified docs for <strong class="text-white">Maria Clara</strong></p>
                            <span class="time-ago">10 mins ago</span>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="dot red"></div>
                        <div>
                            <p>Returned app: <strong class="text-white">Juan Cruz</strong> (Blurred ID)</p>
                            <span class="time-ago">1 hour ago</span>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="dot green"></div>
                        <div>
                            <p>Verified docs for <strong class="text-white">Pedro Santos</strong></p>
                            <span class="time-ago">2 hours ago</span>
                        </div>
                    </li>
                    <li class="activity-item">
                        <div class="dot orange"></div>
                        <div>
                            <p>Flagged suspicious file: <strong class="text-white">Jose Rizal</strong></p>
                            <span class="time-ago">3 hours ago</span>
                        </div>
                    </li>
                </ul>
            </div>

        </div> </div>
</body>
</html>