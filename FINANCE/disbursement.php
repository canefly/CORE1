<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
require_once __DIR__ . '/includes/session_checker.php';

if (file_exists($connection_file)) {
    require_once $connection_file;
} else {
    die("Error: Connection file not found.");
}

if (!isset($pdo)) {
    die("Fatal Error: \$pdo variable is not defined.");
}

// 2. Handle Disbursement Submission (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'release_funds') {
    $app_id = $_POST['application_id'];
    $method = $_POST['disbursement_method'];
    $reference = $_POST['reference_no'];
    
    try {
        $pdo->beginTransaction();
        
        // Get the application details
        $stmtApp = $pdo->prepare("SELECT * FROM loan_applications WHERE id = ? AND status = 'APPROVED'");
        $stmtApp->execute([$app_id]);
        $app = $stmtApp->fetch();
        
        if ($app) {
            // Calculate Dates & Amounts
            $start_date = date('Y-m-d');
            $due_date = date('Y-m-d', strtotime('+1 month')); // First payment due in 1 month
            
            // From application
            $principal = $app['principal_amount'];
            $interest_rate = $app['interest_rate'];
            $term_months = $app['term_months'];
            $interest_method = $app['interest_method'];
            
            // Recalculate monthly due and total outstanding for the Loans table
            $total_interest = $principal * ($interest_rate / 100) * $term_months;
            $outstanding = $principal + $total_interest;
            $monthly_due = $outstanding / $term_months;

            // Insert into Active Loans
            $insertLoan = $pdo->prepare("
                INSERT INTO loans 
                (user_id, application_id, loan_amount, term_months, monthly_due, interest_rate, interest_method, outstanding, next_payment, due_date, start_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
            ");
            
            $insertLoan->execute([
                $app['user_id'], 
                $app['id'], 
                $principal, 
                $term_months, 
                $monthly_due, 
                $interest_rate, 
                $interest_method, 
                $outstanding, 
                $due_date, 
                $due_date, 
                $start_date
            ]);
            
            // You could optionally log this to transactions as a 'DISBURSEMENT' here
            
            $pdo->commit();
            $success_message = "Funds successfully released and loan activated!";
        } else {
            $pdo->rollBack();
            $error_message = "Application not found or not approved.";
        }
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Database Error: " . $e->getMessage();
    }
}

// 3. Fetch Queue Data
try {
    // Find APPROVED applications that are NOT YET in the loans table
    $queryQueue = "
        SELECT la.*, u.fullname 
        FROM loan_applications la 
        JOIN users u ON la.user_id = u.id 
        WHERE la.status = 'APPROVED' 
        AND la.id NOT IN (SELECT application_id FROM loans WHERE application_id IS NOT NULL)
        ORDER BY la.updated_at ASC
    ";
    $stmtQueue = $pdo->query($queryQueue);
    $queueList = $stmtQueue->fetchAll();
    
    $waitingCount = count($queueList);
    
    // Calculate Disbursed Today (from loans table)
    $stmtDisbursed = $pdo->query("SELECT SUM(loan_amount) FROM loans WHERE DATE(start_date) = CURDATE()");
    $disbursedToday = $stmtDisbursed->fetchColumn() ?: 0;

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Disbursement</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/disbursement.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Disbursement Queue</h1>
            <p>Approved loans waiting for fund release.</p>
        </div>

        <?php if(isset($success_message)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #34d399; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="queue-stats">
            <div class="stat-box">
                <div class="stat-icon icon-ready"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <h4><?php echo $waitingCount; ?> Clients</h4>
                    <span>Waiting for Release</span>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon icon-done"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <h4>₱ <?php echo number_format($disbursedToday, 2); ?></h4>
                    <span>Disbursed Today</span>
                </div>
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Client Name</th>
                        <th>Approved Date</th>
                        <th>Loan Amount</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($queueList)): ?>
                        <tr><td colspan="6" style="text-align:center; padding: 20px;">No approved loans waiting for disbursement.</td></tr>
                    <?php else: ?>
                        <?php foreach($queueList as $app): 
                            // Extract initials for the mini-avatar
                            $nameParts = explode(' ', $app['fullname']);
                            $initials = strtoupper(substr($nameParts[0], 0, 1) . (isset($nameParts[1]) ? substr($nameParts[1], 0, 1) : ''));
                        ?>
                        <tr>
                            <td style="color:#60a5fa; font-weight:700;">#APP-<?php echo $app['id']; ?></td>
                            <td>
                                <div class="client-info">
                                    <div class="mini-avatar" style="background: #334155; color: #fff; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center; border-radius: 50%; margin-right: 10px; font-size: 12px; font-weight: bold;">
                                        <?php echo $initials; ?>
                                    </div>
                                    <span><?php echo htmlspecialchars($app['fullname']); ?></span>
                                </div>
                            </td>
                            <td><?php echo date("M d, Y", strtotime($app['updated_at'])); ?></td>
                            <td class="amount-highlight" style="color:#34d399; font-weight: bold;">₱ <?php echo number_format($app['principal_amount'], 2); ?></td>
                            <td><span class="badge-ready" style="background: rgba(96, 165, 250, 0.1); color: #60a5fa; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 600;">Ready for Release</span></td>
                            <td style="text-align:center;">
                                <button class="btn-release" style="background: #3b82f6; color: white; border: none; padding: 8px 16px; border-radius: 6px; cursor: pointer; font-weight: bold;" 
                                        onclick="openModal('<?php echo $app['id']; ?>', '<?php echo htmlspecialchars(addslashes($app['fullname'])); ?>', '<?php echo $app['principal_amount']; ?>')">
                                    <i class="bi bi-cash"></i> Release
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div id="releaseModal" class="modal-overlay" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); align-items: center; justify-content: center; z-index: 1000;">
        <div class="modal-box" style="background: #1e293b; padding: 25px; border-radius: 12px; width: 400px; border: 1px solid #334155;">
            <div class="modal-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <h3 style="color: #fff; margin: 0;">Release Funds</h3>
                <button class="close-modal" onclick="closeModal()" style="background: none; border: none; color: #94a3b8; font-size: 24px; cursor: pointer;">&times;</button>
            </div>
            
            <form method="POST" action="disbursement.php">
                <input type="hidden" name="action" value="release_funds">
                <input type="hidden" name="application_id" id="modalAppId">

                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 5px;">Client Name</label>
                    <input type="text" class="form-input" id="modalClient" readonly style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px;">
                </div>

                <div class="form-group" style="margin-bottom: 15px; background: #0f172a; padding: 15px; border-radius: 8px; border: 1px solid #334155;">
                    <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 10px;">Breakdown</label>
                    <div class="breakdown" style="font-size: 14px; color: #cbd5e1;">
                        <div class="bd-row" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Principal Amount</span>
                            <span id="bd-principal">₱ 0.00</span>
                        </div>
                        <div class="bd-row" style="display: flex; justify-content: space-between; margin-bottom: 8px;">
                            <span>Less: Processing Fee</span>
                            <span style="color:#f87171;">- ₱ 500.00</span>
                        </div>
                        <div class="bd-total" style="display: flex; justify-content: space-between; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #334155; font-weight: bold;">
                            <span>NET PROCEEDS</span>
                            <span id="bd-net" style="color:#34d399;">₱ 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 15px;">
                    <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 5px;">Disbursement Method</label>
                    <select name="disbursement_method" class="form-select" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px;" required>
                        <option value="CASH">Cash Release (OTC)</option>
                        <option value="CHECK">Check Issuance</option>
                        <option value="BANK">Bank Transfer / GCash</option>
                    </select>
                </div>

                <div class="form-group" style="margin-bottom: 25px;">
                    <label class="form-label" style="display: block; color: #94a3b8; font-size: 13px; margin-bottom: 5px;">Reference / Check No. (Optional)</label>
                    <input type="text" name="reference_no" class="form-input" placeholder="e.g. Check #123456" style="width: 100%; padding: 10px; background: #0f172a; border: 1px solid #334155; color: #fff; border-radius: 6px;">
                </div>

                <button type="submit" class="btn-confirm" style="width: 100%; background: #10b981; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                    <i class="bi bi-check-lg"></i> Confirm Release
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('releaseModal');
        
        function openModal(appId, client, amount) {
            // Set hidden ID and visible Client Name
            document.getElementById('modalAppId').value = appId;
            document.getElementById('modalClient').value = client;
            
            // Math for breakdown
            const principal = parseFloat(amount);
            const fee = 500; // Fixed fee for demo
            const net = principal - fee;

            document.getElementById('bd-principal').innerText = '₱ ' + principal.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            document.getElementById('bd-net').innerText = '₱ ' + net.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

            // Make modal flex so it centers
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Close when clicking outside the box
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }
    </script>

</body>
</html>