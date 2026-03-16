<?php
session_start();
include __DIR__ . "/include/config.php"; 

$action = $_POST['action'] ?? '';

if ($action == "signup") {

    $fullname = trim($_POST['fullname']);
    $phone    = trim($_POST['phone']);
    $email    = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if email OR phone exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
    $check->bind_param("ss", $email, $phone);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        // Already registered
        echo "<script>
                alert('Email or Mobile Number already registered!');
                window.history.back();
              </script>";
        exit();
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