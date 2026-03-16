<?php 
// Standard database include
require_once '../includes/db_connect.php';

if (!isset($pdo)) { 
    die("Fatal Error: \$pdo variable is not defined. Check your db_connect.php file."); 
}

try {
    // 1. Pending Review Count (Status = 'PENDING' for LSA)
    $pending_count = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'PENDING'")->fetchColumn() ?: 0;

    // 2. Returned Today Count
    $returned_today = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'REJECTED' AND DATE(updated_at) = CURDATE()")->fetchColumn() ?: 0;

    // 3. Accuracy Rate (Approved vs Total Processed)
    $total_proc = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status IN ('APPROVED', 'REJECTED')")->fetchColumn() ?: 0;
    $total_app = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'APPROVED'")->fetchColumn() ?: 0;
    $accuracy = ($total_proc > 0) ? round(($total_app / $total_proc) * 100) : 100;

    // 4. REJECTION REASONS TRACKING (Converted to PDO)
    function getReasonCount($pdo, $keyword) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM loan_applications WHERE status = 'REJECTED' AND remarks LIKE ?");
        $stmt->execute(['%' . $keyword . '%']);
        return $stmt->fetchColumn() ?: 0;
    }

    $blurryCount = getReasonCount($pdo, 'Blurry ID');
    $expiredCount = getReasonCount($pdo, 'Expired Documents');
    $incompleteCount = getReasonCount($pdo, 'Incomplete Documents');
    $wrongDocCount = getReasonCount($pdo, 'Wrong Document Type');

    // Get total rejections for percentage calculation
    $totalRejections = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'REJECTED'")->fetchColumn() ?: 0;

    function getPercentage($count, $total) {
        return ($total > 0) ? round(($count / $total) * 100) : 0;
    }

    // 5. Recent Activity Log (Converted to PDO)
    $activities = $pdo->query("
        SELECT u.fullname, la.status, la.updated_at, la.remarks 
        FROM loan_applications la 
        JOIN users u ON la.user_id = u.id 
        ORDER BY la.updated_at DESC LIMIT 4
    ")->fetchAll();

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
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

    <?php include '../includes/sidebar.php'; ?>

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
                    <?php if(!empty($activities)): ?>
                        <?php foreach($activities as $act): ?>
                        <li class="activity-item" style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                            <div class="dot" style="height: 10px; width: 10px; border-radius: 50%; background: <?php echo ($act['status'] == 'VERIFIED' || $act['status'] == 'APPROVED') ? '#10b981' : '#ef4444'; ?>;"></div>
                            <div>
                                <p style="color: #f8fafc; font-size: 14px; margin: 0;">
                                    <?php echo ($act['status'] == 'VERIFIED' || $act['status'] == 'APPROVED') ? 'Verified' : 'Returned'; ?> app for 
                                    <strong><?php echo htmlspecialchars($act['fullname']); ?></strong>
                                    <?php if($act['status'] == 'REJECTED' && !empty($act['remarks'])) echo " <span style='color: #94a3b8; font-size: 12px;'>(".htmlspecialchars($act['remarks']).")</span>"; ?>
                                </p>
                                <span style="color: #94a3b8; font-size: 12px;"><?php echo date('h:i A', strtotime($act['updated_at'])); ?></span>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #94a3b8; padding: 20px;">No recent activity found.</p>
                    <?php endif; ?>
                </ul>
            </div>

        </div> 
    </div>
</body>
</html>