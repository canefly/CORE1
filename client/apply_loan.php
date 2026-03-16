<?php
session_start();
require_once __DIR__ . "/include/config.php";
include __DIR__ . "/include/session_checker.php";

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = (int)$_SESSION['user_id'];

// ==============================
// FETCH SYSTEM SETTINGS (Dynamic Rates)
// ==============================
$sysSettings = [];
$sysStmt = $conn->query("SELECT setting_key, setting_value FROM system_settings");
if ($sysStmt) {
    while ($row = $sysStmt->fetch_assoc()) {
        $sysSettings[$row['setting_key']] = $row['setting_value'];
    }
}
// Fallback sa 3.5 at FLAT kung sakaling walang laman ang table
$current_interest_rate = (float)($sysSettings['default_interest_rate'] ?? 3.5);
$current_interest_method = $sysSettings['interest_method'] ?? 'FLAT';

// ==============================
// BLOCK CHECK: existing application / disbursement / active loan
// ==============================
$blockLoanApplication = false;
$blockTitle = "Loan Application Unavailable";
$blockMessage = "You already have an existing loan in process.";

$latestApplication = null;
$latestDisbursement = null;
$latestLoan = null;

// Latest application
$stmt = $conn->prepare("SELECT * FROM loan_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) { $latestApplication = $res->fetch_assoc(); }
$stmt->close();

$appId = (int)($latestApplication['id'] ?? 0);
$appStatus = strtoupper((string)($latestApplication['status'] ?? 'NONE'));

// Related disbursement
if ($appId > 0) {
  $stmt = $conn->prepare("SELECT * FROM loan_disbursement WHERE application_id = ? ORDER BY id DESC LIMIT 1");
  $stmt->bind_param("i", $appId);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && $res->num_rows === 1) { $latestDisbursement = $res->fetch_assoc(); }
  $stmt->close();
}

$disbStatus = strtoupper((string)($latestDisbursement['status'] ?? 'NONE'));

// Latest active loan
$stmt = $conn->prepare("SELECT * FROM loans WHERE user_id = ? AND status = 'ACTIVE' ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) { $latestLoan = $res->fetch_assoc(); }
$stmt->close();

$hasOngoingApplication = in_array($appStatus, ['PENDING', 'VERIFIED', 'APPROVED'], true);
$waitingDisbursement = ($disbStatus === 'WAITING FOR DISBURSEMENT');
$hasActiveLoan = (bool)$latestLoan;

if ($hasOngoingApplication || $waitingDisbursement || $hasActiveLoan) {
  $blockLoanApplication = true;
  if ($hasActiveLoan) {
    $blockTitle = "Existing Active Loan";
    $blockMessage = "You cannot apply for a new loan because you still have an active loan that is not yet fully paid.";
  } elseif ($waitingDisbursement) {
    $blockTitle = "Loan Waiting for Disbursement";
    $blockMessage = "You cannot apply for a new loan because your approved loan is still waiting for disbursement.";
  } elseif ($appStatus === 'PENDING') {
    $blockTitle = "Application Still Pending";
    $blockMessage = "You cannot apply for a new loan because your current application is still pending review.";
  } elseif ($appStatus === 'VERIFIED') {
    $blockTitle = "Application Under Review";
    $blockMessage = "You cannot apply for a new loan because your current application has already been verified and is under Loan Officer review.";
  } elseif ($appStatus === 'APPROVED') {
    $blockTitle = "Application Already Approved";
    $blockMessage = "You cannot apply for a new loan because your current application has already been approved and is being processed.";
  }
}

// POST SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if ($blockLoanApplication) {
    http_response_code(403);
    exit("You cannot apply for a new loan while an existing loan or application is still in process.");
  }

  $principal_amount = (float)($_POST['principal_amount'] ?? 0);
  $term_months = (int)($_POST['term_months'] ?? 0);
  $loan_purpose = trim($_POST['loan_purpose'] ?? '');
  $source_of_income = trim($_POST['source_of_income'] ?? '');
  $estimated_monthly_income = (float)($_POST['estimated_monthly_income'] ?? 0);

  // GAGAMITIN NATIN DITO ANG DYNAMIC RATE MULA SA DATABASE
  $interest_rate = $current_interest_rate;
  $interest_type = 'MONTHLY';
  $interest_method = $current_interest_method;

  $rateDecimal = $interest_rate / 100;
  $total_interest = $principal_amount * $rateDecimal * $term_months;
  $total_payable  = $principal_amount + $total_interest;
  $monthly_due    = $total_payable / $term_months;

  if ($principal_amount <= 0) exit("Invalid loan amount.");
  if ($term_months < 1 || $term_months > 12) exit("Term must be 1-12 months.");
  if ($loan_purpose === '') exit("Loan purpose required.");
  if ($source_of_income === '') exit("Source of income required.");
  if ($estimated_monthly_income <= 0) exit("Estimated monthly income must be > 0.");

  $requiredFiles = ['gov_id' => 'GOV_ID', 'proof_income' => 'PROOF_OF_INCOME', 'proof_billing' => 'PROOF_OF_BILLING'];
  foreach ($requiredFiles as $input => $type) {
    if (!isset($_FILES[$input]) || $_FILES[$input]['error'] !== UPLOAD_ERR_OK) {
      exit("Missing upload: " . $input);
    }
  }

  $conn->begin_transaction();
  try {
    $sql = "INSERT INTO loan_applications
      (user_id, principal_amount, term_months, loan_purpose, source_of_income, estimated_monthly_income, interest_rate, interest_type, interest_method, total_interest, total_payable, monthly_due, status)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idissddssddd", $user_id, $principal_amount, $term_months, $loan_purpose, $source_of_income, $estimated_monthly_income, $interest_rate, $interest_type, $interest_method, $total_interest, $total_payable, $monthly_due);
    $stmt->execute();
    $application_id = $conn->insert_id;
    $stmt->close();

    $uploadDir = __DIR__ . "/uploads/loan_docs/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

    $docStmt = $conn->prepare("INSERT INTO loan_documents (loan_application_id, doc_type, file_path) VALUES (?, ?, ?)");
    $allowedExt = ['jpg','jpeg','png','pdf','webp'];

    foreach ($requiredFiles as $input => $docType) {
      $f = $_FILES[$input];
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      $safeName = $docType . "_" . $application_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;
      $target = $uploadDir . $safeName;
      move_uploaded_file($f['tmp_name'], $target);
      $relativePath = "uploads/loan_docs/" . $safeName;
      $docStmt->bind_param("iss", $application_id, $docType, $relativePath);
      $docStmt->execute();
    }
    $docStmt->close();
    $conn->commit();
    header("Location: dashboard.php?loan_applied=1");
    exit;
  } catch (Exception $e) {
    $conn->rollback();
    exit("Error: " . htmlspecialchars($e->getMessage()));
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Apply for Loan | MicroFinance</title>
  <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
  <link rel="stylesheet" href="assets/css/apply_loan.css">
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>

<div class="main-content">
  <div class="page-header">
    <h1>Loan Application</h1>
    <p>Customize your loan plan and submit your requirements.</p>
  </div>

  <div class="wizard-steps">
    <div class="step active" id="step1-ind"><div class="step-circle">1</div><div class="step-label">Calculator</div></div>
    <div class="step" id="step2-ind"><div class="step-circle">2</div><div class="step-label">Details</div></div>
    <div class="step" id="step3-ind"><div class="step-circle">3</div><div class="step-label">Upload</div></div>
  </div>

  <?php if (!$blockLoanApplication): ?>
  <div id="step1" class="calc-container">
    <div class="calc-inputs">
      <div class="range-group">
        <div class="range-header">
          <span class="range-label">I want to borrow</span>
          <div class="input-wrapper-manual">
            <span class="currency-symbol">₱</span>
            <input type="number" id="manual-amt" class="manual-input" value="10000" min="5000" max="50000" oninput="syncSlider()">
          </div>
        </div>
        <input type="range" min="5000" max="50000" step="1000" value="10000" id="range-amt" oninput="syncInput()">
      </div>

      <div class="range-group">
        <div class="range-header"><span class="range-label">Repayment Term (Months)</span></div>
        <div class="term-options">
          <button type="button" class="term-box" id="btn-term-1" onclick="selectTerm(1, this)">1</button>
          <button type="button" class="term-box" id="btn-term-2" onclick="selectTerm(2, this)">2</button>
          <button type="button" class="term-box" id="btn-term-3" onclick="selectTerm(3, this)">3</button>
          <button type="button" class="term-box active" id="btn-term-6" onclick="selectTerm(6, this)">6</button>
          <button type="button" class="term-box" id="btn-term-9" onclick="selectTerm(9, this)">9</button>
          <button type="button" class="term-box" id="btn-term-12" onclick="selectTerm(12, this)">12</button>
        </div>
        <input type="hidden" id="manual-term" value="6">
      </div>

      <div style="font-size:12px; color:#64748b; margin-top:20px; line-height:1.5;">
        <i class="bi bi-info-circle"></i> Note: Interest rate is currently set at <strong><?= htmlspecialchars($current_interest_rate) ?>% per month</strong>.
      </div>
    </div>

    <div class="calc-result">
      <span class="monthly-label">Monthly Payment</span>
      <div class="monthly-val" id="monthly-pay">₱ 0.00</div>
      <div class="breakdown-row"><span>Principal</span><span id="bd-principal">₱ 0</span></div>
      <div class="breakdown-row"><span>Total Interest</span><span id="bd-interest" style="color:#fbbf24;">₱ 0</span></div>
      <button class="btn-next" type="button" onclick="goToStep(2)">Proceed Application</button>
    </div>
  </div>

  <form id="loanForm" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="principal_amount" id="principal_amount" value="10000">
    <input type="hidden" name="term_months" id="term_months" value="6">
    <input type="hidden" name="interest_rate" value="<?= htmlspecialchars($current_interest_rate) ?>">
    <input type="hidden" name="interest_type" value="MONTHLY">
    <input type="hidden" name="interest_method" value="<?= htmlspecialchars($current_interest_method) ?>">

    <div id="step2" class="form-card" style="display:none;">
      <h3 style="color:#fff; margin-bottom:20px;">Purpose & Income</h3>
      <div class="input-group">
        <label class="input-label">Loan Purpose</label>
        <select class="form-control" name="loan_purpose" required>
          <option value="Business Capital">Business Capital</option>
          <option value="Medical Emergency">Medical Emergency</option>
          <option value="Education / Tuition">Education / Tuition</option>
          <option value="Home Repair">Home Repair</option>
        </select>
      </div>
      <div class="input-group">
        <label class="input-label">Source of Income</label>
        <input type="text" class="form-control" name="source_of_income" placeholder="e.g. Sari-sari Store Owner" required>
      </div>
      <div class="input-group">
        <label class="input-label">Estimated Monthly Income</label>
        <input type="number" class="form-control" name="estimated_monthly_income" placeholder="₱ 0.00" step="0.01" min="0" required>
      </div>
      <button class="btn-next" type="button" onclick="goToStep(3)">Next Step</button>
      <center><button class="btn-back" type="button" onclick="goToStep(1)">Back to Calculator</button></center>
    </div>

    <div id="step3" class="form-card" style="display:none;">
      <h3 style="color:#fff; margin-bottom:20px;">Document Requirements</h3>
      <div class="input-group">
        <label class="input-label">1. Valid Government ID</label>
        <div class="upload-area" onclick="document.getElementById('gov_id').click()">
          <i class="bi bi-person-badge" style="font-size:24px; color:#64748b;"></i>
          <div style="font-size:12px; color:#94a3b8; margin-top:5px;">Click to upload ID (JPG/PNG/PDF)</div>
          <div id="gov_id_name" style="font-size:12px; margin-top:6px; color:#10b981;"></div>
        </div>
        <input type="file" id="gov_id" name="gov_id" accept="image/*,application/pdf" required style="display:none">
      </div>
      <div class="input-group">
        <label class="input-label">2. Proof of Income / Business</label>
        <div class="upload-area" onclick="document.getElementById('proof_income').click()">
          <i class="bi bi-file-earmark-text" style="font-size:24px; color:#64748b;"></i>
          <div style="font-size:12px; color:#94a3b8; margin-top:5px;">Click to upload Document</div>
          <div id="proof_income_name" style="font-size:12px; margin-top:6px; color:#10b981;"></div>
        </div>
        <input type="file" id="proof_income" name="proof_income" accept="image/*,application/pdf" required style="display:none">
      </div>
      <div class="input-group">
        <label class="input-label">3. Proof of Billing / Barangay Certificate</label>
        <div class="upload-area" onclick="document.getElementById('proof_billing').click()">
          <i class="bi bi-file-earmark-text" style="font-size:24px; color:#64748b;"></i>
          <div style="font-size:12px; color:#94a3b8; margin-top:5px;">Click to upload Document</div>
          <div id="proof_billing_name" style="font-size:12px; margin-top:6px; color:#10b981;"></div>
        </div>
        <input type="file" id="proof_billing" name="proof_billing" accept="image/*,application/pdf" required style="display:none">
      </div>
      <button class="btn-next" type="button" onclick="submitApp()">Submit Application</button>
      <center><button class="btn-back" type="button" onclick="goToStep(2)">Back to Details</button></center>
    </div>
  </form>
  <?php endif; ?>
</div>

<div id="errorModal" class="modal-overlay">
  <div class="modal-content">
    <i class="bi bi-exclamation-circle-fill error-icon"></i>
    <h3 id="modalTitle">Action Required</h3>
    <p id="modalMessage">Please ensure all fields are filled out.</p>
    <button class="btn-next" style="margin-top: 10px;" onclick="closeModal()">Understood</button>
  </div>
</div>

<script>
  function showModal(title, message) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalMessage').innerText = message;
    document.getElementById('errorModal').classList.add('show');
  }

  function closeModal() {
    document.getElementById('errorModal').classList.remove('show');
    <?php if ($blockLoanApplication): ?> window.location.href = 'dashboard.php'; <?php endif; ?>
  }

  function checkRules() {
    let amount = parseInt(document.getElementById('manual-amt')?.value || 0) || 0;
    let term12Btn = document.getElementById('btn-term-12');
    let currentTerm = parseInt(document.getElementById('manual-term')?.value || 6);
    if (!term12Btn) return;
    if (amount < 50000) {
      term12Btn.disabled = true;
      if (currentTerm === 12) selectTerm(9, document.getElementById('btn-term-9'));
    } else {
      term12Btn.disabled = false;
    }
  }

  function syncInput() { document.getElementById('manual-amt').value = document.getElementById('range-amt').value; checkRules(); calculate(); }
  function syncSlider() { document.getElementById('range-amt').value = document.getElementById('manual-amt').value; checkRules(); calculate(); }

  function selectTerm(months, btnElement) {
    if (!btnElement || btnElement.disabled) return;
    document.getElementById('manual-term').value = months;
    document.querySelectorAll('.term-box').forEach(box => box.classList.remove('active'));
    btnElement.classList.add('active');
    calculate();
  }

  function calculate() {
    let amount = parseInt(document.getElementById('manual-amt')?.value || 0) || 0;
    let months = parseInt(document.getElementById('manual-term')?.value || 1) || 1;
    let rate = <?= $current_interest_rate / 100 ?>; 

    let totalInterest = amount * rate * months;
    let total = amount + totalInterest;
    let monthly = total / months;

    document.getElementById('monthly-pay').innerText = '₱ ' + monthly.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('bd-principal').innerText = '₱ ' + amount.toLocaleString();
    document.getElementById('bd-interest').innerText = '₱ ' + totalInterest.toLocaleString();
    document.getElementById('principal_amount').value = amount;
    document.getElementById('term_months').value = months;
  }

  function goToStep(step) {
    document.getElementById('step1').style.display = 'none';
    document.getElementById('step2').style.display = 'none';
    document.getElementById('step3').style.display = 'none';
    document.getElementById('step1-ind').classList.remove('active');
    document.getElementById('step2-ind').classList.remove('active');
    document.getElementById('step3-ind').classList.remove('active');
    if (step === 1) document.getElementById('step1').style.display = 'grid';
    else document.getElementById('step' + step).style.display = 'block';
    for (let i = 1; i <= step; i++) document.getElementById('step' + i + '-ind').classList.add('active');
  }

  function submitApp() {
    const income = document.querySelector('[name="estimated_monthly_income"]')?.value || '';
    const source = document.querySelector('[name="source_of_income"]')?.value || '';
    if (!source || !income || parseFloat(income) <= 0) { showModal("Missing Details", "Please complete your Source of Income and Estimated Monthly Income."); goToStep(2); return; }
    
    const govId = document.getElementById('gov_id')?.files.length || 0;
    const proofIncome = document.getElementById('proof_income')?.files.length || 0;
    const proofBilling = document.getElementById('proof_billing')?.files.length || 0;
    if (govId === 0 || proofIncome === 0 || proofBilling === 0) { showModal("Missing Documents", "You must upload all 3 requirements."); return; }
    
    document.getElementById('loanForm').submit();
  }

  document.addEventListener('DOMContentLoaded', () => {
    <?php if ($blockLoanApplication): ?> showModal(<?= json_encode($blockTitle) ?>, <?= json_encode($blockMessage) ?>); return; <?php endif; ?>
    document.getElementById('gov_id')?.addEventListener('change', e => document.getElementById('gov_id_name').innerText = e.target.files[0]?.name || '');
    document.getElementById('proof_income')?.addEventListener('change', e => document.getElementById('proof_income_name').innerText = e.target.files[0]?.name || '');
    document.getElementById('proof_billing')?.addEventListener('change', e => document.getElementById('proof_billing_name').innerText = e.target.files[0]?.name || '');
    checkRules();
    calculate();
  });
</script>
</body>
</html>