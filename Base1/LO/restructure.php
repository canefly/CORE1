<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';
if (file_exists($connection_file)) { require_once $connection_file; } 
else { die("Error: Connection file not found."); }

try {
    // 2. FIXED: Ginamit ang loan_restructure_requests at status na 'VERIFIED'
    $stmt = $pdo->query("
        SELECT r.*, l.application_id, COALESCE(u.fullname, 'Unknown') as fullname 
        FROM loan_restructure_requests r
        LEFT JOIN loans l ON r.loan_id = l.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status = 'VERIFIED'
        ORDER BY r.created_at ASC
    ");
    $restructureRequests = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Restructure Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
        <script>
        (function() {
            const savedTheme = localStorage.getItem("theme");
            // Default to dark if no theme is saved
            if (savedTheme === "dark" || savedTheme === null) {
                document.documentElement.classList.add("dark-mode");
                localStorage.setItem("theme", "dark");
            }
        })();
    </script>
    <link rel="stylesheet" href="assets/css/base-style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Restructure.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Restructure Requests</h1>
            <p>Manage loan modification proposals and negotiations (Verified by LSA).</p>
        </div>

        <div class="filter-bar">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="content-card">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #94a3b8; border-bottom: 1px solid #334155;">
                        <th style="padding: 15px;">App ID</th>
                        <th>Borrower</th>
                        <th>Type</th>
                        <th>Proposed Terms</th>
                        <th>Date Requested</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($restructureRequests)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:20px; color:#94a3b8;">No pending restructure requests.</td></tr>
                    <?php else: ?>
                        <?php foreach($restructureRequests as $req): 
                            $words = explode(" ", $req['fullname']);
                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                        ?>
                        <tr class="data-row" style="border-bottom: 1px solid #334155;">
                            <td style="color:#fbbf24; font-weight:700; padding: 15px;">#LA-<?php echo str_pad($req['application_id'] ?? $req['loan_id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap: 10px;">
                                    <div style="background:#334155; width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; color:#fff;"><?php echo $initials; ?></div>
                                    <span class="client-name" style="color:#cbd5e1;"><?php echo htmlspecialchars($req['fullname']); ?></span>
                                </div>
                            </td>
                            <td><span style="font-size:12px; color:#cbd5e1; font-weight: bold;"><?php echo htmlspecialchars($req['restructure_type']); ?></span></td>
                            <td>
                                <div style="background: rgba(15, 23, 42, 0.5); padding: 6px 10px; border-radius: 6px; display: inline-flex; align-items: center; gap: 10px; border: 1px solid #334155;">
                                    <span style="color: #94a3b8; text-decoration: line-through; font-size: 12px;"><?php echo $req['current_term_months']; ?> MOS</span>
                                    <i class="bi bi-arrow-right" style="color: #60a5fa; font-size: 12px;"></i>
                                    <span style="color: #34d399; font-weight: bold; font-size: 13px;"><?php echo $req['requested_term_months']; ?> MOS</span>
                                </div>
                            </td>
                            <td style="color:#94a3b8;"><?php echo date("M d, Y", strtotime($req['created_at'])); ?></td>
                            <td><span style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: bold;">Verified by LSA</span></td>
                            <td style="text-align:center;">
                                <a href="review_restructure.php?id=<?php echo $req['id']; ?>" style="color:#fbbf24; border:1px solid #fbbf24; padding: 6px 12px; border-radius:6px; text-decoration:none; font-size:12px; font-weight:bold;">
                                    Decide <i class="bi bi-check-circle"></i>
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