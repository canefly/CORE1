<?php
session_start();

require_once '../assets/includes/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. SECURITY CHECKS
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die("Security Validation Failed");
    }

    if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
        header("Location: login-client.php");
        exit;
    }

    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];
    $email = $_SESSION['reset_email'];

    // 2. VALIDATION
    if ($new_pass !== $confirm_pass) {
        header("Location: reset-password.php?error=mismatch");
        exit;
    }

    // 3. HASHING & DATABASE UPDATE
    $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);

    // I-update ang password at i-reset ang lahat ng security penalties
    $stmt = $conn->prepare("UPDATE users SET password = ?, login_attempts = 0, locked_until = NULL, otp_code = NULL, otp_expiry = NULL WHERE email = ?");
    $stmt->bind_param("ss", $hashed_password, $email);
    
    if ($stmt->execute()) {
        // SUCCESS: Linisin ang session
        unset($_SESSION['otp_verified']);
        unset($_SESSION['reset_email']);
        unset($_SESSION['csrf_token']);

        // I-redirect sa login page na may success message
        header("Location: login-client.php?success=password_updated");
    } else {
        header("Location: reset-password.php?error=failed");
    }
    exit;
}