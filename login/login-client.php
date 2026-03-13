<?php
session_start();

// ANTI-BACK BUTTON CACHE PREVENTION
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Auto-redirect if client is already logged in
// (Assuming your client dashboard is located at ../client/dashboard.php)
if (isset($_SESSION['user_id'])) {
    header("Location: ../client/dashboard.php");
    exit;
}

// Catch errors from login-client-action.php
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microfinance | Client Portal</title>
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
                <a href="login-client.php" class="logo-wrapper" style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; align-items: center;">
                    <img src="../assets/img/logo.png" alt="Logo" class="logo" onerror="this.src='https://via.placeholder.com/50'">
                    <span class="logo-text">Microfinance</span>
                </a>
            </div>

            <div class="form-wrapper active" id="loginForm">
                <div class="form-header">
                    <h1 class="form-title">Welcome Back!</h1>
                    <p class="form-subtitle">Sign in to manage your loans and track progress.</p>
                </div>

                <form class="form" action="login-client-action.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    
                    <div class="input-group">
                        <label for="loginEmail" class="input-label">Email Address</label>
                        <div class="input-wrapper">
                            <i data-lucide="mail" class="input-icon"></i>
                           <input 
                                type="email" 
                                id="loginEmail" 
                                name="email" 
                                class="input-field" 
                                placeholder="name@example.com" 
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
                                placeholder="••••••••" 
                                required
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('loginPassword')">
                                <i data-lucide="eye" class="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span>Access Dashboard</span>
                        <i data-lucide="arrow-right" class="btn-icon"></i>
                    </button>
                    
                    <div class="form-footer" style="display: flex; flex-direction: column; gap: 10px; align-items: center; margin-top: 15px;">
                        <span style="font-size: 0.9rem; color: var(--text-muted);">New to MicroFinance? <a href="register.php" class="link">Create an Account</a></span>
                        <a href="login.php" class="link" style="font-size: 0.85rem; opacity: 0.8;">Switch to Employee Portal</a>
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
                    text: 'Invalid email or password.',
                    confirmButtonColor: '#2ca078'
                });
            } else if (error === 'suspended') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Suspended',
                    text: 'Your account has been SUSPENDED. Please contact management.',
                    confirmButtonColor: '#2ca078'
                });
            } else if (error === 'account_invalid') {
                Swal.fire({
                    icon: 'error',
                    title: 'Session Invalid',
                    text: 'Please log in again.',
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

        // Add this right next to your other SweetAlert checks in login-client.php
        const success = "<?php echo $_GET['success'] ?? ''; ?>";
        if (success === 'registered') {
            Swal.fire({
                icon: 'success',
                title: 'Registration Successful!',
                text: 'Welcome to the family. Please log in.',
                confirmButtonColor: '#2ca078'
            });
        }
    </script>
</body>
</html>