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
                
                <form>
                    <div class="form-group">
                        <label class="form-label">Select Active Loan</label>
                        <select class="form-select">
                            <option value="LN-1025">Loan #LN-1025 (Balance: ₱ 15,000)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Type of Restructure</label>
                        <select class="form-select" id="restructureType" onchange="updatePreview()">
                            <option value="extend">Term Extension (Lower Monthly Payment)</option>
                            <option value="holiday">Payment Holiday (Pause for 1 Month)</option>
                            <option value="shorten">Shorten Term (Higher Monthly Payment)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proposed New Term</label>
                        <select class="form-select" id="newTerm" onchange="updatePreview()">
                            <option value="6">6 Months (Current)</option>
                            <option value="9">9 Months (+3 Months)</option>
                            <option value="12">12 Months (+6 Months)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Reason for Request</label>
                        <textarea class="form-textarea" placeholder="Please explain why you need to restructure..."></textarea>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Proof of Hardship (Required)</label>
                        <div class="upload-area">
                            <i class="bi bi-cloud-arrow-up" style="font-size:24px; color:#6b7280;"></i>
                            <span class="upload-text">Upload Medical Cert, Termination Letter, etc.</span>
                        </div>
                    </div>
                </form>
            </div>

            <div class="preview-card">
                <div class="preview-header">Projected Changes</div>

                <div class="compare-row">
                    <span class="compare-label">Loan Term</span>
                    <div class="compare-vals">
                        <span class="val-old">6 Months</span>
                        <span class="val-new" id="preview-term">9 Months</span>
                    </div>
                </div>

                <div class="compare-row">
                    <span class="compare-label">Monthly Payment</span>
                    <div class="compare-vals">
                        <span class="val-old">₱ 4,500</span>
                        <span class="val-new" id="preview-monthly">₱ 3,100</span>
                    </div>
                </div>

                <div class="compare-row">
                    <span class="compare-label">Interest Rate</span>
                    <div class="compare-vals">
                        <span class="val-old">3.5%</span>
                        <span class="val-new">4.0%</span> </div>
                </div>

                <div class="impact-box">
                    <div class="impact-text">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <span>
                            Note: Extending your term will lower your monthly bill but increase the total interest paid by <strong>₱ 1,200</strong>.
                        </span>
                    </div>
                </div>

                <button class="btn-submit" onclick="alert('Restructure Request Submitted to LSA!')">Submit Request</button>
            </div>

        </div>

    </div>

    <script>
        // Simple Simulation Logic for the Preview Panel
        function updatePreview() {
            const type = document.getElementById('restructureType').value;
            const term = document.getElementById('newTerm').value;
            
            const termDisplay = document.getElementById('preview-term');
            const monthlyDisplay = document.getElementById('preview-monthly');

            // Reset
            termDisplay.innerText = term + " Months";

            if (type === 'extend') {
                if(term == '9') monthlyDisplay.innerText = "₱ 3,100";
                if(term == '12') monthlyDisplay.innerText = "₱ 2,450";
                if(term == '6') monthlyDisplay.innerText = "₱ 4,500"; // No change
            } else if (type === 'holiday') {
                termDisplay.innerText = "Paused (1 Mo)";
                monthlyDisplay.innerText = "₱ 0.00 (Next Mo)";
            }
        }
    </script>

</body>
</html>