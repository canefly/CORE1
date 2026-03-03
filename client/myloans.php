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
  if ($monthly_due <= 0) $monthly_due = ($term > 0) ? ($principal / $term) : $principal;

  $start_date = $loan['start_date'] ?? date("Y-m-d");

  // total payable derived from monthly_due * term (flat)
  $total_payable = $monthly_due * $term;

  // 2) transactions of this loan
  // SUCCESS = verified (counts as paid)
  // PAID_PENDING = paid but waiting verification (does NOT reduce balance yet)
  $tx = [];
  $tstmt = $conn->prepare("
    SELECT id, amount, trans_date, status
    FROM transactions
    WHERE loan_id = ?
      AND status IN ('SUCCESS','PAID_PENDING')
    ORDER BY trans_date ASC, id ASC
  ");
  $tstmt->bind_param("i", $loan_id);
  $tstmt->execute();
  $tres = $tstmt->get_result();
  while ($tres && ($row = $tres->fetch_assoc())) $tx[] = $row;
  $tstmt->close();

  // ✅ Balance computation: VERIFIED only (SUCCESS)
  $total_paid_verified = 0.0;
  foreach ($tx as $t) {
    if (($t['status'] ?? '') === 'SUCCESS') {
      $total_paid_verified += (float)$t['amount'];
    }
  }

  $outstanding = max(0, $total_payable - $total_paid_verified);

  $progress = ($total_payable > 0) ? ($total_paid_verified / $total_payable) * 100 : 0;
  $progress = max(0, min(100, $progress));
  $progressText = number_format($progress, 0) . "% Paid";

  // 3) Build installment status dates:
  // - paidDates[inst] set when covered by SUCCESS
  // - pendingDates[inst] set when covered by PAID_PENDING (unless later overwritten by SUCCESS)
  $paidDates = array_fill(1, $term, null);
  $pendingDates = array_fill(1, $term, null);

  $running_all = 0.0; // includes SUCCESS + PAID_PENDING (for display coverage only)
  $inst = 1;

  foreach ($tx as $t) {
    $amt = (float)($t['amount'] ?? 0);
    $status = $t['status'] ?? '';
    $date = $t['trans_date'] ?? null;

    $running_all += $amt;

    // Mark installments as "covered" when cumulative >= monthly_due * inst
    while ($inst <= $term && $running_all + 0.00001 >= ($monthly_due * $inst)) {
      if ($status === 'SUCCESS') {
        $paidDates[$inst] = $date;
        $pendingDates[$inst] = null; // overwrite any previous pending for this installment
      } elseif ($status === 'PAID_PENDING') {
        // only set pending if not already paid
        if (empty($paidDates[$inst])) {
          $pendingDates[$inst] = $date;
        }
      }
      $inst++;
    }

    if ($inst > $term) break;
  }

  // Count consecutive PAID installments from start (strictly SUCCESS)
  $paidInstallments = 0;
  for ($i=1; $i <= $term; $i++) {
    if (!empty($paidDates[$i])) $paidInstallments++;
    else break;
  }

  // Find the first installment that is neither Paid nor Paid Pending => that's "Due Soon"
  $firstActionable = 1;
  while ($firstActionable <= $term) {
    if (!empty($paidDates[$firstActionable]) || !empty($pendingDates[$firstActionable])) {
      $firstActionable++;
      continue;
    }
    break;
  }
  if ($firstActionable > $term) $firstActionable = $term; // safety

  $nextInstallment = min($term, max(1, $firstActionable));
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
    'paidDates' => $paidDates,
    'pendingDates' => $pendingDates,
    'paidInstallments' => $paidInstallments,
    'firstActionable' => $firstActionable
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
              $paidDate = $view['paidDates'][$i];
              $pendingDate = $view['pendingDates'][$i];

              if (!empty($paidDate)) {
                $rowClass = "row-paid";
                $statusText = "Paid";
                $statusStyle = "color:#10b981; font-weight:700;";
                $dateCell = fmtDate($paidDate);
              } elseif (!empty($pendingDate)) {
                $rowClass = "row-due";
                $statusText = "Paid Pending";
                $statusStyle = "color:#38bdf8; font-weight:700;";
                $dateCell = fmtDate($pendingDate);
              } elseif ($i === (int)$view['firstActionable']) {
                $rowClass = "row-due";
                $statusText = "Due Soon";
                $statusStyle = "color:#fbbf24;";
                $dateCell = "-";
              } else {
                $rowClass = "";
                $statusText = "Locked";
                $statusStyle = "";
                $dateCell = "-";
              }
            ?>
            <tr class="<?= $rowClass ?>">
              <td><?= $i ?></td>
              <td><?= htmlspecialchars(fmtDate($dueDate)) ?></td>
              <td><?= htmlspecialchars(peso($view['monthly_due'])) ?></td>
              <td style="<?= $statusStyle ?>"><?= htmlspecialchars($statusText) ?></td>
              <td><?= htmlspecialchars($dateCell) ?></td>
            </tr>
          <?php endfor; ?>
        </tbody>
      </table>

      <div style="margin-top:20px; text-align:right;">
        <?php if ((float)$view['outstanding'] <= 0): ?>
          <span style="background:#334155; color:#e2e8f0; padding:10px 20px; border-radius:8px; font-weight:700; font-size:14px; display:inline-block;">
            Fully Paid
          </span>
        <?php else: ?>
          <!-- ✅ ALWAYS allow paying even if there are PAID_PENDING transactions -->
          <a href="create_checkout.php?loan_id=<?= (int)$view['loan_id'] ?>"
             style="background:#10b981; color:#064e3b; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:14px;">
            Pay Next Due
          </a>
        <?php endif; ?>
      </div>

    </div>
  </div>

  <?php endif; ?>
</div>
</body>
</html>