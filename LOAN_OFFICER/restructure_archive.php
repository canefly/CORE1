<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';
if (file_exists($connection_file)) { require_once $connection_file; } 
else { die("Error: Connection file not found."); }

try {
    // ARCHIVE QUERY
    $stmt = $pdo->query("
        SELECT r.*, l.application_id, COALESCE(u.fullname, 'Unknown') as fullname 
        FROM loan_restructure_requests r
        LEFT JOIN loans l ON r.loan_id = l.id
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.status IN ('APPROVED', 'REJECTED', 'CANCELLED')
        ORDER BY r.updated_at DESC
    ");
    $restructureRequests = $stmt->fetchAll();
    $total_archived = count($restructureRequests);
} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Officer | Restructure Archive</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/restructure_archive.css?v=<?php echo time(); ?>">
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
            <h1>Restructure Requests Archive</h1>
            <p>History of all approved, rejected, and cancelled restructuring applications.</p>
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
                <input type="text" id="searchInput" class="search-input" placeholder="Search by name..." onkeyup="filterTableSearch()">
            </div>
        </div>

        <div class="content-card">
            <table id="dataTable" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="text-align: left; color: #94a3b8; border-bottom: 1px solid #334155; font-size: 12px; text-transform: uppercase;">
                        <th style="padding: 15px;">App ID</th>
                        <th>Borrower</th>
                        <th>Type & Reason</th>
                        <th>Adjustments</th>
                        <th>Date Processed</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($restructureRequests)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px; color:#94a3b8;">No archived restructure requests.</td></tr>
                    <?php else: ?>
                        <?php foreach($restructureRequests as $req): 
                            $words = explode(" ", $req['fullname']);
                            $initials = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : ''));
                            
                            $badge_class = "bg-gray";
                            if ($req['status'] == 'APPROVED') { $badge_class = "bg-green"; }
                            if ($req['status'] == 'REJECTED') { $badge_class = "bg-red"; }
                        ?>
                        <tr class="data-row" style="border-bottom: 1px solid #334155;">
                            <td style="color:#fbbf24; font-weight:700; padding: 15px;">#LA-<?php echo str_pad($req['application_id'] ?? $req['loan_id'], 4, '0', STR_PAD_LEFT); ?></td>
                            <td>
                                <div style="display:flex; align-items:center; gap: 10px;">
                                    <div style="background:var(--border-color, #334155); width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:12px; font-weight:bold; color:var(--text-primary);"><?php echo $initials; ?></div>
                                    <span class="client-name" style="color:var(--text-primary); font-weight:bold;"><?php echo htmlspecialchars($req['fullname'] ?? 'Unknown Client'); ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="font-size:12px; color:var(--text-primary); font-weight: bold;"><?php echo htmlspecialchars($req['restructure_type']); ?></div>
                                <div style="font-size:11px; color:var(--text-tertiary);"><?php echo htmlspecialchars(substr($req['reason'], 0, 25)) . '...'; ?></div>
                            </td>
                            <td>
                                <div style="display: flex; flex-direction: column; gap: 6px;">
                                    <div class="comparison-box">
                                        <span class="val-old"><?php echo $req['current_term_months']; ?>m</span>
                                        <i class="bi bi-arrow-right arrow-icon"></i>
                                        <span class="val-new"><?php echo $req['requested_term_months']; ?>m</span>
                                    </div>
                                </div>
                            </td>
                            <td style="color:#94a3b8; font-size: 13px;"><?php echo date("M d, Y", strtotime($req['updated_at'])); ?></td>
                            <td>
                                <span class="badge <?php echo $badge_class; ?>"><?php echo htmlspecialchars($req['status']); ?></span>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn-assess view-btn"
                                    data-id="#RR-<?php echo str_pad($req['id'], 3, '0', STR_PAD_LEFT); ?>"
                                    data-client="<?php echo htmlspecialchars($req['fullname'] ?? 'Unknown Client'); ?>"
                                    data-loan="LN-<?php echo str_pad($req['loan_id'], 4, '0', STR_PAD_LEFT); ?>"
                                    data-type="<?php echo htmlspecialchars($req['restructure_type']); ?>"
                                    data-reason="<?php echo htmlspecialchars($req['reason']); ?>"
                                    data-status="<?php echo htmlspecialchars($req['status']); ?>"
                                    data-date="<?php echo date('M d, Y', strtotime($req['updated_at'])); ?>"
                                    data-oldterm="<?php echo $req['current_term_months']; ?>m"
                                    data-newterm="<?php echo $req['requested_term_months']; ?>m"
                                    data-olddue="₱<?php echo number_format($req['current_monthly_due'], 2); ?>"
                                    data-newdue="₱<?php echo number_format($req['estimated_monthly_due'], 2); ?>">
                                    <i class="bi bi-eye"></i> View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="myViewModal" class="custom-modal">
        <div class="modal-box">
            <div class="modal-header">
                <h2 style="font-size: 18px; margin: 0; color: var(--text-primary);">Request Details</h2>
                <button class="close-modal-btn"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body">
                <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                    <div>
                        <span style="font-size: 11px; color: var(--text-tertiary);">Request ID</span>
                        <div id="mId" style="font-weight: 700; color: var(--text-primary);"></div>
                    </div>
                    <div style="text-align: right;">
                        <span style="font-size: 11px; color: var(--text-tertiary);">Status</span>
                        <div id="mStatus" class="badge" style="margin-top: 4px; font-weight: bold;"></div>
                    </div>
                </div>
                <div class="detail-group"><label>Client Name:</label><div id="mClient" class="detail-val" style="font-weight: bold; color: var(--text-primary);"></div></div>
                <div class="detail-group"><label>Loan ID:</label><div id="mLoan" class="detail-val"></div></div>
                <div class="detail-group"><label>Restructure Type:</label><div id="mType" class="detail-val" style="color: #f59e0b; font-weight: 700;"></div></div>
                <div class="detail-group"><label>Reason:</label><div id="mReason" class="detail-val" style="background: var(--surface-hover); padding: 10px; border-radius: 6px; font-style: italic;"></div></div>

                <h4 style="font-size: 13px; margin: 20px 0 10px; color: var(--text-primary); text-transform: uppercase;">Adjustments</h4>
                <div style="display: flex; gap: 15px;">
                    <div class="comparison-box" style="flex: 1;">
                        <span style="font-size: 11px; color: var(--text-tertiary);">Term:</span>
                        <span id="mOldTerm" class="val-old"></span>
                        <i class="bi bi-arrow-right arrow-icon"></i>
                        <span id="mNewTerm" class="val-new"></span>
                    </div>
                    <div class="comparison-box" style="flex: 1;">
                        <span style="font-size: 11px; color: var(--text-tertiary);">Due:</span>
                        <span id="mOldDue" class="val-old"></span>
                        <i class="bi bi-arrow-right arrow-icon"></i>
                        <span id="mNewDue" class="val-new"></span>
                    </div>
                </div>
                <div style="margin-top: 20px; font-size: 11px; text-align: right; color: var(--text-tertiary);">Processed on: <span id="mDate" style="font-weight: bold; color: var(--text-secondary);"></span></div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const modal = document.getElementById('myViewModal');

            // EVENT DELEGATION: Binabantayan niya yung buong document para kahit anong mangyari, mababasa yung click.
            document.body.addEventListener('click', function(e) {
                
                // 1. Kapag View Button ang pinindot
                const viewBtn = e.target.closest('.view-btn');
                if (viewBtn) {
                    e.preventDefault();
                    const data = viewBtn.dataset;

                    document.getElementById('mId').innerText = data.id;
                    document.getElementById('mClient').innerText = data.client;
                    document.getElementById('mLoan').innerText = data.loan;
                    document.getElementById('mType').innerText = data.type;
                    document.getElementById('mReason').innerText = data.reason;
                    document.getElementById('mDate').innerText = data.date;
                    document.getElementById('mOldTerm').innerText = data.oldterm;
                    document.getElementById('mNewTerm').innerText = data.newterm;
                    document.getElementById('mOldDue').innerText = data.olddue;
                    document.getElementById('mNewDue').innerText = data.newdue;
                    
                    let statEl = document.getElementById('mStatus');
                    statEl.innerText = data.status;
                    statEl.className = 'badge'; 
                    if(data.status === 'APPROVED') statEl.classList.add('bg-green');
                    else if(data.status === 'REJECTED') statEl.classList.add('bg-red');
                    else statEl.classList.add('bg-gray');

                    modal.style.display = 'flex';
                }

                // 2. Kapag Close Button ang pinindot
                if (e.target.closest('.close-modal-btn')) {
                    modal.style.display = 'none';
                }

                // 3. Kapag sa labas ng modal pinindot
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // FILTER FUNCTIONS
        function filterTableSearch() {
            let input = document.getElementById('searchInput').value.toLowerCase();
            let rows = document.querySelectorAll('.data-row');
            rows.forEach(row => {
                let text = row.querySelector('.client-name').innerText.toLowerCase();
                row.style.display = text.includes(input) ? '' : 'none';
            });
        }

        function filterByStatus(status, btnElement) {
            document.querySelectorAll('.btn-filter').forEach(b => b.classList.remove('active'));
            btnElement.classList.add('active');

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