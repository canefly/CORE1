<?php
session_start();
require_once __DIR__ . "/include/config.php";
include __DIR__ . "/include/session_checker.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$requests = [];

$sql = "
    SELECT
        rr.id,
        rr.loan_id,
        rr.restructure_type,
        rr.outstanding_snapshot,
        rr.current_term_months,
        rr.requested_term_months,
        rr.current_monthly_due,
        rr.estimated_monthly_due,
        rr.interest_rate_snapshot,
        rr.interest_method_snapshot,
        rr.reason,
        rr.proof_doc_path,
        rr.status,
        rr.review_notes,
        rr.reviewed_at,
        rr.created_at,

        l.outstanding AS current_outstanding,
        l.term_months AS current_term_now,
        l.monthly_due AS current_monthly_now,
        l.next_payment,
        l.due_date,
        l.status AS loan_status

    FROM loan_restructure_requests rr
    LEFT JOIN loans l ON l.id = rr.loan_id
    WHERE rr.user_id = ?
    ORDER BY rr.created_at DESC, rr.id DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

while ($row = $res->fetch_assoc()) {
    $requests[] = $row;
}
$stmt->close();

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function peso($value): string
{
    return '₱ ' . number_format((float)$value, 2);
}

function statusClass($status): string
{
    $status = strtoupper((string)$status);

    return match ($status) {
        'APPROVED' => 'status-approved',
        'REJECTED' => 'status-rejected',
        'CANCELLED' => 'status-cancelled',
        default => 'status-pending',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Restructure Requests | MicroFinance</title>

    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/restructure_requests.css?v=<?php echo time(); ?>">
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>My Restructure Requests</h1>
        <p>Track the status of your loan restructuring requests and check your updated payment details after approval.</p>
    </div>

    <?php if (empty($requests)): ?>
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h3>No restructure requests yet</h3>
            <p>You have not submitted any loan restructuring request.</p>
        </div>
    <?php else: ?>
        <div class="request-list">
            <?php foreach ($requests as $row): ?>
                <?php
                    $status = strtoupper((string)$row['status']);
                    $proofPath = trim((string)($row['proof_doc_path'] ?? ''));
                ?>
                <div class="request-card">
                    <div class="card-top">
                        <div class="card-title">
                            <h3>Request #RR-<?php echo str_pad((int)$row['id'], 5, "0", STR_PAD_LEFT); ?></h3>
                            <p>
                                Loan #LN-<?php echo str_pad((int)$row['loan_id'], 4, "0", STR_PAD_LEFT); ?>
                                · Submitted on <?php echo e(date('F d, Y h:i A', strtotime($row['created_at']))); ?>
                            </p>
                        </div>

                        <span class="status-badge <?php echo e(statusClass($status)); ?>">
                            <?php echo e($status); ?>
                        </span>
                    </div>

                    <div class="grid">
                        <div class="mini-card">
                            <div class="mini-label">Restructure Type</div>
                            <div class="mini-value"><?php echo e($row['restructure_type']); ?></div>
                        </div>

                        <div class="mini-card">
                            <div class="mini-label">Outstanding at Request Time</div>
                            <div class="mini-value"><?php echo peso($row['outstanding_snapshot']); ?></div>
                        </div>
                    </div>

                    <div class="compare-wrap">
                        <div class="compare-box">
                            <div class="compare-head">Requested Changes</div>
                            <div class="compare-body">
                                <div class="compare-row">
                                    <span>Requested Term</span>
                                    <strong><?php echo e($row['requested_term_months']); ?> months</strong>
                                </div>
                                <div class="compare-row">
                                    <span>Estimated Monthly Due</span>
                                    <strong><?php echo peso($row['estimated_monthly_due']); ?></strong>
                                </div>
                                <div class="compare-row">
                                    <span>Interest Rate</span>
                                    <strong><?php echo e($row['interest_rate_snapshot']); ?>%</strong>
                                </div>
                                <div class="compare-row">
                                    <span>Interest Method</span>
                                    <strong><?php echo e($row['interest_method_snapshot']); ?></strong>
                                </div>
                            </div>
                        </div>

                        <div class="compare-box">
                            <div class="compare-head">Current Actual Loan Details</div>
                            <div class="compare-body">
                                <div class="compare-row">
                                    <span>Loan Status</span>
                                    <strong><?php echo e($row['loan_status'] ?? 'N/A'); ?></strong>
                                </div>
                                <div class="compare-row">
                                    <span>Current Outstanding</span>
                                    <strong><?php echo isset($row['current_outstanding']) ? peso($row['current_outstanding']) : 'N/A'; ?></strong>
                                </div>
                                <div class="compare-row">
                                    <span>Current Term</span>
                                    <strong><?php echo isset($row['current_term_now']) ? e($row['current_term_now']) . ' months' : 'N/A'; ?></strong>
                                </div>
                                <div class="compare-row">
                                    <span>Current Monthly Due</span>
                                    <strong><?php echo isset($row['current_monthly_now']) ? peso($row['current_monthly_now']) : 'N/A'; ?></strong>
                                </div>
                                <div class="compare-row">
                                    <span>Next Payment</span>
                                    <strong><?php echo !empty($row['next_payment']) ? e(date('F d, Y', strtotime($row['next_payment']))) : 'N/A'; ?></strong>
                                </div>
                                <div class="compare-row">
                                    <span>Due Date</span>
                                    <strong><?php echo !empty($row['due_date']) ? e(date('F d, Y', strtotime($row['due_date']))) : 'N/A'; ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="reason-box">
                        <div class="section-label">Reason for Request</div>
                        <div><?php echo nl2br(e($row['reason'])); ?></div>
                    </div>

                    <?php if ($proofPath !== ''): ?>
                        <div class="proof-wrap">
                            <a class="proof-link" href="<?php echo e($proofPath); ?>" target="_blank">
                                <i class="bi bi-paperclip"></i> View Uploaded Proof
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($row['review_notes'])): ?>
                        <div class="review-box">
                            <div class="section-label">Reviewer Notes</div>
                            <div><?php echo nl2br(e($row['review_notes'])); ?></div>
                            <?php if (!empty($row['reviewed_at'])): ?>
                                <div class="review-date">
                                    Reviewed on <?php echo e(date('F d, Y h:i A', strtotime($row['reviewed_at']))); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($status === 'APPROVED'): ?>
                        <div class="approved-note">
                            <strong>Approved Request:</strong>
                            Your loan restructuring request has been approved. Your current actual monthly due is now
                            <strong><?php echo isset($row['current_monthly_now']) ? peso($row['current_monthly_now']) : 'N/A'; ?></strong>.
                            Please refer to the “Current Actual Loan Details” section above for your latest loan values.
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="assets/js/restructure_requests.js?v=<?php echo time(); ?>"></script>
</body>
</html>