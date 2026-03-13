<?php
// Vacuum up any stray HTML/spaces
ob_start();
session_start();

require_once '../assets/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Check for empty fields
    if (empty($fullname) || empty($phone) || empty($email) || empty($password)) {
        header("Location: register.php?error=missing_fields");
        exit;
    }

    // 2. Check if the email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();

    if ($checkRes->num_rows > 0) {
        header("Location: register.php?error=email_exists");
        exit;
    }
    $checkStmt->close();

    // 3. Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Insert the new user into the database
    $insertStmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("ssss", $fullname, $phone, $email, $hashed_password);

    if ($insertStmt->execute()) {
        header("Location: login-client.php?success=registered");
        exit;
    } else {
        header("Location: register.php?error=failed");
        exit;
    }
}

header("Location: register.php?error=failed");
exit;
?>