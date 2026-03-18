<?php
session_start();
if (!isset($_SESSION['reset_email'])) {
    header("Location: login-client.php");
    exit;
}
$email = $_SESSION['reset_email'];
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP | Microfinance</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="form-header">
                <h1 class="form-title">Enter Verification Code</h1>
                <p class="form-subtitle">We sent a 6-digit code to <b><?php echo $email; ?></b></p>
            </div>

            <form action="verify-otp-action.php" method="POST" class="form">
                <div class="input-group">
                    <input type="text" name="otp" class="input-field" placeholder="000000" maxlength="6" required style="text-align:center; font-size: 2rem; letter-spacing: 10px;">
                </div>
                <button type="submit" class="btn btn-primary">Verify OTP</button>
                <div class="form-footer" style="margin-top: 15px;">
                    <a href="login-client.php" class="link">Back to Login</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        <?php if ($error === 'invalid'): ?>
            Swal.fire({ icon: 'error', title: 'Invalid Code', text: 'The OTP you entered is incorrect or expired.', confirmButtonColor: '#2ca078' });
        <?php endif; ?>
    </script>
</body>
</html>