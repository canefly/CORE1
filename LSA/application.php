<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LSA | Loan Applications</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/Application.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Loan Application Inbox</h1>
            <p>Manage and verify incoming loan requests.</p>
        </div>

        <div class="filter-bar">
            <div class="filter-group">
                <button class="btn-filter active">All</button>
                <button class="btn-filter">Pending</button>
                <button class="btn-filter">Incomplete</button>
            </div>
            
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="search-input" placeholder="Search client name...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Client Name / Attempts</th>
                        <th>Loan Amount</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr>
                        <td style="color:#10b981; font-weight:700;">#LA-1023</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar" style="background:#10b981; color:#064e3b;">JD</div>
                                <div class="client-wrapper">
                                    <span class="client-name">Juan Dela Cruz</span>
                                    <div class="attempt-dots" title="Attempt 1 of 3">
                                        <span class="dot fill-green"></span>
                                        <span class="dot"></span>
                                        <span class="dot"></span>
                                        <span class="attempt-label">1/3</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>₱50,000</td>
                        <td><span class="badge bg-orange">Pending Review</span></td>
                        <td style="text-align:center;">
                            <button class="btn-action" onclick="openModal('Juan Dela Cruz', 'LA-1023')">
                                Review Docs <i class="bi bi-arrow-right"></i>
                            </button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#10b981; font-weight:700;">#LA-1024</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">MC</div>
                                <div class="client-wrapper">
                                    <span class="client-name">Maria Clara</span>
                                    <div class="attempt-dots" title="Attempt 2 of 3">
                                        <span class="dot fill-orange"></span>
                                        <span class="dot fill-orange"></span>
                                        <span class="dot"></span>
                                        <span class="attempt-label">2/3</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>₱15,000</td>
                        <td><span class="badge bg-red">Incomplete</span></td>
                        <td style="text-align:center;">
                            <button class="btn-action" onclick="openModal('Maria Clara', 'LA-1024')">
                                Re-Check <i class="bi bi-arrow-repeat"></i>
                            </button>
                        </td>
                    </tr>

                    <tr>
                        <td style="color:#10b981; font-weight:700;">#LA-1025</td>
                        <td>
                            <div class="client-info">
                                <div class="mini-avatar">PP</div>
                                <div class="client-wrapper">
                                    <span class="client-name">Pedro Penduko</span>
                                    <div class="attempt-dots" title="Final Attempt">
                                        <span class="dot fill-red"></span>
                                        <span class="dot fill-red"></span>
                                        <span class="dot fill-red"></span>
                                        <span class="attempt-label" style="color:#f87171;">Max</span>
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>₱100,000</td>
                        <td><span class="badge bg-blue">Under Review</span></td>
                        <td style="text-align:center;">
                            <button class="btn-action" onclick="openModal('Pedro Penduko', 'LA-1025')">
                                Review Docs <i class="bi bi-arrow-right"></i>
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>
    </div>

    <div id="reviewModal" class="modal-overlay">
        <div class="modal-box">
            
            <div class="modal-header">
                <div class="modal-title">
                    <h2 id="modalClientName">Client Name</h2>
                    <span id="modalAppID">Application ID: #0000</span>
                </div>
                <button class="close-btn" onclick="closeModal()"><i class="bi bi-x-lg"></i></button>
            </div>

            <div class="modal-body">
                <div class="doc-section">
                    
                    <div class="doc-item">
                        <div class="doc-row">
                            <div class="doc-info">
                                <div class="doc-icon"><i class="bi bi-person-badge"></i></div>
                                <div class="doc-text">
                                    <h4>Government ID</h4>
                                    <a href="#" target="_blank"><i class="bi bi-eye"></i> View Preview</a>
                                </div>
                            </div>
                            <div class="doc-actions">
                                <label class="status-label">
                                    <input type="radio" name="doc1" checked onclick="toggleReason('reason1', false)"> Valid
                                </label>
                                <label class="status-label" style="color:#f87171;">
                                    <input type="radio" name="doc1" onclick="toggleReason('reason1', true)"> Invalid
                                </label>
                            </div>
                        </div>
                        
                        <div id="reason1" class="remarks-box">
                            <span class="remarks-label">Reason for rejection:</span>
                            <input type="text" class="remarks-input" placeholder="e.g. ID is expired, Blurred image...">
                        </div>
                    </div>

                    <div class="doc-item">
                        <div class="doc-row">
                            <div class="doc-info">
                                <div class="doc-icon"><i class="bi bi-cash-stack"></i></div>
                                <div class="doc-text">
                                    <h4>Proof of Income</h4>
                                    <a href="#" target="_blank"><i class="bi bi-eye"></i> View Preview</a>
                                </div>
                            </div>
                            <div class="doc-actions">
                                <label class="status-label">
                                    <input type="radio" name="doc2" checked onclick="toggleReason('reason2', false)"> Valid
                                </label>
                                <label class="status-label" style="color:#f87171;">
                                    <input type="radio" name="doc2" onclick="toggleReason('reason2', true)"> Invalid
                                </label>
                            </div>
                        </div>

                        <div id="reason2" class="remarks-box">
                            <span class="remarks-label">Reason for rejection:</span>
                            <input type="text" class="remarks-input" placeholder="e.g. Payslip outdated, Amount unclear...">
                        </div>
                    </div>

                </div>
            </div>

            <div class="modal-footer">
                <button class="btn-reject" onclick="closeModal()">
                    <i class="bi bi-x-circle"></i> Return to Client
                </button>
                <button class="btn-confirm" onclick="closeModal()">
                    <i class="bi bi-check2-circle"></i> Verify & Forward
                </button>
            </div>

        </div>
    </div>

    <script>
        // Open Modal
        function openModal(name, id) {
            document.getElementById('modalClientName').innerText = name;
            document.getElementById('modalAppID').innerText = 'Application ID: #' + id;
            document.getElementById('reviewModal').style.display = 'flex';
        }

        // Close Modal
        function closeModal() {
            document.getElementById('reviewModal').style.display = 'none';
        }

        // Toggle Remarks Input
        function toggleReason(elementId, show) {
            const box = document.getElementById(elementId);
            if(show) {
                box.style.display = 'block';
            } else {
                box.style.display = 'none';
                box.querySelector('input').value = ""; // Clear text when hidden
            }
        }

        // Close when clicking outside modal
        window.onclick = function(event) {
            const modal = document.getElementById('reviewModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>

</body>
</html>