<?php
session_start();

// SECURITY: Kung hindi galing sa successful OTP verification, hindi pwede rito
if (!isset($_SESSION['otp_verified']) || !isset($_SESSION['reset_email'])) {
    header("Location: login-client.php");
    exit;
}

// Generate CSRF token para sa form security
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password | Microfinance</title>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="form-header">
                <h1 class="form-title">Create New Password</h1>
                <p class="form-subtitle">Make sure your new password is secure and easy to remember.</p>
            </div>

            <form action="reset-password-action.php" method="POST" class="form" id="resetForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

                <div class="input-group">
                    <label class="input-label">New Password</label>
                    <div class="input-wrapper">
                        <i data-lucide="lock" class="input-icon"></i>
                        <input type="password" id="newPass" name="new_password" class="input-field" placeholder="••••••••" required>
                    </div>
                </div>

                <div class="input-group">
                    <label class="input-label">Confirm New Password</label>
                    <div class="input-wrapper">
                        <i data-lucide="shield-check" class="input-icon"></i>
                        <input type="password" id="confirmPass" name="confirm_password" class="input-field" placeholder="••••••••" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">Update Password</button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        document.getElementById('resetForm').addEventListener('submit', function(e) {
            const p1 = document.getElementById('newPass').value;
            const p2 = document.getElementById('confirmPass').value;

            if (p1 !== p2) {
                e.preventDefault();
                Swal.fire({
                    icon: 'error',
                    title: 'Mismatch',
                    text: 'Passwords do not match. Please try again.',
                    confirmButtonColor: '#2ca078'
                });
            }
        });

        <?php if ($error === 'mismatch'): ?>
            Swal.fire({ icon: 'error', title: 'Error', text: 'Passwords did not match.', confirmButtonColor: '#2ca078' });
        <?php endif; ?>
    </script>
</body>
</html>