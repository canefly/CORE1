<?php
session_start();
require_once '../assets/includes/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = trim($_POST['otp']);
    $email = $_SESSION['reset_email'] ?? '';

    $stmt = $conn->prepare("SELECT id, otp_expiry FROM users WHERE email = ? AND otp_code = ? LIMIT 1");
    $stmt->bind_param("ss", $email, $otp);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        $now = new DateTime();
        
        if (new DateTime($user['otp_expiry']) > $now) {
            // SUCCESS: OTP is correct and not expired
            $_SESSION['otp_verified'] = true;
            header("Location: reset-password.php");
            exit;
        }
    }
    header("Location: verify-reset-otp.php?error=invalid");
    exit;
}