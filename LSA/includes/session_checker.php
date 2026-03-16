<?php
// LSA/includes/session_checker.php

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

if (session_status() === PHP_SESSION_NONE) { session_start(); }

// 1. Check kung may session
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: ../index.php?error=invalid");
    exit;
}

// 2. STRICT ROLE CHECK: Kick out kapag hindi LSA or FINANCE_ADMIN ang pumasok
if ($_SESSION['admin_role'] !== 'LSA' && $_SESSION['admin_role'] !== 'FINANCE_ADMIN') {
    header("Location: ../index.php?error=invalid");
    exit;
}

// 3. Inactivity Timeout (30 mins)
$timeout_duration = 1800; 
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header("Location: ../index.php?error=timeout");
    exit;
}
$_SESSION['last_activity'] = time();
?>