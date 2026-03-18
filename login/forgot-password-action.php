<?php
session_start();
require_once '../assets/includes/config.php';

require_once dirname(__DIR__) . '/include/mail_helper.php'; 

// 1. SECURITY: Check CSRF
$csrf = $_GET['csrf_token'] ?? '';
if ($csrf !== ($_SESSION['csrf_token'] ?? '')) {
    die("Security Error: Invalid Session. Please refresh the login page.");
}

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    header("Location: login-client.php");
    exit;
}

// 2. I-check ang email gamit ang $conn
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
$stmt->bind_param("s", $email);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 1) {
    $user = $res->fetch_assoc();
    $otp = rand(100000, 999999);
    $expiry = (new DateTime())->modify("+15 minutes")->format('Y-m-d H:i:s');

    // Save OTP sa database
    $upd = $conn->prepare("UPDATE users SET otp_code = ?, otp_expiry = ? WHERE id = ?");
    $upd->bind_param("ssi", $otp, $expiry, $user['id']);
    
    if ($upd->execute()) {
       $subject = "Reset Your Password - MicroFinance Security";
$message = "We received a request to reset your password. Please use the following One-Time Password (OTP) to proceed with your request:
<br><br>
<div style='text-align:center; padding: 20px; background:#f8fafc; border-radius:8px;'>
    <span style='font-size: 32px; font-weight: bold; color: #2ca078; letter-spacing: 5px;'>$otp</span>
</div>
<br>
This code will expire in 15 minutes.";
sendOTP($email, $subject, $message);
        
        // Tawagin ang PHPMailer via mail_helper
        if (sendOTP($email, $subject, $msg)) {
            $_SESSION['reset_email'] = $email;
            header("Location: verify-reset-otp.php?status=sent");
            exit;
        } else {
            // Kung may error sa pag-send ng email
            header("Location: login-client.php?error=mail_failed");
            exit;
        }
    }
} else {
    header("Location: login-client.php?error=not_found");
    exit;
}