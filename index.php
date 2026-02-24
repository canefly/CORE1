<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Microfinance | Login</title>
    
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
            <h2>Secure Access</h2>
            <p>Microfinance Management System</p>
        </div>

        <form id="loginForm" method="POST" action="auth.php">
            
            <label class="field-label">Department</label>
            <div class="input-box">
                <i class="bi bi-people-fill icon"></i>
                <select name="role" id="role" required>
                    <option value="" disabled selected>Select your role...</option>
                    <option value="lsa">Loan Service Associate (LSA)</option>
                    <option value="officer">Loan Officer (Approver)</option>
                    <option value="finance">Finance / Accountant</option>
                    <option value="restructure">Loan Restructure Dept.</option>
                </select>
                <i class="bi bi-chevron-down arrow"></i>
            </div>

            <label class="field-label">Email Address</label>
            <div class="input-box">
                <i class="bi bi-envelope-fill icon"></i>
                <input type="email" name="email" id="email" placeholder="name@company.com" required>
            </div>

            <label class="field-label">Password</label>
            <div class="input-box">
                <i class="bi bi-key-fill icon"></i>
                <input type="password" name="password" id="password" placeholder="••••••••" required>
                <i class="bi bi-eye-slash-fill toggle-pass" id="toggleBtn"></i>
            </div>

            <button type="submit" class="btn-submit">
                LOGIN NOW
            </button>

        </form>

        <div id="errorArea" class="error-msg" style="display: none;"></div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleBtn');
        const passInput = document.getElementById('password');
        const form = document.getElementById('loginForm');
        const errorArea = document.getElementById('errorArea');

        // 1. Password Toggle Logic
        toggleBtn.addEventListener('click', function() {
            const type = passInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passInput.setAttribute('type', type);
            
            // Switch Icon
            this.classList.toggle('bi-eye-fill');
            this.classList.toggle('bi-eye-slash-fill');
        });

        // 2. Simple Validation on Submit
        form.addEventListener('submit', function(e) {
            const role = document.getElementById('role').value;
            
            if(role === "") {
                e.preventDefault();
                errorArea.style.display = 'block';
                errorArea.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Please select a Department Role.';
            }
        });

        // 3. Focus Effect Helper (Optional JS for older browsers, mainly handled by CSS)
        const inputs = document.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.parentElement.classList.add('focused');
            });
            input.addEventListener('blur', () => {
                input.parentElement.classList.remove('focused');
            });
        });
    });
</script>

</body>
</html>