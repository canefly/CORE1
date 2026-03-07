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

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    if (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ? WHERE id = ?");
        $stmt->bind_param("ssi", $fullname, $phone, $user_id);
        
        if ($stmt->execute()) {
            $message = "<div style='background: #10b981; color: #064e3b; padding: 12px; border-radius: 8px; margin-bottom: 20px; font-weight: bold;'><i class='bi bi-check-circle'></i> Profile updated! Looking fresh.</div>";
            $_SESSION['user_name'] = $fullname; 
        } else {
            $message = "<div class='security-alert'><div class='alert-text'><i class='bi bi-exclamation-triangle'></i> Error: " . $conn->error . "</div></div>";
        }
        $stmt->close();
    } 
    
    elseif (isset($_POST['update_password'])) {
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if ($new_password === $confirm_password) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                $message = "<div style='background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); padding: 15px; border-radius: 8px; margin-bottom: 20px; color: #34d399;'><i class='bi bi-shield-check'></i> Password securely updated!</div>";
            }
            $stmt->close();
        } else {
            $message = "<div class='security-alert'><div class='alert-text'><i class='bi bi-exclamation-triangle'></i> Passwords do not match! Try again.</div></div>";
        }
    }
}

// --- FETCH CURRENT USER DATA ---
$stmt = $conn->prepare("SELECT fullname, email, phone, created_at FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Get first letter of name for the Avatar
$initial = strtoupper(substr($user['fullname'], 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>

    <?php include 'include/sidebar.php'; ?>
    <?php include 'include/theme_toggle.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>User Profile</h1>
            <p>Manage your account details and security settings.</p>
        </div>

        <?php echo $message; ?>

        <div class="profile-card">
            <div class="profile-avatar">
                <div class="avatar-img"><?php echo $initial; ?></div>
                <div class="edit-avatar"><i class="bi bi-camera-fill" style="margin-top: 2px;"></i></div>
            </div>
            
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                <div class="profile-meta">
                    <div class="meta-item"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="meta-item"><i class="bi bi-telephone"></i> <?php echo htmlspecialchars($user['phone']); ?></div>
                </div>
            </div>
        </div>

        <div class="settings-container">
            
            <div class="forms-col">
                <div class="card-box">
                    <div class="card-title">Personal Information</div>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-input" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Email Address (Locked)</label>
                                <input type="email" class="form-input" value="<?php echo htmlspecialchars($user['email']); ?>" disabled style="opacity: 0.5;">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-save">Save Changes</button>
                    </form>
                </div>

                <div class="card-box">
                    <div class="card-title">Change Password</div>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" name="new_password" class="form-input" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-input" required>
                            </div>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn-save">Update Password</button>
                    </form>
                </div>
            </div>

            <div class="info-col">
                <div class="card-box">
                    <div class="card-title">Account Security</div>
                    <p style="font-size: 13px; color: #9ca3af; line-height: 1.5;">Your account is protected. We recommend updating your passwords regularly.</p>
                    
                    <div class="security-alert">
                        <div class="alert-text">
                            <i class="bi bi-clock-history"></i> Account created: <br> <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </div>

                <div class="card-box">
                    <div class="card-title">Uploaded Documents</div>
                    
                    <div class="doc-item">
                        <div class="doc-info">
                            <i class="bi bi-person-vcard doc-icon"></i>
                            <div>
                                <div class="doc-name">Government ID</div>
                                <div class="doc-status">Verified</div>
                            </div>
                        </div>
                        <i class="bi bi-eye btn-view" onclick="alert('Viewing feature coming soon!')"></i>
                    </div>
                    
                    <div class="doc-item">
                        <div class="doc-info">
                            <i class="bi bi-file-earmark-text doc-icon"></i>
                            <div>
                                <div class="doc-name">Proof of Income</div>
                                <div class="doc-status">Verified</div>
                            </div>
                        </div>
                        <i class="bi bi-eye btn-view" onclick="alert('Viewing feature coming soon!')"></i>
                    </div>
                </div>
            </div>

        </div>

    </div>

</body>
</html>