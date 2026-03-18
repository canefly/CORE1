<?php
session_start();
require_once __DIR__ . "/include/config.php";
include __DIR__ . "/include/session_checker.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$message = "";

// --- HANDLE FORM SUBMISSIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. UPDATE PROFILE PICTURE
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/profiles/';
        if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
        
        $file_ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
        $new_file_name = 'user_' . $user_id . '_' . time() . '.' . $file_ext;
        
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $upload_dir . $new_file_name)) {
            $stmt = $conn->prepare("UPDATE users SET profile_pic = ? WHERE id = ?");
            $stmt->bind_param("si", $new_file_name, $user_id);
            $stmt->execute();
            $message = "<div class='alert-success'><i class='bi bi-check-circle'></i> Profile picture updated!</div>";
        }
    }

    // 2. UPDATE PERSONAL INFO (With New KYC Fields)
    elseif (isset($_POST['update_profile'])) {
        $fullname = trim($_POST['fullname']);
        $phone = trim($_POST['phone']);
        $dob = $_POST['dob'];
        $gender = $_POST['gender'];
        $occupation = trim($_POST['occupation']);
        $address = trim($_POST['address']);
        
        $stmt = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, dob = ?, gender = ?, occupation = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $fullname, $phone, $dob, $gender, $occupation, $address, $user_id);
        
        if ($stmt->execute()) {
            $message = "<div class='alert-success'><i class='bi bi-check-circle'></i> Profile information updated!</div>";
            $_SESSION['user_name'] = $fullname;
        }
        $stmt->close();
    } 
    
    // 3. UPDATE PASSWORD
    elseif (isset($_POST['update_password'])) {
        $new_password = $_POST['new_password'];
        if ($new_password === $_POST['confirm_password']) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hashed, $user_id);
            $stmt->execute();
            $message = "<div class='alert-success'><i class='bi bi-shield-check'></i> Password securely updated!</div>";
        }
    }
}

// FETCH LATEST DATA
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// AUTO-AGE CALCULATION logic
$age = "N/A";
if (!empty($user['dob']) && $user['dob'] !== '0000-00-00') {
    $age = (new DateTime($user['dob']))->diff(new DateTime())->y;
}

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

    <div class="main-content">
        <div class="page-header">
            <h1>Client Profile</h1>
            <p>Ensure your information is up to date for faster loan processing.</p>
        </div>

        <?php echo $message; ?>

        <div class="profile-card">
            <div class="profile-avatar-container">
                <div class="avatar-wrapper">
                    <?php if (!empty($user['profile_pic'])): ?>
                        <img src="uploads/profiles/<?php echo htmlspecialchars($user['profile_pic']); ?>" alt="Profile">
                    <?php else: ?>
                        <div class="avatar-placeholder"><?php echo $initial; ?></div>
                    <?php endif; ?>
                </div>
                <div class="camera-icon-trigger" onclick="document.getElementById('profilePicInput').click();" title="Change Profile Picture">
                    <i class="bi bi-camera-fill"></i>
                </div>
            </div>

            <form id="avatarForm" method="POST" enctype="multipart/form-data" style="display: none;">
                <input type="file" id="profilePicInput" name="profile_pic" accept="image/*" onchange="document.getElementById('avatarForm').submit();">
            </form>
            
            <div class="profile-info">
                <h2><?php echo htmlspecialchars($user['fullname']); ?></h2>
                <div class="profile-meta">
                    <div class="meta-item"><i class="bi bi-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></div>
                    <div class="meta-item"><i class="bi bi-calendar3"></i> <?php echo $age; ?> Years Old</div>
                    <div class="meta-item"><span class="badge-status">Verified Account</span></div>
                </div>
            </div>
        </div>

        <div class="settings-container">
            <div class="forms-col">
                <div class="card-box">
                    <div class="card-title">KYC & Personal Details</div>
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="fullname" class="form-input" value="<?php echo htmlspecialchars($user['fullname']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" name="phone" class="form-input" value="<?php echo htmlspecialchars($user['phone']); ?>" required>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Date of Birth</label>
                                <input type="date" name="dob" class="form-input" value="<?php echo $user['dob']; ?>">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-input">
                                    <option value="Male" <?php echo ($user['gender'] == 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo ($user['gender'] == 'Female') ? 'selected' : ''; ?>>Female</option>
                                    <option value="Other" <?php echo ($user['gender'] == 'Other') ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Occupation / Source of Income</label>
                                <input type="text" name="occupation" class="form-input" value="<?php echo htmlspecialchars($user['occupation'] ?? ''); ?>" placeholder="e.g. Freelancer, Business Owner">
                            </div>
                            <div class="form-group" style="grid-column: span 2;">
                                <label class="form-label">Residential Address</label>
                                <textarea name="address" class="form-input" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                        <button type="submit" name="update_profile" class="btn-save">Update KYC Information</button>
                    </form>
                </div>
            </div>

            <div class="info-col">
                <div class="card-box">
                    <div class="card-title">Security Status</div>
                    <div class="security-list">
                        <div class="sec-item"><i class="bi bi-shield-check text-success"></i> 2FA Enabled</div>
                        <div class="sec-item"><i class="bi bi-device-ssd text-success"></i> Trusted Device</div>
                    </div>
                </div>
                
                <div class="card-box">
                    <div class="card-title">Change Password</div>
                    <form method="POST">
                        <div class="form-group">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" class="form-input" required>
                        </div>
                        <button type="submit" name="update_password" class="btn-save" style="width: 100%;">Update Security</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

</body>
</html>