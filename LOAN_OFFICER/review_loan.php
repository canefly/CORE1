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

            // 1️⃣ Kunin loan application
            $stmt = $pdo->prepare("
                SELECT *
                FROM loan_applications
                WHERE id = ?
                FOR UPDATE
            ");
            $stmt->execute([$app_id]);
            $app = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$app) {
                throw new Exception("Loan application not found.");
            }

            if ($app['status'] !== 'VERIFIED') {
                throw new Exception("Only VERIFIED applications can be approved.");
            }

            // 2️⃣ Update application status
            $stmt = $pdo->prepare("
                UPDATE loan_applications
                SET status='APPROVED', updated_at=NOW()
                WHERE id=?
            ");
            $stmt->execute([$app_id]);

            // ==============================
            // 3️⃣ Insert into loan_disbursement
            // ==============================

            $stmtCheck = $pdo->prepare("
                SELECT id
                FROM loan_disbursement
                WHERE application_id=?
                LIMIT 1
            ");
            $stmtCheck->execute([$app_id]);

            if (!$stmtCheck->fetchColumn()) {

                $stmtInsert = $pdo->prepare("
                    INSERT INTO loan_disbursement
                    (
                        application_id,
                        user_id,
                        principal_amount,
                        term_months,
                        loan_purpose,
                        source_of_income,
                        estimated_monthly_income,
                        interest_rate,
                        interest_type,
                        interest_method,
                        total_interest,
                        total_payable,
                        monthly_due,
                        status
                    )
                    VALUES
                    (
                        :application_id,
                        :user_id,
                        :principal_amount,
                        :term_months,
                        :loan_purpose,
                        :source_of_income,
                        :estimated_monthly_income,
                        :interest_rate,
                        :interest_type,
                        :interest_method,
                        :total_interest,
                        :total_payable,
                        :monthly_due,
                        'WAITING FOR DISBURSEMENT'
                    )
                ");

                $stmtInsert->execute([
                    ':application_id'=>$app['id'],
                    ':user_id'=>$app['user_id'],
                    ':principal_amount'=>$app['principal_amount'],
                    ':term_months'=>$app['term_months'],
                    ':loan_purpose'=>$app['loan_purpose'],
                    ':source_of_income'=>$app['source_of_income'],
                    ':estimated_monthly_income'=>$app['estimated_monthly_income'],
                    ':interest_rate'=>$app['interest_rate'],
                    ':interest_type'=>$app['interest_type'],
                    ':interest_method'=>$app['interest_method'],
                    ':total_interest'=>$app['total_interest'],
                    ':total_payable'=>$app['total_payable'],
                    ':monthly_due'=>$app['monthly_due']
                ]);
            }

            // ==============================
            // 4️⃣ Insert into loans
            // ==============================

            $stmtCheckLoan = $pdo->prepare("
                SELECT id
                FROM loans
                WHERE application_id=?
                LIMIT 1
            ");
            $stmtCheckLoan->execute([$app_id]);

            if (!$stmtCheckLoan->fetchColumn()) {

                $principal = (float)$app['principal_amount'];
                $term = (int)$app['term_months'];

                $monthly_due = (float)$app['monthly_due'];

                $total_payable = (float)$app['total_payable'];

                $outstanding = $total_payable;

                $start_date = date("Y-m-d");
                $next_payment = date("Y-m-d",strtotime("+1 month"));
                $due_date = date("Y-m-d",strtotime("+{$term} months"));

                $stmtInsertLoan = $pdo->prepare("
                    INSERT INTO loans
                    (
                        user_id,
                        application_id,
                        loan_amount,
                        term_months,
                        monthly_due,
                        interest_rate,
                        interest_method,
                        outstanding,
                        next_payment,
                        due_date,
                        start_date,
                        status
                    )
                    VALUES
                    (
                        :user_id,
                        :application_id,
                        :loan_amount,
                        :term_months,
                        :monthly_due,
                        :interest_rate,
                        :interest_method,
                        :outstanding,
                        :next_payment,
                        :due_date,
                        :start_date,
                        'ACTIVE'
                    )
                ");

                $stmtInsertLoan->execute([
                    ':user_id'=>$app['user_id'],
                    ':application_id'=>$app['id'],
                    ':loan_amount'=>$principal,
                    ':term_months'=>$term,
                    ':monthly_due'=>$monthly_due,
                    ':interest_rate'=>$app['interest_rate'],
                    ':interest_method'=>$app['interest_method'],
                    ':outstanding'=>$outstanding,
                    ':next_payment'=>$next_payment,
                    ':due_date'=>$due_date,
                    ':start_date'=>$start_date
                ]);
            }

            $pdo->commit();

            header("Location: dashboard.php?msg=approved");
            exit;
        }


         elseif ($_POST['action'] === 'reject') {

            $reason = trim($_POST['rejection_reason']);

            $stmt = $pdo->prepare("
                UPDATE loan_applications
                SET status = 'REJECTED', remarks = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$reason, $app_id]);

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
                                // Clean up the doc type name
                                $docName = str_replace('_', ' ', $doc['doc_type']);
                            ?>
                            <div class="doc-card">
                                <div class="doc-icon"><i class="bi bi-file-earmark-image"></i></div>
                                <div class="doc-name"><?php echo htmlspecialchars($docName); ?></div>
                                <a href="../client/<?php echo htmlspecialchars($doc['file_path']); ?>" target="_blank" class="btn-view-doc">View File</a>
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