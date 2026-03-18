<?php
session_start();
if (!isset($_SESSION['temp_user_id'])) { header("Location: login-client.php"); exit; }
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Authorize Device | Microfinance</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <div class="auth-card">
            <div class="form-header">
                <h1 class="form-title">Unrecognized Device</h1>
                <p class="form-subtitle">For your security, please enter the 6-digit code sent to your email.</p>
            </div>
            <form action="verify-device-action.php" method="POST" class="form">
                <div class="input-group">
                    <input type="text" name="otp" class="input-field" placeholder="000000" maxlength="6" required style="text-align:center; font-size: 2rem; letter-spacing: 8px;">
                </div>
                <button type="submit" class="btn btn-primary">Verify Device</button>
            </form>
        </div>
    </div>
    <script>
        <?php if ($error === 'invalid'): ?>
            Swal.fire({ icon: 'error', title: 'Wrong Code', text: 'The code is incorrect or has expired.', confirmButtonColor: '#2ca078' });
        <?php endif; ?>
    </script>
</body>
</html>