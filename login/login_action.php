<?php
// Catch any invisible spaces leaking from config.php
ob_start();

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once '../assets/includes/config.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // By default, let's assume the action is 'login' for this file
    // Or grab it if your form passes an action hidden input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Please enter username and password']);
        exit;
    }

    // --- 1. CHECK FINANCE ADMIN ---
    $stmt = $conn->prepare("SELECT * FROM finance_admin WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if ($password === $user['password'] || password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_role'] = 'FINANCE_ADMIN';
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['full_name'];
            
            ob_clean();
            echo json_encode(['success' => true, 'redirect' => '../finance/dashboard.php']);
            exit;
        }
    }
    $stmt->close();

    // --- 2. CHECK LOAN OFFICER (LO) ---
    $stmt = $conn->prepare("SELECT * FROM lo_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if ($user['status'] !== 'ACTIVE') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Account suspended. Contact Admin.']);
            exit;
        }
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_role'] = 'LO';
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['full_name'];
            
            ob_clean();
            echo json_encode(['success' => true, 'redirect' => '../LO/dashboard.php']);
            exit;
        }
    }
    $stmt->close();

    // --- 3. CHECK LOAN SUPPORT ADMIN (LSA) ---
    $stmt = $conn->prepare("SELECT * FROM lsa_users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if ($res->num_rows === 1) {
        $user = $res->fetch_assoc();
        if ($user['status'] !== 'ACTIVE') {
            ob_clean();
            echo json_encode(['success' => false, 'message' => 'Account suspended. Contact Admin.']);
            exit;
        }
        if (password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_role'] = 'LSA';
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_name'] = $user['full_name'];
            
            ob_clean();
            echo json_encode(['success' => true, 'redirect' => '../lsa/dashboard.php']);
            exit;
        }
    }
    $stmt->close();

    // IF NO MATCH FOUND IN ALL 3 TABLES
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
    exit;
}

ob_clean();
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>