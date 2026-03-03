<?php
session_start();
include __DIR__ . "/include/config.php";

// Redirect if not logged in
if (!isset($_SESSION['user_email'])) {
  header("Location: index.html");
  exit();
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
$email = $_SESSION['user_email'];
$userQuery = $conn->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$userQuery->bind_param("s", $email);
$userQuery->execute();
$userResult = $userQuery->get_result();
$user = $userResult ? $userResult->fetch_assoc() : null;
$userQuery->close();

if (!$user) {
  session_destroy();
  header("Location: index.html");
  exit();
}

$user_id = (int)$user['id'];
$displayName = $user['name'] ?? $user['full_name'] ?? $user['firstname'] ?? "Client";

// Fetch latest ACTIVE loan (if none, fallback latest loan)
$loan = null;

// Try ACTIVE first
$loanQuery = $conn->prepare("SELECT * FROM loans WHERE user_id = ? AND status='ACTIVE' ORDER BY id DESC LIMIT 1");
$loanQuery->bind_param("i", $user_id);
$loanQuery->execute();
$loanResult = $loanQuery->get_result();
if ($loanResult && $loanResult->num_rows === 1) $loan = $loanResult->fetch_assoc();
$loanQuery->close();

// Fallback: latest loan (maybe COMPLETED)
if (!$loan) {
  $loanQuery = $conn->prepare("SELECT * FROM loans WHERE user_id = ? ORDER BY id DESC LIMIT 1");
  $loanQuery->bind_param("i", $user_id);
  $loanQuery->execute();
  $loanResult = $loanQuery->get_result();
  if ($loanResult && $loanResult->num_rows === 1) $loan = $loanResult->fetch_assoc();
  $loanQuery->close();
}

$hasLoan = (bool)$loan;
$loan_id = $hasLoan ? (int)$loan['id'] : 0;
$loanStatus = $hasLoan ? strtoupper((string)($loan['status'] ?? '')) : 'NO LOAN';

$principal = $hasLoan ? (float)($loan['loan_amount'] ?? 0) : 0;
$term = $hasLoan ? (int)($loan['term_months'] ?? 0) : 0;
if ($term <= 0) $term = 1;

$monthly_due = $hasLoan ? (float)($loan['monthly_due'] ?? 0) : 0;
if ($hasLoan && $monthly_due <= 0) $monthly_due = $principal / $term;

$start_date = $hasLoan ? ($loan['start_date'] ?? date("Y-m-d")) : date("Y-m-d");
$total_payable = $monthly_due * $term;

// Sum payments (SUCCESS only = verified)
$total_paid_verified = 0.0;
if ($hasLoan) {
  $sumQ = $conn->prepare("SELECT COALESCE(SUM(amount),0) AS paid FROM transactions WHERE loan_id=? AND status='SUCCESS'");
  $sumQ->bind_param("i", $loan_id);
  $sumQ->execute();
  $sumRow = $sumQ->get_result()->fetch_assoc();
  $sumQ->close();
  $total_paid_verified = (float)($sumRow['paid'] ?? 0);
}

$outstanding = $hasLoan ? max(0, $total_payable - $total_paid_verified) : 0;

// Determine next due date:
// prefer loans.next_payment, else compute based on installments paid
$next_payment = $hasLoan ? ($loan['next_payment'] ?? null) : null;

if ($hasLoan && !$next_payment) {
  // approximate paid installments by amount
  $paidInstallments = 0;
  if ($monthly_due > 0) {
    $paidInstallments = (int)floor($total_paid_verified / $monthly_due + 0.00001);
  }
  $nextInstallment = min($term, $paidInstallments + 1);
  $next_payment = addMonths($start_date, $nextInstallment);
}

// If outstanding is 0, treat as completed in UI
if ($hasLoan && $outstanding <= 0.00001) {
  $loanStatus = 'COMPLETED';
}

// Recent transactions
$transResult = null;
$recentTx = [];
if ($hasLoan) {
  $transQuery = $conn->prepare("SELECT * FROM transactions WHERE loan_id = ? ORDER BY trans_date DESC, id DESC LIMIT 5");
  $transQuery->bind_param("i", $loan_id);
  $transQuery->execute();
  $transResult = $transQuery->get_result();
  while ($transResult && ($r = $transResult->fetch_assoc())) $recentTx[] = $r;
  $transQuery->close();
}

// Due countdown
$days = $hasLoan ? daysLeft($next_payment) : null;
$daysLabel = "";
if ($days !== null) {
  if ($days > 1) $daysLabel = "({$days} Days Left)";
  elseif ($days === 1) $daysLabel = "(1 Day Left)";
  elseif ($days === 0) $daysLabel = "(Due Today)";
  else $daysLabel = "(Overdue " . abs($days) . " Days)";
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

<div class="main-content">

  <div class="page-header">
    <h1>Overview</h1>
    <p>Welcome back, <strong><?= htmlspecialchars($displayName) ?></strong>! Here is your financial summary.</p>
  </div>

  <?php if (!$hasLoan): ?>
    <div class="hero-card">
      <div class="hero-info">
        <h2>No Loan Yet</h2>
        <div class="amount">—</div>
        <div class="due-date">
          <i class="bi bi-info-circle"></i> Apply for a loan to see your repayment summary here.
        </div>
      </div>
      <div class="hero-actions">
        <a href="apply_loan.php" class="btn-pay">
          <i class="bi bi-file-earmark-text"></i> Apply Loan
        </a>
        <a href="myloans.php" class="btn-secondary">
          View Details
        </a>
      </div>
    </div>

  <?php else: ?>
    <div class="hero-card">
      <div class="hero-info">
        <h2>Next Payment Due</h2>
        <div class="amount"><?= peso($monthly_due) ?></div>
        <div class="due-date">
          <i class="bi bi-calendar-event"></i>
          Due on <strong><?= htmlspecialchars(fmtDatePretty($next_payment)) ?></strong>
          <?= $daysLabel ? " " . htmlspecialchars($daysLabel) : "" ?>
        </div>
      </div>
      <div class="hero-actions">
        <?php if ($outstanding <= 0.00001): ?>
          <a href="myloans.php" class="btn-pay">
            <i class="bi bi-check2-circle"></i> Loan Completed
          </a>
        <?php else: ?>
          <a href="create_checkout.php?loan_id=<?= (int)$loan_id ?>" class="btn-pay">
            <i class="bi bi-qr-code-scan"></i> Pay Now
          </a>
        <?php endif; ?>

        <a href="myloans.php" class="btn-secondary">
          View Details
        </a>
      </div>
    </div>

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

      <!-- Keep your credit score UI (still static unless you have a table for it) -->
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

    <div class="table-card">
      <div class="section-head">
        <h3>Recent Transactions</h3>
        <a href="transactions.php" class="link-all">View All</a>
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
          <?php if (empty($recentTx)): ?>
            <tr>
              <td colspan="5" style="padding:18px; color:#94a3b8;">No transactions yet.</td>
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
  <?php endif; ?>

</div>
</body>
</html>