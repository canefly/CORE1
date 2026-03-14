<?php
session_start();
require_once __DIR__ . '/includes/db_connect.php'; 
require_once __DIR__ . '/includes/session_checker.php';

// --- ACTION: ADD NEW OFFICER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_officer') {
    $fullname = trim($_POST['full_name']);
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $role = $_POST['role'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        if ($role === 'LSA') {
            $stmt = $pdo->prepare("INSERT INTO lsa_users (full_name, username, password, status) VALUES (?, ?, ?, 'ACTIVE')");
        } else {
            $stmt = $pdo->prepare("INSERT INTO lo_users (full_name, username, password, status) VALUES (?, ?, ?, 'ACTIVE')");
        }
        $stmt->execute([$fullname, $username, $hashed_password]);
        $msg = "Officer successfully added to " . $role . " department!";
    } catch (PDOException $e) {
        $error = "Username already exists or database error.";
    }
}

// --- ACTION: EDIT OFFICER (PASSWORD & PIC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_officer') {
    $officer_id = (int)$_POST['officer_id'];
    $role = $_POST['role'];
    $target_table = ($role === 'LSA') ? 'lsa_users' : 'lo_users';
    
    // 1. Update Password (kung nilagyan ng laman)
    if (!empty($_POST['new_password'])) {
        $hashed_pw = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE {$target_table} SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_pw, $officer_id]);
        $msg = "Officer credentials updated successfully!";
    }

    // 2. Upload Profile Picture (Pathing Fixed base sa screenshot)
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        // Papunta sa client/uploads/profiles/
        $upload_dir = __DIR__ . '/../client/uploads/profiles/'; 
        if(!is_dir($upload_dir)) mkdir($upload_dir, 0777, true); 
        
        $file_extension = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $new_filename = $role . '_' . $officer_id . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $new_filename;

        // Simpleng validation para image lang talaga
        $allowed = ['jpg', 'jpeg', 'png'];
        if (in_array($file_extension, $allowed)) {
            if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $target_file)) {
                // Dapat may `profile_pic` column ka na sa lsa_users at lo_users table ha!
                try {
                    $stmt = $pdo->prepare("UPDATE {$target_table} SET profile_pic = ? WHERE id = ?");
                    $stmt->execute([$new_filename, $officer_id]);
                    $msg = "Officer profile picture updated successfully!";
                } catch (PDOException $e) {
                    $error = "Database Error: Make sure 'profile_pic' column exists in " . $target_table;
                }
            } else {
                $error = "Failed to move uploaded file.";
            }
        } else {
            $error = "Invalid file type. Only JPG and PNG are allowed.";
        }
    }
}

// --- ACTION: TOGGLE STATUS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $officer_id = (int)$_POST['officer_id'];
    $new_status = $_POST['new_status'];
    $role = $_POST['role'];
    $target_table = ($role === 'LSA') ? 'lsa_users' : 'lo_users';

    $stmt = $pdo->prepare("UPDATE {$target_table} SET status = ? WHERE id = ?");
    $stmt->execute([$new_status, $officer_id]);
    $msg = "{$role} officer status updated to " . $new_status;
}

// Fetch lahat ng officers
$stmt = $pdo->query("
    SELECT id, full_name, username, 'LSA' as role, status, created_at FROM lsa_users
    UNION ALL
    SELECT id, full_name, username, 'LO' as role, status, created_at FROM lo_users
    ORDER BY created_at DESC
");
$officers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | Manage Officers</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/global.css">
    <link rel="stylesheet" href="assets/css/manage_officers.css">
</head>
<body>
    <script>
        // THE ANTI-FLASHBANG PROTOCOL 
        if (localStorage.getItem('theme') === null) {
            localStorage.setItem('theme', 'dark'); 
        }
        if (localStorage.getItem('theme') === 'dark') {
            document.documentElement.classList.add('dark-mode');
        }
    </script>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <div>
                <h1>Manage Officers</h1>
                <p>Control access levels for Loan Support Agents and Loan Officers.</p>
            </div>
            <button class="btn-primary" onclick="openAddModal()">
                <i class="bi bi-person-plus-fill"></i> Add Officer
            </button>
        </div>

        <?php if(isset($msg)): ?>
            <div style="background: rgba(16,185,129,0.1); color:#34d399; padding: 15px; border-radius:8px; border:1px solid #10b981; margin-bottom:20px;">
                <i class="bi bi-check-circle"></i> <?= $msg ?>
            </div>
        <?php endif; ?>
        <?php if(isset($error)): ?>
            <div style="background: rgba(239,68,68,0.1); color:#f87171; padding: 15px; border-radius:8px; border:1px solid #ef4444; margin-bottom:20px;">
                <i class="bi bi-exclamation-triangle"></i> <?= $error ?>
            </div>
        <?php endif; ?>

        <div class="content-card">
            <table>
                <thead>
                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Department</th>
                        <th>Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($officers)): ?>
                        <tr><td colspan="5" style="text-align:center; padding: 20px;">No officers found.</td></tr>
                    <?php else: ?>
                        <?php foreach($officers as $row): ?>
                        <tr>
                            <td style="color:#fff; font-weight:bold;"><?= htmlspecialchars($row['full_name']) ?></td>
                            <td>@<?= htmlspecialchars($row['username']) ?></td>
                            <td style="color:#3b82f6; font-weight:bold;"><?= htmlspecialchars($row['role']) ?></td>
                            <td>
                                <?php if($row['status'] === 'ACTIVE'): ?>
                                    <span class="badge-active">ACTIVE</span>
                                <?php else: ?>
                                    <span class="badge-suspended"><?= htmlspecialchars($row['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:center;">
                                <button type="button" class="btn-action" style="color:#fbbf24; border-color:rgba(251,191,36,0.3); margin-right:5px;" 
                                    onclick="openEditModal(<?= $row['id'] ?>, '<?= $row['role'] ?>', '<?= addslashes($row['full_name']) ?>')">
                                    <i class="bi bi-pencil-square"></i> Edit
                                </button>

                                <form method="POST" style="display:inline;" onsubmit="return confirmAction('<?= $row['status'] ?>', '<?= addslashes($row['full_name']) ?>')">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="officer_id" value="<?= $row['id'] ?>">
                                    <input type="hidden" name="role" value="<?= $row['role'] ?>"> 
                                    
                                    <?php if($row['status'] === 'ACTIVE'): ?>
                                        <input type="hidden" name="new_status" value="SUSPENDED">
                                        <button type="submit" class="btn-action btn-suspend"><i class="bi bi-pause-circle"></i> Hold Access</button>
                                    <?php else: ?>
                                        <input type="hidden" name="new_status" value="ACTIVE">
                                        <button type="submit" class="btn-action btn-activate"><i class="bi bi-play-circle"></i> Activate</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="modal-overlay" id="addOfficerModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeAddModal()"><i class="bi bi-x-lg"></i></button>
            <h2 style="color:#fff; margin-top:0; margin-bottom:20px; font-size:20px;">Add System Officer</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_officer">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" required placeholder="e.g. Noriel Dimailig">
                </div>
                <div class="form-group">
                    <label>Username</label>
                    <input type="text" name="username" class="form-control" required placeholder="For login">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Assign Department</label>
                    <select name="role" class="form-control" required>
                        <option value="LSA">Loan Support Agent (LSA)</option>
                        <option value="LO">Loan Officer (LO)</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary" style="width:100%; justify-content:center; margin-top:10px;">Create Account</button>
            </form>
        </div>
    </div>

    <div class="modal-overlay" id="editOfficerModal">
        <div class="modal-content">
            <button class="close-modal" onclick="closeEditModal()"><i class="bi bi-x-lg"></i></button>
            <h2 style="color:#fff; margin-top:0; margin-bottom:20px; font-size:20px;">Edit <span id="editOfficerName" style="color:#3b82f6;"></span></h2>
            
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="edit_officer">
                <input type="hidden" name="officer_id" id="editOfficerId">
                <input type="hidden" name="role" id="editOfficerRole">
                
                <div class="form-group">
                    <label>Change Profile Picture</label>
                    <input type="file" name="profile_pic" class="form-control" accept="image/png, image/jpeg">
                </div>
                
                <div class="form-group">
                    <label>New Password <span style="font-weight:normal; text-transform:none;">(Leave blank if no change)</span></label>
                    <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn-primary" style="width:100%; justify-content:center; margin-top:10px; background:#fbbf24; color:#000;">
                    Save Changes
                </button>
            </form>
        </div>
    </div>

    <script>
        // Add Modal Logic
        function openAddModal() { document.getElementById('addOfficerModal').style.display = 'flex'; }
        function closeAddModal() { document.getElementById('addOfficerModal').style.display = 'none'; }
        
        // Edit Modal Logic
        function openEditModal(id, role, name) {
            document.getElementById('editOfficerId').value = id;
            document.getElementById('editOfficerRole').value = role;
            document.getElementById('editOfficerName').innerText = name;
            document.getElementById('editOfficerModal').style.display = 'flex';
        }
        function closeEditModal() { document.getElementById('editOfficerModal').style.display = 'none'; }

        function confirmAction(currentStatus, name) {
            let action = currentStatus === 'ACTIVE' ? 'suspend access for' : 'activate access for';
            return confirm("Are you sure you want to " + action + " " + name + "?");
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target == document.getElementById('addOfficerModal')) { closeAddModal(); }
            if (event.target == document.getElementById('editOfficerModal')) { closeEditModal(); }
        }
    </script>
</body>
</html>