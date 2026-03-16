<?php
session_start();

// ANTI-BACK BUTTON CACHE PREVENTION
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Auto-redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if ($_SESSION['admin_role'] === 'LSA') header("Location: ../LSA/dashboard.php");
    elseif ($_SESSION['admin_role'] === 'LO') header("Location: ../LO/dashboard.php");
    elseif ($_SESSION['admin_role'] === 'FINANCE_ADMIN') header("Location: ../finance/dashboard.php");
    exit;
}

// Catch errors from login_action.php
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microfinance | Employee Portal</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
    (function() {
      const savedTheme = localStorage.getItem("theme");
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (savedTheme === "dark" || (!savedTheme && systemDark)) {
        document.documentElement.classList.add("dark-mode");
      }
    })();
    </script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>
<body>
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i data-lucide="sun" class="sun-icon"></i>
        <i data-lucide="moon" class="moon-icon"></i>
    </button>

    <div class="container">
        <div class="auth-card">
            <div class="logo-section">
                <a href="login.php" class="logo-wrapper" style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; align-items: center;">
                    <img src="../assets/img/logo.png" alt="Logo" class="logo" onerror="this.src='https://via.placeholder.com/50'">
                    <span class="logo-text">Microfinance</span>
                </a>
            </div>

            <div class="form-wrapper active" id="loginForm">
                <div class="form-header">
                    <h1 class="form-title">Employee Portal</h1>
                    <p class="form-subtitle">Sign in to access your dashboard</p>
                </div>

                <form class="form" action="login_action.php" method="POST">
                    <div class="input-group">
                        <label for="loginUsername" class="input-label">Username</label>
                        <div class="input-wrapper">
                            <i data-lucide="user" class="input-icon"></i>
                           <input 
                                type="text" 
                                id="loginUsername" 
                                name="username" 
                                class="input-field" 
                                placeholder="Enter your username" 
                                required
                                autocomplete="off"
                           >
                        </div>
                    </div>

                    <div class="input-group">
                        <div class="label-row">
                            <label for="loginPassword" class="input-label">Password</label>
                        </div>
                        <div class="input-wrapper">
                            <i data-lucide="lock" class="input-icon"></i>
                            <input 
                                type="password" 
                                id="loginPassword" 
                                name="password" 
                                class="input-field" 
                                placeholder="Enter your password" 
                                required
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('loginPassword')">
                                <i data-lucide="eye" class="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span>Secure Login</span>
                        <i data-lucide="arrow-right" class="btn-icon"></i>
                    </button>
                    
                    <div class="form-footer">
                        <a href="login-client.php" class="link">Switch to Client Portal</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/login.js?v=<?php echo time(); ?>"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const error = "<?php echo $error; ?>";
            
            if (error === 'invalid') {
                Swal.fire({
                    icon: 'error',
                    title: 'Access Denied',
                    text: 'Invalid username or password.',
                    confirmButtonColor: '#2ca078'
                });
            } else if (error === 'suspended') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Suspended',
                    text: 'Your account is SUSPENDED. Contact HR/Admin.',
                    confirmButtonColor: '#2ca078'
                });
            } else if (error === 'timeout') {
                Swal.fire({
                    icon: 'info',
                    title: 'Session Expired',
                    text: 'Session expired due to inactivity.',
                    confirmButtonColor: '#2ca078'
                });
            } else if (error === 'logged_out') {
                Swal.fire({
                    icon: 'success',
                    title: 'Logged Out',
                    text: 'Successfully logged out.',
                    confirmButtonColor: '#2ca078',
                    timer: 2000,
                    showConfirmButton: false
                });
            }
        });
    </script>
</body>
</html>