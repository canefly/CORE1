<?php
session_start();
require_once __DIR__ . "/include/config.php";
include __DIR__ . "/include/session_checker.php";

// Kick out if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message = "";

/**
 * Build valid term options based on old term and restructure type.
 */
function getValidTermOptions(int $oldTerm, string $type): array
{
    $options = [];

    switch ($type) {
        case 'Extension':
            $candidates = [$oldTerm + 3, $oldTerm + 6, $oldTerm + 9, $oldTerm + 12];
            foreach ($candidates as $term) {
                if ($term > $oldTerm && $term <= 60) {
                    $options[] = $term;
                }
            }
            break;

        case 'Holiday':
            $candidates = [$oldTerm + 1, $oldTerm + 2, $oldTerm + 3, $oldTerm + 6];
            foreach ($candidates as $term) {
                if ($term > $oldTerm && $term <= 60) {
                    $options[] = $term;
                }
            }
            break;

        case 'Shorten':
            $candidates = [$oldTerm - 3, $oldTerm - 6, $oldTerm - 9, $oldTerm - 12];
            foreach ($candidates as $term) {
                if ($term >= 1 && $term < $oldTerm) {
                    $options[] = $term;
                }
            }
            break;
    }

    return array_values(array_unique($options));
}

/**
 * Estimate monthly payment based on OUTSTANDING only.
 */
function estimateMonthly(float $principal, float $ratePercent, int $termMonths): float
{
    if ($termMonths <= 0) return 0;

    $rateDecimal = $ratePercent / 100;
    $totalInterest = $principal * $rateDecimal * $termMonths;

    return ($principal + $totalInterest) / $termMonths;
}

/**
 * Check if the loan has a payment that is still awaiting collection verification.
 */
function hasPendingPayment(mysqli $conn, int $loanId, int $userId): bool
{
    $stmt = $conn->prepare("
        SELECT id
        FROM transactions
        WHERE loan_id = ? 
          AND user_id = ? 
          AND status = 'PAID_PENDING'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $loanId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasPending = $result->num_rows > 0;
    $stmt->close();

    return $hasPending;
}

/**
 * Only allow one pending restructure request per loan.
 */
function hasPendingRestructureRequest(mysqli $conn, int $loanId, int $userId): bool
{
    $stmt = $conn->prepare("
        SELECT id
        FROM loan_restructure_requests
        WHERE loan_id = ?
          AND user_id = ?
          AND status = 'PENDING'
        LIMIT 1
    ");
    $stmt->bind_param("ii", $loanId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasPending = $result->num_rows > 0;
    $stmt->close();

    return $hasPending;
}

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_loan_id   = (int)($_POST['loan_id'] ?? 0);
    $restructure_type = trim($_POST['restructure_type'] ?? '');
    $new_term         = (int)($_POST['new_term'] ?? 0);
    $reason           = trim($_POST['reason'] ?? '');

    $allowedTypes = ['Extension', 'Holiday', 'Shorten'];

    if (!in_array($restructure_type, $allowedTypes, true)) {
        $message = "<div style='background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>Invalid restructure type selected.</div>";
    } else {
        // IMPORTANT:
        // Restructuring is based on OUTSTANDING, not loan_amount.
        $loanQuery = $conn->prepare("
            SELECT 
                id,
                outstanding,
                interest_rate,
                interest_method,
                term_months,
                monthly_due,
                status
            FROM loans
            WHERE id = ?
              AND user_id = ?
              AND status = 'ACTIVE'
            LIMIT 1
        ");
        $loanQuery->bind_param("ii", $target_loan_id, $user_id);
        $loanQuery->execute();
        $loanResult = $loanQuery->get_result();
        $targetLoan = $loanResult->fetch_assoc();
        $loanQuery->close();

        if (!$targetLoan) {
            $message = "<div style='background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>Error: Active loan not found.</div>";
        } elseif ((float)$targetLoan['outstanding'] <= 0) {
            $message = "<div style='background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>This loan has no outstanding balance to restructure.</div>";
        } elseif (hasPendingPayment($conn, $target_loan_id, $user_id)) {
            $message = "<div style='background:#f59e0b;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>You cannot request restructuring yet because you still have a payment pending verification. Please wait until collections verifies your payment so the outstanding balance is accurate.</div>";
        } elseif (hasPendingRestructureRequest($conn, $target_loan_id, $user_id)) {
            $message = "<div style='background:#f59e0b;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>You already have a pending restructure request for this loan.</div>";
        } elseif ($reason === '') {
            $message = "<div style='background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>Please provide a reason for your restructure request.</div>";
        } elseif (empty($_FILES['proof_doc']['name'])) {
            $message = "<div style='background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>Please upload a proof document.</div>";
        } else {
            $oldTerm        = (int)$targetLoan['term_months'];
            $outstanding    = (float)$targetLoan['outstanding']; // THIS is the restructuring basis
            $rate           = (float)$targetLoan['interest_rate'];
            $interestMethod = (string)$targetLoan['interest_method'];
            $oldMonthly     = (float)$targetLoan['monthly_due'];

            $validTerms = getValidTermOptions($oldTerm, $restructure_type);

            if ($new_term <= 0 || !in_array($new_term, $validTerms, true)) {
                $message = "<div style='background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>Invalid proposed term selected for the chosen restructure type.</div>";
            } else {
                $estimatedNewMonthly = estimateMonthly($outstanding, $rate, $new_term);

                $conn->begin_transaction();

                try {
                    // Upload proof document
                    $uploadDir = __DIR__ . "/uploads/loan_docs/";
                    if (!is_dir($uploadDir)) {
                        mkdir($uploadDir, 0775, true);
                    }

                    $f = $_FILES['proof_doc'];
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
                    $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];

                    if (!in_array($ext, $allowedExt, true)) {
                        throw new Exception("Invalid file type. Only JPG, JPEG, PNG, and PDF are allowed.");
                    }

                    if (!isset($f['tmp_name']) || !is_uploaded_file($f['tmp_name'])) {
                        throw new Exception("Uploaded file is invalid.");
                    }

                    $safeName = "RESTRUCTURE_PROOF_" . $target_loan_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;
                    $target = $uploadDir . $safeName;

                    if (!move_uploaded_file($f['tmp_name'], $target)) {
                        throw new Exception("Failed to upload proof document.");
                    }

                    $relativePath = "uploads/loan_docs/" . $safeName;

                    // Save restructure request to dedicated table
                    $sql = "INSERT INTO loan_restructure_requests (
                                loan_id,
                                user_id,
                                restructure_type,
                                outstanding_snapshot,
                                current_term_months,
                                requested_term_months,
                                current_monthly_due,
                                estimated_monthly_due,
                                interest_rate_snapshot,
                                interest_method_snapshot,
                                reason,
                                proof_doc_path,
                                status
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')";

                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "iisdiidddsss",
                        $target_loan_id,
                        $user_id,
                        $restructure_type,
                        $outstanding,
                        $oldTerm,
                        $new_term,
                        $oldMonthly,
                        $estimatedNewMonthly,
                        $rate,
                        $interestMethod,
                        $reason,
                        $relativePath
                    );

                    if (!$stmt->execute()) {
                        throw new Exception("Failed to save restructure request: " . $stmt->error);
                    }

                    $stmt->close();

                    $conn->commit();

                    $message = "<div style='background:#10b981;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>
                        <i class='bi bi-check-circle'></i> Restructure request submitted successfully! Waiting for review.
                    </div>";
                } catch (Exception $e) {
                    $conn->rollback();
                    $message = "<div style='background:#ef4444;color:white;padding:10px;border-radius:8px;margin-bottom:20px;'>
                        Error submitting request: " . htmlspecialchars($e->getMessage()) . "
                    </div>";
                }
            }
        }
    }
}

// --- 2. FETCH ACTIVE LOANS FOR DROPDOWN ---
// Show only loans that are ACTIVE and still have OUTSTANDING balance.
$active_loans = [];
$stmt = $conn->prepare("
    SELECT id, loan_amount, outstanding, term_months, monthly_due, interest_rate
    FROM loans
    WHERE user_id = ?
      AND status = 'ACTIVE'
      AND outstanding > 0
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $active_loans[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Restructure | MicroFinance</title>

    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/restructure.css">
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>

<div class="main-content">

    <div class="page-header">
        <h1>Loan Restructuring</h1>
        <p>Apply for changes to your loan terms if you are facing financial difficulties.</p>
    </div>

    <?php echo $message; ?>

    <div class="notice-card">
        <div class="notice-icon"><i class="bi bi-info-circle"></i></div>
        <div class="notice-content">
            <h4>Before you proceed</h4>
            <p>Restructuring is subject to approval. You must provide a valid reason and supporting proof. Final terms are still subject to review.</p>
        </div>
    </div>

    <div class="form-container">

        <div class="request-card">
            <div class="section-title">Application Details</div>

            <?php if (empty($active_loans)): ?>
                <p style="color:#94a3b8;padding:20px;text-align:center;">You don't have any active loans to restructure right now.</p>
            <?php else: ?>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="form-label">Select Active Loan</label>
                        <select class="form-select" name="loan_id" id="loanSelect" onchange="handleRestructureChange()" required>
                            <?php foreach ($active_loans as $loan): ?>
                                <option
                                    value="<?php echo (int)$loan['id']; ?>"
                                    data-outstanding="<?php echo htmlspecialchars($loan['outstanding']); ?>"
                                    data-monthly="<?php echo htmlspecialchars($loan['monthly_due']); ?>"
                                    data-term="<?php echo htmlspecialchars($loan['term_months']); ?>"
                                    data-rate="<?php echo htmlspecialchars($loan['interest_rate']); ?>"
                                >
                                    Loan #LN-<?php echo str_pad($loan['id'], 4, "0", STR_PAD_LEFT); ?>
                                    (Balance: ₱ <?php echo number_format((float)$loan['outstanding'], 2); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type of Restructure</label>
                        <select class="form-select" name="restructure_type" id="restructureType" onchange="handleRestructureChange()" required>
                            <option value="Extension">Term Extension (Lower Monthly Payment)</option>
                            <option value="Holiday">Payment Holiday (Pause for 1 Month)</option>
                            <option value="Shorten">Shorten Term (Higher Monthly Payment)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proposed New Term (Months)</label>
                        <select name="new_term" id="newTerm" class="form-select" onchange="updatePreview()" required>
                        </select>
                        <small style="display:block;margin-top:8px;color:#64748b;">
                            Options are automatically suggested based on the selected restructure type.
                        </small>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reason for Request</label>
                        <textarea name="reason" class="form-textarea" placeholder="Please explain why you need to restructure..." required></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proof of Hardship (Required)</label>
                        <div class="upload-area" onclick="document.getElementById('proof_doc').click()" style="cursor:pointer;">
                            <i class="bi bi-cloud-arrow-up" style="font-size:24px;color:#6b7280;"></i>
                            <span class="upload-text" id="file-label">Upload Medical Cert, Termination Letter, etc.</span>
                            <input
                                type="file"
                                name="proof_doc"
                                id="proof_doc"
                                accept=".jpg,.jpeg,.png,.pdf,image/*,application/pdf"
                                style="display:none;"
                                required
                                onchange="document.getElementById('file-label').innerText = this.files[0] ? this.files[0].name : 'Upload Medical Cert, Termination Letter, etc.'"
                            >
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" style="margin-top:20px;width:100%;">Submit Request</button>
                </form>
            <?php endif; ?>
        </div>

        <div class="preview-card" <?php if (empty($active_loans)) echo 'style="display:none;"'; ?>>
            <div class="preview-header">Projected Changes</div>

            <div class="compare-row">
                <span class="compare-label">Loan Term</span>
                <div class="compare-vals">
                    <span class="val-old" id="old-term">X Months</span>
                    <span class="val-new" id="preview-term">Y Months</span>
                </div>
            </div>

            <div class="compare-row">
                <span class="compare-label">Monthly Payment</span>
                <div class="compare-vals">
                    <span class="val-old" id="old-monthly">₱ 0.00</span>
                    <span class="val-new" id="preview-monthly">₱ 0.00</span>
                </div>
            </div>

            <div class="compare-row">
                <span class="compare-label">Interest Rate</span>
                <div class="compare-vals">
                    <span class="val-old" id="old-rate">0%</span>
                    <span class="val-new" id="preview-rate">0%</span>
                </div>
            </div>

            <div class="impact-box">
                <div class="impact-text">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span id="impact-note">
                        Note: These projections are estimates. Final terms will be reviewed and finalized upon approval.
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>

<script>
function peso(val) {
    return "₱ " + Number(val).toLocaleString(undefined, {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

function getLoanData() {
    const loanSelect = document.getElementById('loanSelect');
    if (!loanSelect || loanSelect.options.length === 0) return null;

    const selectedOption = loanSelect.options[loanSelect.selectedIndex];

    return {
        outstanding: parseFloat(selectedOption.getAttribute('data-outstanding')) || 0,
        oldMonthly: parseFloat(selectedOption.getAttribute('data-monthly')) || 0,
        oldTerm: parseInt(selectedOption.getAttribute('data-term')) || 0,
        rate: parseFloat(selectedOption.getAttribute('data-rate')) || 0
    };
}

function buildTermOptions() {
    const loanData = getLoanData();
    const type = document.getElementById('restructureType').value;
    const newTermSelect = document.getElementById('newTerm');

    if (!loanData || !newTermSelect) return;

    const oldTerm = loanData.oldTerm;
    let options = [];

    if (type === 'Extension') {
        options = [oldTerm + 3, oldTerm + 6, oldTerm + 9, oldTerm + 12]
            .filter(term => term > oldTerm && term <= 60);
    } else if (type === 'Holiday') {
        options = [oldTerm + 1, oldTerm + 2, oldTerm + 3, oldTerm + 6]
            .filter(term => term > oldTerm && term <= 60);
    } else if (type === 'Shorten') {
        options = [oldTerm - 3, oldTerm - 6, oldTerm - 9, oldTerm - 12]
            .filter(term => term >= 1 && term < oldTerm);
    }

    options = [...new Set(options)];
    newTermSelect.innerHTML = '';

    if (options.length === 0) {
        const opt = document.createElement('option');
        opt.value = '';
        opt.textContent = 'No available options';
        newTermSelect.appendChild(opt);
        return;
    }

    options.forEach((term, index) => {
        const opt = document.createElement('option');
        opt.value = term;

        if (type === 'Holiday') {
            const extension = term - oldTerm;
            opt.textContent = `${term} months (includes +${extension} month extension)`;
        } else {
            opt.textContent = `${term} months`;
        }

        if (index === 0) opt.selected = true;
        newTermSelect.appendChild(opt);
    });
}

function updatePreview() {
    const loanData = getLoanData();
    if (!loanData) return;

    const type = document.getElementById('restructureType').value;
    const newTerm = parseInt(document.getElementById('newTerm').value) || 0;

    const outstanding = loanData.outstanding;
    const oldMonthly = loanData.oldMonthly;
    const oldTerm = loanData.oldTerm;
    const rate = loanData.rate;

    document.getElementById('old-term').innerText = oldTerm + " Months";
    document.getElementById('old-monthly').innerText = peso(oldMonthly);
    document.getElementById('old-rate').innerText = rate + "%";
    document.getElementById('preview-rate').innerText = rate + "%";

    const termDisplay = document.getElementById('preview-term');
    const monthlyDisplay = document.getElementById('preview-monthly');
    const impactNote = document.getElementById('impact-note');

    if (!newTerm) {
        termDisplay.innerText = "-";
        monthlyDisplay.innerText = "₱ 0.00";
        impactNote.innerText = "No valid restructuring option is available for this loan.";
        return;
    }

    const totalInterest = outstanding * (rate / 100) * newTerm;
    const estimatedMonthly = (outstanding + totalInterest) / newTerm;

    if (type === 'Extension') {
        termDisplay.innerText = newTerm + " Months";
        monthlyDisplay.innerText = peso(estimatedMonthly);
        impactNote.innerText = "Extension usually lowers the monthly payment, but total paid over time may become higher.";
    } else if (type === 'Holiday') {
        termDisplay.innerText = newTerm + " Months";
        monthlyDisplay.innerText = peso(estimatedMonthly);
        impactNote.innerText = "Payment holiday gives temporary relief now, but usually extends the loan term.";
    } else if (type === 'Shorten') {
        termDisplay.innerText = newTerm + " Months";
        monthlyDisplay.innerText = peso(estimatedMonthly);
        impactNote.innerText = "Shortening the term usually increases monthly payment, but finishes the loan faster.";
    }
}

function handleRestructureChange() {
    buildTermOptions();
    updatePreview();
}

window.onload = function () {
    handleRestructureChange();
};
</script>

</body>
</html>