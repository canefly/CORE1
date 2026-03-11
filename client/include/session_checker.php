<?php
// CORE1/client/include/session_checker.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. BASIC CHECK: May naka-login ba?
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
// 1. BASIC CHECK: May naka-login ba?
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. SESSION TIMEOUT PROTECTION (Auto-logout after 30 minutes of inactivity)
$timeout_duration = 1800; // 30 mins in seconds
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: login.php?msg=session_expired");
    exit;
}
$_SESSION['last_activity'] = time();

// 3. SESSION FIXATION PROTECTION (Regenerate ID every 15 minutes)
if (!isset($_SESSION['created_at'])) {
    $_SESSION['created_at'] = time();
} else if (time() - $_SESSION['created_at'] > 900) {
    session_regenerate_id(true);
    $_SESSION['created_at'] = time();
}

// 4. MADIIN NA SQL INJECTION PROTECTION & DB VERIFICATION
// Sinisiguro nito na hindi "dinaya" ang session at existing pa talaga ang user sa database.
require_once __DIR__ . '/config.php';

$current_user_id = (int)$_SESSION['user_id'];

// Gamit ang PREPARED STATEMENT para 100% block ang SQL Attacks (ex. ' OR 1=1 -- )
$stmt_check = $conn->prepare("SELECT id FROM users WHERE id = ? LIMIT 1");
$stmt_check->bind_param("i", $current_user_id);
$stmt_check->execute();
$stmt_check->store_result();

if ($stmt_check->num_rows === 0) {
    // Kung nag-login tapos biglang binura ng Admin yung account niya, i-kick out agad!
    $stmt_check->close();
    session_unset();
    session_destroy();
    header("Location: login.php?msg=account_invalid");
    exit;
}
$stmt_check->close();
?>