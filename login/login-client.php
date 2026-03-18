<?php
session_start();

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

if (isset($_SESSION['user_id'])) {
    header("Location: ../client/dashboard.php");
    exit;
}

$error = $_GET['error'] ?? '';
$wait = $_GET['wait'] ?? 0;
$attempts = $_GET['attempts'] ?? 0;
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
      if (savedTheme === "dark") { document.documentElement.classList.add("dark-mode"); }
    })();
    </script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    <style> .locked-mode #loginForm { display: none; } </style>
</head>
<body class="<?php echo ($error === 'locked') ? 'locked-mode' : ''; ?>">
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i data-lucide="sun" class="sun-icon"></i> <i data-lucide="moon" class="moon-icon"></i>
    </button>

    <div class="container">
        <div class="auth-card">
            <div class="logo-section">
                <div class="logo-wrapper">
                    <img src="../assets/img/logo.png" alt="Logo" class="logo">
                    <span class="logo-text">Microfinance</span>
                </div>
            </div>

            <div class="form-wrapper active" id="loginForm">
                <div class="form-header">
                    <h1 class="form-title">Welcome Back!</h1>
                    <p class="form-subtitle">Securely manage your loans and tracking.</p>
                </div>

                <form class="form" id="mainLoginForm" action="login-client-action.php" method="POST">
                    <input type="hidden" name="action" value="login">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="input-group">
                        <label for="loginEmail" class="input-label">Email Address</label>
                        <div class="input-wrapper">
                            <i data-lucide="mail" class="input-icon"></i>
                            <input type="email" id="loginEmail" name="email" class="input-field" placeholder="name@example.com" required autocomplete="off">
                        </div>
                    </div>

                    <div class="input-group">
                        <label for="loginPassword" class="input-label">Password</label>
                        <div class="input-wrapper">
                            <i data-lucide="lock" class="input-icon"></i>
                            <input type="password" id="loginPassword" name="password" class="input-field" placeholder="••••••••" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('loginPassword')">
                                <i data-lucide="eye" class="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary">
                        <span>Access Dashboard</span>
                        <i data-lucide="arrow-right" class="btn-icon"></i>
                    </button>
                    
                    <div class="form-footer" style="display: flex; flex-direction: column; gap: 10px; align-items: center; margin-top: 15px;">
                        <a href="javascript:void(0)" onclick="handleForgotPass()" class="link" style="font-size: 0.85rem;">Forgot Password?</a>
                        <span style="font-size: 0.9rem; color: var(--text-muted);">New? <a href="register.php" class="link">Create Account</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/login.js?v=<?php echo time(); ?>"></script>
    <script>
        function handleForgotPass() {
            const email = document.getElementById('loginEmail').value.trim();
            if (!email) {
                Swal.fire({ icon: 'info', title: 'Email Required', text: 'Type your email first so we can send the OTP.', confirmButtonColor: '#2ca078' });
                return;
            }
            window.location.href = `forgot-password-action.php?email=${encodeURIComponent(email)}&csrf_token=<?php echo $_SESSION['csrf_token']; ?>`;
        }

        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
            const error = "<?php echo $error; ?>";
            const waitMins = parseInt("<?php echo $wait; ?>");

            if (error === 'invalid') {
                Swal.fire({ icon: 'error', title: 'Login Failed', html: `Invalid credentials.<br><b style="color:#e74c3c">Attempt <?php echo $attempts; ?> of 3</b>`, confirmButtonColor: '#2ca078' });
            } else if (error === 'locked') {
                let secondsLeft = waitMins * 60;
                Swal.fire({
                    icon: 'warning', title: 'System Locked',
                    html: 'Please wait: <br><b id="countdown" style="font-size: 2rem; color:#e74c3c"></b>',
                    allowOutsideClick: false, showConfirmButton: false,
                    didOpen: () => {
                        const b = Swal.getHtmlContainer().querySelector('#countdown');
                        timerInterval = setInterval(() => {
                            let m = Math.floor(secondsLeft / 60); let s = secondsLeft % 60;
                            b.textContent = `${m}:${s < 10 ? '0' : ''}${s}`;
                            if (--secondsLeft < 0) { clearInterval(timerInterval); window.location.href = 'login-client.php'; }
                        }, 1000);
                    }
                });
            } else if (error === 'not_found') {
                Swal.fire({ icon: 'error', title: 'Not Found', text: 'That email is not registered in our system.', confirmButtonColor: '#2ca078' });
            }
        });
    </script>
</body>
</html>