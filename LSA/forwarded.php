<?php 
// Standard database include
include 'includes/db_connect.php'; 

/** * FETCH FORWARDED APPLICATIONS
 * Kinukuha natin ang mga 'APPROVED' status.
 */
$query = "SELECT la.id, la.principal_amount, la.updated_at, la.status, u.fullname 
          FROM loan_applications la 
          JOIN users u ON la.user_id = u.id 
          WHERE la.status = 'APPROVED' 
          ORDER BY la.updated_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Forwarded Applications</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Forwarded.css">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Approved & Forwarded</h1>
            <p>Applications verified by LSA, currently waiting for Loan Officer approval.</p>
        </div>

        <div class="filter-bar" style="justify-content: flex-end; display: flex; margin-bottom: 20px;">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="forwardSearch" class="search-input" placeholder="Search approved clients...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Client Name</th>
                        <th>Loan Amount</th>
                        <th>Date Forwarded</th>
                        <th>Current Status</th>
                        </tr>
                </thead>
                <tbody id="forwardedTable">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="color:#a78bfa; font-weight:700;">#LA-<?php echo $row['id']; ?></td>
                            <td>
                                <div class="client-info">
                                    <div class="mini-avatar" style="background:#a78bfa; color:white;">
                                        <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                    </div>
                                    <span class="client-name"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($row['principal_amount'], 2); ?></td>
                            <td><?php echo date('M d, Y', strtotime($row['updated_at'])); ?></td>
                            <td>
                                <span class="badge bg-purple" style="background: rgba(139, 92, 246, 0.1); color: #a78bfa; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 700;">
                                    Verified & Forwarded
                                </span>
                            </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 30px; color: #94a3b8;">No forwarded applications found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('forwardSearch').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.getElementById('forwardedTable').getElementsByTagName('tr');
            for (let row of rows) {
                const name = row.querySelector('.client-name');
                if (name) {
                    const text = name.textContent || name.innerText;
                    row.style.display = text.toLowerCase().includes(filter) ? "" : "none";
                }
            }
        });
    </script>
</body>
</html>