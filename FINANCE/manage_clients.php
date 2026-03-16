<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php'; 
require_once __DIR__ . '/includes/session_checker.php';

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
                                onclick="viewClient('<?= addslashes($row['fullname']) ?>', '<?= addslashes($row['email']) ?>', '<?= addslashes($row['phone'] ?? 'N/A') ?>', '<?= addslashes($row['id_document'] ?? 'default_id.png') ?>')">
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
        <div class="modal-content large" style="width: 500px;">
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
                <label style="color:#9ca3af; font-size:11px; font-weight:bold; text-transform:uppercase; margin-bottom: 8px; display:block;">Submitted Valid ID / Document</label>
                <img src="" id="viewDoc" alt="Client Document" style="width: 100%; height: 200px; object-fit: contain; background: #111827; border-radius: 8px; border: 1px solid #374151;">
            </div>
        </div>
    </div>

    <script>
        // TAMA ANG PATHING DITO BASE SA SCREENSHOT MO (../client/uploads/loan_docs/)
        function viewClient(name, email, phone, docPath) {
            document.getElementById('viewName').innerText = name;
            document.getElementById('viewEmail').innerText = email;
            document.getElementById('viewPhone').innerText = phone;
            document.getElementById('viewDoc').src = '../client/uploads/loan_docs/' + docPath; 
            document.getElementById('viewClientModal').style.display = 'flex';
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
            let modal = document.getElementById('viewClientModal');
            if (event.target == modal) { modal.style.display = "none"; }
        }
    </script>
</body>
</html>