<?php
session_start();
require_once '../assets/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $user_id = $_SESSION['temp_user_id'] ?? null;
    $device_hash = $_SESSION['temp_device_hash'] ?? null;

    if (!$user_id || !$device_hash) { header("Location: login-client.php"); exit; }

    $stmt = $conn->prepare("SELECT id, fullname, email, otp_expiry FROM users WHERE id = ? AND otp_code = ? LIMIT 1");
    $stmt->bind_param("is", $user_id, $otp);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if ((new DateTime($user['otp_expiry'])) > (new DateTime())) {
            
            // 1. SAVE NEW DEVICE
            $ip = $_SERVER['REMOTE_ADDR'];
            $ua = $_SERVER['HTTP_USER_AGENT'];
            $ins = $conn->prepare("INSERT INTO user_devices (user_id, device_hash, ip_address, user_agent) VALUES (?, ?, ?, ?)");
            $ins->bind_param("isss", $user_id, $device_hash, $ip, $ua);
            
            if ($ins->execute()) {
                // 2. COMPLETE LOGIN
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                
                // Linisin ang security fields
                $conn->query("UPDATE users SET otp_code = NULL, otp_expiry = NULL, login_attempts = 0 WHERE id = $user_id");
                unset($_SESSION['temp_user_id'], $_SESSION['temp_device_hash']);

                header("Location: ../client/dashboard.php");
                exit;
            }
        }
    }
    header("Location: verify-device.php?error=invalid");
    exit;
}