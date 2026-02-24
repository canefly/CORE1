<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Payment Monitoring</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/payments.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Payment Monitoring</h1>
            <p>Track daily collections and manage overdue accounts.</p>
        </div>

        <div class="summary-grid">
            <div class="summary-card">
                <div class="icon-box icon-blue"><i class="bi bi-calendar-check"></i></div>
                <div class="meta">
                    <h3>₱ 45,000</h3>
                    <span>Expected Today</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="icon-box icon-green"><i class="bi bi-cash-stack"></i></div>
                <div class="meta">
                    <h3>₱ 12,500</h3>
                    <span>Collected So Far</span>
                </div>
            </div>
            <div class="summary-card">
                <div class="icon-box icon-red"><i class="bi bi-exclamation-diamond"></i></div>
                <div class="meta">
                    <h3>5</h3>
                    <span>Overdue Accounts</span>
                </div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="switchTab('due')">Due Today (3)</button>
            <button class="tab-btn" onclick="switchTab('overdue')">Overdue / Arrears (2)</button>
            <button class="tab-btn" onclick="switchTab('history')">Payment History</button>
        </div>

        <div id="tab-due" class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Client Name</th>
                        <th>Installment #</th>
                        <th>Amount Due</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="color:#60a5fa;">#LN-1002</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">MC</div>
                                <span>Maria Clara</span>
                            </div>
                        </td>
                        <td>4 of 12</td>
                        <td style="font-weight:700;">₱ 3,500.00</td>
                        <td><span class="status-due">Waiting</span></td>
                        <td style="text-align:center;">
                            <button class="btn-receive" onclick="openModal('Maria Clara', '3500')">
                                <i class="bi bi-box-arrow-in-down"></i> Pay
                            </button>
                        </td>
                    </tr>
                    <tr>
                        <td style="color:#60a5fa;">#LN-1005</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">GT</div>
                                <span>Gary Thompson</span>
                            </div>
                        </td>
                        <td>2 of 6</td>
                        <td style="font-weight:700;">₱ 5,000.00</td>
                        <td><span class="status-due">Waiting</span></td>
                        <td style="text-align:center;">
                            <button class="btn-receive" onclick="openModal('Gary Thompson', '5000')">
                                <i class="bi bi-box-arrow-in-down"></i> Pay
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="tab-overdue" class="content-card" style="display:none;">
            <table>
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Client Name</th>
                        <th>Days Late</th>
                        <th>Penalty</th>
                        <th>Total Due</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td style="color:#f87171;">#LN-0998</td>
                        <td>Juan Dela Cruz</td>
                        <td style="color:#f87171; font-weight:700;">5 Days</td>
                        <td>₱ 250.00</td>
                        <td style="font-weight:700;">₱ 4,250.00</td>
                        <td style="text-align:center;">
                            <button class="btn-notify"><i class="bi bi-bell"></i> Remind</button>
                            <button class="btn-receive" onclick="openModal('Juan Dela Cruz', '4250')">Pay</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

    </div>

    <div id="payModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Receive Payment</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <form>
                <div class="form-group">
                    <label class="form-label">Client Name</label>
                    <input type="text" class="form-input" id="modalClient" readonly>
                </div>
                <div class="form-group">
                    <label class="form-label">Amount Received (₱)</label>
                    <input type="number" class="form-input" id="modalAmount">
                </div>
                <div class="form-group">
                    <label class="form-label">Reference Number (Optional)</label>
                    <input type="text" class="form-input" placeholder="e.g., GCash Ref #">
                </div>
                <button type="button" class="btn-submit">Confirm Payment</button>
            </form>
        </div>
    </div>

    <script>
        // Simple Tab Switching Logic
        function switchTab(tabName) {
            // Hide all contents
            document.getElementById('tab-due').style.display = 'none';
            document.getElementById('tab-overdue').style.display = 'none';
            // In a real app, you'd have a 'history' div too
            
            // Show selected
            if(tabName === 'due') document.getElementById('tab-due').style.display = 'block';
            if(tabName === 'overdue') document.getElementById('tab-overdue').style.display = 'block';

            // Update buttons (Quick hack for visual state)
            const btns = document.querySelectorAll('.tab-btn');
            btns.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
        }

        // Modal Logic
        const modal = document.getElementById('payModal');
        function openModal(client, amount) {
            document.getElementById('modalClient').value = client;
            document.getElementById('modalAmount').value = amount;
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