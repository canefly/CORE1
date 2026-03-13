<?php
// Catch any invisible spaces leaking from config.php
ob_start();
session_start();

require_once '../assets/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            header("Location: login-client.php?error=invalid");
            exit;
        }

        $stmt = $conn->prepare("SELECT id, email, password, fullname FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['fullname'] = $user['fullname'];
                
                header("Location: ../client/dashboard.php");
                exit;
            }
        }
        
        header("Location: login-client.php?error=invalid");
        exit;
    }
}

header("Location: login-client.php?error=invalid");
exit;
?>