<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php'; 
require_once __DIR__ . '/includes/session_checker.php';

// --- ACTION: FETCH CLIENT DOCUMENTS (AJAX) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'get_client_docs') {
    $client_id = (int)$_POST['client_id'];
    
    // Get the most recent loan application for this user
    $appStmt = $pdo->prepare("SELECT id FROM loan_applications WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $appStmt->execute([$client_id]);
    $app = $appStmt->fetch(PDO::FETCH_ASSOC);
    
    $docs = [];
    if ($app) {
        $docStmt = $pdo->prepare("SELECT doc_type, file_path FROM loan_documents WHERE loan_application_id = ?");
        $docStmt->execute([$app['id']]);
        $docs = $docStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'docs' => $docs]);
    exit;
}

// --- ACTION: TOGGLE CLIENT STATUS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_client_status') {
    $client_id = (int)$_POST['client_id'];
    $new_status = $_POST['new_status']; // 'SUSPENDED' or 'ACTIVE'

    $stmt = $pdo->prepare("UPDATE users SET account_status = ? WHERE id = ?");
    $stmt->execute([$new_status, $client_id]);
    $msg = "Client account successfully updated to " . $new_status;
}

// Fetch all clients (Sinama na natin ang phone at address para sa Modal)
$stmt = $pdo->query("
    SELECT u.id, u.fullname, u.email, u.phone, u.account_status, 
           COALESCE(SUM(l.outstanding), 0) as total_debt
    FROM users u
    LEFT JOIN loans l ON u.id = l.user_id AND l.status = 'ACTIVE'
    GROUP BY u.id
    ORDER BY total_debt DESC, u.id DESC
");
$clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Clients</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
        <script>
        // THE ANTI-FLASHBANG PROTOCOL 
        if (localStorage.getItem('theme') === null) {
            localStorage.setItem('theme', 'dark'); 
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/manage_clients.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h1>Manage Client Accounts</h1>
            <p>Monitor borrowers, view total unpaid balances, and suspend delinquent accounts.</p>
        </div>

        <?php if(isset($msg)): ?>
            <div style="background: rgba(16,185,129,0.1); color:#34d399; padding: 15px; border-radius:8px; border:1px solid #10b981; margin-bottom:20px;">
                <i class="bi bi-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Client Name</th>
                        <th>Email Address</th>
                        <th>Total Unpaid Balance</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($clients as $row): ?>
                    <tr>
                        <td style="color:#fff; font-weight:bold;"><?= htmlspecialchars($row['fullname']) ?></td>
                        <td><?= htmlspecialchars($row['email']) ?></td>
                        <td class="<?= $row['total_debt'] > 0 ? 'debt-val' : '' ?>">
                            ₱ <?= number_format($row['total_debt'], 2) ?>
                        </td>
                        <td>
                            <?php if($row['account_status'] === 'ACTIVE'): ?>
                                <span class="badge-active">ACTIVE</span>
                            <?php else: ?>
                                <span class="badge-suspended">SUSPENDED</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <button class="btn-action btn-view" style="margin-right: 5px; color: #3b82f6; border-color: rgba(59,130,246,0.3);" 
                                onclick="viewClient(<?= $row['id'] ?>, '<?= addslashes($row['fullname']) ?>', '<?= addslashes($row['email']) ?>', '<?= addslashes($row['phone'] ?? 'N/A') ?>')">
                                <i class="bi bi-eye-fill"></i> View
                            </button>

                            <form method="POST" style="display:inline;" onsubmit="return confirmAction('<?= $row['account_status'] ?>', '<?= addslashes($row['fullname']) ?>')">
                                <input type="hidden" name="action" value="toggle_client_status">
                                <input type="hidden" name="client_id" value="<?= $row['id'] ?>">
                                
                                <?php if($row['account_status'] === 'ACTIVE'): ?>
                                    <input type="hidden" name="new_status" value="SUSPENDED">
                                    <button type="submit" class="btn-action btn-suspend"><i class="bi bi-person-x-fill"></i> Suspend</button>
                                <?php else: ?>
                                    <input type="hidden" name="new_status" value="ACTIVE">
                                    <button type="submit" class="btn-action btn-activate"><i class="bi bi-person-check-fill"></i> Activate</button>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="viewClientModal">
        <div class="modal-content large" style="width: 600px;">
            <button class="close-modal" onclick="closeClientModal()"><i class="bi bi-x-lg"></i></button>
            <h2 style="color:#fff; margin-top:0; margin-bottom:20px; font-size:20px;">Client Information</h2>
            
            <div class="client-details-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                <div class="detail-group">
                    <label style="color:#9ca3af; font-size:11px; font-weight:bold; text-transform:uppercase;">Full Name</label>
                    <p id="viewName" style="color:#fff; font-size:14px; margin-top:4px; background:#111827; padding:8px; border-radius:6px; border:1px solid #374151;">Loading...</p>
                </div>
                <div class="detail-group">
                    <label style="color:#9ca3af; font-size:11px; font-weight:bold; text-transform:uppercase;">Email Address</label>
                    <p id="viewEmail" style="color:#fff; font-size:14px; margin-top:4px; background:#111827; padding:8px; border-radius:6px; border:1px solid #374151;">Loading...</p>
                </div>
                <div class="detail-group">
                    <label style="color:#9ca3af; font-size:11px; font-weight:bold; text-transform:uppercase;">Phone Number</label>
                    <p id="viewPhone" style="color:#fff; font-size:14px; margin-top:4px; background:#111827; padding:8px; border-radius:6px; border:1px solid #374151;">Loading...</p>
                </div>
            </div>

            <div class="detail-group" style="margin-top: 15px;">
                <label style="color:#9ca3af; font-size:11px; font-weight:bold; text-transform:uppercase; margin-bottom: 8px; display:block;">Submitted Documents</label>
                <div id="documentGallery" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 15px;">
                    <!-- Dynamically populated via AJAX -->
                </div>
            </div>
        </div>
    </div>

    <!-- Lightbox Overlay -->
    <div class="modal-overlay" id="lightboxOverlay" style="display: none; z-index: 9999; flex-direction: column; align-items: center; justify-content: center; background: rgba(17, 24, 39, 0.95);">
        <div style="position: relative; max-width: 90%; max-height: 90vh; display: flex; flex-direction: column; align-items: center;">
            <button onclick="closeLightbox()" style="position: absolute; top: -30px; right: -30px; background: none; border: none; color: #fff; font-size: 30px; cursor: pointer;"><i class="bi bi-x-lg"></i></button>
            
            <div style="display: flex; align-items: center; justify-content: center; position: relative; width: 100%;">
                <button id="lightboxPrev" onclick="prevImage()" style="position: absolute; left: -50px; background: none; border: none; color: #fff; font-size: 40px; cursor: pointer; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><i class="bi bi-chevron-left"></i></button>
                <img id="lightboxImg" src="" style="max-height: 80vh; max-width: 100%; object-fit: contain; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.5);">
                <button id="lightboxNext" onclick="nextImage()" style="position: absolute; right: -50px; background: none; border: none; color: #fff; font-size: 40px; cursor: pointer; text-shadow: 0 2px 4px rgba(0,0,0,0.5);"><i class="bi bi-chevron-right"></i></button>
            </div>
            
            <div id="lightboxCaption" style="color: #e5e7eb; margin-top: 15px; font-size: 14px; font-weight: bold; text-transform: uppercase; letter-spacing: 1px;"></div>
        </div>
    </div>

    <script>
        let currentDocs = [];
        let currentImageIndex = 0;

        // Dynamically fetch and view user documents via AJAX
        function viewClient(clientId, name, email, phone) {
            document.getElementById('viewName').innerText = name;
            document.getElementById('viewEmail').innerText = email;
            document.getElementById('viewPhone').innerText = phone;
            
            const gallery = document.getElementById('documentGallery');
            gallery.innerHTML = '<p style="color:#9ca3af; font-size: 13px; grid-column: 1 / -1; text-align: center; padding: 20px 0;">Loading documents...</p>';
            document.getElementById('viewClientModal').style.display = 'flex';

            const formData = new FormData();
            formData.append('action', 'get_client_docs');
            formData.append('client_id', clientId);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                gallery.innerHTML = '';
                if (data.status === 'success' && data.docs && data.docs.length > 0) {
                    currentDocs = data.docs;
                    data.docs.forEach((doc, index) => {
                        const col = document.createElement('div');
                        col.style.display = 'flex';
                        col.style.flexDirection = 'column';
                        col.style.gap = '8px';

                        const label = document.createElement('span');
                        label.style.color = '#9ca3af';
                        label.style.fontSize = '10px';
                        label.style.fontWeight = 'bold';
                        label.style.textTransform = 'uppercase';
                        label.style.textAlign = 'center';
                        label.innerText = doc.doc_type ? doc.doc_type.replace(/_/g, ' ') : 'DOCUMENT';

                        const img = document.createElement('img');
                        let path = doc.file_path.startsWith('/') ? doc.file_path.substring(1) : doc.file_path;
                        img.src = '../client/' + path;
                        img.alt = doc.doc_type;
                        img.style.width = '100%';
                        img.style.height = '150px';
                        img.style.objectFit = 'cover';
                        img.style.background = '#111827';
                        img.style.borderRadius = '8px';
                        img.style.border = '1px solid #374151';
                        img.style.cursor = 'pointer';
                        img.title = 'Click to view full size';
                        img.onclick = () => openLightbox(index);

                        col.appendChild(label);
                        col.appendChild(img);
                        gallery.appendChild(col);
                    });
                } else {
                    currentDocs = [];
                    gallery.innerHTML = '<p style="color:#9ca3af; font-size: 13px; grid-column: 1 / -1; text-align: center; padding: 20px 0;">No documents found for this user.</p>';
                }
            })
            .catch(error => {
                console.error('Error fetching documents:', error);
                currentDocs = [];
                gallery.innerHTML = '<p style="color:#ef4444; font-size: 13px; grid-column: 1 / -1; text-align: center; padding: 20px 0;">Failed to load documents.</p>';
            });
        }

        // --- Lightbox Functions ---
        function openLightbox(index) {
            currentImageIndex = index;
            updateLightbox();
            document.getElementById('lightboxOverlay').style.display = 'flex';
        }

        function updateLightbox() {
            if (currentDocs.length === 0) return;
            const doc = currentDocs[currentImageIndex];
            let path = doc.file_path.startsWith('/') ? doc.file_path.substring(1) : doc.file_path;
            
            document.getElementById('lightboxImg').src = '../client/' + path;
            document.getElementById('lightboxCaption').innerText = doc.doc_type ? doc.doc_type.replace(/_/g, ' ') : 'DOCUMENT';

            document.getElementById('lightboxPrev').style.visibility = currentImageIndex > 0 ? 'visible' : 'hidden';
            document.getElementById('lightboxNext').style.visibility = currentImageIndex < currentDocs.length - 1 ? 'visible' : 'hidden';
        }

        function prevImage() {
            if (currentImageIndex > 0) {
                currentImageIndex--;
                updateLightbox();
            }
        }

        function nextImage() {
            if (currentImageIndex < currentDocs.length - 1) {
                currentImageIndex++;
                updateLightbox();
            }
        }

        function closeLightbox() {
            document.getElementById('lightboxOverlay').style.display = 'none';
        }

        function closeClientModal() { document.getElementById('viewClientModal').style.display = 'none'; }
        
        function confirmAction(currentStatus, name) {
            if (currentStatus === 'ACTIVE') {
                return confirm("WARNING: Suspending " + name + " will block them from logging in. Do you want to proceed?");
            } else {
                return confirm("Activate " + name + "'s account so they can log in again?");
            }
        }

        window.onclick = function(event) {
            let clientModal = document.getElementById('viewClientModal');
            let lightbox = document.getElementById('lightboxOverlay');
            if (event.target == lightbox) { closeLightbox(); }
            if (event.target == clientModal) { closeClientModal(); }
        }
    </script>
</body>
</html>