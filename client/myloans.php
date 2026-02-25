<?php
session_start();
require_once __DIR__ . "/include/config.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}
$user_id = (int)$_SESSION['user_id'];

function peso($n) { return "₱ " . number_format((float)$n, 2); }
function fmtDate($d) {
  if (!$d) return "-";
  try { return (new DateTime($d))->format("M d, Y"); }
  catch (Exception $e) { return "-"; }
}
function addMonths($date, $m) {
  $dt = new DateTime($date);
  $dt->modify("+{$m} month");
  return $dt->format("Y-m-d");
}

// 1) get active loan (latest)
$loan = null;
$stmt = $conn->prepare("SELECT * FROM loans WHERE user_id = ? AND status = 'ACTIVE' ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) $loan = $res->fetch_assoc();
$stmt->close();

if (!$loan) {
  $view = null;
} else {
  $loan_id = (int)$loan['id'];

  $principal = (float)($loan['loan_amount'] ?? 0);
  $term = (int)($loan['term_months'] ?? 0);
  if ($term <= 0) $term = 1;

  $monthly_due = (float)($loan['monthly_due'] ?? 0);
  if ($monthly_due <= 0) $monthly_due = $principal / $term;

  $start_date = $loan['start_date'] ?? date("Y-m-d");

  // total payable derived from monthly_due * term (flat)
  $total_payable = $monthly_due * $term;

  // 2) transactions of this loan
  $tx = [];
  $tstmt = $conn->prepare("
    SELECT amount, trans_date
    FROM transactions
    WHERE loan_id = ? AND status='SUCCESS'
    ORDER BY trans_date ASC, id ASC
  ");
  $tstmt->bind_param("i", $loan_id);
  $tstmt->execute();
  $tres = $tstmt->get_result();
  while ($tres && ($row = $tres->fetch_assoc())) $tx[] = $row;
  $tstmt->close();

  $total_paid = 0.0;
  foreach ($tx as $t) $total_paid += (float)$t['amount'];

  // ✅ Always compute remaining from payments (UI will be correct)
  $computed_outstanding = max(0, $total_payable - $total_paid);

  // If DB has outstanding, still prefer computed (para consistent)
  $outstanding = $computed_outstanding;

  $progress = ($total_payable > 0) ? ($total_paid / $total_payable) * 100 : 0;
  $progress = max(0, min(100, $progress));
  $progressText = number_format($progress, 0) . "% Paid";

  // 3) Build installment paid dates (approx based on cumulative payments)
  $installmentPaidDates = array_fill(1, $term, null);
  $running = 0.0;
  $inst = 1;
  foreach ($tx as $t) {
    $running += (float)$t['amount'];
    while ($inst <= $term && $running >= ($monthly_due * $inst)) {
      $installmentPaidDates[$inst] = $t['trans_date'];
      $inst++;
    }
    if ($inst > $term) break;
  }

  $paidInstallments = 0;
  for ($i=1; $i <= $term; $i++) {
    if ($installmentPaidDates[$i]) $paidInstallments++;
    else break;
  }

  $nextInstallment = min($term, $paidInstallments + 1);
  $nextDeadline = addMonths($start_date, $nextInstallment);

  $view = [
    'loan_id' => $loan_id,
    'contract' => "#LN-" . str_pad((string)$loan_id, 4, "0", STR_PAD_LEFT),
    'principal' => $principal,
    'outstanding' => $outstanding,
    'monthly_due' => $monthly_due,
    'next_deadline' => $nextDeadline,
    'progress' => $progress,
    'progressText' => $progressText,
    'term' => $term,
    'start_date' => $start_date,
    'installmentPaidDates' => $installmentPaidDates,
    'paidInstallments' => $paidInstallments
  ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Loans | MicroFinance</title>

  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/myloans.css">
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>My Loans</h1>
    <p>View your active contract and repayment schedule.</p>
  </div>

  <?php if (!$view): ?>
    <div class="active-loan-card">
      <div class="loan-body">
        <h3 style="color:#fff; margin-bottom:8px;">No Active Loan</h3>
        <p style="color:#94a3b8; margin-bottom:16px;">You don’t have an active loan yet. Apply first to see your schedule here.</p>
        <a href="apply_loan.php" style="background:#10b981; color:#064e3b; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:14px;">
          Apply Loan
        </a>
      </div>
    </div>
  <?php else: ?>

  <div class="active-loan-card">
    <div class="card-header">
      <div class="loan-ref">Contract <span><?= htmlspecialchars($view['contract']) ?></span></div>
      <span class="status-badge">Active &bull; On Time</span>
    </div>

    <div class="loan-body">

      <div class="stats-grid">
        <div class="stat-item">
          <h4>Principal Amount</h4>
          <div class="val"><?= peso($view['principal']) ?></div>
        </div>
        <div class="stat-item">
          <h4>Remaining Balance</h4>
          <div class="val text-gold"><?= peso($view['outstanding']) ?></div>
        </div>
        <div class="stat-item">
          <h4>Monthly Due</h4>
          <div class="val"><?= peso($view['monthly_due']) ?></div>
        </div>
        <div class="stat-item">
          <h4>Next Deadline</h4>
          <div class="val" style="color:#fbbf24;"><?= fmtDate($view['next_deadline']) ?></div>
        </div>
      </div>

      <div class="progress-container">
        <div class="progress-labels">
          <span>Repayment Progress</span>
          <span style="color:#10b981; font-weight:700;"><?= htmlspecialchars($view['progressText']) ?></span>
        </div>
        <div class="progress-track">
          <div class="progress-fill" style="width: <?= (float)$view['progress'] ?>%;"></div>
        </div>
      </div>

      <span class="table-title">Amortization Schedule</span>
      <table class="schedule-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Due Date</th>
            <th>Amount Due</th>
            <th>Status</th>
            <th>Date Paid</th>
          </tr>
        </thead>
        <tbody>
          <?php for ($i=1; $i <= $view['term']; $i++): ?>
            <?php
              $dueDate = addMonths($view['start_date'], $i);
              $paidDate = $view['installmentPaidDates'][$i];

              if ($paidDate) {
                $rowClass = "row-paid";
                $statusText = "Paid";
                $statusStyle = "color:#10b981; font-weight:700;";
              } elseif ($i === $view['paidInstallments'] + 1) {
                $rowClass = "row-due";
                $statusText = "Due Soon";
                $statusStyle = "color:#fbbf24;";
              } else {
                $rowClass = "";
                $statusText = "Locked";
                $statusStyle = "";
              }
            ?>
            <tr class="<?= $rowClass ?>">
              <td><?= $i ?></td>
              <td><?= htmlspecialchars(fmtDate($dueDate)) ?></td>
              <td><?= htmlspecialchars(peso($view['monthly_due'])) ?></td>
              <td style="<?= $statusStyle ?>"><?= htmlspecialchars($statusText) ?></td>
              <td><?= htmlspecialchars($paidDate ? fmtDate($paidDate) : "-") ?></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>

      <div style="margin-top:20px; text-align:right;">
        <a href="create_checkout.php?loan_id=<?= (int)$view['loan_id'] ?>"
           style="background:#10b981; color:#064e3b; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:14px;">
          Pay Next Due
        </a>
      </div>

    </div>
  </div>

  <?php endif; ?>
</div>
</body>
</html>