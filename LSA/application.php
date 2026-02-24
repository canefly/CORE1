<?php 
// Standard include for your existing database connection
include 'includes/db_connect.php'; 

/** * FETCH PENDING APPLICATIONS
 * Joins loan_applications and users to get real client names.
 * The LSA inbox specifically handles 'PENDING' requests.
 */
$query = "SELECT la.id, la.principal_amount, la.status, u.fullname 
          FROM loan_applications la 
          JOIN users u ON la.user_id = u.id 
          WHERE la.status = 'PENDING' 
          ORDER BY la.created_at DESC";
$result = $conn->query($query);
?>

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
            <p>Review and verify incoming client loan requests.</p>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div style="background: #10b981; color: white; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <i class="bi bi-check-circle"></i> <?php echo htmlspecialchars($_GET['msg']); ?>
            </div>
        <?php endif; ?>

        <div class="filter-bar" style="justify-content: flex-end;">
            <div class="search-wrapper">
                <i class="bi bi-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input" placeholder="Search client name...">
            </div>
        </div>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>App ID</th>
                        <th>Client Name</th>
                        <th>Loan Amount</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody id="applicationTable">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="color:#10b981; font-weight:700;">#LA-<?php echo $row['id']; ?></td>
                            <td>
                                <div class="client-info">
                                    <div class="mini-avatar" style="background:#10b981; color:#064e3b;">
                                        <?php echo strtoupper(substr($row['fullname'], 0, 1)); ?>
                                    </div>
                                    <div class="client-wrapper">
                                        <span class="client-name"><?php echo htmlspecialchars($row['fullname']); ?></span>
                                    </div>
                                </div>
                            </td>
                            <td>₱<?php echo number_format($row['principal_amount'], 2); ?></td>
                            <td><span class="badge bg-orange"><?php echo $row['status']; ?></span></td>
                            <td style="text-align:center;">
                                <button class="btn-action" onclick="openModal('<?php echo addslashes($row['fullname']); ?>', '<?php echo $row['id']; ?>')">
                                    Review Docs <i class="bi bi-arrow-right"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="5" style="text-align:center; padding: 20px;">No pending applications available.</td></tr>
                    <?php endif; ?>
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

            <form action="process_review.php" method="POST">
                <input type="hidden" name="application_id" id="hiddenAppID">
                
                <div class="modal-body">
                    <div id="docContainer" class="doc-section">
                        <p style="color: #94a3b8;">Fetching documents...</p>
                    </div>

                    <div class="remarks-section" id="rejectionSection" style="display: none; margin-top: 15px; border-top: 1px solid #334155; padding-top:15px; padding-left: 25px; padding-right: 25px;">
                        <label style="color: #94a3b8; font-size: 13px; margin-bottom: 8px; display: block;">Quick Reasons:</label>
                        
                        <div class="quick-checks" style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 15px;">
                            <label style="color: #cbd5e1; font-size: 13px; cursor: pointer;">
                                <input type="checkbox" class="reason-check" value="Blurry ID"> Blurry ID
                            </label>
                            <label style="color: #cbd5e1; font-size: 13px; cursor: pointer;">
                                <input type="checkbox" class="reason-check" value="Expired Documents"> Expired Docs
                            </label>
                            <label style="color: #cbd5e1; font-size: 13px; cursor: pointer;">
                                <input type="checkbox" class="reason-check" value="Incomplete Documents"> Incomplete Docs
                            </label>
                            <label style="color: #cbd5e1; font-size: 13px; cursor: pointer;">
                                <input type="checkbox" class="reason-check" value="Wrong Document Type"> Wrong Doc Type
                            </label>
                        </div>

                        <label style="color: #94a3b8; font-size: 13px; margin-bottom: 5px; display: block;">Final Remarks / Other Reason:</label>
                        <textarea name="remarks" id="finalRemarks" class="remarks-input" 
                                  placeholder="Check boxes above or type custom reason here..." 
                                  style="width: 100%; background: #1e293b; border: 1px solid #334155; color: white; padding: 10px; border-radius: 6px; min-height: 80px;"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="status" value="REJECTED" class="btn-reject" onmouseover="showRemarks()">
                        <i class="bi bi-x-circle"></i> Return to Client
                    </button>
                    <button type="submit" name="status" value="APPROVED" class="btn-confirm">
                        <i class="bi bi-check2-circle"></i> Verify & Forward
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.getElementById('searchInput').addEventListener('keyup', function() {
            const filter = this.value.toLowerCase();
            const rows = document.getElementById('applicationTable').getElementsByTagName('tr');
            for (let row of rows) {
                const name = row.querySelector('.client-name');
                if (name) {
                    const text = name.textContent || name.innerText;
                    row.style.display = text.toLowerCase().includes(filter) ? "" : "none";
                }
            }
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('reason-check')) {
                const remarksArea = document.getElementById('finalRemarks');
                const checkboxes = document.querySelectorAll('.reason-check');
                let selectedReasons = [];
                checkboxes.forEach(cb => { if (cb.checked) selectedReasons.push(cb.value); });
                remarksArea.value = selectedReasons.join(', ');
            }
        });

        async function openModal(name, id) {
            document.getElementById('modalClientName').innerText = name;
            document.getElementById('modalAppID').innerText = 'Application ID: #' + id;
            document.getElementById('hiddenAppID').value = id;
            document.getElementById('rejectionSection').style.display = 'none';
            document.getElementById('finalRemarks').value = "";
            document.querySelectorAll('.reason-check').forEach(cb => cb.checked = false);
            
            const docContainer = document.getElementById('docContainer');
            docContainer.innerHTML = "<p>Loading files...</p>";

            try {
                const response = await fetch(`get_application_details.php?id=${id}`);
                const docs = await response.json();
                docContainer.innerHTML = ""; 
                if(docs.length === 0) {
                    docContainer.innerHTML = "<p>No files found for this ID.</p>";
                } else {
                    docs.forEach(doc => {
                        let label = doc.doc_type.replace(/_/g, ' ').toLowerCase();
                        label = label.charAt(0).toUpperCase() + label.slice(1);
                        docContainer.innerHTML += `
                            <div class="doc-item">
                                <div class="doc-row">
                                    <div class="doc-info">
                                        <div class="doc-icon"><i class="bi bi-file-earmark-check"></i></div>
                                        <div class="doc-text">
                                            <h4>${label}</h4>
                                            <a href="${doc.file_path}" target="_blank"><i class="bi bi-eye"></i> View Preview</a>
                                        </div>
                                    </div>
                                </div>
                            </div>`;
                    });
                }
            } catch (e) {
                docContainer.innerHTML = "<p style='color:red;'>Failed to load documents.</p>";
            }
            document.getElementById('reviewModal').style.display = 'flex';
        }

        function closeModal() { document.getElementById('reviewModal').style.display = 'none'; }
        function showRemarks() { document.getElementById('rejectionSection').style.display = 'block'; }
        window.onclick = function(event) { if (event.target == document.getElementById('reviewModal')) closeModal(); }
    </script>
</body>
</html>