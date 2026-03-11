<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';
if (file_exists($connection_file)) { require_once $connection_file; } 
else { die("Error: Connection file not found."); }

// 2. Get Filters from URL
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$quick_filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// 🟢 NEW LOGIC: DEFAULT TO "TODAY" IF NO FILTERS ARE APPLIED
if (empty($start_date) && empty($end_date) && empty($quick_filter)) {
    $quick_filter = 'today';
}

// If a quick filter is active, clear manual date inputs
if ($quick_filter === 'today' || $quick_filter === 'all') {
    $start_date = '';
    $end_date = '';
}

try {
    // 3. The UNION Query
    $query = "
        SELECT * FROM (
            SELECT 
                'COLLECTION' as log_type,
                t.trans_date as tx_date,
                t.receipt_number as ref_no,
                u.fullname as client_name,
                t.loan_id,
                t.provider_method as method,
                t.amount as cash_in,
                0 as cash_out,
                t.status
            FROM transactions t
            JOIN users u ON t.user_id = u.id
            WHERE t.status = 'SUCCESS'
            
            UNION ALL
            
            SELECT 
                'DISBURSEMENT' as log_type,
                l.start_date as tx_date,
                CONCAT('REL-', l.id) as ref_no,
                u.fullname as client_name,
                l.id as loan_id,
                'SYSTEM' as method,
                0 as cash_in,
                l.loan_amount as cash_out,
                'COMPLETED' as status
            FROM loans l
            JOIN users u ON l.user_id = u.id
        ) AS master_log 
    ";
    
    $params = [];
    
    // 4. Apply Filters to SQL
    if ($quick_filter === 'today') {
        $query .= " WHERE DATE(tx_date) = CURDATE() ";
    } elseif (!empty($start_date) && !empty($end_date)) {
        $query .= " WHERE DATE(tx_date) BETWEEN ? AND ? ";
        $params[] = $start_date;
        $params[] = $end_date;
    } elseif (!empty($start_date)) {
        $query .= " WHERE DATE(tx_date) >= ? ";
        $params[] = $start_date;
    } elseif (!empty($end_date)) {
        $query .= " WHERE DATE(tx_date) <= ? ";
        $params[] = $end_date;
    }

    $query .= " ORDER BY tx_date DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $all_logs = $stmt->fetchAll();

    // Calculate Dynamic Dashboard Totals
    $total_in = 0;
    $total_out = 0;
    foreach($all_logs as $log) {
        $total_in += $log['cash_in'];
        $total_out += $log['cash_out'];
    }

    // 5. HANDLE EXCEL (CSV) EXPORT
    if (isset($_GET['export']) && $_GET['export'] == 'csv') {
        $filename = 'Master_Cashflow_Log';
        if ($quick_filter === 'today') {
            $filename .= '_Today_' . date('Y-m-d');
        } elseif ($start_date && $end_date) {
            $filename .= '_' . $start_date . '_to_' . $end_date;
        } else {
            $filename .= '_All_Time';
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename . '.csv');
        $output = fopen('php://output', 'w');
        fputcsv($output, array('Date & Time', 'Transaction Type', 'Reference No.', 'Client Name', 'Loan ID', 'Method', 'Cash IN (Received)', 'Cash OUT (Disbursed)', 'Status'));
        
        foreach ($all_logs as $row) {
            fputcsv($output, array(
                date("M d, Y g:i A", strtotime($row['tx_date'])),
                $row['log_type'],
                $row['ref_no'] ? $row['ref_no'] : 'N/A',
                $row['client_name'],
                'LN-' . str_pad($row['loan_id'], 4, '0', STR_PAD_LEFT),
                $row['method'],
                number_format($row['cash_in'], 2, '.', ''),
                number_format($row['cash_out'], 2, '.', ''),
                $row['status']
            ));
        }
        fclose($output);
        exit();
    }

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Master Cashflow Log</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/transactions.css">

</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Master Cashflow Log</h1>
                <p>Complete timeline of all disbursed funds and received collections.</p>
                <?php if ($quick_filter === 'today'): ?>
                    <p style="color: #60a5fa; margin-top: 5px; font-weight: bold;"><i class="bi bi-calendar-event"></i> Showing records for Today</p>
                <?php elseif ($start_date && $end_date): ?>
                    <p style="color: #60a5fa; margin-top: 5px; font-weight: bold;"><i class="bi bi-calendar-range"></i> Showing records from <?php echo date("M d, Y", strtotime($start_date)); ?> to <?php echo date("M d, Y", strtotime($end_date)); ?></p>
                <?php endif; ?>
            </div>
           
        </div>

        <form method="GET" class="filter-form" id="filterForm">
            <div>
                <label>Date From:</label>
                <input type="date" name="start_date" id="start_date" class="form-control-date" value="<?php echo htmlspecialchars($start_date); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <div>
                <label>Date To:</label>
                <input type="date" name="end_date" id="end_date" class="form-control-date" value="<?php echo htmlspecialchars($end_date); ?>" max="<?php echo date('Y-m-d'); ?>">
            </div>
            <button type="submit" class="btn-filter" id="btnFilter"><i class="bi bi-funnel"></i> Apply Filter</button>
            
            <div class="shortcut-divider">
                <span style="color: #64748b; font-size: 12px; margin-right: 5px; line-height: 38px;">Quick Filters:</span>
                
                <a href="transaction_log.php?filter=today" class="btn-shortcut <?php echo ($quick_filter === 'today') ? 'active' : ''; ?>">
                    Today
                </a>
                
                <a href="transaction_log.php?filter=all" class="btn-shortcut <?php echo ($quick_filter === 'all' || (empty($quick_filter) && empty($start_date))) ? 'active' : ''; ?>">
                    All Time
                </a>
            </div>

             <div style="display: flex; gap: 10px; margin-left: auto;">
                <button type="button" class="btn-export" onclick="window.print()">
                    <i class="bi bi-printer"></i> Print Paper
                </button>
                <a href="transaction_log.php?export=csv&filter=<?php echo $quick_filter; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="btn-export" style="background: #10b981; color: #fff; text-decoration: none; border-color: #10b981;">
                    <i class="bi bi-file-earmark-spreadsheet"></i> Export to Excel
                </a>
            </div>
        </form>

        <div class="toolbar" style="margin-bottom: 20px;">
            <div style="display: flex; gap: 30px;">
                <div style="color: #60a5fa; font-size: 16px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-arrow-up-right-circle"></i> Total Out: ₱ <?php echo number_format($total_out, 2); ?>
                </div>
                <div style="color: #34d399; font-size: 16px; font-weight: bold; display: flex; align-items: center; gap: 8px;">
                    <i class="bi bi-arrow-down-left-circle"></i> Total In: ₱ <?php echo number_format($total_in, 2); ?>
                </div>
            </div>
            <div>
                <input type="text" id="searchInput" class="search-input" placeholder="Search Ref No, Client..." onkeyup="filterTable()">
            </div>
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Type</th>
                        <th>Ref No.</th>
                        <th>Client Name</th>
                        <th class="text-right">Cash OUT (Disbursed)</th>
                        <th class="text-right">Cash IN (Received)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($all_logs)): ?>
                        <tr><td colspan="7" style="text-align:center; padding: 20px;">No records found for this filter.</td></tr>
                    <?php else: ?>
                        <?php foreach($all_logs as $row): ?>
                        <tr class="tx-row">
                            <td style="color:#cbd5e1;"><?php echo date("M d, Y - g:i A", strtotime($row['tx_date'])); ?></td>
                            
                            <td>
                                <?php if($row['log_type'] == 'DISBURSEMENT'): ?>
                                    <span class="type-disburse"><i class="bi bi-box-arrow-up-right"></i> Release</span>
                                <?php else: ?>
                                    <span class="type-collect"><i class="bi bi-box-arrow-in-down-left"></i> Payment</span>
                                <?php endif; ?>
                            </td>
                            
                            <td style="color:#94a3b8; font-family: monospace;"><?php echo htmlspecialchars($row['ref_no'] ?? 'N/A'); ?></td>
                            <td class="client-name"><?php echo htmlspecialchars($row['client_name']); ?></td>
                            
                            <td class="text-right">
                                <?php if($row['cash_out'] > 0): ?>
                                    <span class="val-out">- ₱ <?php echo number_format($row['cash_out'], 2); ?></span>
                                <?php else: ?>
                                    <span style="color:#475569;">--</span>
                                <?php endif; ?>
                            </td>
                            
                            <td class="text-right">
                                <?php if($row['cash_in'] > 0): ?>
                                    <span class="val-in">+ ₱ <?php echo number_format($row['cash_in'], 2); ?></span>
                                <?php else: ?>
                                    <span style="color:#475569;">--</span>
                                <?php endif; ?>
                            </td>
                            
                            <td><span class="badge badge-success"><?php echo htmlspecialchars($row['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <script>
        // JS Table Filter
        function filterTable() {
            let input = document.getElementById("searchInput").value.toLowerCase();
            let rows = document.querySelectorAll(".tx-row");
            rows.forEach(row => {
                let text = row.textContent.toLowerCase();
                row.style.display = text.includes(input) ? "" : "none";
            });
        }

        // 🟢 JS CALENDAR VALIDATION 
        const startDateInput = document.getElementById('start_date');
        const endDateInput = document.getElementById('end_date');

        // When Start Date changes, End Date cannot be earlier than it
        startDateInput.addEventListener('change', function() {
            if (this.value) {
                endDateInput.min = this.value; 
            }
        });

        // When End Date changes, Start Date cannot be later than it
        endDateInput.addEventListener('change', function() {
            if (this.value) {
                startDateInput.max = this.value;
            } else {
                // Reset max to today if empty
                const today = new Date().toISOString().split('T')[0];
                startDateInput.max = today;
            }
        });
    </script>
</body>
</html>