<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions | MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/transactions.css">
</head>
<body>

    <?php include 'include/sidebar.php'; ?>
    <?php include 'include/theme_toggle.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <div>
                <h1>Transaction History</h1>
                <p>View your payment records and official receipts.</p>
            </div>
            <button class="btn-report" onclick="openModal()">
                <i class="bi bi-cloud-upload"></i> Report Payment
            </button>
        </div>

        <div class="history-card">
            <table>
                <thead>
                    <tr>
                        <th>Date & Time</th>
                        <th>Reference No.</th>
                        <th>Description</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Receipt</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td>
                            <div style="font-weight:600; color:#fff;">Oct 20, 2025</div>
                            <div style="font-size:12px; color:#9ca3af;">10:45 AM</div>
                        </td>
                        <td><span class="ref-code">GC-991200</span></td>
                        <td>Payment via GCash</td>
                        <td class="amount-credit">+ ₱ 3,500.00</td>
                        <td><span class="status-badge status-pending">Pending Review</span></td>
                        <td>
                            <a href="#" style="color:#60a5fa; font-size:13px; text-decoration:none;">View Image</a>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <div style="font-weight:600; color:#fff;">Sep 25, 2025</div>
                            <div style="font-size:12px; color:#9ca3af;">02:15 PM</div>
                        </td>
                        <td><span class="ref-code">CSH-0021</span></td>
                        <td>Cash Payment (OTC)</td>
                        <td class="amount-credit">+ ₱ 3,500.00</td>
                        <td><span class="status-badge status-verified">Verified</span></td>
                        <td>
                            <a href="#" style="color:#60a5fa; font-size:13px; text-decoration:none;">Download OR</a>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <div style="font-weight:600; color:#fff;">Sep 26, 2025</div>
                            <div style="font-size:12px; color:#9ca3af;">12:00 AM</div>
                        </td>
                        <td><span class="ref-code">SYS-PNL-01</span></td>
                        <td>Late Payment Penalty</td>
                        <td class="amount-debit">- ₱ 250.00</td>
                        <td><span class="status-badge status-posted">Posted</span></td>
                        <td>
                            <span style="color:#6b7280; font-size:13px;">System Generated</span>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <div style="font-weight:600; color:#fff;">Aug 25, 2025</div>
                            <div style="font-size:12px; color:#9ca3af;">09:00 AM</div>
                        </td>
                        <td><span class="ref-code">LN-DISB-10</span></td>
                        <td>Loan Disbursement (Principal)</td>
                        <td class="amount-credit" style="color:#60a5fa;">+ ₱ 25,000.00</td>
                        <td><span class="status-badge status-verified">Success</span></td>
                        <td>
                            <a href="#" style="color:#60a5fa; font-size:13px; text-decoration:none;">View Contract</a>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

    </div>

    <div id="reportModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Report a Payment</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <form>
                <div class="form-group">
                    <label class="form-label">Payment Channel</label>
                    <select class="form-input" style="cursor:pointer;">
                        <option>GCash</option>
                        <option>Maya</option>
                        <option>Bank Transfer (BPI/BDO)</option>
                        <option>Palawan / Remittance</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Reference Number</label>
                    <input type="text" class="form-input" placeholder="e.g. 10029938812">
                </div>

                <div class="form-group">
                    <label class="form-label">Amount Paid</label>
                    <input type="number" class="form-input" placeholder="₱ 0.00">
                </div>

                <div class="form-group">
                    <label class="form-label">Upload Receipt / Screenshot</label>
                    <div class="upload-box">
                        <i class="bi bi-card-image"></i>
                        <span class="upload-text">Click to browse or drag file here</span>
                        <input type="file" style="display:none;">
                    </div>
                </div>

                <button type="button" class="btn-submit" onclick="alert('Payment Submitted for Review!')">Submit Payment</button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('reportModal');

        function openModal() {
            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        // Close on outside click
        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }
    </script>

</body>
</html>