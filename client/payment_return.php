<?php
session_start();
require_once __DIR__ . "/include/config.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

$tx_id = (int)($_GET['tx_id'] ?? 0);
$ok = isset($_GET['ok']);
$cancel = isset($_GET['cancel']);
if ($tx_id <= 0) exit("Invalid tx_id");

$stmt = $conn->prepare("
  SELECT t.id, t.status, t.amount, t.trans_date, t.loan_id
  FROM transactions t
  JOIN loans l ON l.id = t.loan_id
  WHERE t.id=? AND l.user_id=?
  LIMIT 1
");
$stmt->bind_param("ii", $tx_id, $user_id);
$stmt->execute();
$tx = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$tx) exit("Transaction not found.");

$status = strtoupper((string)($tx['status'] ?? 'PENDING'));
if ($ok && $status === 'PENDING') {
  header("Refresh: 3; url=payment_return.php?ok=1&tx_id=" . $tx_id);
}

function peso($n){ return "₱ " . number_format((float)$n, 2); }
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Payment Result</title>
  <style>
    body{font-family:Arial;background:#0b1220;color:#e5e7eb;padding:24px}
    .card{max-width:720px;margin:0 auto;background:#0f172a;border:1px solid rgba(255,255,255,.08);border-radius:14px;padding:20px}
    .badge{display:inline-block;padding:6px 10px;border-radius:999px;font-weight:700;font-size:12px}
    .success{background:rgba(16,185,129,.15);color:#10b981}
    .pending{background:rgba(251,191,36,.15);color:#fbbf24}
    .failed{background:rgba(239,68,68,.15);color:#ef4444}
    a.btn{display:inline-block;margin-top:14px;padding:10px 14px;border-radius:10px;text-decoration:none;font-weight:700}
    .btn1{background:#10b981;color:#064e3b}
    .btn2{background:#1f2937;color:#e5e7eb;margin-left:8px}
    .muted{color:#94a3b8}
  </style>
</head>
<body>
  <div class="card">
    <h2 style="margin:0 0 6px;">Payment Result</h2>
    <p class="muted" style="margin:0 0 16px;">
      Transaction #<?= (int)$tx['id'] ?> • Loan #<?= (int)$tx['loan_id'] ?> • Amount <?= peso($tx['amount']) ?>
    </p>

    <?php
      $badge = "pending";
      if ($status === "SUCCESS") $badge = "success";
      if ($status === "FAILED")  $badge = "failed";
    ?>
    <div class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></div>

    <?php if ($cancel): ?>
      <p style="margin-top:14px;">Na-cancel yung checkout. Pwede ka ulit mag-try sa My Loans.</p>
    <?php elseif ($status === "PENDING"): ?>
      <p style="margin-top:14px;">Hinihintay yung webhook confirmation… auto refresh every 3 seconds.</p>
    <?php elseif ($status === "SUCCESS"): ?>
      <p style="margin-top:14px;">✅ Payment recorded! You can view your receipt now.</p>
    <?php else: ?>
      <p style="margin-top:14px;">❌ Payment failed. Please try again.</p>
    <?php endif; ?>

    <div>
      <a class="btn btn1" href="myloans.php">Back to My Loans</a>
      <a class="btn btn2" href="receipt.php?tx_id=<?= (int)$tx_id ?>">View Receipt</a>
    </div>
  </div>
</body>
</html>