<?php
session_start();
require_once __DIR__ . "/include/config.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

function peso($n) {
  return "₱ " . number_format((float)$n, 2);
}
function fmtDate($d) {
  if (!$d) return "-";
  try {
    return (new DateTime($d))->format("M d, Y");
  } catch (Exception $e) {
    return "-";
  }
}
function addMonths($date, $m) {
  $dt = new DateTime($date);
  $dt->modify("+{$m} month");
  return $dt->format("Y-m-d");
}

// ==============================
// Latest application
// ==============================
$application = null;
$appStmt = $conn->prepare("
  SELECT *
  FROM loan_applications
  WHERE user_id = ?
  ORDER BY id DESC
  LIMIT 1
");
$appStmt->bind_param("i", $user_id);
$appStmt->execute();
$appRes = $appStmt->get_result();
if ($appRes && $appRes->num_rows === 1) {
  $application = $appRes->fetch_assoc();
}
$appStmt->close();

$appId = (int)($application['id'] ?? 0);
$appStatus = strtoupper((string)($application['status'] ?? 'NO APPLICATION'));

// ==============================
// Related disbursement
// ==============================
$disbursement = null;
if ($appId > 0) {
  $disbStmt = $conn->prepare("
    SELECT *
    FROM loan_disbursement
    WHERE application_id = ?
    ORDER BY id DESC
    LIMIT 1
  ");
  $disbStmt->bind_param("i", $appId);
  $disbStmt->execute();
  $disbRes = $disbStmt->get_result();
  if ($disbRes && $disbRes->num_rows === 1) {
    $disbursement = $disbRes->fetch_assoc();
  }
  $disbStmt->close();
}
$disbStatus = strtoupper((string)($disbursement['status'] ?? 'NONE'));

// ==============================
// Active loan only if disbursed na
// ==============================
$loan = null;
$stmt = $conn->prepare("
  SELECT l.*
  FROM loans l
  INNER JOIN loan_disbursement ld ON ld.application_id = l.application_id
  WHERE l.user_id = ?
    AND l.status = 'ACTIVE'
    AND ld.status = 'DISBURSED'
  ORDER BY l.id DESC
  LIMIT 1
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
  $loan = $res->fetch_assoc();
}
$stmt->close();

// ==============================
// Status container display
// ==============================
$statusLabel = "NO APPLICATION";
$statusMessage = "You do not have any loan application yet.";
$statusClass = "status-neutral";

if ($appStatus === 'PENDING') {
  $statusLabel = 'PENDING';
  $statusMessage = "Your loan application is waiting for initial review.";
  $statusClass = "status-pending";
} elseif ($appStatus === 'VERIFIED') {
  $statusLabel = 'VERIFIED';
  $statusMessage = "Your application has been verified and is under Loan Officer review.";
  $statusClass = "status-verified";
} elseif ($appStatus === 'APPROVED' && $disbStatus !== 'WAITING FOR DISBURSEMENT') {
  $statusLabel = 'APPROVED';
  $statusMessage = "Your application has been approved and is being prepared for disbursement.";
  $statusClass = "status-approved";
} elseif ($disbStatus === 'WAITING FOR DISBURSEMENT') {
  $statusLabel = 'WAITING FOR DISBURSEMENT';
  $statusMessage = "Your loan is approved and waiting for disbursement.";
  $statusClass = "status-waiting";
} elseif ($disbStatus === 'DISBURSED') {
  $statusLabel = 'DISBURSED';
  $statusMessage = "Your loan has been disbursed. Your payment schedule is now available below.";
  $statusClass = "status-disbursed";
} elseif ($appStatus === 'REJECTED') {
  $statusLabel = 'REJECTED';
  $statusMessage = "Your latest loan application was rejected.";
  $statusClass = "status-rejected";
} elseif ($appStatus === 'CANCELLED') {
  $statusLabel = 'CANCELLED';
  $statusMessage = "Your latest loan application was cancelled.";
  $statusClass = "status-cancelled";
}

// ==============================
// Build active loan schedule
// ==============================
$view = null;

if ($loan) {
  $loan_id = (int)$loan['id'];

  $principal = (float)($loan['loan_amount'] ?? 0);
  $term = (int)($loan['term_months'] ?? 0);
  if ($term <= 0) $term = 1;

  $monthly_due = (float)($loan['monthly_due'] ?? 0);
  if ($monthly_due <= 0) {
    $monthly_due = ($term > 0) ? ($principal / $term) : $principal;
  }

  $start_date = $loan['start_date'] ?? date("Y-m-d");
  $total_payable = $monthly_due * $term;

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
  while ($tres && ($row = $tres->fetch_assoc())) {
    $tx[] = $row;
  }
  $tstmt->close();

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

  $paidDates = array_fill(1, $term, null);
  $pendingDates = array_fill(1, $term, null);

  $running_all = 0.0;
  $inst = 1;

  foreach ($tx as $t) {
    $amt = (float)($t['amount'] ?? 0);
    $status = $t['status'] ?? '';
    $date = $t['trans_date'] ?? null;

    $running_all += $amt;

    while ($inst <= $term && $running_all + 0.00001 >= ($monthly_due * $inst)) {
      if ($status === 'SUCCESS') {
        $paidDates[$inst] = $date;
        $pendingDates[$inst] = null;
      } elseif ($status === 'PAID_PENDING') {
        if (empty($paidDates[$inst])) {
          $pendingDates[$inst] = $date;
        }
      }
      $inst++;
    }

    if ($inst > $term) break;
  }

  $paidInstallments = 0;
  for ($i = 1; $i <= $term; $i++) {
    if (!empty($paidDates[$i])) $paidInstallments++;
    else break;
  }

  $firstActionable = 1;
  while ($firstActionable <= $term) {
    if (!empty($paidDates[$firstActionable]) || !empty($pendingDates[$firstActionable])) {
      $firstActionable++;
      continue;
    }
    break;
  }
  if ($firstActionable > $term) $firstActionable = $term;

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

  <style>
    .status-card{
      background:#111827;
      border:1px solid #1f2937;
      border-radius:18px;
      padding:24px;
      margin-bottom:22px;
      box-shadow:0 10px 30px rgba(0,0,0,.18);
    }
    .status-header{
      display:flex;
      justify-content:space-between;
      align-items:center;
      gap:16px;
      flex-wrap:wrap;
      margin-bottom:18px;
    }
    .status-title{
      color:#fff;
      font-size:1.3rem;
      font-weight:700;
      margin:0;
    }
    .status-badge-top{
      padding:8px 14px;
      border-radius:999px;
      font-size:.85rem;
      font-weight:700;
      letter-spacing:.3px;
      border:1px solid transparent;
    }
    .status-neutral{ background:#1f2937; color:#cbd5e1; border-color:#334155; }
    .status-pending{ background:rgba(245,158,11,.12); color:#fbbf24; border-color:rgba(245,158,11,.35); }
    .status-verified{ background:rgba(59,130,246,.12); color:#60a5fa; border-color:rgba(59,130,246,.35); }
    .status-approved{ background:rgba(34,197,94,.12); color:#4ade80; border-color:rgba(34,197,94,.35); }
    .status-waiting{ background:rgba(168,85,247,.12); color:#c084fc; border-color:rgba(168,85,247,.35); }
    .status-disbursed{ background:rgba(16,185,129,.12); color:#34d399; border-color:rgba(16,185,129,.35); }
    .status-rejected{ background:rgba(239,68,68,.12); color:#f87171; border-color:rgba(239,68,68,.35); }
    .status-cancelled{ background:rgba(148,163,184,.12); color:#cbd5e1; border-color:rgba(148,163,184,.35); }

    .status-grid{
      display:grid;
      grid-template-columns:repeat(auto-fit,minmax(190px,1fr));
      gap:14px;
      margin-top:16px;
    }
    .status-item{
      background:#0f172a;
      border:1px solid #1e293b;
      border-radius:14px;
      padding:16px;
    }
    .status-item h4{
      margin:0 0 8px;
      color:#94a3b8;
      font-size:.85rem;
      font-weight:600;
    }
    .status-item .val{
      color:#fff;
      font-size:1rem;
      font-weight:700;
    }
    .status-message{
      color:#cbd5e1;
      line-height:1.6;
      margin:0;
    }
  </style>
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>My Loans</h1>
    <p>Track your loan application and repayment details.</p>
  </div>

  <!-- Loan Application Status -->
  <div class="status-card">
    <div class="status-header">
      <h2 class="status-title">Loan Application Status</h2>
      <span class="status-badge-top <?= htmlspecialchars($statusClass) ?>">
        <?= htmlspecialchars($statusLabel) ?>
      </span>
    </div>

    <p class="status-message"><?= htmlspecialchars($statusMessage) ?></p>

    <div class="status-grid">
      <div class="status-item">
        <h4>Application ID</h4>
        <div class="val">
          <?= $appId > 0 ? '#LA-' . str_pad((string)$appId, 4, "0", STR_PAD_LEFT) : '—' ?>
        </div>
      </div>

      <div class="status-item">
        <h4>Requested Amount</h4>
        <div class="val">
          <?= $application ? peso($application['principal_amount'] ?? 0) : '—' ?>
        </div>
      </div>

      <div class="status-item">
        <h4>Term</h4>
        <div class="val">
          <?= $application ? (int)($application['term_months'] ?? 0) . ' Months' : '—' ?>
        </div>
      </div>

      <div class="status-item">
        <h4>Current Stage</h4>
        <div class="val"><?= htmlspecialchars($statusLabel) ?></div>
      </div>
    </div>
  </div>

  <!-- Payment Schedule -->
  <?php if ($view): ?>
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
            <?php for ($i = 1; $i <= $view['term']; $i++): ?>
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
              <tr class="<?= htmlspecialchars($rowClass) ?>">
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
            <a href="create_checkout.php?loan_id=<?= (int)$view['loan_id'] ?>"
               style="background:#10b981; color:#064e3b; padding:10px 20px; border-radius:8px; text-decoration:none; font-weight:700; font-size:14px;">
              Pay Next Due
            </a>
          <?php endif; ?>
        </div>
      </div>
    </div>
  <?php elseif ($disbStatus !== 'DISBURSED'): ?>
    <div class="active-loan-card">
      <div class="loan-body">
        <h3 style="color:#fff; margin-bottom:8px;">Payment Schedule</h3>
        <p style="color:#94a3b8; margin-bottom:16px;">
          Your payment schedule will appear here once your loan has been disbursed and activated.
        </p>
        <button disabled style="background:#334155; color:#94a3b8; padding:10px 20px; border-radius:8px; border:none; font-weight:700; font-size:14px;">
          Schedule not available yet
        </button>
      </div>
    </div>
  <?php endif; ?>
</div>
</body>
</html>