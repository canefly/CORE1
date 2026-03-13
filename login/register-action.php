<?php
// Vacuum up any stray HTML/spaces
ob_start();
session_start();

require_once '../assets/includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1. Check for empty fields
    if (empty($fullname) || empty($phone) || empty($email) || empty($password)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please fill in all the required fields.']);
        exit;
    }

    // 2. Check if the email already exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();

    if ($checkRes->num_rows > 0) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'This email is already registered. Please log in instead.']);
        exit;
    }
    $checkStmt->close();

    // 3. Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 4. Insert the new user into the database
    $insertStmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password) VALUES (?, ?, ?, ?)");
    $insertStmt->bind_param("ssss", $fullname, $phone, $email, $hashed_password);

    if ($insertStmt->execute()) {
        ob_clean();
        echo json_encode([
            'success' => true, 
            'message' => 'Welcome to the family! Redirecting...',
            'redirect' => 'login-client.php?success=registered'
        ]);
        exit;
    } else {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Registration failed. Please try again.']);
        exit;
    }
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>