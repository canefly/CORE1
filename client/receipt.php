<?php
// CLIENT/receipt.php
session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";
require_once __DIR__ . "/receipt_image_generator.php";

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
$user_id = (int)$_SESSION['user_id'];

$tx_id = (int)($_GET['tx_id'] ?? 0);
if ($tx_id <= 0) exit("Invalid tx_id");

// must belong to user (via loans)
$stmt = $conn->prepare("
  SELECT t.*, l.user_id AS loan_user_id
  FROM transactions t
  JOIN loans l ON l.id = t.loan_id
  WHERE t.id=? AND l.user_id=?
  LIMIT 1
");
$stmt->bind_param("ii", $tx_id, $user_id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows !== 1) exit("Receipt not found.");
$tx = $res->fetch_assoc();
$stmt->close();

function peso($n) { return "₱ " . number_format((float)$n, 2); }
function fmtDateTime($d) {
  if (!$d) return "-";
  try { return (new DateTime($d))->format("M d, Y h:i A"); }
  catch (Exception $e) { return "-"; }
}

$status = strtoupper((string)($tx['status'] ?? 'PENDING'));
$loan_id = (int)($tx['loan_id'] ?? 0);
$contract = "#LN-" . str_pad((string)$loan_id, 4, "0", STR_PAD_LEFT);

$rcptNo = (string)($tx['receipt_number'] ?? '-');

// Method: show nice placeholder while pending
$methodRaw = strtoupper((string)($tx['provider_method'] ?? 'TO_BE_CONFIRMED'));
$method = ($methodRaw === 'TO_BE_CONFIRMED' || $methodRaw === '') ? "To be confirmed (selected at checkout)" : $methodRaw;

// Proof: pending uses checkout id, success uses payment id
$proof = (string)($tx['paymongo_payment_id'] ?? '');
$proofLabel = "PayMongo Payment ID (Proof)";
if (!$proof) {
  $proof = (string)($tx['paymongo_checkout_id'] ?? '');
  $proofLabel = "PayMongo Checkout ID (Proof)";
}
if (!$proof) $proof = "-";

$verifyLink = PUBLIC_BASE_URL . "/client/receipt.php?tx_id=" . $tx_id;

// DB stored paths (we now store relative like "receipts/xxx.png")
$pendingImg = (string)($tx['receipt_image_pending_url'] ?? '');
$finalImg   = (string)($tx['receipt_image_final_url'] ?? '');

// ======================
// FALLBACK: AUTO-GENERATE LOCAL IMAGE IF MISSING
// ======================
$dir = ensure_receipt_dir();

// build expected filenames
$expectedPending = ($rcptNo !== '-' ? ($rcptNo . "-PENDING.png") : ("TX{$tx_id}-PENDING.png"));
$expectedFinal   = ($rcptNo !== '-' ? ($rcptNo . "-FINAL.png")   : ("TX{$tx_id}-FINAL.png"));

$filePathPending = $dir . "/" . $expectedPending;
$filePathFinal   = $dir . "/" . $expectedFinal;

$urlPending = "receipts/" . $expectedPending;
$urlFinal   = "receipts/" . $expectedFinal;

// If DB pendingImg empty, but file exists, use it
if (!$pendingImg && file_exists($filePathPending)) $pendingImg = $urlPending;
if (!$finalImg && file_exists($filePathFinal)) $finalImg = $urlFinal;

// If still missing, generate based on current status
if ($status === 'PENDING') {
  if (!file_exists($filePathPending)) {
    // generate pending now
    generate_receipt_png($tx, $verifyLink, $filePathPending);
  }
  if (file_exists($filePathPending)) $pendingImg = $urlPending;
} else {
  if (!file_exists($filePathFinal)) {
    // generate final now (SUCCESS/FAILED)
    generate_receipt_png($tx, $verifyLink, $filePathFinal);
  }
  if (file_exists($filePathFinal)) $finalImg = $urlFinal;

  // keep pending visible if exists
  if (!$pendingImg && file_exists($filePathPending)) $pendingImg = $urlPending;
}

// Optional cache-bust so browser always shows latest
$cb = time();
if ($pendingImg) $pendingImg .= (str_contains($pendingImg, '?') ? '&' : '?') . "v=" . $cb;
if ($finalImg)   $finalImg   .= (str_contains($finalImg, '?') ? '&' : '?') . "v=" . $cb;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Receipt | MicroFinance</title>

  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/receipt.css">
</head>
<body>
  <div class="wrap">
    <div class="card">
      <div class="top">
        <div>
          <h2 class="h2">Payment Receipt</h2>
          <div class="sub">Contract <b><?= htmlspecialchars($contract) ?></b></div>
        </div>
        <div class="badge <?= ($status==='SUCCESS'?'paid':($status==='FAILED'?'failed':'pending')) ?>">
          <?= htmlspecialchars($status) ?>
        </div>
      </div>

      <div class="grid">
        <div class="item"><div class="k">Receipt Reference No.</div><div class="v"><?= htmlspecialchars($rcptNo) ?></div></div>
        <div class="item"><div class="k">Paid Date</div><div class="v"><?= htmlspecialchars(fmtDateTime($tx['trans_date'] ?? null)) ?></div></div>

        <div class="item"><div class="k">Amount</div><div class="v"><?= htmlspecialchars(peso($tx['amount'])) ?></div></div>
        <div class="item"><div class="k">Payment Method</div><div class="v"><?= htmlspecialchars($method) ?></div></div>

        <div class="item"><div class="k"><?= htmlspecialchars($proofLabel) ?></div><div class="v"><?= htmlspecialchars($proof) ?></div></div>
        <div class="item"><div class="k">Verify Link</div><div class="v small"><?= htmlspecialchars($verifyLink) ?></div></div>
      </div>

      <div class="imgBlock">
        <div class="imgTitle">Receipt Image</div>

        <?php if ($status === 'PENDING'): ?>
          <?php if ($pendingImg): ?>
            <img class="img" src="<?= htmlspecialchars($pendingImg) ?>" alt="Pending Receipt Image">
            <div class="note">This is a <b>PENDING</b> receipt image. It will be replaced once payment is confirmed.</div>
          <?php else: ?>
            <div class="warn">Pending receipt image not found. (Enable GD + check receipts folder permissions)</div>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($finalImg): ?>
            <img class="img" src="<?= htmlspecialchars($finalImg) ?>" alt="Final Receipt Image">
            <?php if ($pendingImg): ?>
              <details class="details">
                <summary>Show Pending Receipt Image</summary>
                <img class="img" src="<?= htmlspecialchars($pendingImg) ?>" alt="Pending Receipt Image">
              </details>
            <?php endif; ?>
          <?php else: ?>
            <div class="warn">Final receipt image not generated yet. (Webhook should update status + image)</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="btns">
        <a class="btn btn-dark" href="myloans.php">Back to My Loans</a>
        <a class="btn btn-green btn-print" href="#">Print</a>
      </div>
    </div>
  </div>

  <script src="assets/js/receipt.js"></script>
</body>
</html>