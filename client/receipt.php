<?php
// CLIENT/receipt.php
declare(strict_types=1);

session_start();
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/paymongo_config.php";
require_once __DIR__ . "/receipt_image_generator.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];
$tx_id   = (int)($_GET['tx_id'] ?? 0);
if ($tx_id <= 0) {
  exit("Invalid tx_id");
}

/**
 * Helpers
 */
function peso($n): string {
  return "₱ " . number_format((float)$n, 2);
}

function fmtDateTime($d): string {
  if (!$d) return "-";
  try {
    return (new DateTime((string)$d))->format("M d, Y h:i A");
  } catch (Exception $e) {
    return "-";
  }
}

function status_badge_class(string $realStatus): string {
  $s = strtoupper($realStatus);
  if ($s === "SUCCESS") return "paid";
  if ($s === "FAILED")  return "failed";
  return "pending";
}

function should_show_pending_only(string $realStatus): bool {
  $s = strtoupper($realStatus);
  return ($s === "PENDING" || $s === "PAID_PENDING");
}

function build_contract(int $loan_id): string {
  return "#LN-" . str_pad((string)$loan_id, 4, "0", STR_PAD_LEFT);
}

/**
 * 1) Fetch tx (must belong to logged-in user via loans table)
 */
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

/**
 * 2) Normalize fields
 */
$status  = strtoupper((string)($tx["status"] ?? "PENDING"));
$loan_id = (int)($tx["loan_id"] ?? 0);
$amount  = (float)($tx["amount"] ?? 0);

$contract = build_contract($loan_id);
$rcptNo   = (string)($tx["receipt_number"] ?? "-");

$methodRaw = strtoupper((string)($tx["provider_method"] ?? ""));
$method    = $methodRaw !== "" ? $methodRaw : "Selected at PayMongo Checkout";

// Proof: pending uses checkout id, paid uses payment id
$proof = (string)($tx["paymongo_payment_id"] ?? "");
$proofLabel = "PayMongo Payment ID (Proof)";
if ($proof === "") {
  $proof = (string)($tx["paymongo_checkout_id"] ?? "");
  $proofLabel = "PayMongo Checkout ID (Proof)";
}
if ($proof === "") $proof = "-";

$verifyLink = PUBLIC_BASE_URL . "/client/receipt.php?tx_id=" . $tx_id;

/**
 * 3) Receipt image resolution logic
 * Rule:
 * - While PENDING or PAID_PENDING: show ONLY pending image (ONE receipt)
 * - Only when SUCCESS/FAILED: show final image
 */
$pendingImg = (string)($tx["receipt_image_pending_url"] ?? "");
$finalImg   = (string)($tx["receipt_image_final_url"] ?? "");

// Fallback file names (local generation)
$dir = ensure_receipt_dir();
$expectedPending = ($rcptNo !== "-" ? "{$rcptNo}-PENDING.png" : "TX{$tx_id}-PENDING.png");
$expectedFinal   = ($rcptNo !== "-" ? "{$rcptNo}-FINAL.png"   : "TX{$tx_id}-FINAL.png");

$filePathPending = $dir . "/" . $expectedPending;
$filePathFinal   = $dir . "/" . $expectedFinal;

$urlPending = "receipts/" . $expectedPending;
$urlFinal   = "receipts/" . $expectedFinal;

// If DB path missing but file exists, use it
if ($pendingImg === "" && file_exists($filePathPending)) $pendingImg = $urlPending;
if ($finalImg === "" && file_exists($filePathFinal))     $finalImg   = $urlFinal;

// HARD RULE: while not verified, never show final
$pendingOnly = should_show_pending_only($status);
if ($pendingOnly) {
  // ensure pending image exists
  if (!file_exists($filePathPending)) {
    generate_receipt_png($tx, $verifyLink, $filePathPending);
  }
  if (file_exists($filePathPending)) $pendingImg = $urlPending;

  // never show final while pendingOnly
  $finalImg = "";
} else {
  // SUCCESS / FAILED -> generate final if missing
  if (!file_exists($filePathFinal)) {
    generate_receipt_png($tx, $verifyLink, $filePathFinal);
  }
  if (file_exists($filePathFinal)) $finalImg = $urlFinal;
}

// cache bust (avoid stale browser image)
$cb = time();
if ($pendingImg !== "") $pendingImg .= (str_contains($pendingImg, '?') ? '&' : '?') . "v={$cb}";
if ($finalImg !== "")   $finalImg   .= (str_contains($finalImg, '?') ? '&' : '?') . "v={$cb}";

/**
 * 4) Customer-facing status label
 * You can choose:
 * - show "PENDING" while PAID_PENDING (recommended)
 * OR show "SUCCESS" with verification note (your earlier idea)
 *
 * I’ll keep it clear: show SUCCESS only when really SUCCESS.
 * For PAID_PENDING, show PENDING (For Verification) label.
 */
$displayStatus = $status;
if ($status === "PAID_PENDING") {
  $displayStatus = "PAID_PENDING";
}
$badgeClass = status_badge_class($displayStatus);
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
        <div class="badge <?= htmlspecialchars($badgeClass) ?>">
          <?= htmlspecialchars($displayStatus) ?>
        </div>
      </div>

      <div class="grid">
        <div class="item"><div class="k">Receipt Reference No.</div><div class="v"><?= htmlspecialchars($rcptNo) ?></div></div>
        <div class="item"><div class="k">Paid Date</div><div class="v"><?= htmlspecialchars(fmtDateTime($tx['trans_date'] ?? null)) ?></div></div>

        <div class="item"><div class="k">Amount</div><div class="v"><?= htmlspecialchars(peso($amount)) ?></div></div>
        <div class="item"><div class="k">Payment Method</div><div class="v"><?= htmlspecialchars($method) ?></div></div>

        <div class="item"><div class="k"><?= htmlspecialchars($proofLabel) ?></div><div class="v"><?= htmlspecialchars($proof) ?></div></div>
        <div class="item"><div class="k">Verify Link</div><div class="v small"><?= htmlspecialchars($verifyLink) ?></div></div>
      </div>

      <div class="imgBlock">
        <div class="imgTitle">Receipt Image</div>

        <?php if ($status === "PAID_PENDING"): ?>
          <div class="note">
            ✅ Payment received. <b>Please wait for Finance verification</b> before we deduct this amount from your balance.
          </div>
        <?php endif; ?>

        <?php if ($pendingOnly): ?>
          <?php if ($pendingImg !== ""): ?>
            <img class="img" src="<?= htmlspecialchars($pendingImg) ?>" alt="Pending Receipt Image">
            <div class="note">
              This receipt is <b>PAID_PENDING</b> until Finance verifies the payment.
            </div>
          <?php else: ?>
            <div class="warn">Pending receipt image not found. (Enable GD + check receipts folder permissions)</div>
          <?php endif; ?>
        <?php else: ?>
          <?php if ($finalImg !== ""): ?>
            <img class="img" src="<?= htmlspecialchars($finalImg) ?>" alt="Final Receipt Image">
          <?php else: ?>
            <div class="warn">Final receipt image not generated yet.</div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="btns">
        <a class="btn btn-dark" href="myloans.php">Back to My Loans</a>
        <button type="button" class="btn btn-green btn-print" onclick="window.print()">Print</button>
      </div>
    </div>
  </div>
</body>
</html>