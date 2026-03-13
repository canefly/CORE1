<?php 
// Standard database include
include 'includes/db_connect.php'; 

/** * ANALYTICS QUERIES
 * Fetching real-time counts from the loan_applications table.
 */

// 1. Pending Review Count
$pending_sql = "SELECT COUNT(*) as total FROM loan_applications WHERE status = 'PENDING'";
$pending_res = $conn->query($pending_sql);
$pending_count = $pending_res->fetch_assoc()['total'];

// 2. Returned Today Count
$returned_sql = "SELECT COUNT(*) as total FROM loan_applications 
                 WHERE status = 'REJECTED' AND DATE(updated_at) = CURDATE()";
$returned_res = $conn->query($returned_sql);
$returned_today = $returned_res->fetch_assoc()['total'];

// 3. Accuracy Rate (Approved vs Total Processed)
$total_processed_sql = "SELECT COUNT(*) as total FROM loan_applications WHERE status IN ('APPROVED', 'REJECTED')";
$total_proc_res = $conn->query($total_processed_sql);
$total_proc = $total_proc_res->fetch_assoc()['total'];

$approved_sql = "SELECT COUNT(*) as total FROM loan_applications WHERE status = 'APPROVED'";
$app_res = $conn->query($approved_sql);
$total_app = $app_res->fetch_assoc()['total'];

$accuracy = ($total_proc > 0) ? round(($total_app / $total_proc) * 100) : 100;

/** * REJECTION REASONS TRACKING
 * Counts occurrences of keywords within the 'remarks' column.
 */
function getReasonCount($conn, $keyword) {
    $keyword = mysqli_real_escape_string($conn, $keyword);
    $sql = "SELECT COUNT(*) as total FROM loan_applications 
            WHERE status = 'REJECTED' AND remarks LIKE '%$keyword%'";
    $res = $conn->query($sql);
    return $res->fetch_assoc()['total'];
}

$blurryCount = getReasonCount($conn, 'Blurry ID');
$expiredCount = getReasonCount($conn, 'Expired Documents');
$incompleteCount = getReasonCount($conn, 'Incomplete Documents');
$wrongDocCount = getReasonCount($conn, 'Wrong Document Type');

// Get total rejections for percentage calculation
$totalRejections = ($returned_today > 0) ? $returned_today : ($total_proc - $total_app);

function getPercentage($count, $total) {
    return ($total > 0) ? round(($count / $total) * 100) : 0;
}

// 4. Recent Activity Log
$activity_sql = "SELECT u.fullname, la.status, la.updated_at, la.remarks 
                 FROM loan_applications la 
                 JOIN users u ON la.user_id = u.id 
                 ORDER BY la.updated_at DESC LIMIT 4";
$activities = $conn->query($activity_sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA Dashboard | Analytics</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Dashboard.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>LSA Overview</h1>
            <p>Real-time analytics for loan processing and document verification.</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon icon-orange"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <h3><?php echo $pending_count; ?></h3>
                    <span>Pending Review</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-red"><i class="bi bi-file-earmark-x"></i></div>
                <div class="stat-info">
                    <h3><?php echo $returned_today; ?></h3>
                    <span>Returned Today</span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon icon-green"><i class="bi bi-check-circle"></i></div>
                <div class="stat-info">
                    <h3><?php echo $accuracy; ?>%</h3>
                    <span>Accuracy Rate</span>
                </div>
            </div>
        </div>

        <div class="analytics-grid">
            
            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-bar-chart-fill"></i> Document Issue Tracking</h3>
                    <span class="card-subtitle">Breakdown of most common rejection reasons</span>
                </div>

                <div class="bar-chart-container" style="padding: 20px;">
                    <div class="bar-item" style="margin-bottom: 20px;">
                        <div class="bar-label" style="display: flex; justify-content: space-between; color: #cbd5e1; font-size: 14px; margin-bottom: 8px;">
                            <span>Blurry ID</span>
                            <span><?php echo getPercentage($blurryCount, $totalRejections); ?>%</span>
                        </div>
                        <div class="progress-bg" style="background: #334155; height: 10px; border-radius: 5px;">
                            <div class="progress-fill" style="width: <?php echo getPercentage($blurryCount, $totalRejections); ?>%; background: #10b981; height: 100%; border-radius: 5px;"></div>
                        </div>
                    </div>

                    <div class="bar-item" style="margin-bottom: 20px;">
                        <div class="bar-label" style="display: flex; justify-content: space-between; color: #cbd5e1; font-size: 14px; margin-bottom: 8px;">
                            <span>Incomplete Documents</span>
                            <span><?php echo getPercentage($incompleteCount, $totalRejections); ?>%</span>
                        </div>
                        <div class="progress-bg" style="background: #334155; height: 10px; border-radius: 5px;">
                            <div class="progress-fill" style="width: <?php echo getPercentage($incompleteCount, $totalRejections); ?>%; background: #34d399; height: 100%; border-radius: 5px;"></div>
                        </div>
                    </div>

                    <div class="bar-item" style="margin-bottom: 20px;">
                        <div class="bar-label" style="display: flex; justify-content: space-between; color: #cbd5e1; font-size: 14px; margin-bottom: 8px;">
                            <span>Expired Documents</span>
                            <span><?php echo getPercentage($expiredCount, $totalRejections); ?>%</span>
                        </div>
                        <div class="progress-bg" style="background: #334155; height: 10px; border-radius: 5px;">
                            <div class="progress-fill" style="width: <?php echo getPercentage($expiredCount, $totalRejections); ?>%; background: #fbbf24; height: 100%; border-radius: 5px;"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card">
                <div class="card-header">
                    <h3><i class="bi bi-clock-history"></i> Recent Activity</h3>
                </div>

                <ul class="activity-list">
                    <?php if($activities && $activities->num_rows > 0): ?>
                        <?php while($act = $activities->fetch_assoc()): ?>
                        <li class="activity-item" style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <div class="dot" style="height: 10px; width: 10px; border-radius: 50%; background: <?php echo ($act['status'] == 'APPROVED') ? '#10b981' : '#ef4444'; ?>;"></div>
                            <div>
                                <p style="color: #f8fafc; font-size: 14px; margin: 0;">
                                    <?php echo ($act['status'] == 'APPROVED') ? 'Verified' : 'Returned'; ?> app for 
                                    <strong><?php echo htmlspecialchars($act['fullname']); ?></strong>
                                    <?php if($act['status'] == 'REJECTED') echo " <span style='color: #94a3b8; font-size: 12px;'>(".htmlspecialchars($act['remarks']).")</span>"; ?>
                                </p>
                                <span style="color: #94a3b8; font-size: 12px;"><?php echo date('h:i A', strtotime($act['updated_at'])); ?></span>
                            </div>
                        </li>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p style="color: #94a3b8; padding: 20px;">No recent activity found.</p>
                    <?php endif; ?>
                </ul>
            </div>

        </div> 
    </div>
</body>
</html>