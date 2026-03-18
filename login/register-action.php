<?php
// Vacuum up any stray HTML/spaces
ob_start();
session_start();

// ABSOLUTE PATH FIX: Lalabas sa login folder para hanapin ang include/config.php
require_once '../assets/includes/config.php';


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $dob = $_POST['dob'];
    $gender = $_POST['gender'];
    $occupation = trim($_POST['occupation']);
    $address = trim($_POST['address']);
    $phone = trim($_POST['phone']);

    // Check if email exists
    $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check->bind_param("s", $email);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        header("Location: register.php?error=email_exists");
        exit;
    }

    // Insert with KYC Fields
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, password, phone, dob, gender, occupation, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $fullname, $email, $password, $phone, $dob, $gender, $occupation, $address);

    if ($stmt->execute()) {
        header("Location: login-client.php?success=registered");
    } else {
        header("Location: register.php?error=failed");
    }
    exit;
}

    // 3. CHECK IF EMAIL ALREADY EXISTS
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param("s", $email);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result();

    if ($checkRes->num_rows > 0) {
        header("Location: register.php?error=email_exists");
        exit;
    }
    $checkStmt->close();

    // 4. HASH THE PASSWORD
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. INSERT NEW USER WITH KYC DETAILS
    // Siguraduhin na ang table columns ay: fullname, phone, email, password, dob, gender, occupation, address
    $insertStmt = $conn->prepare("INSERT INTO users (fullname, phone, email, password, dob, gender, occupation, address) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    
    // Walong (8) "s" dahil lahat ay strings sa bind_param
    $insertStmt->bind_param("ssssssss", $fullname, $phone, $email, $hashed_password, $dob, $gender, $occupation, $address);

    if ($insertStmt->execute()) {
        // SUCCESS: Redirect sa login page na may tagumpay na mensahe
        header("Location: login-client.php?success=registered");
        exit;
    } else {
        header("Location: register.php?error=failed");
        exit;
    }

// Kapag hindi POST request, balik sa register
header("Location: register.php?error=failed");
exit;