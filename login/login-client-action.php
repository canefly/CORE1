<?php
ob_start();
session_start();
require_once '../assets/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Error: Invalid Token");
    }

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT id, email, password, fullname, login_attempts, locked_until FROM users WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $now = new DateTime();

        // 1. Check kung locked pa ang account
        if ($user['locked_until'] && (new DateTime($user['locked_until'])) > $now) {
            $diff = (new DateTime($user['locked_until']))->getTimestamp() - $now->getTimestamp();
            header("Location: login-client.php?error=locked&wait=" . ceil($diff / 60));
            exit;
        }

        // 2. Verify Password
        if (password_verify($password, $user['password'])) {
            
            // --- NEW DEVICE DETECTION START ---
            $device_hash = hash('sha256', $_SERVER['HTTP_USER_AGENT']);
            
            $dev_stmt = $conn->prepare("SELECT id FROM user_devices WHERE user_id = ? AND device_hash = ?");
            $dev_stmt->bind_param("is", $user['id'], $device_hash);
            $dev_stmt->execute();
            $is_known = $dev_stmt->get_result()->num_rows > 0;

            if (!$is_known) {
                // HINDI KILALA ANG DEVICE: Generate OTP at Send Email
                $otp = rand(100000, 999999);
                $expiry = (new DateTime())->modify("+10 minutes")->format('Y-m-d H:i:s');
                
                $upd = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
                $upd->bind_param("ssi", $otp, $expiry, $user['id']);
                $upd->execute();

                require_once '../include/mail_helper.php';
                $subject = "Security Alert: New Device Login";
$message = "An attempt to log in was detected from a new device or browser. To ensure the security of your account, please verify this activity using this code:
<br><br>
<div style='text-align:center; padding: 20px; background:#f8fafc; border-radius:8px;'>
    <span style='font-size: 32px; font-weight: bold; color: #2ca078; letter-spacing: 5px;'>$otp</span>
</div>
<br>
<b>Device Info:</b> {$_SERVER['HTTP_USER_AGENT']}<br>
<b>IP Address:</b> {$_SERVER['REMOTE_ADDR']}";
sendOTP($email, $subject, $message);

                // Huwag muna i-login. Itago muna sa temp session.
                $_SESSION['temp_user_id'] = $user['id'];
                $_SESSION['temp_device_hash'] = $device_hash;
                header("Location: verify-device.php");
                exit;
            }

            // KILALA NA ANG DEVICE: Proceed to Dashboard
            $conn->query("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE id = " . $user['id']);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['fullname'] = $user['fullname'];
            header("Location: ../client/dashboard.php");
            exit;
        } else {
            // Failed login logic...
            $attempts = $user['login_attempts'] + 1;
            $conn->query("UPDATE users SET login_attempts = $attempts WHERE id = " . $user['id']);
            header("Location: login-client.php?error=invalid&attempts=$attempts");
            exit;
        }
    }
}
header("Location: login-client.php?error=invalid");