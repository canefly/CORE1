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
        WHERE la.status = 'APPROVED' 
        ORDER BY la.updated_at DESC
    ");
    $approvedApps = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Approved Loans</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Approved.css">
    <script>
        (function() {
            const savedTheme = localStorage.getItem("theme");
            if (savedTheme === "dark" || savedTheme === null) {
                document.documentElement.classList.add("dark-mode");
                localStorage.setItem("theme", "dark");
            }
        })();
    </script>
    <link rel="stylesheet" href="assets/css/base-style.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Approved Loans History</h1>
            <p>Archive of all successfully approved loan applications.</p>
        </div>

        <div class="content-card">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #94a3b8; border-bottom: 1px solid #334155;">
                        <th style="padding: 15px;">App ID</th>
                        <th>Borrower</th>
                        <th>Approved Amount</th>
                        <th>Interest</th>
                        <th>Approval Date</th>
                        <th>Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($approvedApps)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">No approved applications found.</td></tr>
                    <?php else: ?>
                        <?php foreach($approvedApps as $app): 
                            $words = explode(" ", $app['fullname']);
                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        ?>
                        <tr style="border-bottom: 1px solid #334155;">
                            <td style="color:#34d399; font-weight:700; padding: 15px;">#LA-<?php echo $app['id']; ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap: 10px;">
                                    <div style="background:#334155; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; color:#fff;"><?php echo $initials; ?></div>
                                    <span style="color:#cbd5e1;"><?php echo htmlspecialchars($app['fullname']); ?></span>
                                </div>
                            </td>
                            <td style="color:#e2e8f0; font-weight: bold;">₱<?php echo number_format($app['principal_amount'], 2); ?></td>
                            <td style="color:#cbd5e1;"><?php echo $app['interest_rate']; ?>%</td>
                            <td style="color:#94a3b8;"><?php echo date("M d, Y", strtotime($app['updated_at'])); ?></td>
                            <td>
                                <span style="background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold;">
                                    <i class="bi bi-check2"></i> Approved
                                </span>
                            </td>
                            <td style="text-align:center; display: flex; justify-content: center; gap: 8px;">
                                <a href="review_loan.php?id=<?php echo $app['id']; ?>" title="View Details" style="color:#cbd5e1; background: #334155; padding: 6px 10px; border-radius:4px; text-decoration: none;"><i class="bi bi-eye"></i></a>
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