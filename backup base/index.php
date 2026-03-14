<?php
session_start();

// MADIIN: ANTI-BACK BUTTON CACHE PREVENTION
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Kung naka-login na, ibato agad sa tamang dashboard!
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    if ($_SESSION['admin_role'] === 'LSA') header("Location: lsa/dashboard.php");
    elseif ($_SESSION['admin_role'] === 'LO') header("Location: LOAN_OFFICER/dashboard.php");
    elseif ($_SESSION['admin_role'] === 'FINANCE_ADMIN') header("Location: finance/dashboard.php");
    exit;
}

$error_msg = '';
if (isset($_GET['error'])) {
    if ($_GET['error'] === 'invalid') $error_msg = "Invalid username or password.";
    elseif ($_GET['error'] === 'suspended') $error_msg = "Your account is SUSPENDED. Contact HR/Admin.";
    elseif ($_GET['error'] === 'timeout') $error_msg = "Session expired due to inactivity.";
    elseif ($_GET['error'] === 'logged_out') $error_msg = "Successfully logged out.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microfinance |  Officer's Portal</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet"> 
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="css/login.css">
</head>
<body>

<div class="container">
    <div class="login-wrapper">
        
        <div class="header">
            <div class="logo">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h2>MICROFINANCE</h2>
            <p>Welcome, Officer!</p>
        </div>

        <?php if($error_msg): ?>
            <div class="error-msg" style="display: block; margin-bottom: 15px;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?= htmlspecialchars($error_msg) ?>
            </div>
        <?php endif; ?>

        <form id="loginForm" method="POST" action="auth.php">
            
            <label class="field-label">Username</label>
            <div class="input-box">
                <i class="bi bi-person-fill icon"></i>
                <input type="text" name="username" id="username" placeholder="Enter your username" required autocomplete="off">
            </div>

            <label class="field-label">Password</label>
            <div class="input-box">
                <i class="bi bi-key-fill icon"></i>
                <input type="password" name="password" id="password" placeholder="••••••••" required>
                <i class="bi bi-eye-slash-fill toggle-pass" id="toggleBtn"></i>
            </div>

            <button type="submit" class="btn-submit">
                SECURE LOGIN <i class="bi bi-box-arrow-in-right"></i>
            </button>

        </form>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // 1. Password Visibility Toggle
        const toggleBtn = document.getElementById('toggleBtn');
        const passInput = document.getElementById('password');

        if(toggleBtn && passInput) {
            toggleBtn.addEventListener('click', function() {
                const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passInput.setAttribute('type', type);
                this.classList.toggle('bi-eye-fill');
                this.classList.toggle('bi-eye-slash-fill');
            });
        }

        // 2. Input Focus Effects
        const inputs = document.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => input.parentElement.classList.add('focused'));
            input.addEventListener('blur', () => input.parentElement.classList.remove('focused'));
        });

        // 3. AUTO-HIDE ERROR MESSAGE AFTER 5 SECONDS
        const errorMsg = document.querySelector('.error-msg');
        if (errorMsg) {
            setTimeout(() => {
                // Fade out effect
                errorMsg.style.transition = 'opacity 0.5s ease';
                errorMsg.style.opacity = '0';
                
                // Tuluyang tanggalin sa screen pagkatapos mag-fade
                setTimeout(() => {
                    errorMsg.style.display = 'none';
                }, 500); // Wait 0.5s for the fade animation to finish
            }, 5000); // 5000 milliseconds = 5 seconds
        }
    });
</script>

</body>
</html>