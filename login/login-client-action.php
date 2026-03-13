<?php
// Catch any invisible spaces leaking from config.php
ob_start();
session_start();

require_once '../assets/includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Please enter both email and password.']);
            exit;
        }

        $stmt = $conn->prepare("SELECT id, email, password, account_status, fullname FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();

            if (password_verify($password, $user['password'])) {
                if (isset($user['account_status']) && $user['account_status'] === 'SUSPENDED') {
                    ob_clean();
                    echo json_encode(['success' => false, 'message' => 'Your account is suspended. Please contact management.']);
                    exit;
                }

                session_regenerate_id(true);
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['fullname'] = $user['fullname'];
                
                ob_clean();
                echo json_encode(['success' => true, 'redirect' => '../client/dashboard.php']);
                exit;
            }
        }
        
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>