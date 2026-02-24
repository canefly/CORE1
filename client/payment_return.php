<?php
session_start();
require_once __DIR__ . "/include/config.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$user_id = (int)$_SESSION['user_id'];
$tx_id = (int)($_GET['tx_id'] ?? 0);
$ok = isset($_GET['ok']);
$cancel = isset($_GET['cancel']);

$tx = null;
if ($tx_id > 0) {
  $stmt = $conn->prepare("
    SELECT id, loan_id, amount, status, trans_date, paymongo_payment_id, checkout_url, receipt_url
    FROM transactions
    WHERE id=? LIMIT 1
  ");
  $stmt->bind_param("i", $tx_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows === 1) $tx = $res->fetch_assoc();
  $stmt->close();
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Payment</title>
  <?php if ($ok && $tx && $tx['status'] !== 'SUCCESS'): ?>
    <!-- Auto refresh habang hinihintay webhook -->
    <meta http-equiv="refresh" content="2">
  <?php endif; ?>
</head>
<body style="font-family: Arial; padding:20px;">

<?php if ($cancel): ?>
  <h2>Payment Cancelled</h2>
  <p>Pwede mo ulit subukan.</p>

<?php elseif ($ok): ?>
  <h2>Payment Status</h2>

  <?php if (!$tx): ?>
    <p>Transaction not found.</p>

  <?php elseif ($tx['status'] === 'SUCCESS'): ?>
    <p><strong>PAID ✅</strong></p>
    <p>Amount: ₱ <?= number_format((float)$tx['amount'], 2) ?></p>
    <p>Date: <?= htmlspecialchars($tx['trans_date'] ?? '-') ?></p>
    <p>Reference: <?= htmlspecialchars($tx['paymongo_payment_id'] ?? '-') ?></p>

    <?php if (!empty($tx['receipt_url'])): ?>
      <p><a href="<?= htmlspecialchars($tx['receipt_url']) ?>" target="_blank">View Receipt</a></p>
    <?php else: ?>
      <p><a href="receipt.php?tx_id=<?= (int)$tx['id'] ?>" target="_blank">View Receipt</a></p>
    <?php endif; ?>

  <?php else: ?>
    <p>Processing... (waiting for webhook confirmation)</p>
    <p>Status: <?= htmlspecialchars($tx['status']) ?></p>
  <?php endif; ?>

<?php else: ?>
  <h2>Payment Status</h2>
<?php endif; ?>

<br>
<a href="myloans.php">Back to My Loans</a>

</body>
</html>