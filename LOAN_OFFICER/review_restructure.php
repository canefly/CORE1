<?php
session_start();
require_once __DIR__ . "/includes/db_connect.php";
require_once __DIR__ . '/includes/session_checker.php';

if (!isset($pdo)) {
    die("Database connection \$pdo is missing.");
}

// 🚨 FIX 1: Ginamit ang tamang Session Variable para kay Loan Officer!
$lo_id = (int)($_SESSION['admin_id'] ?? 0);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No Restructure Request ID provided.");
}

$request_id = (int)$_GET['id'];
$error_message = '';

if ($request_id <= 0) {
    die("Invalid restructure request ID.");
}

/* =========================
   HANDLE APPROVE / REJECT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = trim($_POST['action'] ?? '');
    $notes  = trim($_POST['notes'] ?? '');

    try {
        $pdo->beginTransaction();

        // Lock request
        $stmtReq = $pdo->prepare("
            SELECT rr.*, u.fullname
            FROM loan_restructure_requests rr
            JOIN users u ON rr.user_id = u.id
            WHERE rr.id = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmtReq->execute([$request_id]);
        $reqLock = $stmtReq->fetch();

        if (!$reqLock) {
            throw new Exception("Restructure request not found.");
        }

        if (($reqLock['status'] ?? '') !== 'VERIFIED') {
            throw new Exception("Only VERIFIED requests can be processed by Loan Officer.");
        }

        // Lock loan
        $stmtLoan = $pdo->prepare("
            SELECT *
            FROM loans
            WHERE id = ?
              AND user_id = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmtLoan->execute([(int)$reqLock['loan_id'], (int)$reqLock['user_id']]);
        $loan = $stmtLoan->fetch();

        if (!$loan) {
            throw new Exception("Original loan record not found.");
        }

        if (($loan['status'] ?? '') !== 'ACTIVE') {
            throw new Exception("Only ACTIVE loans can be restructured.");
        }

        if ((float)$loan['outstanding'] <= 0) {
            throw new Exception("Loan has no outstanding balance to restructure.");
        }

        if ($action === 'approve') {
            // Updated Check para sa admin_id
            if ($lo_id <= 0) {
                throw new Exception("Loan Officer session is missing. Please log in again.");
            }

            // Final re-check for pending payment
            $stmtPending = $pdo->prepare("
                SELECT id
                FROM transactions
                WHERE loan_id = ?
                  AND user_id = ?
                  AND status = 'PAID_PENDING'
                LIMIT 1
            ");
            $stmtPending->execute([(int)$loan['id'], (int)$loan['user_id']]);
            $pendingTx = $stmtPending->fetch();

            if ($pendingTx) {
                throw new Exception("Cannot approve restructure while payment is still pending verification.");
            }

            $principalAmount = (float)$loan['outstanding']; // OUTSTANDING ang base
            $newTerm         = (int)$reqLock['requested_term_months'];
            $newMonthly      = (float)$reqLock['estimated_monthly_due'];
            $interestRate    = (float)$loan['interest_rate'];
            $interestMethod  = (string)$loan['interest_method'];

            if ($newTerm <= 0) {
                throw new Exception("Invalid approved term.");
            }

            if ($newMonthly <= 0) {
                throw new Exception("Invalid approved monthly due.");
            }

            // 1) archive old loan
            $stmtOldLoan = $pdo->prepare("
                UPDATE loans
                SET status = 'RESTRUCTURED'
                WHERE id = ?
                  AND status = 'ACTIVE'
                LIMIT 1
            ");
            $stmtOldLoan->execute([(int)$loan['id']]);

            if ($stmtOldLoan->rowCount() <= 0) {
                throw new Exception("Failed to mark original loan as RESTRUCTURED.");
            }

            // 2) create new restructured loan
            $stmtInsertNew = $pdo->prepare("
                INSERT INTO restructured_loans (
                    original_loan_id,
                    restructure_request_id,
                    user_id,
                    principal_amount,
                    term_months,
                    monthly_due,
                    interest_rate,
                    interest_method,
                    outstanding,
                    start_date,
                    next_payment,
                    due_date,
                    status,
                    created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, CURDATE(), NULL, NULL, 'ACTIVE', NOW())
            ");

            $stmtInsertNew->execute([
                (int)$loan['id'],
                (int)$reqLock['id'],
                (int)$loan['user_id'],
                $principalAmount,
                $newTerm,
                $newMonthly,
                $interestRate,
                $interestMethod,
                $principalAmount
            ]);

            // 3) approve request
            $stmtApproveReq = $pdo->prepare("
                UPDATE loan_restructure_requests
                SET
                    status = 'APPROVED',
                    reviewed_by = ?,
                    reviewed_at = NOW(),
                    review_notes = ?
                WHERE id = ?
                  AND status = 'VERIFIED'
                LIMIT 1
            ");
            $stmtApproveReq->execute([$lo_id, $notes, $request_id]);

            if ($stmtApproveReq->rowCount() <= 0) {
                throw new Exception("Failed to update request to APPROVED.");
            }

            $pdo->commit();
            header("Location: restructure.php?approved=1");
            exit;
        }

        if ($action === 'reject') {
            if ($notes === '') {
                throw new Exception("Rejection notes are required.");
            }

            $stmtReject = $pdo->prepare("
                UPDATE loan_restructure_requests
                SET
                    status = 'REJECTED',
                    reviewed_by = ?,
                    reviewed_at = NOW(),
                    review_notes = ?
                WHERE id = ?
                  AND status = 'VERIFIED'
                LIMIT 1
            ");
            $stmtReject->execute([$lo_id, $notes, $request_id]);

            if ($stmtReject->rowCount() <= 0) {
                throw new Exception("Failed to reject restructure request.");
            }

            $pdo->commit();
            header("Location: restructure.php?rejected=1");
            exit;
        }

        throw new Exception("Invalid action.");
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $error_message = $e->getMessage();
    }
}

/* =========================
   FETCH PAGE DATA
========================= */
$stmt = $pdo->prepare("
    SELECT
        rr.*,
        u.fullname,
        u.phone,
        l.loan_amount,
        l.outstanding AS current_outstanding_now,
        l.term_months AS current_term_now,
        l.monthly_due AS current_monthly_now,
        l.interest_rate AS current_interest_rate_now,
        l.interest_method AS current_interest_method_now,
        l.status AS loan_status,
        l.start_date,
        l.next_payment,
        l.due_date
    FROM loan_restructure_requests rr
    JOIN users u ON rr.user_id = u.id
    JOIN loans l ON rr.loan_id = l.id
    WHERE rr.id = ?
    LIMIT 1
");
$stmt->execute([$request_id]);
$req = $stmt->fetch();

if (!$req) {
    die("Restructure request not found.");
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$hasPendingPayment = false;
$stmtPendingView = $pdo->prepare("
    SELECT id
    FROM transactions
    WHERE loan_id = ?
      AND user_id = ?
      AND status = 'PAID_PENDING'
    LIMIT 1
");
$stmtPendingView->execute([(int)$req['loan_id'], (int)$req['user_id']]);
$hasPendingPayment = (bool)$stmtPendingView->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LO | Review Restructure</title>

    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <link rel="stylesheet" href="assets/css/review_restructure.css?v=<?php echo time(); ?>">
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
            <div>
                <h1>Restructure Dossier: #RR-<?php echo str_pad((int)$req['id'], 4, '0', STR_PAD_LEFT); ?></h1>
                <p>Final Loan Officer evaluation for restructuring request.</p>
            </div>
            <a href="restructure.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Queue</a>
        </div>

        <?php if ($error_message): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo e($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="dossier-grid">
            <div class="left-col">
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-person-badge"></i> Client Profile</div>
                    <div class="data-row">
                        <span class="data-label">Full Name</span>
                        <span class="data-value"><?php echo e($req['fullname']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Contact Number</span>
                        <span class="data-value"><?php echo e($req['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Request Status</span>
                        <span class="data-value val-highlight"><?php echo e($req['status']); ?></span>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-file-earmark-text"></i> Current Loan Snapshot</div>
                    <div class="data-row">
                        <span class="data-label">Loan ID</span>
                        <span class="data-value">LN-<?php echo str_pad((int)$req['loan_id'], 4, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Original Loan Amount</span>
                        <span class="data-value">₱ <?php echo number_format((float)$req['loan_amount'], 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Outstanding</span>
                        <span class="data-value val-good">₱ <?php echo number_format((float)$req['current_outstanding_now'], 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Term</span>
                        <span class="data-value"><?php echo (int)$req['current_term_now']; ?> months</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Monthly Due</span>
                        <span class="data-value">₱ <?php echo number_format((float)$req['current_monthly_now'], 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Interest Rate</span>
                        <span class="data-value"><?php echo number_format((float)$req['current_interest_rate_now'], 2); ?>%</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Interest Method</span>
                        <span class="data-value"><?php echo e($req['current_interest_method_now']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Loan Status</span>
                        <span class="data-value"><?php echo e($req['loan_status']); ?></span>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-arrow-repeat"></i> Requested Restructure Details</div>
                    <div class="data-row">
                        <span class="data-label">Restructure Type</span>
                        <span class="data-value"><?php echo e($req['restructure_type']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Outstanding Snapshot Basis</span>
                        <span class="data-value val-good">₱ <?php echo number_format((float)$req['outstanding_snapshot'], 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Term (at request time)</span>
                        <span class="data-value"><?php echo (int)$req['current_term_months']; ?> months</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Requested Term</span>
                        <span class="data-value val-highlight"><?php echo (int)$req['requested_term_months']; ?> months</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Monthly Due</span>
                        <span class="data-value">₱ <?php echo number_format((float)$req['current_monthly_due'], 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Estimated New Monthly Due</span>
                        <span class="data-value val-good">₱ <?php echo number_format((float)$req['estimated_monthly_due'], 2); ?></span>
                    </div>

                    <div class="note-box">
                        <div class="note-title">Client Reason</div>
                        <div class="note-body"><?php echo nl2br(e($req['reason'])); ?></div>
                    </div>

                    <?php if (!empty($req['verifier_notes'])): ?>
                        <div class="note-box" style="margin-top:15px;">
                            <div class="note-title">LSA Verification Notes</div>
                            <div class="note-body"><?php echo nl2br(e($req['verifier_notes'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-folder2-open"></i> Submitted Proof</div>
                    <div class="doc-grid">
                        <div class="doc-card">
                            <div class="doc-icon"><i class="bi bi-file-earmark-medical"></i></div>
                            <div class="doc-name">Hardship Proof</div>
                            <?php if (!empty($req['proof_doc_path'])): ?>
                                <a class="btn-view-doc" href="../client/<?php echo e($req['proof_doc_path']); ?>" target="_blank">View Document</a>
                            <?php else: ?>
                                <span class="no-doc">No document uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-clipboard-check"></i> Restructure Assessment</div>

                    <div class="status-box">
                        <div class="status-label">Current Queue Stage</div>
                        <div class="status-value">VERIFIED</div>
                        <p class="status-help">
                            This request was already verified by LSA and is now awaiting final Loan Officer decision.
                        </p>
                    </div>

                    <?php if ($hasPendingPayment): ?>
                        <div class="alert-error" style="margin-top:0;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            This loan currently has a payment with status <strong>PAID_PENDING</strong>. Approval is blocked until payment verification is completed.
                        </div>
                    <?php endif; ?>

                    <div class="note-box" style="margin-top:15px;">
                        <div class="note-title">Approval Summary</div>
                        <div class="note-body">
                            <strong>Basis of restructure:</strong> current outstanding amount only.<br>
                            <strong>Old loan status after approval:</strong> RESTRUCTURED.<br>
                            <strong>New active loan record:</strong> will be created in restructured_loans.
                        </div>
                    </div>

                    <form method="POST" style="margin-top:20px;">
                        <input type="hidden" name="action" value="approve">
                        <label class="form-label">Loan Officer Notes</label>
                        <textarea name="notes" class="form-input" placeholder="Optional approval notes..."></textarea>
                        <button type="submit" class="btn-approve" <?php echo $hasPendingPayment ? 'disabled style="opacity:.6;cursor:not-allowed;"' : ''; ?>>
                            <i class="bi bi-check-circle-fill"></i> Approve Request
                        </button>
                    </form>

                    <button type="button" class="btn-reject" onclick="openRejectModal()">
                        <i class="bi bi-x-circle-fill"></i> Reject Request
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal-overlay" id="rejectModal">
        <div class="modal-box">
            <h3 class="modal-title">Reject Restructure Request</h3>
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <label class="form-label">Reason for Rejection</label>
                <textarea name="notes" class="form-input" placeholder="Explain why the request is being rejected..." required></textarea>
                <div class="modal-actions">
                    <button type="button" class="btn-cancel" onclick="closeRejectModal()">Cancel</button>
                    <button type="submit" class="btn-confirm-reject">Confirm Reject</button>
                </div>
            </form>
        </div>
    </div>

    <script src="assets/js/review_restructure.js?v=<?php echo time(); ?>"></script>
</body>
</html>