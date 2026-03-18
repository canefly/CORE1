<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
if (file_exists($connection_file)) {
    require_once $connection_file;
} else {
    die("Error: Connection file not found.");
}

if (!isset($pdo)) {
    die("Fatal Error: \$pdo variable is not defined.");
}

/*
|--------------------------------------------------------------------------
| Simple Helpers
|--------------------------------------------------------------------------
*/
function getPrincipalAmount($row)
{
    if (isset($row['loan_amount']) && is_numeric($row['loan_amount'])) {
        return (float)$row['loan_amount'];
    }
    if (isset($row['principal_amount']) && is_numeric($row['principal_amount'])) {
        return (float)$row['principal_amount'];
    }
    if (isset($row['principal']) && is_numeric($row['principal'])) {
        return (float)$row['principal'];
    }
    return 0;
}

function getMonthlyDue($row)
{
    if (isset($row['monthly_due']) && is_numeric($row['monthly_due'])) {
        return (float)$row['monthly_due'];
    }
    if (isset($row['monthly_payment']) && is_numeric($row['monthly_payment'])) {
        return (float)$row['monthly_payment'];
    }
    if (isset($row['amortization']) && is_numeric($row['amortization'])) {
        return (float)$row['amortization'];
    }
    return 0;
}

function getOutstanding($row)
{
    if (isset($row['outstanding']) && is_numeric($row['outstanding'])) {
        return (float)$row['outstanding'];
    }
    if (isset($row['remaining_balance']) && is_numeric($row['remaining_balance'])) {
        return (float)$row['remaining_balance'];
    }
    if (isset($row['balance']) && is_numeric($row['balance'])) {
        return (float)$row['balance'];
    }
    return 0;
}

function getNextDeadline($row)
{
    if (!empty($row['next_payment'])) {
        return $row['next_payment'];
    }
    if (!empty($row['next_deadline'])) {
        return $row['next_deadline'];
    }
    if (!empty($row['due_date'])) {
        return $row['due_date'];
    }
    return '';
}

function getTermMonths($row)
{
    if (isset($row['term_months']) && is_numeric($row['term_months'])) {
        return (int)$row['term_months'];
    }
    if (isset($row['term']) && is_numeric($row['term'])) {
        return (int)$row['term'];
    }
    return 0;
}

function getTotalPayable($row)
{
    if (isset($row['total_payable']) && is_numeric($row['total_payable'])) {
        return (float)$row['total_payable'];
    }
    if (isset($row['total_amount']) && is_numeric($row['total_amount'])) {
        return (float)$row['total_amount'];
    }
    return 0;
}

function getRepaymentProgress($row)
{
    $principal = getPrincipalAmount($row);
    $outstanding = getOutstanding($row);
    $totalPayable = getTotalPayable($row);

    $basis = $totalPayable > 0 ? $totalPayable : $principal;

    if ($basis <= 0) {
        return [
            'paid' => 0,
            'basis' => 0,
            'percent' => 0
        ];
    }

    $paid = $basis - $outstanding;
    if ($paid < 0) $paid = 0;
    if ($paid > $basis) $paid = $basis;

    $percent = ($paid / $basis) * 100;
    if ($percent < 0) $percent = 0;
    if ($percent > 100) $percent = 100;

    return [
        'paid' => $paid,
        'basis' => $basis,
        'percent' => $percent
    ];
}

function buildAmortizationSchedule($row)
{
    $schedule = [];

    $termMonths = getTermMonths($row);
    $monthlyDue = getMonthlyDue($row);
    $outstanding = getOutstanding($row);
    $principal = getPrincipalAmount($row);
    $totalPayable = getTotalPayable($row);

    $basis = $totalPayable > 0 ? $totalPayable : $principal;

    if ($termMonths <= 0 && $monthlyDue > 0 && $basis > 0) {
        $termMonths = (int)ceil($basis / $monthlyDue);
    }

    if ($termMonths <= 0) {
        return $schedule;
    }

    $paidAmount = $basis - $outstanding;
    if ($paidAmount < 0) $paidAmount = 0;

    $baseDateRaw = '';
    if (!empty($row['start_date'])) {
        $baseDateRaw = $row['start_date'];
    } elseif (!empty($row['next_payment'])) {
        $baseDateRaw = $row['next_payment'];
    } elseif (!empty($row['next_deadline'])) {
        $baseDateRaw = $row['next_deadline'];
    }

    try {
        if (!empty($baseDateRaw) && $baseDateRaw !== '0000-00-00' && $baseDateRaw !== '0000-00-00 00:00:00') {
            $baseDate = new DateTime($baseDateRaw);
        } else {
            $baseDate = new DateTime();
        }
    } catch (Exception $e) {
        $baseDate = new DateTime();
    }

    $installmentAmount = $monthlyDue > 0 ? $monthlyDue : ($basis / $termMonths);

    for ($i = 1; $i <= $termMonths; $i++) {
        $dueDate = clone $baseDate;
        if ($i > 1) {
            $dueDate->modify('+' . ($i - 1) . ' month');
        }

        if ($paidAmount >= ($installmentAmount * $i)) {
            $status = 'PAID';
        } elseif ($paidAmount >= ($installmentAmount * ($i - 1))) {
            $status = 'PENDING';
        } else {
            $status = 'UPCOMING';
        }

        $schedule[] = [
            'installment_no' => $i,
            'due_date' => $dueDate->format('Y-m-d'),
            'amount' => $installmentAmount,
            'status' => $status
        ];
    }

    return $schedule;
}

/*
|--------------------------------------------------------------------------
| 2. Handle Payment Submission (POST Request)
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'receive_payment') {
    $loan_id = $_POST['loan_id'];
    $amount_received = floatval($_POST['amount_received']);
    $reference = trim($_POST['reference_no']);
    $method = $_POST['payment_method'];
    $payment_source = $_POST['payment_source'] ?? 'loan';

    try {
        $pdo->beginTransaction();

        if ($payment_source === 'restructured') {
            $stmtLoan = $pdo->prepare("SELECT * FROM restructured_loans WHERE id = ? AND status IN ('ACTIVE', 'ONGOING', 'APPROVED')");
            $stmtLoan->execute([$loan_id]);
            $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);

            if ($loan) {
                $userId = $loan['user_id'] ?? null;
                $receipt_no = $reference ? $reference : 'OTC-' . date('Ymd-His');

                $insertTx = $pdo->prepare("
                    INSERT INTO transactions (user_id, restructured_loan_id, amount, status, trans_date, provider_method, receipt_number) 
                    VALUES (?, ?, ?, 'SUCCESS', NOW(), ?, ?)
                ");
                $insertTx->execute([$userId, $loan_id, $amount_received, $method, $receipt_no]);

                $currentOutstanding = getOutstanding($loan);
                $new_outstanding = $currentOutstanding - $amount_received;

                if (isset($loan['outstanding'])) {
                    $outstandingColumn = 'outstanding';
                } elseif (isset($loan['remaining_balance'])) {
                    $outstandingColumn = 'remaining_balance';
                } else {
                    $outstandingColumn = 'outstanding';
                }

                if (isset($loan['next_payment'])) {
                    $nextPaymentColumn = 'next_payment';
                } elseif (isset($loan['next_deadline'])) {
                    $nextPaymentColumn = 'next_deadline';
                } else {
                    $nextPaymentColumn = '';
                }

                if ($new_outstanding <= 0) {
                    $updateSql = "UPDATE restructured_loans SET {$outstandingColumn} = 0, status = 'COMPLETED' WHERE id = ?";
                    $updateLoan = $pdo->prepare($updateSql);
                    $updateLoan->execute([$loan_id]);

                    $success_message = "Payment received! The restructured loan is now fully paid.";
                } else {
                    if ($nextPaymentColumn !== '') {
                        $updateSql = "UPDATE restructured_loans SET {$outstandingColumn} = ?, {$nextPaymentColumn} = DATE_ADD({$nextPaymentColumn}, INTERVAL 1 MONTH) WHERE id = ?";
                        $updateLoan = $pdo->prepare($updateSql);
                        $updateLoan->execute([$new_outstanding, $loan_id]);
                    } else {
                        $updateSql = "UPDATE restructured_loans SET {$outstandingColumn} = ? WHERE id = ?";
                        $updateLoan = $pdo->prepare($updateSql);
                        $updateLoan->execute([$new_outstanding, $loan_id]);
                    }

                    $success_message = "Payment of ₱" . number_format($amount_received, 2) . " received successfully.";
                }

                $pdo->commit();
            } else {
                $pdo->rollBack();
                $error_message = "Restructured loan not found or already closed.";
            }
        } else {
            // Original normal loan logic preserved
            $stmtLoan = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND status = 'ACTIVE'");
            $stmtLoan->execute([$loan_id]);
            $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);

            if ($loan) {
                $insertTx = $pdo->prepare("
                    INSERT INTO transactions (user_id, loan_id, amount, status, trans_date, provider_method, receipt_number) 
                    VALUES (?, ?, ?, 'SUCCESS', NOW(), ?, ?)
                ");
                $receipt_no = $reference ? $reference : 'OTC-' . date('Ymd-His');
                $insertTx->execute([$loan['user_id'], $loan_id, $amount_received, $method, $receipt_no]);

                $new_outstanding = $loan['outstanding'] - $amount_received;

                if ($new_outstanding <= 0) {
                    $updateLoan = $pdo->prepare("UPDATE loans SET outstanding = 0, status = 'PAID' WHERE id = ?");
                    $updateLoan->execute([$loan_id]);
                    $success_message = "Payment received! The loan is now fully paid.";
                } else {
                    $updateLoan = $pdo->prepare("
                        UPDATE loans 
                        SET outstanding = ?, next_payment = DATE_ADD(next_payment, INTERVAL 1 MONTH) 
                        WHERE id = ?
                    ");
                    $updateLoan->execute([$new_outstanding, $loan_id]);
                    $success_message = "Payment of ₱" . number_format($amount_received, 2) . " received successfully.";
                }

                $pdo->commit();
            } else {
                $pdo->rollBack();
                $error_message = "Loan not found or already closed.";
            }
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Database Error: " . $e->getMessage();
    }
}

/*
|--------------------------------------------------------------------------
| 3. Fetch KPI & Dashboard Data
|--------------------------------------------------------------------------
*/
try {
    $clientsList = [];
    $clientDetailsMap = [];
    $today = date('Y-m-d');

    // 3A. Get active restructured loans first
    $stmtRestructured = $pdo->query("
        SELECT rl.*, u.fullname
        FROM restructured_loans rl
        JOIN users u ON rl.user_id = u.id
        WHERE rl.status IN ('ACTIVE', 'ONGOING', 'APPROVED')
        ORDER BY rl.id DESC
    ");
    $restructuredList = $stmtRestructured->fetchAll(PDO::FETCH_ASSOC);

    $restructuredBaseLoanIds = [];

    foreach ($restructuredList as $row) {
        $row['source_type'] = 'restructured';
        $row['record_id'] = $row['id'];
        $row['client_name'] = $row['fullname'];

        if (isset($row['loan_id']) && !empty($row['loan_id'])) {
            $restructuredBaseLoanIds[] = $row['loan_id'];
        }

        $clientsList[] = $row;
    }

    // 3B. Get active normal loans not already restructured
    $stmtLoans = $pdo->query("
        SELECT l.*, u.fullname
        FROM loans l
        JOIN users u ON l.user_id = u.id
        WHERE l.status = 'ACTIVE'
        ORDER BY l.id DESC
    ");
    $loanList = $stmtLoans->fetchAll(PDO::FETCH_ASSOC);

    foreach ($loanList as $row) {
        if (in_array($row['id'], $restructuredBaseLoanIds)) {
            continue;
        }

        $row['source_type'] = 'loan';
        $row['record_id'] = $row['id'];
        $row['client_name'] = $row['fullname'];

        $clientsList[] = $row;
    }

    // sort by client name
    usort($clientsList, function ($a, $b) {
        return strcmp($a['client_name'], $b['client_name']);
    });

    // Collected Today
    $stmtCollected = $pdo->query("SELECT SUM(amount) FROM transactions WHERE DATE(trans_date) = CURDATE() AND status = 'SUCCESS'");
    $collectedToday = $stmtCollected->fetchColumn() ?: 0;

    // Prepare lists
    $expectedToday = 0;
    $overdueCount = 0;
    $dueTodayList = [];
    $overdueList = [];

    foreach ($clientsList as $row) {
        $nextDeadline = getNextDeadline($row);
        $monthlyDue = getMonthlyDue($row);

        if (!empty($nextDeadline) && $nextDeadline <= $today) {
            $expectedToday += $monthlyDue;
        }

        if (!empty($nextDeadline) && $nextDeadline === $today) {
            $dueTodayList[] = $row;
        }

        if (!empty($nextDeadline) && $nextDeadline < $today) {
            $row['days_late'] = (new DateTime($today))->diff(new DateTime($nextDeadline))->days;
            $overdueList[] = $row;
            $overdueCount++;
        }

        $progress = getRepaymentProgress($row);
        $schedule = buildAmortizationSchedule($row);

        $detailKey = $row['source_type'] . '_' . $row['record_id'];
        $clientDetailsMap[$detailKey] = [
            'source_type' => $row['source_type'],
            'record_id' => $row['record_id'],
            'client_name' => $row['client_name'],
            'monthly_due' => getMonthlyDue($row),
            'principal' => getPrincipalAmount($row),
            'outstanding' => getOutstanding($row),
            'next_deadline' => getNextDeadline($row),
            'repayment_paid' => $progress['paid'],
            'repayment_basis' => $progress['basis'],
            'repayment_percent' => round($progress['percent'], 2),
            'amortization_schedule' => $schedule
        ];
    }

    usort($overdueList, function ($a, $b) {
        return ($b['days_late'] ?? 0) <=> ($a['days_late'] ?? 0);
    });

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Payment Monitoring</title>
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script>
        if (localStorage.getItem('theme') === null) {
            localStorage.setItem('theme', 'dark'); 
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/payments.css">

    <style>
        .btn-view {
            background: #3b82f6;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-view:hover {
            opacity: 0.92;
        }

        .loan-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 999px;
            font-size: 11px;
            font-weight: 700;
        }

        .loan-badge.normal {
            background: rgba(59, 130, 246, 0.15);
            color: #93c5fd;
        }

        .loan-badge.restructured {
            background: rgba(168, 85, 247, 0.15);
            color: #d8b4fe;
        }

        .client-summary-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }

        .client-summary-item {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 10px;
            padding: 14px;
        }

        .client-summary-item span {
            display: block;
            color: #94a3b8;
            font-size: 12px;
            margin-bottom: 6px;
        }

        .client-summary-item strong {
            color: #fff;
            font-size: 16px;
        }

        .progress-wrap {
            margin-bottom: 18px;
        }

        .progress-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            color: #cbd5e1;
            font-size: 13px;
        }

        .progress-bar-bg {
            width: 100%;
            height: 12px;
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 999px;
            overflow: hidden;
        }

        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #34d399);
            width: 0%;
            transition: width .3s ease;
        }

        .schedule-table-wrap {
            max-height: 300px;
            overflow: auto;
            border: 1px solid #334155;
            border-radius: 8px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-table th,
        .schedule-table td {
            padding: 10px 12px;
            border-bottom: 1px solid #1e293b;
            text-align: left;
            color: #e2e8f0;
            font-size: 13px;
        }

        .schedule-table th {
            background: #0f172a;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .sched-paid {
            color: #34d399;
            font-weight: 700;
        }

        .sched-pending {
            color: #fbbf24;
            font-weight: 700;
        }

        .sched-upcoming {
            color: #93c5fd;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .client-summary-grid {
                grid-template-columns: 1fr;
            }

            #detailsModal .modal-box {
                width: 95% !important;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="main-content">
        
    <div class="page-header">
        <h1>Payment Monitoring</h1>
        <p>Track daily collections and manage overdue accounts.</p>
    </div>

    <?php if (isset($success_message)): ?>
        <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #34d399; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
        </div>
    <?php endif; ?>

    <div class="summary-grid">
        <div class="summary-card">
            <div class="icon-box icon-blue"><i class="bi bi-calendar-check"></i></div>
            <div class="meta">
                <h3>₱ <?php echo number_format($expectedToday, 2); ?></h3>
                <span>Expected Current & Overdue</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="icon-box icon-green"><i class="bi bi-cash-stack"></i></div>
            <div class="meta">
                <h3>₱ <?php echo number_format($collectedToday, 2); ?></h3>
                <span>Collected Today</span>
            </div>
        </div>
        <div class="summary-card">
            <div class="icon-box icon-red"><i class="bi bi-exclamation-diamond"></i></div>
            <div class="meta">
                <h3><?php echo $overdueCount; ?></h3>
                <span>Overdue Accounts</span>
            </div>
        </div>
    </div>

    <div class="tabs">
        <button class="tab-btn active" onclick="switchTab('clients', this)">Clients (<?php echo count($clientsList); ?>)</button>
        <button class="tab-btn" onclick="switchTab('due', this)">Due Today (<?php echo count($dueTodayList); ?>)</button>
        <button class="tab-btn" onclick="switchTab('overdue', this)">Overdue / Arrears (<?php echo $overdueCount; ?>)</button>
    </div>

    <!-- CLIENTS -->
    <div id="tab-clients" class="content-card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Type</th>
                    <th>Monthly Payment</th>
                    <th>Principal</th>
                    <th>Outstanding</th>
                    <th>Next Deadline</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($clientsList)): ?>
                    <tr><td colspan="8" style="text-align:center; padding: 20px;">No active clients found.</td></tr>
                <?php else: ?>
                    <?php foreach ($clientsList as $client): ?>
                        <?php
                            $nameParts = explode(' ', trim($client['client_name']));
                            $initials = strtoupper(
                                (isset($nameParts[0][0]) ? $nameParts[0][0] : '') .
                                (isset($nameParts[1][0]) ? $nameParts[1][0] : '')
                            );

                            $detailKey = $client['source_type'] . '_' . $client['record_id'];
                            $prefix = $client['source_type'] === 'restructured' ? '#RL-' : '#LN-';
                        ?>
                        <tr>
                            <td style="color:#60a5fa;"><?php echo $prefix . $client['record_id']; ?></td>
                            <td>
                                <div class="client-info">
                                    <div class="mini-avatar" style="background: #334155; color: #fff; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; font-size: 12px; font-weight: bold;">
                                        <?php echo htmlspecialchars($initials); ?>
                                    </div>
                                    <span><?php echo htmlspecialchars($client['client_name']); ?></span>
                                </div>
                            </td>
                            <td>
                                <?php if ($client['source_type'] === 'restructured'): ?>
                                    <span class="loan-badge restructured">Restructured</span>
                                <?php else: ?>
                                    <span class="loan-badge normal">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;">₱ <?php echo number_format(getMonthlyDue($client), 2); ?></td>
                            <td>₱ <?php echo number_format(getPrincipalAmount($client), 2); ?></td>
                            <td style="color:#94a3b8;">₱ <?php echo number_format(getOutstanding($client), 2); ?></td>
                            <td><?php echo !empty(getNextDeadline($client)) ? date('M d, Y', strtotime(getNextDeadline($client))) : 'N/A'; ?></td>
                            <td style="text-align:center;">
                                <button 
                                    class="btn-view"
                                    onclick='openDetailsModal(<?php echo json_encode($clientDetailsMap[$detailKey], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                    <i class="bi bi-eye-fill"></i> View
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- DUE TODAY -->
    <div id="tab-due" class="content-card" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Type</th>
                    <th>Amount Due</th>
                    <th>Outstanding Balance</th>
                    <th>Status</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($dueTodayList)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 20px;">No payments due exactly today.</td></tr>
                <?php else: ?>
                    <?php foreach ($dueTodayList as $due): ?>
                        <?php
                            $prefix = $due['source_type'] === 'restructured' ? '#RL-' : '#LN-';
                        ?>
                        <tr>
                            <td style="color:#60a5fa;"><?php echo $prefix . $due['record_id']; ?></td>
                            <td><?php echo htmlspecialchars($due['client_name']); ?></td>
                            <td>
                                <?php if ($due['source_type'] === 'restructured'): ?>
                                    <span class="loan-badge restructured">Restructured</span>
                                <?php else: ?>
                                    <span class="loan-badge normal">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td style="font-weight:700;">₱ <?php echo number_format(getMonthlyDue($due), 2); ?></td>
                            <td style="color:#94a3b8;">₱ <?php echo number_format(getOutstanding($due), 2); ?></td>
                            <td><span class="status-due" style="background: rgba(245, 158, 11, 0.1); color: #fbbf24; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Waiting</span></td>
                            <td style="text-align:center;">
                                <button class="btn-receive" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;" 
                                        onclick="openModal('<?php echo $due['record_id']; ?>', '<?php echo htmlspecialchars(addslashes($due['client_name'])); ?>', '<?php echo getMonthlyDue($due); ?>', '<?php echo $due['source_type']; ?>')">
                                    <i class="bi bi-box-arrow-in-down"></i> Pay
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- OVERDUE -->
    <div id="tab-overdue" class="content-card" style="display:none;">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Client Name</th>
                    <th>Type</th>
                    <th>Days Late</th>
                    <th>Penalty (5%)</th>
                    <th>Total Due</th>
                    <th style="text-align:center;">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($overdueList)): ?>
                    <tr><td colspan="7" style="text-align:center; padding: 20px;">No overdue accounts. Great job!</td></tr>
                <?php else: ?>
                    <?php foreach ($overdueList as $overdue): ?>
                        <?php
                            $penalty = getMonthlyDue($overdue) * 0.05;
                            $totalDue = getMonthlyDue($overdue) + $penalty;
                            $prefix = $overdue['source_type'] === 'restructured' ? '#RL-' : '#LN-';
                        ?>
                        <tr>
                            <td style="color:#f87171;"><?php echo $prefix . $overdue['record_id']; ?></td>
                            <td><?php echo htmlspecialchars($overdue['client_name']); ?></td>
                            <td>
                                <?php if ($overdue['source_type'] === 'restructured'): ?>
                                    <span class="loan-badge restructured">Restructured</span>
                                <?php else: ?>
                                    <span class="loan-badge normal">Normal</span>
                                <?php endif; ?>
                            </td>
                            <td style="color:#f87171; font-weight:700;"><?php echo $overdue['days_late']; ?> Days</td>
                            <td>₱ <?php echo number_format($penalty, 2); ?></td>
                            <td style="font-weight:700;">₱ <?php echo number_format($totalDue, 2); ?></td>
                            <td style="text-align:center;">
                                <button class="btn-receive" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;" 
                                        onclick="openModal('<?php echo $overdue['record_id']; ?>', '<?php echo htmlspecialchars(addslashes($overdue['client_name'])); ?>', '<?php echo $totalDue; ?>', '<?php echo $overdue['source_type']; ?>')">
                                    Pay
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- PAYMENT MODAL -->
<div id="payModal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 1000;">
    <div class="modal-box" style="background: #1e293b; padding: 25px; border-radius: 12px; width: 400px; border: 1px solid #334155;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #fff; margin: 0;">Receive Payment</h3>
            <button class="close-modal" onclick="closeModal()" style="background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer;">&times;</button>
        </div>
        
        <form method="POST" action="payments.php">
            <input type="hidden" name="action" value="receive_payment">
            <input type="hidden" name="loan_id" id="modalLoanId">
            <input type="hidden" name="payment_source" id="modalPaymentSource" value="loan">

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 5px;">Client Name</label>
                <input type="text" class="form-input" id="modalClient" readonly style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 5px;">Amount Received (₱)</label>
                <input type="number" step="0.01" name="amount_received" class="form-input" id="modalAmount" required style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px; font-weight: bold; color: #34d399;">
            </div>

            <div class="form-group" style="margin-bottom: 15px;">
                <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 5px;">Payment Method</label>
                <select name="payment_method" class="form-select" required style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px;">
                    <option value="CASH">Cash (OTC)</option>
                    <option value="GCASH">GCash</option>
                    <option value="BANK_TRANSFER">Bank Transfer</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 25px;">
                <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 5px;">Reference Number (Optional)</label>
                <input type="text" name="reference_no" class="form-input" placeholder="e.g., GCash Ref #" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px;">
            </div>

            <button type="submit" class="btn-submit" style="width: 100%; background: #10b981; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                Confirm Payment
            </button>
        </form>
    </div>
</div>

<!-- DETAILS MODAL -->
<div id="detailsModal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 1001;">
    <div class="modal-box" style="background: #1e293b; padding: 25px; border-radius: 12px; width: 900px; max-width: 95%; border: 1px solid #334155; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #fff; margin: 0;">Client Loan Details</h3>
            <button class="close-modal" onclick="closeDetailsModal()" style="background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer;">&times;</button>
        </div>

        <div style="margin-bottom: 20px;">
            <h2 id="detailsClientName" style="color: #fff; margin: 0 0 5px 0;"></h2>
            <p id="detailsLoanId" style="color: #94a3b8; margin: 0;"></p>
        </div>

        <div class="client-summary-grid">
            <div class="client-summary-item">
                <span>Monthly Payment</span>
                <strong id="detailsMonthlyPayment">₱ 0.00</strong>
            </div>
            <div class="client-summary-item">
                <span>Principal</span>
                <strong id="detailsPrincipal">₱ 0.00</strong>
            </div>
            <div class="client-summary-item">
                <span>Outstanding</span>
                <strong id="detailsOutstanding">₱ 0.00</strong>
            </div>
            <div class="client-summary-item">
                <span>Next Deadline</span>
                <strong id="detailsNextDeadline">N/A</strong>
            </div>
        </div>

        <div class="progress-wrap">
            <div class="progress-top">
                <span>Repayment Progress</span>
                <span id="detailsProgressText">0%</span>
            </div>
            <div class="progress-bar-bg">
                <div id="detailsProgressBar" class="progress-bar-fill"></div>
            </div>
            <div style="margin-top: 8px; color: #94a3b8; font-size: 13px;" id="detailsProgressSubtext">
                ₱ 0.00 paid of ₱ 0.00
            </div>
        </div>

        <div>
            <h4 style="color:#fff; margin-bottom: 12px;">Amortization Schedule</h4>
            <div class="schedule-table-wrap">
                <table class="schedule-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="detailsScheduleBody">
                        <tr>
                            <td colspan="4" style="text-align:center; color:#94a3b8;">No amortization schedule available.</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div style="margin-top: 18px; text-align: right;">
            <button type="button" onclick="closeDetailsModal()" style="background:#334155; color:#fff; border:none; padding:10px 18px; border-radius:8px; cursor:pointer;">
                Close
            </button>
        </div>
    </div>
</div>

<script>
    function switchTab(tabName, btn) {
        document.getElementById('tab-clients').style.display = 'none';
        document.getElementById('tab-due').style.display = 'none';
        document.getElementById('tab-overdue').style.display = 'none';

        if (tabName === 'clients') document.getElementById('tab-clients').style.display = 'block';
        if (tabName === 'due') document.getElementById('tab-due').style.display = 'block';
        if (tabName === 'overdue') document.getElementById('tab-overdue').style.display = 'block';

        const btns = document.querySelectorAll('.tab-btn');
        btns.forEach(function(tabBtn) {
            tabBtn.classList.remove('active');
        });

        if (btn) {
            btn.classList.add('active');
        }
    }

    const modal = document.getElementById('payModal');
    const detailsModal = document.getElementById('detailsModal');
    
    function openModal(loanId, client, amount, sourceType) {
        document.getElementById('modalLoanId').value = loanId;
        document.getElementById('modalClient').value = client;
        document.getElementById('modalAmount').value = amount;
        document.getElementById('modalPaymentSource').value = sourceType || 'loan';
        modal.style.display = 'flex';
    }
    
    function closeModal() {
        modal.style.display = 'none';
    }

    function formatPeso(value) {
        const number = parseFloat(value || 0);
        return '₱ ' + number.toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    function formatDate(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString('en-PH', {
            year: 'numeric',
            month: 'short',
            day: '2-digit'
        });
    }

    function openDetailsModal(data) {
        document.getElementById('detailsClientName').textContent = data.client_name || 'Client';

        const prefix = data.source_type === 'restructured' ? '#RL-' : '#LN-';
        const label = data.source_type === 'restructured' ? 'Restructured Loan ID: ' : 'Loan ID: ';
        document.getElementById('detailsLoanId').textContent = label + prefix + (data.record_id || '');

        document.getElementById('detailsMonthlyPayment').textContent = formatPeso(data.monthly_due);
        document.getElementById('detailsPrincipal').textContent = formatPeso(data.principal);
        document.getElementById('detailsOutstanding').textContent = formatPeso(data.outstanding);
        document.getElementById('detailsNextDeadline').textContent = formatDate(data.next_deadline);

        const percent = parseFloat(data.repayment_percent || 0);
        document.getElementById('detailsProgressBar').style.width = percent + '%';
        document.getElementById('detailsProgressText').textContent = percent.toFixed(2) + '%';
        document.getElementById('detailsProgressSubtext').textContent =
            formatPeso(data.repayment_paid) + ' paid of ' + formatPeso(data.repayment_basis);

        const scheduleBody = document.getElementById('detailsScheduleBody');
        scheduleBody.innerHTML = '';

        if (Array.isArray(data.amortization_schedule) && data.amortization_schedule.length > 0) {
            data.amortization_schedule.forEach(function(row) {
                let statusClass = 'sched-upcoming';
                if (row.status === 'PAID') statusClass = 'sched-paid';
                if (row.status === 'PENDING') statusClass = 'sched-pending';

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${row.installment_no}</td>
                    <td>${formatDate(row.due_date)}</td>
                    <td>${formatPeso(row.amount)}</td>
                    <td class="${statusClass}">${row.status}</td>
                `;
                scheduleBody.appendChild(tr);
            });
        } else {
            scheduleBody.innerHTML = `
                <tr>
                    <td colspan="4" style="text-align:center; color:#94a3b8;">No amortization schedule available.</td>
                </tr>
            `;
        }

        detailsModal.style.display = 'flex';
    }

    function closeDetailsModal() {
        detailsModal.style.display = 'none';
    }
    
    window.onclick = function(e) {
        if (e.target == modal) closeModal();
        if (e.target == detailsModal) closeDetailsModal();
    }
</script>

</body>
</html>