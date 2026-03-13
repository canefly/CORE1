<?php 
// Standard database include from your LSA/includes folder
include 'includes/db_connect.php'; 

/** * FETCH RETURNED APPLICATIONS
 * We query for 'REJECTED' status to see what needs client attention.
 * We join with 'users' to get the client's full name.
 */
$query = "SELECT la.id, la.updated_at, la.remarks, u.fullname 
          FROM loan_applications la 
          JOIN users u ON la.user_id = u.id 
          WHERE la.status = 'REJECTED' 
          ORDER BY la.updated_at DESC";
$result = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Returned Applications</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Returned.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Returned Applications</h1>
            <p>Applications waiting for client re-submission or document fixes.</p>
        </div>

        <div class="top-bar">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="returnSearch" class="search-input" placeholder="Search client name...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Client Name</th>
                        <th>Date Returned</th>
                        <th>Rejection Reason</th>
                        <th>Status</th>
                        <th style="text-align:center;">History</th>
                    </tr>
                </thead>
                <tbody id="returnedTable">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="color:#ef4444; font-weight:700;">#LA-<?php echo $row['id']; ?></td>
                            <td>
                                <div class="client-info">
                                    <div class="mini-avatar" style="background:#ef4444; color:white;">
                                        <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                    </div>
                                    <span class="client-name"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($row['updated_at'])); ?></td>
                            <td>
                                <span class="reason-text"><?php echo htmlspecialchars($row['remarks']); ?></span>
                            </td>
                            <td><span class="badge-returned">Waiting for Client</span></td>
                            <td style="text-align:center;">
                                <a href="view_details.php?id=<?php echo $row['id']; ?>" class="btn-view" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center; padding: 30px; color: #94a3b8;">No returned applications found in the system.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        /**
         * LIVE SEARCH FOR RETURNED LIST
         * Filters the table rows instantly as the LSA types.
         */
        document.getElementById('returnSearch').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.getElementById('returnedTable').getElementsByTagName('tr');

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