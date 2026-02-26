<?php 
include 'includes/db_connect.php'; 

/** * FETCH RESTRUCTURE REQUESTS
 * Queries the database for applications with restructure keywords.
 */
$query = "SELECT la.id, la.principal_amount, la.loan_purpose, la.updated_at, u.fullname 
          FROM loan_applications la 
          JOIN users u ON la.user_id = u.id 
          WHERE (la.loan_purpose LIKE '%Restructure%' OR la.loan_purpose LIKE '%Extension%')
          AND la.status = 'PENDING'
          ORDER BY la.updated_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Restructure Requests</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Restructure.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Restructure Requests</h1>
            <p>Review requests to modify existing loan terms due to financial hardship.</p>
        </div>

        <div class="filter-bar" style="justify-content: flex-end; display: flex; margin-bottom: 20px;">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="restructureSearch" class="search-input" placeholder="Search client name...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Client Name</th>
                        <th>Reason for Request</th>
                        <th>Date Filed</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody id="restructureTable">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="color:#fbbf24; font-weight:700;">#RQ-<?php echo $row['id']; ?></td>
                            <td>
                                <div class="client-info">
                                    <div class="mini-avatar" style="background:#fbbf24; color:#451a03;">
                                        <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                    </div>
                                    <span class="client-name"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                </div>
                            </td>
                            <td><span style="font-size:13px; color:#cbd5e1;"><?php echo htmlspecialchars($row['loan_purpose']); ?></span></td>
                            <td><?php echo date('M d, Y', strtotime($row['updated_at'])); ?></td>
                            <td><span class="badge bg-amber">New Request</span></td>
                            <td style="text-align:center;">
                                <button class="btn-assess" onclick="openModal('<?php echo addslashes($row['fullname']); ?>', '<?php echo $row['id']; ?>')">
                                    Assess <i class="bi bi-clipboard-check"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px; color: #94a3b8;">No pending restructure requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('restructureSearch').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.getElementById('restructureTable').getElementsByTagName('tr');
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