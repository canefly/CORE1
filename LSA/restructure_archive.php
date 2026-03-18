<?php
// Core 1 - Restructure Archive (PDO Version)
require_once __DIR__ . "/includes/db_connect.php";

$archive_query = "
    SELECT r.*, u.fullname 
    FROM loan_restructure_requests r
    LEFT JOIN users u ON r.user_id = u.id
    WHERE r.status IN ('APPROVED', 'REJECTED', 'CANCELLED')
    ORDER BY r.updated_at DESC
";

$stmt = $pdo->query($archive_query);
$archived_requests = $stmt->fetchAll();
$total_archived = count($archived_requests);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Restructure Archive</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Restructure.css?v=<?php echo time(); ?>">
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
    
    <style>
        /* SINIGURADO KONG NANDITO NA YUNG MODAL CSS PARA LILITAW TALAGA */
        .custom-modal {
            display: none; 
            position: fixed; z-index: 9999; left: 0; top: 0;
            width: 100%; height: 100%;
            background-color: rgba(0, 0, 0, 0.7);
            align-items: center; justify-content: center;
        }
        .modal-box {
            background-color: var(--surface, #1e293b);
            border: 1px solid var(--border-color, #334155);
            border-radius: 12px; width: 90%; max-width: 500px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            color: var(--text-primary, #fff);
        }
        .modal-header { padding: 20px; border-bottom: 1px solid var(--border-color, #334155); display: flex; justify-content: space-between; }
        .modal-body { padding: 20px; }
        .detail-group { margin-bottom: 12px; font-size: 13px; }
        .detail-group label { color: var(--text-tertiary, #94a3b8); display: block; margin-bottom: 4px; }
        .detail-val { color: var(--text-secondary, #e2e8f0); }
        .close-modal-btn { background: none; border: none; color: #ef4444; font-size: 20px; cursor: pointer; }
    </style>
</head>
<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Restructure Request Archive</h1>
            <p>History of all processed loan restructure applications.</p>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <button class="btn-filter active" onclick="filterByStatus('all', this)">Archived (<?php echo $total_archived; ?>)</button>
                <button class="btn-filter" onclick="filterByStatus('approved', this)">Approved</button>
                <button class="btn-filter" onclick="filterByStatus('rejected', this)">Rejected</button>
                <button class="btn-filter" onclick="filterByStatus('cancelled', this)">Cancelled</button>
            </div>
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" id="searchInput" placeholder="Search Loan ID or Name..." onkeyup="filterTableSearch()">
            </div>
        </div>

        <div class="content-card">
            <table id="dataTable">
                <thead>
                    <tr>
                        <th>Req ID</th>
                        <th>Client Info</th>
                        <th>Type & Reason</th>
                        <th>Adjustments</th>
                        <th>Date Processed</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($total_archived > 0): ?>
                        <?php foreach ($archived_requests as $row): ?>
                            <?php 
                                $badge_class = "bg-gray";
                                if ($row['status'] == 'APPROVED') { $badge_class = "bg-green"; }
                                if ($row['status'] == 'REJECTED') { $badge_class = "bg-red"; }
                                
                                $words = explode(" ", trim($row['fullname'] ?? 'Unknown'));
                                $initials = strtoupper(substr($words[0] ?? '', 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                                
                                // Clean outputs
                                $safe_reason = htmlspecialchars($row['reason'], ENT_QUOTES);
                                $safe_client = htmlspecialchars($row['fullname'] ?? 'Unknown Client', ENT_QUOTES);
                            ?>
                            <tr class="data-row">
                                <td><strong style="color: var(--text-primary);">#RR-<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?></strong></td>
                                
                                <td>
                                    <div class="client-info">
                                        <div class="mini-avatar"><?php echo htmlspecialchars($initials); ?></div>
                                        <div>
                                            <div class="client-name" style="font-weight: 700; color: var(--text-primary); margin-bottom: 2px;">
                                                <?php echo $safe_client; ?>
                                            </div>
                                            <div style="font-size: 11px; color: var(--text-tertiary);">Loan ID: #<?php echo $row['loan_id']; ?></div>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div style="font-weight: 700; color: var(--text-primary); margin-bottom: 2px;"><?php echo htmlspecialchars($row['restructure_type']); ?></div>
                                    <div style="font-size: 11px; color: var(--text-tertiary);"><?php echo substr($safe_reason, 0, 25) . '...'; ?></div>
                                </td>

                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 6px;">
                                        <div class="comparison-box">
                                            <span class="val-old"><?php echo $row['current_term_months']; ?>m</span>
                                            <i class="bi bi-arrow-right arrow-icon"></i>
                                            <span class="val-new"><?php echo $row['requested_term_months']; ?>m</span>
                                        </div>
                                    </div>
                                </td>

                                <td><div style="color: var(--text-secondary); font-size: 13px;"><?php echo date('M d, Y', strtotime($row['updated_at'])); ?></div></td>
                                
                                <td><span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>

                                <td>
                                    <button type="button" class="btn-assess" 
                                        onclick="openMyModal(
                                            '#RR-<?php echo str_pad($row['id'], 3, '0', STR_PAD_LEFT); ?>',
                                            '<?php echo $safe_client; ?>',
                                            'LN-<?php echo str_pad($row['loan_id'], 4, '0', STR_PAD_LEFT); ?>',
                                            '<?php echo htmlspecialchars($row['restructure_type']); ?>',
                                            '<?php echo $safe_reason; ?>',
                                            '<?php echo $row['status']; ?>',
                                            '<?php echo date('M d, Y', strtotime($row['updated_at'])); ?>',
                                            '<?php echo $row['current_term_months']; ?>m',
                                            '<?php echo $row['requested_term_months']; ?>m',
                                            '₱<?php echo number_format($row['current_monthly_due'], 2); ?>',
                                            '₱<?php echo number_format($row['estimated_monthly_due'], 2); ?>'
                                        )">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 40px;">No archived requests.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="myViewModal" class="custom-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 style="font-size: 18px; margin: 0;">Request Details</h2>
                <button class="close-modal-btn" onclick="closeMyModal()"><i class="bi bi-x-circle-fill"></i></button>
            </div>
            <div class="modal-body">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <div>
                        <span style="font-size: 11px; color: var(--text-tertiary);">Request ID</span>
                        <div id="mId" style="font-weight: 700; color: var(--text-primary);"></div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 11px; color: var(--text-tertiary);">Status</span>
                        <div id="mStatus" style="margin-top: 4px; font-weight: bold;"></div>
                    </div>
                </div>
                <div class="detail-group"><label>Client Name:</label><div id="mClient" class="detail-val" style="font-weight: bold;"></div></div>
                <div class="detail-group"><label>Loan ID:</label><div id="mLoan" class="detail-val"></div></div>
                <div class="detail-group"><label>Restructure Type:</label><div id="mType" class="detail-val" style="color: #f59e0b; font-weight: 700;"></div></div>
                <div class="detail-group"><label>Reason:</label><div id="mReason" class="detail-val" style="background: rgba(0,0,0,0.2); padding: 10px; border-radius: 6px;"></div></div>

                <h4 style="font-size: 13px; margin: 20px 0 10px;">Adjustments</h4>
                <div style="display: flex; gap: 15px;">
                    <div class="comparison-box" style="flex: 1;">
                        <span style="font-size: 11px;">Term:</span>
                        <span id="mOldTerm" style="text-decoration: line-through; color: #94a3b8;"></span>
                        <i class="bi bi-arrow-right"></i>
                        <span id="mNewTerm" style="color: #f59e0b; font-weight: bold;"></span>
                    </div>
                    <div class="comparison-box" style="flex: 1;">
                        <span style="font-size: 11px;">Due:</span>
                        <span id="mOldDue" style="text-decoration: line-through; color: #94a3b8;"></span>
                        <i class="bi bi-arrow-right"></i>
                        <span id="mNewDue" style="color: #f59e0b; font-weight: bold;"></span>
                    </div>
                </div>
                <div style="margin-top: 20px; font-size: 11px; text-align: right;">Processed on: <span id="mDate"></span></div>
            </div>
        </div>
    </div>

    <script>
        // 1. OPEN MODAL FUNCTION
        function openMyModal(id, client, loan, type, reason, status, date, oldT, newT, oldD, newD) {
            document.getElementById('mId').innerText = id;
            document.getElementById('mClient').innerText = client;
            document.getElementById('mLoan').innerText = loan;
            document.getElementById('mType').innerText = type;
            document.getElementById('mReason').innerText = reason;
            document.getElementById('mDate').innerText = date;
            document.getElementById('mOldTerm').innerText = oldT;
            document.getElementById('mNewTerm').innerText = newT;
            document.getElementById('mOldDue').innerText = oldD;
            document.getElementById('mNewDue').innerText = newD;
            
            let statEl = document.getElementById('mStatus');
            statEl.innerText = status;
            statEl.style.color = (status === 'APPROVED') ? '#10b981' : (status === 'REJECTED' ? '#ef4444' : '#94a3b8');

            document.getElementById('myViewModal').style.display = 'flex';
        }

        // 2. CLOSE MODAL FUNCTION
        function closeMyModal() {
            document.getElementById('myViewModal').style.display = 'none';
        }

        // 3. SEARCH BOX FUNCTION
        function filterTableSearch() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('.data-row');
            rows.forEach(row => {
                let text = row.innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }

        // 4. STATUS BUTTONS FUNCTION
        function filterByStatus(status, btnElement) {
            // Update button colors
            document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
            btnElement.classList.add('active');

            // Filter rows
            let rows = document.querySelectorAll('.data-row');
            rows.forEach(row => {
                let badge = row.querySelector('.badge').innerText.toLowerCase();
                if (status === 'all' || badge === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html>