<?php
session_start();
require_once __DIR__ . "/include/config.php";

// Kick out if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message = "";

// --- 1. HANDLE FORM SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_loan_id = (int)$_POST['loan_id'];
    $restructure_type = $_POST['restructure_type'] ?? '';
    $new_term = (int)$_POST['new_term'];
    $reason = trim($_POST['reason'] ?? '');
    
    // Fetch the target loan to get the outstanding balance
    $loanQuery = $conn->prepare("SELECT outstanding, interest_rate FROM loans WHERE id = ? AND user_id = ? AND status = 'ACTIVE'");
    $loanQuery->bind_param("ii", $target_loan_id, $user_id);
    $loanQuery->execute();
    $loanResult = $loanQuery->get_result();
    $targetLoan = $loanResult->fetch_assoc();
    $loanQuery->close();

    if (!$targetLoan) {
        $message = "<div style='background: #ef4444; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;'>Error: Active loan not found.</div>";
    } elseif (empty($reason) || empty($_FILES['proof_doc']['name'])) {
        $message = "<div style='background: #ef4444; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;'>Please provide a reason and upload your proof document.</div>";
    } else {
        $outstanding = $targetLoan['outstanding'];
        $rate = $targetLoan['interest_rate'];
        
        // We tag the loan purpose so the LSA Admin picks it up as a Restructure
        $loan_purpose = "Restructure ($restructure_type) - $reason";
        
        $conn->begin_transaction();
        try {
            // Insert into loan_applications
            $sql = "INSERT INTO loan_applications 
                    (user_id, principal_amount, term_months, loan_purpose, source_of_income, estimated_monthly_income, interest_rate, interest_type, interest_method, status) 
                    VALUES (?, ?, ?, ?, 'Restructure Request', 0, ?, 'MONTHLY', 'FLAT', 'PENDING')";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idiss", $user_id, $outstanding, $new_term, $loan_purpose, $rate);
            $stmt->execute();
            $application_id = $conn->insert_id;
            $stmt->close();

            // Handle the File Upload
            $uploadDir = __DIR__ . "/uploads/loan_docs/";
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

            $f = $_FILES['proof_doc'];
            $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
            $safeName = "PROOF_OF_INCOME_" . $application_id . "_" . bin2hex(random_bytes(8)) . "." . $ext;
            $target = $uploadDir . $safeName;

            if (move_uploaded_file($f['tmp_name'], $target)) {
                $relativePath = "uploads/loan_docs/" . $safeName;
                // Note: using PROOF_OF_INCOME because the DB ENUM restricts doc_types
                $docStmt = $conn->prepare("INSERT INTO loan_documents (loan_application_id, doc_type, file_path) VALUES (?, 'PROOF_OF_INCOME', ?)");
                $docStmt->bind_param("is", $application_id, $relativePath);
                $docStmt->execute();
                $docStmt->close();
            }

            $conn->commit();
            $message = "<div style='background: #10b981; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;'><i class='bi bi-check-circle'></i> Restructure request submitted successfully! Waiting for LSA review.</div>";

        } catch (Exception $e) {
            $conn->rollback();
            $message = "<div style='background: #ef4444; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;'>Error submitting request: " . $e->getMessage() . "</div>";
        }
    }
}

// --- 2. FETCH ACTIVE LOANS FOR DROPDOWN ---
$active_loans = [];
$stmt = $conn->prepare("SELECT id, loan_amount, outstanding, term_months, monthly_due FROM loans WHERE user_id = ? AND status = 'ACTIVE'");
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
                <p>Restructuring is subject to approval. You must provide a valid reason (e.g., medical emergency, job loss) and proof. A restructuring fee may apply.</p>
            </div>
        </div>

        <div class="form-container">
            
            <div class="request-card">
                <div class="section-title">Application Details</div>
                
                <?php if (empty($active_loans)): ?>
                    <p style="color: #94a3b8; padding: 20px; text-align: center;">You don't have any active loans to restructure right now.</p>
                <?php else: ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label class="form-label">Select Active Loan</label>
                            <select class="form-select" name="loan_id" id="loanSelect" onchange="updatePreview()">
                                <?php foreach ($active_loans as $loan): ?>
                                    <option value="<?php echo $loan['id']; ?>" 
                                            data-outstanding="<?php echo $loan['outstanding']; ?>"
                                            data-monthly="<?php echo $loan['monthly_due']; ?>"
                                            data-term="<?php echo $loan['term_months']; ?>">
                                        Loan #LN-<?php echo str_pad($loan['id'], 4, "0", STR_PAD_LEFT); ?> (Balance: ₱ <?php echo number_format($loan['outstanding'], 2); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Type of Restructure</label>
                            <select class="form-select" name="restructure_type" id="restructureType" onchange="updatePreview()">
                                <option value="Extension">Term Extension (Lower Monthly Payment)</option>
                                <option value="Holiday">Payment Holiday (Pause for 1 Month)</option>
                                <option value="Shorten">Shorten Term (Higher Monthly Payment)</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Proposed New Term (Months)</label>
                            <input type="number" name="new_term" id="newTerm" class="form-select" value="6" min="1" max="24" oninput="updatePreview()" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Reason for Request</label>
                            <textarea name="reason" class="form-textarea" placeholder="Please explain why you need to restructure..." required></textarea>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Proof of Hardship (Required)</label>
                            <div class="upload-area" onclick="document.getElementById('proof_doc').click()" style="cursor: pointer;">
                                <i class="bi bi-cloud-arrow-up" style="font-size:24px; color:#6b7280;"></i>
                                <span class="upload-text" id="file-label">Upload Medical Cert, Termination Letter, etc.</span>
                                <input type="file" name="proof_doc" id="proof_doc" accept="image/*,application/pdf" style="display:none;" required onchange="document.getElementById('file-label').innerText = this.files[0].name">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn-submit" style="margin-top: 20px; width: 100%;">Submit Request</button>
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
                        <span class="val-old">3.5%</span>
                        <span class="val-new">3.5%</span> 
                    </div>
                </div>

                <div class="impact-box">
                    <div class="impact-text">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>
                            Note: These projections are estimates. Final terms will be reviewed and finalized by the Loan Officer upon approval.
                        </span>
                    </div>
                </div>
            </div>

        </div>

    </div>

    <script>
        // Dynamic JavaScript to handle real-time calculations for the Preview Card
        function updatePreview() {
            const loanSelect = document.getElementById('loanSelect');
            if (!loanSelect || loanSelect.options.length === 0) return;

            const selectedOption = loanSelect.options[loanSelect.selectedIndex];
            const outstanding = parseFloat(selectedOption.getAttribute('data-outstanding'));
            const oldMonthly = parseFloat(selectedOption.getAttribute('data-monthly'));
            const oldTerm = parseInt(selectedOption.getAttribute('data-term'));

            const type = document.getElementById('restructureType').value;
            const newTerm = parseInt(document.getElementById('newTerm').value) || 1;
            
            // Set old values
            document.getElementById('old-term').innerText = oldTerm + " Months";
            document.getElementById('old-monthly').innerText = "₱ " + oldMonthly.toLocaleString(undefined, {minimumFractionDigits: 2});

            // Calculate new values (Estimating 3.5% flat rate on remaining balance)
            const termDisplay = document.getElementById('preview-term');
            const monthlyDisplay = document.getElementById('preview-monthly');

            if (type === 'Holiday') {
                termDisplay.innerText = "Paused (1 Mo)";
                monthlyDisplay.innerText = "₱ 0.00 (Next Mo)";
            } else {
                termDisplay.innerText = newTerm + " Months";
                // Basic Flat Rate Calculation: (Outstanding + (Outstanding * 0.035 * newTerm)) / newTerm
                const newTotalInterest = outstanding * 0.035 * newTerm;
                const newMonthly = (outstanding + newTotalInterest) / newTerm;
                monthlyDisplay.innerText = "₱ " + newMonthly.toLocaleString(undefined, {minimumFractionDigits: 2});
            }
        }

        // Run once on load to populate the initial preview
        window.onload = updatePreview;
    </script>

</body>
</html>