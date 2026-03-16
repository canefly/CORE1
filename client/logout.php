<?php
// CORE1/client/logout.php
session_start();

// 1. Tanggalin lahat ng laman ng session variables
$_SESSION = array();

// 2. Patayin pati ang Session Cookie sa browser ng user
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 3. Tuluyang sirain ang session sa server
session_unset();
session_destroy();

// 4. I-redirect pabalik sa login page
header("Location: ../index.php");
exit;
?>