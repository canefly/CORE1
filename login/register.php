<?php
session_start();
if (isset($_SESSION['user_id'])) { header("Location: ../client/dashboard.php"); exit; }
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
      if (savedTheme === "dark") {
        document.documentElement.classList.add("dark-mode");
      }
    })();
    </script>

    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="icon" type="image/png" href="../assets/img/logo.png">
    
    <style>
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; text-align: left; }
        .full-width { grid-column: span 2; }
        @media (max-width: 480px) { .form-grid { grid-template-columns: 1fr; } .full-width { grid-column: span 1; } }
        
        /* Password Strength UI */
        .strength-meter { width: 100%; height: 6px; background: #334155; border-radius: 10px; margin-top: 8px; overflow: hidden; }
        #strength-bar { height: 100%; width: 0%; transition: 0.3s; }
        .req-list { list-style: none; padding: 0; margin: 10px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 5px; font-size: 11px; }
        .req-list li { color: #94a3b8; display: flex; align-items: center; gap: 5px; }
        .req-list li.valid { color: #10b981; }
        .req-list li.valid::before { content: '●'; }
        .req-list li.invalid::before { content: '○'; }
        #match-text { font-weight: 600; transition: 0.2s; }
    </style>
</head>
<body>
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
        <i data-lucide="sun" class="sun-icon"></i>
        <i data-lucide="moon" class="moon-icon"></i>
    </button>

    <div class="container" style="padding: 40px 0;">
        <div class="auth-card" style="max-width: 550px;">
            <div class="logo-section">
                <div class="logo-wrapper">
                    <img src="../assets/img/logo.png" alt="Logo" class="logo">
                    <span class="logo-text">Microfinance</span>
                </div>
            </div>

            <div class="form-wrapper active">
                <div class="form-header">
                    <h1 class="form-title">Join the Family</h1>
                    <p class="form-subtitle">Create a secure account for your financial needs.</p>
                </div>

                <form class="form" action="register-action.php" method="POST" id="regForm">
                    <div class="form-grid">
                        <div class="input-group full-width">
                            <label class="input-label">Full Name</label>
                            <input type="text" name="fullname" class="input-field" placeholder="Juan Dela Cruz" required>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Mobile Number</label>
                            <input type="tel" name="phone" class="input-field" placeholder="0912 345 6789" required>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Email Address</label>
                            <input type="email" name="email" class="input-field" placeholder="name@example.com" required>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Birth Date</label>
                            <input type="date" name="dob" class="input-field" required>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Gender</label>
                            <select name="gender" class="input-field" style="padding-left: 1rem;" required>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>

                        <div class="input-group full-width">
                            <label class="input-label">Occupation</label>
                            <input type="text" name="occupation" class="input-field" placeholder="e.g. Small Business Owner" required>
                        </div>

                        <div class="input-group full-width">
                            <label class="input-label">Residential Address</label>
                            <textarea name="address" class="input-field" rows="2" placeholder="House No, St, Brgy, City" required></textarea>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="regPassword" name="password" class="input-field" oninput="validateForm()" required>
                            </div>
                            <div class="strength-meter"><div id="strength-bar"></div></div>
                            <p id="strength-text" style="font-size: 10px; color: #94a3b8; margin-top: 5px;">Strength: Weak</p>
                        </div>

                        <div class="input-group">
                            <label class="input-label">Confirm Password</label>
                            <div class="input-wrapper">
                                <input type="password" id="confirmPassword" class="input-field" oninput="validateForm()" required>
                            </div>
                            <p id="match-text" style="font-size: 11px; margin-top: 5px;"></p>
                        </div>
                    </div>

                    <ul class="req-list">
                        <li id="req-len">8+ Characters</li>
                        <li id="req-up">Uppercase (A-Z)</li>
                        <li id="req-num">Number (0-9)</li>
                        <li id="req-sp">Special Char (@$!%)</li>
                    </ul>

                    <button type="submit" class="btn btn-primary" id="submitBtn" disabled style="opacity: 0.5; margin-top: 15px; width: 100%;">
                        <span>Create Account</span>
                    </button>
                    
                    <div class="form-footer" style="text-align: center; margin-top: 20px;">
                        <span style="font-size: 0.9rem; color: #94a3b8;">Already a member? <a href="login-client.php" class="link">Sign In</a></span>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="../assets/js/login.js?v=<?php echo time(); ?>"></script>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            lucide.createIcons();
            
            // Handle Theme Toggle Button
            const themeToggle = document.getElementById('themeToggle');
            themeToggle.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark-mode');
                localStorage.setItem('theme', isDark ? 'dark' : 'light');
            });
        });

        function validateForm() {
            const pass = document.getElementById('regPassword').value;
            const confirm = document.getElementById('confirmPassword').value;
            const btn = document.getElementById('submitBtn');
            const bar = document.getElementById('strength-bar');
            const matchText = document.getElementById('match-text');

            const reqs = {
                len: pass.length >= 8,
                up: /[A-Z]/.test(pass),
                num: /[0-9]/.test(pass),
                sp: /[@$!%*?&]/.test(pass)
            };

            document.getElementById('req-len').className = reqs.len ? 'valid' : 'invalid';
            document.getElementById('req-up').className = reqs.up ? 'valid' : 'invalid';
            document.getElementById('req-num').className = reqs.num ? 'valid' : 'invalid';
            document.getElementById('req-sp').className = reqs.sp ? 'valid' : 'invalid';

            let score = (reqs.len + reqs.up + reqs.num + reqs.sp) * 25;
            bar.style.width = score + "%";
            bar.style.backgroundColor = score < 50 ? "#ef4444" : (score < 100 ? "#fbbf24" : "#10b981");

            if (confirm === "") {
                matchText.innerText = "";
            } else if (pass === confirm) {
                matchText.innerText = "✓ Passwords match";
                matchText.style.color = "#10b981";
            } else {
                matchText.innerText = "✗ Passwords do not match";
                matchText.style.color = "#ef4444";
            }

            const isReady = (score === 100 && pass === confirm && pass !== "");
            btn.disabled = !isReady;
            btn.style.opacity = isReady ? "1" : "0.5";
        }
    </script>
</body>
</html>