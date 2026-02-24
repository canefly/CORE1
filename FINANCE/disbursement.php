<!DOCTYPE html>
<html lang="en"> adadad
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Disbursement</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/disbursement.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Disbursement Queue</h1>
            <p>Approved loans waiting for fund release.</p>
        </div>

        <div class="queue-stats">
            <div class="stat-box">
                <div class="stat-icon icon-ready"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <h4>3 Clients</h4>
                    <span>Waiting for Release</span>
                </div>
            </div>
            <div class="stat-box">
                <div class="stat-icon icon-done"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <h4>₱ 150,000</h4>
                    <span>Disbursed Today</span>
                </div>
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Loan ID</th>
                        <th>Client Name</th>
                        <th>Approved Date</th>
                        <th>Loan Amount</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#60a5fa; font-weight:700;">#LN-1020</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">RS</div>
                                <span>Richard Smith</span>
                            </div>
                        </td>
                        <td>Oct 20, 2025</td>
                        <td class="amount-highlight">₱ 50,000.00</td>
                        <td><span class="badge-ready">Ready for Release</span></td>
                        <td style="text-align:center;">
                            <button class="btn-release" onclick="openModal('Richard Smith', '50000')">
                                <i class="bi bi-cash"></i> Release
                            </button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#60a5fa; font-weight:700;">#LN-1021</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">SC</div>
                                <span>Sarah Connor</span>
                            </div>
                        </td>
                        <td>Oct 20, 2025</td>
                        <td class="amount-highlight">₱ 25,000.00</td>
                        <td><span class="badge-ready">Ready for Release</span></td>
                        <td style="text-align:center;">
                            <button class="btn-release" onclick="openModal('Sarah Connor', '25000')">
                                <i class="bi bi-cash"></i> Release
                            </button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#60a5fa; font-weight:700;">#LN-1019</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">MJ</div>
                                <span>Michael Jordan</span>
                            </div>
                        </td>
                        <td>Oct 19, 2025</td>
                        <td class="amount-highlight">₱ 100,000.00</td>
                        <td><span class="badge-ready">Ready for Release</span></td>
                        <td style="text-align:center;">
                            <button class="btn-release" onclick="openModal('Michael Jordan', '100000')">
                                <i class="bi bi-cash"></i> Release
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

    <div id="releaseModal" class="modal-overlay">
        <div class="modal-box">
            <div class="modal-header">
                <h3>Release Funds</h3>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            
            <form>
                <div class="form-group">
                    <label class="form-label">Client Name</label>
                    <input type="text" class="form-input" id="modalClient" readonly>
                </div>

                <div class="form-group">
                    <label class="form-label">Breakdown</label>
                    <div class="breakdown">
                        <div class="bd-row">
                            <span>Principal Amount</span>
                            <span id="bd-principal">₱ 0.00</span>
                        </div>
                        <div class="bd-row">
                            <span>Less: Processing Fee</span>
                            <span style="color:#f87171;">- ₱ 500.00</span>
                        </div>
                        <div class="bd-row">
                            <span>Less: Advance Interest</span>
                            <span style="color:#f87171;">- ₱ 0.00</span>
                        </div>
                        <div class="bd-total">
                            <span>NET PROCEEDS</span>
                            <span id="bd-net" style="color:#60a5fa;">₱ 0.00</span>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Disbursement Method</label>
                    <select class="form-select">
                        <option value="cash">Cash Release (OTC)</option>
                        <option value="check">Check Issuance</option>
                        <option value="bank">Bank Transfer / GCash</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Reference / Check No.</label>
                    <input type="text" class="form-input" placeholder="e.g. Check #123456 or Ref No.">
                </div>

                <button type="button" class="btn-confirm">
                    <i class="bi bi-check-lg"></i> Confirm Release
                </button>
            </form>
        </div>
    </div>

    <script>
        const modal = document.getElementById('releaseModal');
        
        function openModal(client, amount) {
            // Set basic info
            document.getElementById('modalClient').value = client;
            
            // Format Currency
            const principal = parseFloat(amount);
            const fee = 500; // Fixed fee for demo
            const net = principal - fee;

            document.getElementById('bd-principal').innerText = '₱ ' + principal.toLocaleString();
            document.getElementById('bd-net').innerText = '₱ ' + net.toLocaleString();

            modal.style.display = 'flex';
        }

        function closeModal() {
            modal.style.display = 'none';
        }

        window.onclick = function(e) {
            if (e.target == modal) closeModal();
        }
    </script>

</body>
</html>