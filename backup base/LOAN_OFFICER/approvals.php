<?php
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';
if (file_exists($connection_file)) { require_once $connection_file; } 
else { die("Error: Connection file not found."); }

try {
    $stmt = $pdo->query("
        SELECT la.*, u.fullname 
        FROM loan_applications la 
        JOIN users u ON la.user_id = u.id 
        WHERE la.status = 'VERIFIED' 
        ORDER BY la.created_at ASC
    ");
    $pendingApps = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>
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
                <button class="btn-filter active">All Queue (<?php echo count($pendingApps); ?>)</button>
            </div>
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search borrower..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="content-card">
            <table id="dataTable" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #94a3b8; border-bottom: 1px solid #334155;">
                        <th style="padding: 15px;">App ID</th>
                        <th>Borrower Name</th>
                        <th>Loan Amount</th>
                        <th>Term</th>
                        <th>Risk Level</th>
                        <th>Applied Date</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($pendingApps)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">Queue is empty.</td></tr>
                    <?php else: ?>
                        <?php foreach($pendingApps as $app): 
                            $words = explode(" ", $app['fullname']);
                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                            
                            $income = $app['estimated_monthly_income'] ?: 1;
                            $monthly_due = $app['monthly_due'] ?: (($app['principal_amount'] * 1.035) / $app['term_months']);
                            $dti = ($monthly_due / $income) * 100;
                            
                            if ($dti < 20) { $risk = 'Low Risk'; $risk_color = '#10b981; background: rgba(16, 185, 129, 0.1);'; }
                            elseif ($dti < 40) { $risk = 'Medium Risk'; $risk_color = '#fbbf24; background: rgba(245, 158, 11, 0.1);'; }
                            else { $risk = 'High Risk'; $risk_color = '#f87171; background: rgba(239, 68, 68, 0.1);'; }
                        ?>
                        <tr class="data-row" style="border-bottom: 1px solid #334155;">
                            <td style="color:#a78bfa; font-weight:700; padding: 15px;">#LA-<?php echo $app['id']; ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap: 10px;">
                                    <div style="background:#334155; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; color:#fff;"><?php echo $initials; ?></div>
                                    <span class="client-name" style="color:#cbd5e1;"><?php echo htmlspecialchars($app['fullname']); ?></span>
                                </div>
                            </td>
                            <td style="color:#e2e8f0; font-weight: bold;">₱<?php echo number_format($app['principal_amount'], 2); ?></td>
                            <td style="color:#cbd5e1;"><span style="background: rgba(148, 163, 184, 0.1); padding: 4px 8px; border-radius: 4px; font-size: 11px;"><?php echo $app['term_months']; ?> MOS</span></td>
                            <td><span style="padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold; color: <?php echo $risk_color; ?>"><?php echo $risk; ?></span></td>
                            <td style="color:#94a3b8;"><?php echo date("M d, Y", strtotime($app['created_at'])); ?></td>
                            <td style="text-align:center;">
                                <a href="review_loan.php?id=<?php echo $app['id']; ?>" style="color:#a78bfa; border:1px solid #a78bfa; padding: 6px 12px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:bold;">
                                    Review <i class="bi bi-arrow-right-short"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        function filterTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll(".data-row");
            rows.forEach(row => {
                let name = row.querySelector(".client-name").textContent.toLowerCase();
                row.style.display = name.includes(input) ? "" : "none";
            });
        }
    </script>
</body>
</html>