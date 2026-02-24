<?php
session_start();


$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? '';


// TEMP ONLY (replace with database later)
$validRoles = ['lsa', 'officer', 'finance', 'restructure'];


if (in_array($role, $validRoles)) {
    $_SESSION['logged_in'] = true;
    $_SESSION['role'] = $role;


    switch ($role) {
        case 'lsa':
            header('Location: lsa/dashboard.php');
            break;
        case 'officer':
            header('Location: LOAN_OFFICER/dashboard.php');
            break;
        case 'finance':
            header('Location: finance/dashboard.php');
            break;
        case 'restructure':
            header('Location: restructure/dashboard.php');
            break;
    }
    exit;
}


header('Location: index.php?error=1');
exit;