<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
if (file_exists($connection_file)) {
    require_once $connection_file;
}
else {
    die("Error: Connection file not found.");
}

if (!isset($pdo)) {
    die("Fatal Error: \$pdo variable is not defined.");
}

// 2. Handle Payment Submission (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'receive_payment') {
    $loan_id = $_POST['loan_id'];
    $amount_received = floatval($_POST['amount_received']);
    $reference = trim($_POST['reference_no']);
    $method = $_POST['payment_method'];

    try {
        $pdo->beginTransaction();

        // Fetch current loan details
        $stmtLoan = $pdo->prepare("SELECT * FROM loans WHERE id = ? AND status = 'ACTIVE'");
        $stmtLoan->execute([$loan_id]);
        $loan = $stmtLoan->fetch();

        if ($loan) {
            // Log the transaction
            $insertTx = $pdo->prepare("
                INSERT INTO transactions (user_id, loan_id, amount, status, trans_date, provider_method, receipt_number) 
                VALUES (?, ?, ?, 'SUCCESS', NOW(), ?, ?)
            ");
            // Generating a simple receipt number for OTC transactions
            $receipt_no = $reference ? $reference : 'OTC-' . date('Ymd-His');
            $insertTx->execute([$loan['user_id'], $loan_id, $amount_received, $method, $receipt_no]);

            // Calculate new balance
            $new_outstanding = $loan['outstanding'] - $amount_received;

            if ($new_outstanding <= 0) {
                // Loan is fully paid
                $updateLoan = $pdo->prepare("UPDATE loans SET outstanding = 0, status = 'PAID' WHERE id = ?");
                $updateLoan->execute([$loan_id]);
                $success_message = "Payment received! The loan is now fully paid.";
            }
            else {
                // Loan is still active, advance the next payment date by 1 month
                $updateLoan = $pdo->prepare("
                    UPDATE loans 
                    SET outstanding = ?, next_payment = DATE_ADD(next_payment, INTERVAL 1 MONTH) 
                    WHERE id = ?
                ");
                $updateLoan->execute([$new_outstanding, $loan_id]);
                $success_message = "Payment of ₱" . number_format($amount_received, 2) . " received successfully.";
            }

            $pdo->commit();
        }
        else {
            $pdo->rollBack();
            $error_message = "Loan not found or already closed.";
        }
    }
    catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Database Error: " . $e->getMessage();
    }
}

// 3. Fetch KPI & Dashboard Data
try {
    // Expected Collections Today (Sum of monthly due for active loans where next payment is today or earlier)
    $stmtExpected = $pdo->query("SELECT SUM(monthly_due) FROM loans WHERE status = 'ACTIVE' AND next_payment <= CURDATE()");
    $expectedToday = $stmtExpected->fetchColumn() ?: 0;

    // Collected Today (Sum of successful transactions today)
    $stmtCollected = $pdo->query("SELECT SUM(amount) FROM transactions WHERE DATE(trans_date) = CURDATE() AND status = 'SUCCESS'");
    $collectedToday = $stmtCollected->fetchColumn() ?: 0;

    // Overdue Accounts Count
    $stmtOverdueCount = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'ACTIVE' AND next_payment < CURDATE()");
    $overdueCount = $stmtOverdueCount->fetchColumn() ?: 0;

    // Lists for Tabs
    // Due Today list (next_payment is exact match to today)
    $stmtDue = $pdo->query("
        SELECT l.*, u.fullname 
        FROM loans l 
        JOIN users u ON l.user_id = u.id 
        WHERE l.status = 'ACTIVE' AND l.next_payment = CURDATE()
    ");
    $dueTodayList = $stmtDue->fetchAll();

    // Overdue list (next_payment is in the past)
    $stmtOverdueList = $pdo->query("
        SELECT l.*, u.fullname, DATEDIFF(CURDATE(), l.next_payment) as days_late 
        FROM loans l 
        JOIN users u ON l.user_id = u.id 
        WHERE l.status = 'ACTIVE' AND l.next_payment < CURDATE()
        ORDER BY days_late DESC
    ");
    $overdueList = $stmtOverdueList->fetchAll();

}
catch (PDOException $e) {
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
        // THE ANTI-FLASHBANG PROTOCOL 
        if (localStorage.getItem('theme') === null) {
            localStorage.setItem('theme', 'dark'); 
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/payments.css">
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
        <?php
endif; ?>

        <?php if (isset($error_message)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
            </div>
        <?php
endif; ?>

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
            <button class="tab-btn active" onclick="switchTab('due')">Due Today (<?php echo count($dueTodayList); ?>)</button>
            <button class="tab-btn" onclick="switchTab('overdue')">Overdue / Arrears (<?php echo $overdueCount; ?>)</button>
        </div>

        <div id="tab-due" class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Client Name</th>
                        <th>Amount Due</th>
                        <th>Outstanding Balance</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($dueTodayList)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 20px;">No payments due exactly today.</td></tr>
                    <?php
else: ?>
                        <?php foreach ($dueTodayList as $due):
        $nameParts = explode(' ', $due['fullname']);
        $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
?>
                        <tr>
                            <td style="color:#60a5fa;">#LN-<?php echo $due['id']; ?></td>
                            <td>
                                <div class="client-info">
                                    <div class="mini-avatar" style="background: #334155; color: #fff; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; font-size: 12px; font-weight: bold;">
                                        <?php echo $initials; ?>
                                    </div>
                                    <span><?php echo htmlspecialchars($due['fullname']); ?></span>
                                </div>
                            </td>
                            <td style="font-weight:700;">₱ <?php echo number_format($due['monthly_due'], 2); ?></td>
                            <td style="color:#94a3b8;">₱ <?php echo number_format($due['outstanding'], 2); ?></td>
                            <td><span class="status-due" style="background: rgba(245, 158, 11, 0.1); color: #fbbf24; padding: 4px 8px; border-radius: 4px; font-size: 11px;">Waiting</span></td>
                            <td style="text-align:center;">
                                <button class="btn-receive" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;" 
                                        onclick="openModal('<?php echo $due['id']; ?>', '<?php echo htmlspecialchars(addslashes($due['fullname'])); ?>', '<?php echo $due['monthly_due']; ?>')">
                                    <i class="bi bi-box-arrow-in-down"></i> Pay
                                </button>
                            </td>
                        </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>

        <div id="tab-overdue" class="content-card" style="display:none;">
            <table>
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Client Name</th>
                        <th>Days Late</th>
                        <th>Penalty (5%)</th>
                        <th>Total Due</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($overdueList)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 20px;">No overdue accounts. Great job!</td></tr>
                    <?php
else: ?>
                        <?php foreach ($overdueList as $overdue):
        // Calculate simple 5% flat penalty for display demo purposes
        $penalty = $overdue['monthly_due'] * 0.05;
        $totalDue = $overdue['monthly_due'] + $penalty;
?>
                        <tr>
                            <td style="color:#f87171;">#LN-<?php echo $overdue['id']; ?></td>
                            <td><?php echo htmlspecialchars($overdue['fullname']); ?></td>
                            <td style="color:#f87171; font-weight:700;"><?php echo $overdue['days_late']; ?> Days</td>
                            <td>₱ <?php echo number_format($penalty, 2); ?></td>
                            <td style="font-weight:700;">₱ <?php echo number_format($totalDue, 2); ?></td>
                            <td style="text-align:center;">
                                <button class="btn-receive" style="background: #10b981; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: bold;" 
                                        onclick="openModal('<?php echo $overdue['id']; ?>', '<?php echo htmlspecialchars(addslashes($overdue['fullname'])); ?>', '<?php echo $totalDue; ?>')">
                                    Pay
                                </button>
                            </td>
                        </tr>
                        <?php
    endforeach; ?>
                    <?php
endif; ?>
                </tbody>
            </table>
        </div>

    </div>

    <div id="payModal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 1000;">
        <div class="modal-box" style="background: #1e293b; padding: 25px; border-radius: 12px; width: 400px; border: 1px solid #334155;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #fff; margin: 0;">Receive Payment</h3>
                <button class="close-modal" onclick="closeModal()" style="background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            
            <form method="POST" action="payments.php">
                <input type="hidden" name="action" value="receive_payment">
                <input type="hidden" name="loan_id" id="modalLoanId">

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

    <script>
        function switchTab(tabName) {
            document.getElementById('tab-due').style.display = 'none';
            document.getElementById('tab-overdue').style.display = 'none';
            
            if(tabName === 'due') document.getElementById('tab-due').style.display = 'block';
            if(tabName === 'overdue') document.getElementById('tab-overdue').style.display = 'block';

            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        const modal = document.getElementById('payModal');
        
        function openModal(loanId, client, amount) {
            document.getElementById('modalLoanId').value = loanId;
            document.getElementById('modalClient').value = client;
            document.getElementById('modalAmount').value = amount;
            modal.style.display = 'flex';
        }
        
        function closeModal() {
            modal.style.display = 'none';
        }
        
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }
    </script>

</body>
</html>