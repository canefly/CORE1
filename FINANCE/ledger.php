<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';

if (file_exists($connection_file)) {
    require_once $connection_file;
} else {
    die("Error: Connection file not found at " . $connection_file);
}

if (!isset($pdo)) {
    die("Fatal Error: \$pdo variable is not defined in includes/db_connect.php.");
}

// 2. Fetch All Loans with User Data and Application Data
try {
    // We calculate total interest as a fallback in case it's missing from the application table
    $query = "
        SELECT 
            l.id as loan_id,
            u.fullname,
            l.start_date,
            l.loan_amount as principal,
            l.outstanding,
            l.status,
            COALESCE(la.total_interest, (l.loan_amount * (l.interest_rate / 100) * l.term_months)) as total_interest
        FROM loans l
        LEFT JOIN users u ON l.user_id = u.id
        LEFT JOIN loan_applications la ON l.application_id = la.id
        ORDER BY l.id DESC
    ";
    
    $stmt = $pdo->query($query);
    $loans = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Master Ledger</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/ledger.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Loan Ledger</h1>
                <p>Complete record of all active and closed loan accounts.</p>
            </div>
        </div>

        <div class="toolbar">
            <div class="filter-group">
                <div class="search-box">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" id="searchInput" class="search-input" placeholder="Search client, Loan ID...">
                </div>
                
                <select class="select-filter" id="statusFilter">
                    <option value="all">All Status</option>
                    <option value="ACTIVE">Active</option>
                    <option value="PAID">Fully Paid</option>
                    <option value="OVERDUE">Overdue</option>
                </select>
            </div>

            <button class="btn-export" onclick="window.print()">
                <i class="bi bi-download"></i> Export / Print
            </button>
        </div>

        <div class="ledger-container">
            <div class="table-wrapper">
                <table id="ledgerTable">
                    <thead>
                        <tr>
                            <th>Loan ID</th>
                            <th>Client Name</th>
                            <th>Release Date</th>
                            <th class="text-right">Principal</th>
                            <th class="text-right">Total Interest</th>
                            <th class="text-right">Total Paid</th>
                            <th class="text-right">Balance</th>
                            <th>Progress</th>
                            <th>Status</th>
                            <th style="text-align:center;">View</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($loans)): ?>
                            <tr><td colspan="10" style="text-align:center; padding: 20px;">No loan records found.</td></tr>
                        <?php else: ?>
                            <?php foreach ($loans as $row): 
                                // Math logic for the row
                                $principal = $row['principal'];
                                $interest = $row['total_interest'];
                                $total_payable = $principal + $interest;
                                $balance = $row['outstanding'];
                                
                                // Total Paid = Total Payable minus current Balance
                                $total_paid = $total_payable - $balance;
                                if ($total_paid < 0) $total_paid = 0; // Prevent negative values
                                
                                // Progress Percentage
                                $progress = ($total_payable > 0) ? round(($total_paid / $total_payable) * 100) : 0;
                                if ($progress > 100) $progress = 100;
                                
                                // --- AUTOMATIC PAID STATUS FIX ---
                                $displayStatus = $row['status'];
                                if ($balance <= 0) {
                                    $displayStatus = 'PAID';
                                }

                                // Determine progress bar color based on status/progress
                                $progressColor = '#60a5fa'; // Blue default
                                if ($progress >= 100) $progressColor = '#10b981'; // Green if paid
                                if ($displayStatus == 'OVERDUE') $progressColor = '#f87171'; // Red if overdue
                                
                                // Format Status Badge CSS Class
                                $statusClass = 'status-active';
                                if ($displayStatus == 'CLOSED' || $displayStatus == 'PAID') $statusClass = 'status-paid';
                                if ($displayStatus == 'OVERDUE') $statusClass = 'status-overdue';
                            ?>
                            <tr class="ledger-row" data-status="<?php echo strtoupper($displayStatus); ?>">
                                <td style="color:#fbbf24; font-weight:700;">#LN-<?php echo str_pad($row['loan_id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td class="client-name"><?php echo htmlspecialchars($row['fullname'] ?? 'N/A'); ?></td>
                                <td><?php echo $row['start_date'] ? date("M d, Y", strtotime($row['start_date'])) : 'Pending'; ?></td>
                                <td class="text-right font-mono"><?php echo number_format($principal, 2); ?></td>
                                <td class="text-right font-mono"><?php echo number_format($interest, 2); ?></td>
                                <td class="text-right font-mono" style="color:#34d399; font-weight: bold;"><?php echo number_format($total_paid, 2); ?></td>
                                <td class="text-right font-mono" style="color:#fff; font-weight: bold;"><?php echo number_format($balance, 2); ?></td>
                                <td>
                                    <span class="progress-text"><?php echo $progress; ?>% Paid</span>
                                    <div class="progress-wrapper">
                                        <div class="progress-fill" style="width: <?php echo $progress; ?>%; background:<?php echo $progressColor; ?>;"></div>
                                    </div>
                                </td>
                                <td><span class="status-badge <?php echo $statusClass; ?>"><?php echo htmlspecialchars($displayStatus); ?></span></td>
                                <td style="text-align:center;">
                                    <a href="view_loan.php?id=<?php echo $row['loan_id']; ?>" class="btn-view" style="color: #fbbf24; border: 1px solid #fbbf24; padding: 4px 10px; border-radius: 4px; text-decoration: none; font-size: 11px; font-weight: bold; transition: 0.2s;">OPEN</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const searchInput = document.getElementById('searchInput');
        const statusFilter = document.getElementById('statusFilter');
        const rows = document.querySelectorAll('.ledger-row');

        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase();
            const statusValue = statusFilter.value.toUpperCase();

            rows.forEach(row => {
                const textContent = row.textContent.toLowerCase();
                const rowStatus = row.getAttribute('data-status');
                
                const matchesSearch = textContent.includes(searchTerm);
                const matchesStatus = (statusValue === 'ALL' || rowStatus === statusValue);

                if (matchesSearch && matchesStatus) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        searchInput.addEventListener('keyup', filterTable);
        statusFilter.addEventListener('change', filterTable);
    </script>

</body>
</html>