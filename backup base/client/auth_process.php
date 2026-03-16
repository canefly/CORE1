<?php
session_start();
require_once __DIR__ . '/include/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);

        // Gamitin ang MADIIN na Prepared Statement
        $stmt = $conn->prepare("SELECT id, password, account_status FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $user = $res->fetch_assoc();

            // Check kung tama ang password
            if (password_verify($password, $user['password'])) {
                
                // === ITO YUNG SUSPENSION CHECKER ===
                if (isset($user['account_status']) && $user['account_status'] === 'SUSPENDED') {
                    // Kick out pabalik sa login na may error message
                    header("Location: login.php?msg=suspended");
                    exit;
                }

                // Kung ACTIVE, papasukin sa dashboard!
                $_SESSION['user_id'] = $user['id'];
                
                // Para sa Session Fixation protection (ginawa natin sa session_checker.php)
                $_SESSION['created_at'] = time();
                $_SESSION['last_activity'] = time();

                header("Location: dashboard.php");
                exit;
            } else {
                header("Location: login.php?msg=invalid"); // Mali ang password
                exit;
            }
        } else {
            header("Location: login.php?msg=invalid"); // Walang ganyang email
            exit;
        }
    }

    // Insert new user
    $stmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $fullname, $phone, $email, $password);

    if ($stmt->execute()) {
        echo "<script>
                alert('Registration successful! Please login.');
                window.location.href = 'login.php';
              </script>";
        exit();
    } else {
        echo "<script>
                alert('Registration failed! Please try again.');
                window.history.back();
              </script>";
        exit();
    }
}

elseif ($action == "login") {

    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();

       if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = (int)$user['id'];      // ✅ ADD THIS
            $_SESSION['user_email'] = $user['email'];     // optional, keep
            $_SESSION['fullname'] = $user['fullname'];    // optional, useful sa UI
            header("Location: dashboard.php");
            exit();
        } else {
            echo "<script>
                    alert('Incorrect password!');
                    window.history.back();
                  </script>";
        }
    } else {
        echo "<script>
                alert('User not found!');
                window.history.back();
              </script>";
    }
}
?>