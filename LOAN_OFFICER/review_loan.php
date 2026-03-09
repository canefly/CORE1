<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
if (file_exists($connection_file)) { require_once $connection_file; } 
else { die("Error: Connection file not found."); }

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No Application ID provided.");
}

$app_id = intval($_GET['id']);

// 2. Handle Decision Submissions (POST Requests)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        if ($_POST['action'] === 'approve') {

            $pdo->beginTransaction();

            // Lock + fetch application details
            $stmtApp = $pdo->prepare("
                SELECT id, user_id, principal_amount, term_months, monthly_due,
                       interest_rate, interest_method, total_payable
                FROM loan_applications
                WHERE id = ?
                FOR UPDATE
            ");
            $stmtApp->execute([$app_id]);
            $appRow = $stmtApp->fetch(PDO::FETCH_ASSOC);

            if (!$appRow) {
                throw new Exception("Application not found.");
            }

            // 1) Update application status
            $stmtUpd = $pdo->prepare("
                UPDATE loan_applications
                SET status = 'APPROVED', updated_at = NOW()
                WHERE id = ?
            ");
            $stmtUpd->execute([$app_id]);

            // 2) Prevent duplicate loan record
            $stmtCheck = $pdo->prepare("SELECT id FROM loans WHERE application_id = ? LIMIT 1");
            $stmtCheck->execute([$app_id]);
            $existingLoanId = $stmtCheck->fetchColumn();

            if (!$existingLoanId) {
                $principal  = (float)$appRow['principal_amount'];
                $termMonths = (int)$appRow['term_months'];
                $monthlyDue = $appRow['monthly_due'] !== null ? (float)$appRow['monthly_due'] : 0.0;
                $outstanding = $appRow['total_payable'] !== null ? (float)$appRow['total_payable'] : $principal;

                if ($monthlyDue <= 0 && $termMonths > 0) {
                    $monthlyDue = $outstanding / $termMonths;
                }

                $startDate   = date('Y-m-d');
                $nextPayment = date('Y-m-d', strtotime('+1 month'));
                $dueDate     = date('Y-m-d', strtotime("+{$termMonths} months"));

                // Insert into loans
                $stmtIns = $pdo->prepare("
                    INSERT INTO loans
                        (user_id, application_id, loan_amount, term_months, monthly_due,
                         interest_rate, interest_method, outstanding, next_payment, due_date,
                         start_date, status)
                    VALUES
                        (:user_id, :application_id, :loan_amount, :term_months, :monthly_due,
                         :interest_rate, :interest_method, :outstanding, :next_payment, :due_date,
                         :start_date, 'ACTIVE')
                ");

                $stmtIns->execute([
                    ':user_id'         => (int)$appRow['user_id'],
                    ':application_id'  => (int)$appRow['id'],
                    ':loan_amount'     => $principal,
                    ':term_months'     => $termMonths,
                    ':monthly_due'     => $monthlyDue,
                    ':interest_rate'   => (float)$appRow['interest_rate'],
                    ':interest_method' => $appRow['interest_method'] ?? 'FLAT',
                    ':outstanding'     => $outstanding,
                    ':next_payment'    => $nextPayment,
                    ':due_date'        => $dueDate,
                    ':start_date'      => $startDate,
                ]);
            }

            // 3) INSERT NOTIFICATION FOR APPROVAL
            $notif_title = "Loan Application Approved";
            $notif_msg = "Congratulations! Your loan application <strong>#LA-{$app_id}</strong> has been formally approved by the Loan Officer. The funds are now queued for disbursement.";
            $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, icon, link) VALUES (?, ?, ?, 'success', 'bi-patch-check-fill', 'myloans.php?app_id={$app_id}')");           
            $stmtNotif->execute([(int)$appRow['user_id'], $notif_title, $notif_msg]);

            $pdo->commit();

            header("Location: dashboard.php?msg=approved");
            exit;

        } elseif ($_POST['action'] === 'reject') {

            $reason = trim($_POST['rejection_reason']);

            // Fetch user_id before updating
            $stmtUser = $pdo->prepare("SELECT user_id FROM loan_applications WHERE id = ?");
            $stmtUser->execute([$app_id]);
            $user_id = $stmtUser->fetchColumn();

            $stmt = $pdo->prepare("
                UPDATE loan_applications
                SET status = 'REJECTED', remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $app_id]);

            // INSERT NOTIFICATION FOR REJECTION
            if ($user_id) {
                $notif_title = "Loan Application Declined";
                $notif_msg = "We regret to inform you that your application <strong>#LA-{$app_id}</strong> was declined after review. Reason: " . htmlspecialchars($reason);
                $stmtNotif = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, icon, link) VALUES (?, ?, ?, 'danger', 'bi-x-circle-fill', 'myloans.php?app_id={$app_id}')");                
                $stmtNotif->execute([$user_id, $notif_title, $notif_msg]);
            }

            header("Location: dashboard.php?msg=rejected");
            exit;
        }

    } catch (Throwable $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = "Error: " . $e->getMessage();
    }
}

// 3. Fetch Application Data
try {
    $stmt = $pdo->prepare("
        SELECT la.*, u.fullname, u.phone, u.email 
        FROM loan_applications la 
        JOIN users u ON la.user_id = u.id 
        WHERE la.id = ?
    ");
    $stmt->execute([$app_id]);
    $app = $stmt->fetch();

    if (!$app) { die("Application not found."); }

    // Fetch Documents
    $stmtDocs = $pdo->prepare("SELECT * FROM loan_documents WHERE loan_application_id = ?");
    $stmtDocs->execute([$app_id]);
    $documents = $stmtDocs->fetchAll();

    // 4. Calculate Risk Metrics
    $principal = $app['principal_amount'];
    $income = $app['estimated_monthly_income'] ?: 1;

    $monthly_due = $app['monthly_due'];
    if (!$monthly_due) {
        $monthly_due = ($principal + ($principal * ($app['interest_rate']/100) * $app['term_months'])) / $app['term_months'];
    }

    $dti = ($monthly_due / $income) * 100;

    if ($dti < 20) { $risk_level = 'LOW RISK'; $risk_color = '#34d399'; }
    elseif ($dti < 40) { $risk_level = 'MEDIUM RISK'; $risk_color = '#fbbf24'; }
    else { $risk_level = 'HIGH RISK'; $risk_color = '#f87171'; }

} catch (PDOException $e) {
    die("Query Failed: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Review Application</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/Review_loan.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Application Dossier: #LA-<?php echo str_pad($app['id'], 4, '0', STR_PAD_LEFT); ?></h1>
                <p>Review the borrower's profile and financials to make a decision.</p>
            </div>
            <a href="dashboard.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Queue</a>
        </div>

        <?php if(isset($error_message)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="dossier-grid">
            
            <div class="left-col">
                
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-person-badge"></i> Borrower Profile</div>
                    <div class="data-row">
                        <span class="data-label">Full Name</span>
                        <span class="data-value"><?php echo htmlspecialchars($app['fullname']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Contact Number</span>
                        <span class="data-value"><?php echo htmlspecialchars($app['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Source of Income</span>
                        <span class="data-value"><?php echo htmlspecialchars($app['source_of_income']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Declared Monthly Income</span>
                        <span class="data-value val-good">₱ <?php echo number_format($app['estimated_monthly_income'], 2); ?></span>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-file-earmark-text"></i> Loan Request Details</div>
                    <div class="data-row">
                        <span class="data-label">Principal Amount</span>
                        <span class="data-value val-highlight">₱ <?php echo number_format($principal, 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Requested Term</span>
                        <span class="data-value"><?php echo $app['term_months']; ?> Months</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Loan Purpose</span>
                        <span class="data-value"><?php echo htmlspecialchars($app['loan_purpose']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Estimated Monthly Payment</span>
                        <span class="data-value val-bad">₱ <?php echo number_format($monthly_due, 2); ?></span>
                    </div>
                </div>

               <div class="panel">
                    <div class="panel-title"><i class="bi bi-folder-check"></i> Verification Documents</div>
                    <div class="doc-grid">
                        <?php if (empty($documents)): ?>
                            <p style="color:#94a3b8; font-size: 13px;">No documents uploaded.</p>
                        <?php else: ?>
                            <?php foreach($documents as $doc): 
                                $docName = str_replace('_', ' ', $doc['doc_type']);
                            ?>
                            <div class="doc-card">
                                <div class="doc-icon"><i class="bi bi-file-earmark-image"></i></div>
                                <div class="doc-name"><?php echo htmlspecialchars($docName); ?></div>
                                <a href="../../client/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn-view-doc">View File</a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

            </div>

            <div class="right-col">
                
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-shield-check"></i> Risk Assessment</div>
                    
                    <div class="risk-box">
                        <div style="font-size: 13px; color: #94a3b8; text-transform: uppercase; font-weight: bold;">System Rating</div>
                        <div class="risk-score" style="color: <?php echo $risk_color; ?>;"><?php echo $risk_level; ?></div>
                        <div style="font-size: 13px; color: #94a3b8;">Based on Debt-to-Income Ratio</div>
                    </div>

                    <div class="data-row">
                        <span class="data-label">DTI Ratio:</span>
                        <span class="data-value" style="color: <?php echo $risk_color; ?>;"><?php echo round($dti, 1); ?>%</span>
                    </div>
                    <p style="font-size: 12px; color: #64748b; line-height: 1.5; margin-top: 15px;">
                        * A ratio under 20% is ideal. If the ratio exceeds 40%, the borrower may struggle to meet monthly obligations.
                    </p>
                </div>

                <?php if ($app['status'] === 'VERIFIED'): ?>
                <div class="panel" style="border-color: #3b82f6;">
                    <div class="panel-title" style="color: #60a5fa;"><i class="bi bi-hammer"></i> Official Decision</div>
                    
                    <form method="POST" onsubmit="return confirm('Are you sure you want to APPROVE this loan? This action will immediately move it to the Disbursement Queue.');">
                        <input type="hidden" name="action" value="approve">
                        <button type="submit" class="btn-approve">
                            <i class="bi bi-check-circle-fill"></i> Approve Application
                        </button>
                    </form>

                    <button type="button" class="btn-reject" onclick="openRejectModal()">
                        <i class="bi bi-x-circle-fill"></i> Reject Application
                    </button>
                </div>
                <?php else: ?>
                    <div class="panel">
                        <div class="panel-title">Current Status</div>
                        <h2 style="color: <?php echo ($app['status'] == 'APPROVED') ? '#34d399' : '#f87171'; ?>; text-align: center; margin-bottom: 10px;">
                            <?php echo $app['status']; ?>
                        </h2>
                        <?php if ($app['status'] == 'REJECTED' && !empty($app['remarks'])): ?>
                            <p style="text-align: center; font-size: 13px; color: #94a3b8;">Reason: <?php echo htmlspecialchars($app['remarks']); ?></p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>
    </div>

    <div id="rejectModal" class="modal-overlay">
        <div class="modal-box">
            <h3 style="color: #f87171; margin-bottom: 15px;"><i class="bi bi-exclamation-triangle"></i> Reject Application</h3>
            <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px;">Please provide a reason for rejecting this application. This will be recorded in the system.</p>
            
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <textarea name="rejection_reason" class="form-input" placeholder="e.g., Unverifiable income, Blurry ID, High Debt-to-Income ratio..." required></textarea>
                
                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="closeRejectModal()" style="flex: 1; padding: 12px; background: #334155; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Cancel</button>
                    <button type="submit" style="flex: 1; padding: 12px; background: #f87171; color: #fff; border: none; border-radius: 6px; cursor: pointer; font-weight: bold;">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('rejectModal');
        function openRejectModal() { modal.style.display = 'flex'; }
        function closeRejectModal() { modal.style.display = 'none'; }
        window.onclick = function(e) { if (e.target == modal) closeRejectModal(); }
    </script>

</body>
</html>