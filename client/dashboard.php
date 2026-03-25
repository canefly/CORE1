<?php
session_start();
include __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/session_checker.php";

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php?msg=account_invalid"); 
    exit;
}

function peso($n) { return "₱ " . number_format((float)$n, 2); }
function fmtDatePretty($d) {
  if (!$d) return "-";
  try { return (new DateTime($d))->format("M d, Y"); }
  catch (Exception $e) { return "-"; }
}
function addMonths($date, $m) {
  $dt = new DateTime($date);
  $dt->modify("+{$m} month");
  return $dt->format("Y-m-d");
}
function daysLeft($dateYmd) {
  if (!$dateYmd) return null;
  try {
    $today = new DateTime(date("Y-m-d"));
    $due = new DateTime($dateYmd);
    return (int)$today->diff($due)->format("%r%a");
  } catch (Exception $e) {
    return null;
  }
}

// Fetch user info
// Fetch user info gamit ang user_id (Mas secured!)
$current_user_id = $_SESSION['user_id'];
$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
$userQuery->bind_param("i", $current_user_id);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult ? $userResult->fetch_assoc() : null;
$userQuery->close();

if (!$user) {
  session_destroy();
  
  header("Location: login.php?msg=account_invalid"); 
  exit();
}

$user_id = (int)$user['id'];
$displayName = $user['name'] ?? $user['full_name'] ?? $user['fullname'] ?? $user['firstname'] ?? "Client";

// ==============================
// Latest application
// ==============================
$application = null;
$appQuery = $conn->prepare("
  SELECT *
  FROM loan_applications
  WHERE user_id = ?
  ORDER BY id DESC
  LIMIT 1
");
$appQuery->bind_param("i", $user_id);
$appQuery->execute();
$appResult = $appQuery->get_result();
if ($appResult && $appResult->num_rows === 1) {
  $application = $appResult->fetch_assoc();
}
$appQuery->close();

$appStatus = strtoupper((string)($application['status'] ?? 'NO APPLICATION'));
$appId = (int)($application['id'] ?? 0);

// ==============================
// Related disbursement for latest application
// ==============================
$disbursement = null;
if ($appId > 0) {
  $disbQuery = $conn->prepare("
    SELECT *
    FROM loan_disbursement
    WHERE application_id = ?
    ORDER BY id DESC
    LIMIT 1
  ");
  $disbQuery->bind_param("i", $appId);
  $disbQuery->execute();
  $disbResult = $disbQuery->get_result();
  if ($disbResult && $disbResult->num_rows === 1) {
    $disbursement = $disbResult->fetch_assoc();
  }
  $disbQuery->close();
}
$disbStatus = strtoupper((string)($disbursement['status'] ?? 'NONE'));

// ==============================
// THE FIX: ACTIVE LOAN CHECKER (Prioritize Restructured Loans)
// ==============================
$loan = null;
$is_restructured = false;

// CHECK 1: May active ba siya na Restructured Loan?
$restructureQuery = $conn->prepare("SELECT * FROM restructured_loans WHERE user_id = ? AND status = 'ACTIVE' ORDER BY id DESC LIMIT 1");
$restructureQuery->bind_param("i", $user_id);
$restructureQuery->execute();
$restructureResult = $restructureQuery->get_result();

if ($restructureResult && $restructureResult->num_rows === 1) {
    $loan = $restructureResult->fetch_assoc();
    $is_restructured = true; // Flag for transaction checking later
} 
$restructureQuery->close();

// CHECK 2: Kung walang Restructured, i-check ang normal na Loans table
if (!$loan) {
    $loanQuery = $conn->prepare("
      SELECT l.*
      FROM loans l
      INNER JOIN loan_disbursement ld ON ld.application_id = l.application_id
      WHERE l.user_id = ?
        AND l.status = 'ACTIVE'
        AND ld.status = 'DISBURSED'
      ORDER BY l.id DESC
      LIMIT 1
    ");
    $loanQuery->bind_param("i", $user_id);
    $loanQuery->execute();
    $loanResult = $loanQuery->get_result();
    if ($loanResult && $loanResult->num_rows === 1) {
      $loan = $loanResult->fetch_assoc();
    }
    $loanQuery->close();
}

$hasLoan = (bool)$loan;
$loan_id = $hasLoan ? (int)$loan['id'] : 0;
$loanStatus = $hasLoan ? strtoupper((string)($loan['status'] ?? '')) : 'NO LOAN';

// ==============================
// Ongoing checks
// ==============================
$hasOngoingApplication = in_array($appStatus, ['PENDING', 'VERIFIED', 'APPROVED'], true);
$waitingDisbursement = ($disbStatus === 'WAITING FOR DISBURSEMENT');
$hasActiveLoan = $hasLoan;

$canApplyLoan = !($hasOngoingApplication || $waitingDisbursement || $hasActiveLoan);

// ==============================
// Active loan calculations
// ==============================
$principal = $hasLoan ? (float)($loan['loan_amount'] ?? $loan['principal_amount'] ?? 0) : 0;
$term = $hasLoan ? (int)($loan['term_months'] ?? 0) : 0;
if ($term <= 0) $term = 1;

$monthly_due = $hasLoan ? (float)($loan['monthly_due'] ?? 0) : 0;
if ($hasLoan && $monthly_due <= 0) $monthly_due = $principal / $term;

$start_date = $hasLoan ? ($loan['start_date'] ?? date("Y-m-d")) : date("Y-m-d");
$total_payable = $monthly_due * $term;

// Transaction Query Fix: Where to look based on loan type
$total_paid_verified = 0.0;
if ($hasLoan) {
  $target_column = $is_restructured ? 'restructured_loan_id' : 'loan_id'; // Smart routing
  
  $sumQ = $conn->prepare("
    SELECT COALESCE(SUM(amount),0) AS paid
    FROM transactions
    WHERE $target_column = ? AND status = 'SUCCESS'
  ");
  $sumQ->bind_param("i", $loan_id);
  $sumQ->execute();
  $sumRow = $sumQ->get_result()->fetch_assoc();
  $sumQ->close();
  $total_paid_verified = (float)($sumRow['paid'] ?? 0);
}

// Outstanding Calculation
if ($hasLoan && isset($loan['outstanding'])) {
    $outstanding = (float)$loan['outstanding']; // Pwede mong gamitin agad yung nasa table
} else {
    $outstanding = $hasLoan ? max(0, $total_payable - $total_paid_verified) : 0;
}

$next_payment = $hasLoan ? ($loan['next_payment'] ?? null) : null;
if ($hasLoan && !$next_payment) {
  $paidInstallments = 0;
  if ($monthly_due > 0) {
    $paidInstallments = (int)floor($total_paid_verified / $monthly_due + 0.00001);
  }
  $nextInstallment = min($term, $paidInstallments + 1);
  $next_payment = addMonths($start_date, $nextInstallment);
}

if ($hasLoan && $outstanding <= 0.00001) {
  $loanStatus = 'COMPLETED';
}

// ==============================
// Recent transactions
// ==============================
$recentTx = [];
if ($hasLoan) {
  $target_column = $is_restructured ? 'restructured_loan_id' : 'loan_id';
  
  $transQuery = $conn->prepare("
    SELECT *
    FROM transactions
    WHERE $target_column = ?
    ORDER BY trans_date DESC, id DESC
    LIMIT 5
  ");
  $transQuery->bind_param("i", $loan_id);
  $transQuery->execute();
  $transResult = $transQuery->get_result();
  while ($transResult && ($r = $transResult->fetch_assoc())) {
    $recentTx[] = $r;
  }
  $transQuery->close();
}

// ==============================
// Due countdown
// ==============================
$days = $hasLoan ? daysLeft($next_payment) : null;
$daysLabel = "";
if ($days !== null) {
  if ($days > 1) $daysLabel = "({$days} Days Left)";
  elseif ($days === 1) $daysLabel = "(1 Day Left)";
  elseif ($days === 0) $daysLabel = "(Due Today)";
  else $daysLabel = "(Overdue " . abs($days) . " Days)";
}

// ==============================
// Grace Period & Restructure Check
// ==============================
$grace_period = 3; 
$gpQuery = $conn->query("SELECT setting_value FROM system_settings WHERE setting_key = 'grace_period'");
if ($gpQuery && $gpRow = $gpQuery->fetch_assoc()) {
  $grace_period = (int)$gpRow['setting_value'];
}

// True if loan is active, not restructured yet, overdue days > grace period, and balance is not fully paid
$offerRestructure = ($hasLoan && !$is_restructured && $days !== null && $days < 0 && abs($days) > $grace_period && $outstanding > 0);

// ==============================
// Hero text
// ==============================
$heroTitle = "No Loan Yet";
$heroAmount = "—";
$heroText = "Apply for a loan to see your repayment summary here.";
$heroBadge = null;

if ($hasLoan) {
  $heroTitle = $is_restructured ? "Next Payment Due (Restructured)" : "Next Payment Due";
  $heroAmount = peso($monthly_due);
  $heroText = "Due on " . fmtDatePretty($next_payment) . ($daysLabel ? " " . $daysLabel : "");
  $heroBadge = $loanStatus;
} elseif ($waitingDisbursement) {
  $heroTitle = "Loan Approved";
  $heroAmount = "WAITING FOR DISBURSEMENT";
  $heroText = "Your loan is approved and waiting for disbursement.";
  $heroBadge = "WAITING FOR DISBURSEMENT";
} elseif ($hasOngoingApplication) {
  $heroTitle = "Loan Application Status";
  $heroAmount = $appStatus;
  if ($appStatus === 'PENDING') {
    $heroText = "Your application is pending initial review.";
  } elseif ($appStatus === 'VERIFIED') {
    $heroText = "Your application has been verified and is now under Loan Officer review.";
  } elseif ($appStatus === 'APPROVED') {
    $heroText = "Your application is approved and is being prepared for disbursement.";
  } else {
    $heroText = "Your application is currently being processed.";
  }
  $heroBadge = $appStatus;
} elseif (in_array($appStatus, ['REJECTED', 'CANCELLED'], true)) {
  $heroTitle = "Latest Application";
  $heroAmount = $appStatus;
  $heroText = "Your last application was " . strtolower($appStatus) . ". You may submit a new one.";
  $heroBadge = $appStatus;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard | MicroFinance</title>

  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>
<?php include 'include/chat_support.php'; ?>

<div class="main-content">

  <div class="page-header">
    <h1>Overview</h1>
    <p>Welcome back, <strong><?= htmlspecialchars($displayName) ?></strong>! Here is your financial summary.</p>
  </div>

  <div class="hero-card">
    <div class="hero-info">
      <h2><?= htmlspecialchars($heroTitle) ?></h2>
      <div class="amount"><?= htmlspecialchars($heroAmount) ?></div>
      <div class="due-date">
        <i class="bi bi-info-circle"></i>
        <?= htmlspecialchars($heroText) ?>
      </div>
    </div>

    <div class="hero-actions">
      <?php if ($hasLoan): ?>
        <?php if ($outstanding <= 0.00001): ?>
          <a href="myloans.php" class="btn-pay">
            <i class="bi bi-check2-circle"></i> Loan Completed
          </a>
        <?php else: ?>
          <a href="create_checkout.php?loan_id=<?= (int)$loan_id ?>&is_restructured=<?= $is_restructured ? 1 : 0 ?>" class="btn-pay">
            <i class="bi bi-qr-code-scan"></i> Pay Now
          </a>
        <?php endif; ?>
      <?php elseif ($canApplyLoan): ?>
        <a href="apply_loan.php" class="btn-pay">
          <i class="bi bi-file-earmark-text"></i> Apply Loan
        </a>
      <?php else: ?>
        <button class="btn-pay" type="button" disabled style="opacity:.65; cursor:not-allowed;">
          <i class="bi bi-lock-fill"></i> Loan In Process
        </button>
      <?php endif; ?>

      <a href="myloans.php" class="btn-secondary">
        View Details
      </a>
    </div>
  </div>


  <?php if ($offerRestructure): ?>
  <div class="alert-restructure" style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.4); padding: 18px 24px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 16px;">
      <i class="bi bi-exclamation-octagon-fill" style="color: #ef4444; font-size: 32px;"></i>
      <div>
          <h3 style="margin: 0 0 6px 0; color: #ef4444; font-size: 18px;">Account Overdue Past Grace Period</h3>
          <p style="margin: 0; color: #e2e8f0; font-size: 14px; line-height: 1.5;">
              Your payment is overdue by <strong><?= abs($days) ?> days</strong>, exceeding the <?= $grace_period ?>-day grace period. If you are experiencing financial hardship, we can help.
          </p>
          <a href="restructure.php" style="display: inline-block; margin-top: 10px; background: #ef4444; color: #fff; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-weight: bold; font-size: 13px;">
              Apply for Loan Restructure <i class="bi bi-arrow-right"></i>
          </a>
      </div>
  </div>
  <?php endif; ?>
  

  <?php if ($heroBadge && !$hasLoan): ?>
    <div class="stats-grid">
      <div class="stat-box">
        <h4>Current Status</h4>
        <div class="val text-blue"><?= htmlspecialchars($heroBadge) ?></div>
      </div>

      <div class="stat-box">
        <h4>Application ID</h4>
        <div class="val">#LA-<?= str_pad((string)$appId, 4, "0", STR_PAD_LEFT) ?></div>
      </div>

      <div class="stat-box">
        <h4>Requested Amount</h4>
        <div class="val">
          <?= $application ? peso($application['principal_amount'] ?? 0) : "—" ?>
        </div>
      </div>

      <div class="stat-box score-box">
        <div class="score-ring">
          <span class="score-text">720</span>
        </div>
        <div class="score-label">
          <h4>Credit Score</h4>
          <span class="score-desc">Excellent <i class="bi bi-graph-up-arrow"></i></span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <?php if ($hasLoan): ?>
    <div class="stats-grid">
      <div class="stat-box">
        <h4>Outstanding Balance</h4>
        <div class="val"><?= peso($outstanding) ?></div>
      </div>

      <div class="stat-box">
        <h4>Total Amount Paid</h4>
        <div class="val text-green"><?= peso($total_paid_verified) ?></div>
      </div>

      <div class="stat-box">
        <h4>Loan Status</h4>
        <div class="val text-blue"><?= htmlspecialchars($loanStatus) ?></div>
      </div>

      <div class="stat-box score-box">
        <div class="score-ring">
          <span class="score-text">720</span>
        </div>
        <div class="score-label">
          <h4>Credit Score</h4>
          <span class="score-desc">Excellent <i class="bi bi-graph-up-arrow"></i></span>
        </div>
      </div>
    </div>
  <?php endif; ?>

  <div class="table-card">
    <div class="section-head">
      <h3>Recent Transactions</h3>
      <?php if ($hasLoan): ?>
        <a href="transactions.php" class="link-all">View All</a>
      <?php endif; ?>
    </div>

    <table>
      <thead>
        <tr>
          <th>Date</th>
          <th>Activity</th>
          <th>Method</th>
          <th>Amount</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$hasLoan): ?>
          <tr>
            <td colspan="5" style="padding:18px; color:#94a3b8;">
              No transactions yet. Your payment history will appear here once your loan is disbursed and payments begin.
            </td>
          </tr>
        <?php elseif (empty($recentTx)): ?>
          <tr>
            <td colspan="5" style="padding:18px; color:#94a3b8;">
              No transactions yet.
            </td>
          </tr>
        <?php else: ?>
          <?php foreach ($recentTx as $t): ?>
            <?php
              $st = strtoupper((string)($t['status'] ?? 'PENDING'));
              $badgeClass = "bg-yellow";
              $badgeText  = "Pending";

              if ($st === 'SUCCESS') { $badgeClass = "bg-green"; $badgeText = "Verified"; }
              elseif ($st === 'PAID_PENDING') { $badgeClass = "bg-yellow"; $badgeText = "Paid Pending"; }
              elseif ($st === 'FAILED') { $badgeClass = "bg-red"; $badgeText = "Failed"; }
              elseif ($st === 'PENDING') { $badgeClass = "bg-yellow"; $badgeText = "Pending"; }

              $method = $t['provider_method'] ?? '—';
              $ref = $t['receipt_number'] ?? '';
              $methodLabel = $ref ? ($method . " (Ref: " . $ref . ")") : $method;

              $activity = "Payment";
              if ($st === 'FAILED') $activity = "Payment Failed";
              elseif ($st === 'PAID_PENDING') $activity = "Payment Submitted";
              elseif ($st === 'SUCCESS') $activity = "Payment Verified";
            ?>
            <tr>
              <td><?= htmlspecialchars(fmtDatePretty($t['trans_date'] ?? null)) ?></td>
              <td><?= htmlspecialchars($activity) ?></td>
              <td><?= htmlspecialchars($methodLabel) ?></td>
              <td style="font-weight:700;"><?= htmlspecialchars(peso($t['amount'] ?? 0)) ?></td>
              <td><span class="badge <?= htmlspecialchars($badgeClass) ?>"><?= htmlspecialchars($badgeText) ?></span></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

</div>
</body>
</html>