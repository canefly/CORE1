<?php
session_start();
require_once __DIR__ . "/include/config.php";
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }

$tx_id = (int)($_GET['tx_id'] ?? 0);
if ($tx_id <= 0) exit("Invalid tx_id");

$stmt = $conn->prepare("
  SELECT t.*, l.id AS loan_id
  FROM transactions t
  JOIN loans l ON l.id = t.loan_id
  WHERE t.id=? LIMIT 1
");
$stmt->bind_param("i", $tx_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) exit("Receipt not found.");
$tx = $res->fetch_assoc();
$stmt->close();
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>Receipt</title></head>
<body style="font-family:Arial; padding:20px; max-width:700px;">
  <h2>Payment Receipt</h2>
  <hr>
  <p><strong>Transaction #</strong> <?= (int)$tx['id'] ?></p>
  <p><strong>Loan #</strong> <?= (int)$tx['loan_id'] ?></p>
  <p><strong>Status</strong> <?= htmlspecialchars($tx['status']) ?></p>
  <p><strong>Amount</strong> ₱ <?= number_format((float)$tx['amount'], 2) ?></p>
  <p><strong>Date</strong> <?= htmlspecialchars($tx['trans_date'] ?? '-') ?></p>
  <p><strong>PayMongo Payment ID</strong> <?= htmlspecialchars($tx['paymongo_payment_id'] ?? '-') ?></p>
  <p><strong>PayMongo Checkout</strong> <?= htmlspecialchars($tx['paymongo_checkout_id'] ?? '-') ?></p>
  <?php if (!empty($tx['checkout_url'])): ?>
    <p><a href="<?= htmlspecialchars($tx['checkout_url']) ?>" target="_blank">Open Checkout Link</a></p>
  <?php endif; ?>
  <hr>
  <button onclick="window.print()">Print</button>
</body>
</html>