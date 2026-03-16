<?php
session_start();
require_once __DIR__ . "/includes/db_connect.php";

$lsa_id = (int)($_SESSION['user_id'] ?? $_SESSION['account_id'] ?? 0);

if (!isset($_GET['id']) || empty($_GET['id'])) {
    die("Error: No Restructure Request ID provided.");
}

$request_id = (int)$_GET['id'];
$error_message = '';
$success_message = '';

if ($request_id <= 0) {
    die("Invalid restructure request ID.");
}

/* =========================
   HANDLE VERIFY / REJECT
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = trim($_POST['action'] ?? '');
    $notes  = trim($_POST['notes'] ?? '');

    $pdo->beginTransaction();

    try {
        $stmtReq = $pdo->prepare("
            SELECT
                rr.*,
                u.fullname,
                u.phone,
                l.status AS loan_status
            FROM loan_restructure_requests rr
            JOIN users u ON rr.user_id = u.id
            JOIN loans l ON rr.loan_id = l.id
            WHERE rr.id = ?
            LIMIT 1
            FOR UPDATE
        ");

        if (!$stmtReq) {
            $errorInfo = $pdo->errorInfo();
            throw new Exception("Prepare failed: " . $errorInfo[2]);
        }

        if (!$stmtReq->execute([$request_id])) {
            $errorInfo = $stmtReq->errorInfo();
            throw new Exception("Execute failed: " . $errorInfo[2]);
        }

        $req = $stmtReq->fetch(PDO::FETCH_ASSOC);

        if (!$req) {
            throw new Exception("Restructure request not found.");
        }

        if (($req['status'] ?? '') !== 'PENDING') {
            throw new Exception("Only PENDING requests can be processed by LSA.");
        }

        if ($action === 'verify') {
            if (empty($req['reason'])) {
                throw new Exception("Cannot verify request with missing reason.");
            }

            if (empty($req['proof_doc_path'])) {
                throw new Exception("Cannot verify request with missing proof document.");
            }

            $stmtUpd = $pdo->prepare("
                UPDATE loan_restructure_requests
                SET
                    status = 'VERIFIED',
                    verified_by = ?,
                    verified_at = NOW(),
                    verifier_notes = ?
                WHERE id = ?
                  AND status = 'PENDING'
                LIMIT 1
            ");

            if (!$stmtUpd) {
                $errorInfo = $pdo->errorInfo();
                throw new Exception("Prepare failed: " . $errorInfo[2]);
            }

            if (!$stmtUpd->execute([$lsa_id, $notes, $request_id])) {
                $errorInfo = $stmtUpd->errorInfo();
                throw new Exception("Failed to verify request: " . $errorInfo[2]);
            }

            $pdo->commit();

            header("Location: restructure.php?verified=1");
            exit;
        }

        if ($action === 'reject') {
            if ($notes === '') {
                throw new Exception("Rejection notes are required.");
            }

            $stmtUpd = $pdo->prepare("
                UPDATE loan_restructure_requests
                SET
                    status = 'REJECTED',
                    verified_by = ?,
                    verified_at = NOW(),
                    verifier_notes = ?
                WHERE id = ?
                  AND status = 'PENDING'
                LIMIT 1
            ");

            if (!$stmtUpd) {
                $errorInfo = $pdo->errorInfo();
                throw new Exception("Prepare failed: " . $errorInfo[2]);
            }

            if (!$stmtUpd->execute([$lsa_id, $notes, $request_id])) {
                $errorInfo = $stmtUpd->errorInfo();
                throw new Exception("Failed to reject request: " . $errorInfo[2]);
            }

            $pdo->commit();

            header("Location: restructure.php?rejected=1");
            exit;
        }

        throw new Exception("Invalid action.");
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

/* =========================
   FETCH REQUEST DETAILS
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

if (!$stmt) {
    $errorInfo = $pdo->errorInfo();
    die("Prepare failed: " . $errorInfo[2]);
}

if (!$stmt->execute([$request_id])) {
    $errorInfo = $stmt->errorInfo();
    die("Execute failed: " . $errorInfo[2]);
}

$req = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$req) {
    die("Restructure request not found.");
}

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Review Restructure</title>
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
                <p>Validate the submitted restructuring requirements before forwarding to Loan Officer.</p>
            </div>
            <a href="restructure_requests.php" class="btn-back"><i class="bi bi-arrow-left"></i> Back to Queue</a>
        </div>

        <?php if ($error_message): ?>
            <div class="alert-error">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="dossier-grid">
            <div class="left-col">
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-person-badge"></i> Client Profile</div>
                    <div class="data-row">
                        <span class="data-label">Full Name</span>
                        <span class="data-value"><?php echo htmlspecialchars($req['fullname']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Contact Number</span>
                        <span class="data-value"><?php echo htmlspecialchars($req['phone'] ?? 'N/A'); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Request Status</span>
                        <span class="data-value val-highlight"><?php echo htmlspecialchars($req['status']); ?></span>
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
                        <span class="data-label">Outstanding Basis for Restructure</span>
                        <span class="data-value val-good">₱ <?php echo number_format((float)$req['outstanding_snapshot'], 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Term</span>
                        <span class="data-value"><?php echo (int)$req['current_term_months']; ?> months</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Current Monthly Due</span>
                        <span class="data-value">₱ <?php echo number_format((float)$req['current_monthly_due'], 2); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Interest Rate</span>
                        <span class="data-value"><?php echo number_format((float)$req['interest_rate_snapshot'], 2); ?>%</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Interest Method</span>
                        <span class="data-value"><?php echo htmlspecialchars($req['interest_method_snapshot']); ?></span>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-arrow-repeat"></i> Requested Restructure Details</div>
                    <div class="data-row">
                        <span class="data-label">Restructure Type</span>
                        <span class="data-value"><?php echo htmlspecialchars($req['restructure_type']); ?></span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Requested Term</span>
                        <span class="data-value val-highlight"><?php echo (int)$req['requested_term_months']; ?> months</span>
                    </div>
                    <div class="data-row">
                        <span class="data-label">Estimated New Monthly Due</span>
                        <span class="data-value val-good">₱ <?php echo number_format((float)$req['estimated_monthly_due'], 2); ?></span>
                    </div>
                    <div class="note-box">
                        <div class="note-title">Client Reason</div>
                        <div class="note-body"><?php echo nl2br(htmlspecialchars($req['reason'])); ?></div>
                    </div>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-folder2-open"></i> Submitted Proof</div>
                    <div class="doc-grid">
                        <div class="doc-card">
                            <div class="doc-icon"><i class="bi bi-file-earmark-medical"></i></div>
                            <div class="doc-name">Hardship Proof</div>
                            <?php if (!empty($req['proof_doc_path'])): ?>
                                <a class="btn-view-doc" href="<?php echo htmlspecialchars($req['proof_doc_path']); ?>" target="_blank">View Document</a>
                            <?php else: ?>
                                <span class="no-doc">No document uploaded</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="right-col">
                <div class="panel">
                    <div class="panel-title"><i class="bi bi-clipboard-check"></i> LSA Decision Panel</div>

                    <div class="status-box">
                        <div class="status-label">Current Queue Stage</div>
                        <div class="status-value">PENDING</div>
                        <p class="status-help">Verify only if the requirements are complete and valid. Verified requests will be forwarded to Loan Officer.</p>
                    </div>

                    <form method="POST" id="verifyForm">
                        <input type="hidden" name="action" value="verify">
                        <label class="form-label">Verification Notes</label>
                        <textarea name="notes" class="form-input" placeholder="Optional notes for Loan Officer..."></textarea>
                        <button type="submit" class="btn-approve">
                            <i class="bi bi-check-circle-fill"></i> Verify Request
                        </button>
                    </form>

                    <button type="button" class="btn-reject" onclick="openRejectModal()">
                        <i class="bi bi-x-circle-fill"></i> Reject Request
                    </button>
                </div>

                <div class="panel">
                    <div class="panel-title"><i class="bi bi-shield-check"></i> Verification Checklist</div>
                    <div class="checklist">
                        <div class="check-item"><i class="bi bi-check2-square"></i> Proof document uploaded</div>
                        <div class="check-item"><i class="bi bi-check2-square"></i> Request has valid reason</div>
                        <div class="check-item"><i class="bi bi-check2-square"></i> Outstanding amount is captured</div>
                        <div class="check-item"><i class="bi bi-check2-square"></i> Restructure details are complete</div>
                    </div>
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