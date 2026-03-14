<?php
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';
if (file_exists($connection_file)) { require_once $connection_file; } 
else { die("Error: Connection file not found."); }
if (!isset($pdo)) { die("Fatal Error: \$pdo variable is not defined."); }

try {
    // KPI: Pending Approvals
    $pendingCount = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'VERIFIED'")->fetchColumn() ?: 0;
    
    // KPI: Approved this month
    $approvedCount = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'APPROVED' AND MONTH(updated_at) = MONTH(CURRENT_DATE()) AND YEAR(updated_at) = YEAR(CURRENT_DATE())")->fetchColumn() ?: 0;
    
    // KPI: Rejected this month
    $rejectedCount = $pdo->query("SELECT COUNT(*) FROM loan_applications WHERE status = 'REJECTED' AND MONTH(updated_at) = MONTH(CURRENT_DATE()) AND YEAR(updated_at) = YEAR(CURRENT_DATE())")->fetchColumn() ?: 0;

    // KPI: Total Disbursed (From active loans)
    $totalDisbursed = $pdo->query("SELECT SUM(loan_amount) FROM loans WHERE status = 'ACTIVE'")->fetchColumn() ?: 0;

    // Fetch Priority Pending Applications
    $stmt = $pdo->query("
        SELECT la.*, u.fullname 
        FROM loan_applications la 
        JOIN users u ON la.user_id = u.id 
        WHERE la.status = 'VERIFIED' 
        ORDER BY la.created_at ASC LIMIT 5
    ");
    $priorityApps = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>
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
            <p>Welcome back. You have <strong><?php echo $pendingCount; ?> applications</strong> pending your decision today.</p>
        </div>

        <div class="analytics-grid">
            <div class="stat-card card-purple">
                <div class="stat-header"><div class="stat-icon"><i class="bi bi-hourglass-split"></i></div></div>
                <div class="stat-value"><?php echo $pendingCount; ?></div>
                <div class="stat-label">Pending Approval</div>
                <div class="stat-trend trend-up"><i class="bi bi-clock-history"></i> Current Queue</div>
            </div>

            <div class="stat-card card-green">
                <div class="stat-header"><div class="stat-icon"><i class="bi bi-check-lg"></i></div></div>
                <div class="stat-value"><?php echo $approvedCount; ?></div>
                <div class="stat-label">Approved (This Month)</div>
                <div class="stat-trend trend-up"><i class="bi bi-graph-up-arrow"></i> System Wide</div>
            </div>

            <div class="stat-card card-red">
                <div class="stat-header"><div class="stat-icon"><i class="bi bi-x-lg"></i></div></div>
                <div class="stat-value"><?php echo $rejectedCount; ?></div>
                <div class="stat-label">Rejected (This Month)</div>
                <div class="stat-trend trend-down"><i class="bi bi-shield-lock"></i> Protected Capital</div>
            </div>

            <div class="stat-card card-blue">
                <div class="stat-header"><div class="stat-icon"><i class="bi bi-wallet2"></i></div></div>
                <div class="stat-value">₱<?php echo number_format($totalDisbursed / 1000000, 2); ?>M</div>
                <div class="stat-label">Total Disbursed</div>
                <div class="stat-trend"><span style="color:#94a3b8;">Active Portfolio</span></div>
            </div>
        </div>

        <div class="section-title" style="display:flex; justify-content:space-between; align-items:center; margin-bottom: 20px;">
            <span style="font-size:18px; color:#fff; font-weight:700;">Priority Applications (Waiting for Decision)</span>
            <a href="approvals.php" class="view-all" style="color:#60a5fa; text-decoration:none;">View All Queue <i class="bi bi-arrow-right"></i></a>
        </div>

        <div class="content-card">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #94a3b8; border-bottom: 1px solid #334155;">
                        <th style="padding: 15px;">App ID</th>
                        <th>Borrower</th>
                        <th>Loan Amount</th>
                        <th>LSA Verification</th>
                        <th>Risk Assessment</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($priorityApps)): ?>
                        <tr><td colspan="6" style="text-align:center; padding:20px; color:#94a3b8;">No pending applications.</td></tr>
                    <?php else: ?>
                        <?php foreach($priorityApps as $app): 
                            // Initials
                            $words = explode(" ", $app['fullname']);
                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                            
                            // Dynamic Risk Logic (Debt to Income Ratio)
                            $income = $app['estimated_monthly_income'] ?: 1;
                            $monthly_due = $app['monthly_due'] ?: (($app['principal_amount'] * 1.035) / $app['term_months']);
                            $dti = ($monthly_due / $income) * 100;
                            
                            if ($dti < 20) { $risk = 'Low Risk'; $risk_class = 'risk-low'; $risk_color = '#10b981; background: rgba(16, 185, 129, 0.1);'; }
                            elseif ($dti < 40) { $risk = 'Medium Risk'; $risk_class = 'risk-med'; $risk_color = '#fbbf24; background: rgba(245, 158, 11, 0.1);'; }
                            else { $risk = 'High Risk'; $risk_class = 'risk-high'; $risk_color = '#f87171; background: rgba(239, 68, 68, 0.1);'; }
                        ?>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="color:#a78bfa; font-weight:700; padding: 15px;">#LA-<?php echo $app['id']; ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap: 10px;">
                                    <div style="background:#334155; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; color:#fff;"><?php echo $initials; ?></div>
                                    <span style="color:#cbd5e1;"><?php echo htmlspecialchars($app['fullname']); ?></span>
                                </div>
                            </td>
                            <td style="color:#e2e8f0;">₱<?php echo number_format($app['principal_amount'], 2); ?></td>
                            <td style="color:#cbd5e1;">
                                <i class="bi bi-check-circle-fill" style="color:#10b981; margin-right:5px;"></i> Verified
                            </td>
                            <td><span style="padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; color: <?php echo $risk_color; ?>"><?php echo $risk; ?></span></td>
                            <td style="text-align:center;">
                                <a href="review_loan.php?id=<?php echo $app['id']; ?>" style="color:#a78bfa; border:1px solid #a78bfa; padding: 6px 12px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:bold;">
                                    Decide <i class="bi bi-arrow-right-short"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>