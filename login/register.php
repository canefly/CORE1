<?php
session_start();

// Auto-redirect if client is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../client/dashboard.php");
    exit;
}

// Catch errors from register-action.php
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microfinance | Create Account</title>
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
                <a href="register.php" class="logo-wrapper" style="text-decoration: none; cursor: pointer; display: flex; flex-direction: column; align-items: center;">
                    <img src="../assets/img/logo.png" alt="Logo" class="logo" onerror="this.src='https://via.placeholder.com/50'">
                    <span class="logo-text">Microfinance</span>
                </a>
            </div>

            <div class="form-wrapper active" id="registerForm">
                <div class="form-header">
                    <h1 class="form-title">Let's Get Started!</h1>
                    <p class="form-subtitle">Create an account to join our community.</p>
                </div>

                <form class="form" action="register-action.php" method="POST">
                    
                    <div class="input-group">
                        <label for="regFullName" class="input-label">Full Name</label>
                        <div class="input-wrapper">
                            <i data-lucide="user" class="input-icon"></i>
                           <input 
                                type="text" 
                                id="regFullName" 
                                name="fullname" 
                                class="input-field" 
                                placeholder="Juan Dela Cruz" 
                                required
                                autocomplete="off"
                           >
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="regPhone" class="input-label">Mobile Number</label>
                        <div class="input-wrapper">
                            <i data-lucide="phone" class="input-icon"></i>
                           <input 
                                type="tel" 
                                id="regPhone" 
                                name="phone" 
                                class="input-field" 
                                placeholder="0912 345 6789" 
                                required
                                autocomplete="off"
                           >
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="regEmail" class="input-label">Email Address</label>
                        <div class="input-wrapper">
                            <i data-lucide="mail" class="input-icon"></i>
                           <input 
                                type="email" 
                                id="regEmail" 
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
                            <label for="regPassword" class="input-label">Password</label>
                        </div>
                        <div class="input-wrapper">
                            <i data-lucide="lock" class="input-icon"></i>
                            <input 
                                type="password" 
                                id="regPassword" 
                                name="password" 
                                class="input-field" 
                                placeholder="••••••••" 
                                required
                            >
                            <button type="button" class="toggle-password" onclick="togglePassword('regPassword')">
                                <i data-lucide="eye" class="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <span>Join the Family</span>
                        <i data-lucide="user-plus" class="btn-icon"></i>
                    </button>
                    
                    <div class="form-footer" style="text-align: center; margin-top: 15px;">
                        <span style="font-size: 0.9rem; color: var(--text-muted);">Already have an account? <a href="login-client.php" class="link">Sign In Here</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/login.js?v=<?php echo time(); ?>"></script>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const error = "<?php echo $error; ?>";
            
            if (error === 'email_exists') {
                Swal.fire({
                    icon: 'warning',
                    title: 'Email Already in Use',
                    text: 'This email is already registered. Please log in instead.',
                    confirmButtonColor: '#2ca078'
                });
            } else if (error === 'failed') {
                Swal.fire({
                    icon: 'error',
                    title: 'Registration Failed',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#2ca078'
                });
            } else if (error === 'missing_fields') {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Fields',
                    text: 'Please fill in all the required fields.',
                    confirmButtonColor: '#2ca078'
                });
            }
        });
    </script>
</body>
</html>