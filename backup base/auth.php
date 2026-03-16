<?php
session_start();

// Siguraduhin na TAMA ang folder name: 'include' (base sa screenshot mo)
require_once __DIR__ . '/include/config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_input = trim($_POST['username'] ?? '');
    $pass_input = $_POST['password'] ?? '';

    try {
        // 1. DATABASE SELECTOR (Auto-detect if PDO or MySQLi)
        if (isset($pdo)) {
            // --- PDO VERSION ---
            $sql = "SELECT id, username, password, status, 'LSA' as role, full_name FROM lsa_users WHERE username = ?
                    UNION ALL
                    SELECT id, username, password, status, 'LO' as role, full_name FROM lo_users WHERE username = ?
                    UNION ALL
                    SELECT id, username, password, 'ACTIVE' as status, 'FINANCE_ADMIN' as role, full_name FROM finance_admin WHERE username = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_input, $user_input, $user_input]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        } else {
            // --- MYSQLI VERSION ($conn) ---
            $sql = "(SELECT id, username, password, status, 'LSA' as role, full_name FROM lsa_users WHERE username = ?)
                    UNION ALL
                    (SELECT id, username, password, status, 'LO' as role, full_name FROM lo_users WHERE username = ?)
                    UNION ALL
                    (SELECT id, username, password, 'ACTIVE' as status, 'FINANCE_ADMIN' as role, full_name FROM finance_admin WHERE username = ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sss", $user_input, $user_input, $user_input);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
        }

        // 2. VALIDATION LOGIC (Hybrid: Tanggap ang Plain Text at Hashed)
        if ($user) {
            
            // Check kung match ang password (Plain Text OR Hashed)
            $password_matches = ($pass_input === $user['password'] || password_verify($pass_input, $user['password']));

            if ($password_matches) {
                
                // Check kung suspended (maliban sa Finance Admin na laging Active)
                if ($user['status'] === 'SUSPENDED') {
                    header("Location: index.php?error=suspended");
                    exit;
                }

                // SECURITY: Iwas Session Hijacking
                session_regenerate_id(true);

                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_role'] = $user['role'];
                $_SESSION['admin_name'] = $user['full_name'];
                $_SESSION['last_activity'] = time();

                // 3. DYNAMIC REDIRECT base sa Role
                if ($user['role'] === 'LSA') {
                    header('Location: lsa/dashboard.php');
                } elseif ($user['role'] === 'LO') {
                    header('Location: LOAN_OFFICER/dashboard.php');
                } elseif ($user['role'] === 'FINANCE_ADMIN') {
                    header('Location: finance/dashboard.php');
                }
                exit;
            }
        }

        // Kung mali ang username o password
        header("Location: index.php?error=invalid");
        exit;

    } catch (Exception $e) {
        // Para makita mo kung may database error talaga
        die("System Error: " . $e->getMessage());
    }
} else {
    header("Location: index.php");
    exit;
}