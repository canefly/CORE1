<?php
require_once __DIR__ . "/includes/db_connect.php";

        // fetch pending restructure requests
        $query = "
        SELECT
            rr.*,
            u.fullname
        FROM loan_restructure_requests rr
        JOIN users u ON rr.user_id = u.id
        WHERE rr.status = 'PENDING'
        ORDER BY rr.created_at ASC
        ";

        $result = $conn->query($query);

        if (!$result) {
                    die("Query Failed: " . $conn->error);
                }

                $pendingRequests = [];

                while ($row = $result->fetch_assoc()) {
                    $pendingRequests[] = $row;
                }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Restructure Requests</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Restructure.css?v=<?php echo time(); ?>">
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Pending Restructure Requests</h1>
            <p>Review restructuring requests and verify submitted requirements before forwarding to Loan Officer.</p>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <button class="btn-filter active">All Queue (<?php echo count($pendingRequests); ?>)</button>
            </div>
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search client..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="content-card">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Client</th>
                        <th>Loan ID</th>
                        <th>Type</th>
                        <th>Outstanding</th>
                        <th>Requested Term</th>
                        <th>Date Filed</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($pendingRequests)): ?>
                        <tr>
                            <td colspan="8" class="empty-row">Queue is empty.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($pendingRequests as $req): ?>
                            <?php
                                $words = explode(" ", trim($req['fullname']));
                                $initials = strtoupper(
                                    substr($words[0] ?? '', 0, 1) .
                                    (isset($words[1]) ? substr($words[1], 0, 1) : '')
                                );
                            ?>
                            <tr class="data-row">
                                <td class="req-id">#RR-<?php echo (int)$req['id']; ?></td>
                                <td>
                                    <div class="client-info">
                                        <div class="mini-avatar"><?php echo htmlspecialchars($initials); ?></div>
                                        <span class="client-name"><?php echo htmlspecialchars($req['fullname']); ?></span>
                                    </div>
                                </td>
                                <td>LN-<?php echo str_pad((int)$req['loan_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <span class="type-badge"><?php echo htmlspecialchars($req['restructure_type']); ?></span>
                                </td>
                                <td class="money">₱<?php echo number_format((float)$req['outstanding_snapshot'], 2); ?></td>
                                <td><?php echo (int)$req['requested_term_months']; ?> MOS</td>
                                <td><?php echo date("M d, Y", strtotime($req['created_at'])); ?></td>
                                <td style="text-align:center;">
                                    <a href="review_restructure.php?id=<?php echo (int)$req['id']; ?>" class="btn-review">
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

    <script src="assets/js/Restructure.js?v=<?php echo time(); ?>"></script>
</body>
</html>