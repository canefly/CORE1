<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MicroFinance - Portal</title>
    
    <link rel="stylesheet" href="assets/css/login.css">
    
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body>

    <div class="split-left" id="leftPanel">
        <div class="brand-text">
            <h1 id="brandTitle">Financial Growth,<br>Simplified.</h1>
            <p id="brandDesc">We are thrilled to have you here. Join our secure platform to manage loans, track progress, and achieve your financial dreams.</p>
        </div>
    </div>

    <div class="split-right">
        <div class="auth-container">
            
            <div class="auth-header">
                <i class="bi bi-person-heart header-icon" id="headerIcon"></i>
                <h2 id="pageTitle">Let's Get Started!</h2>
                <p id="pageSubTitle">Create an account to join our community.</p>
            </div>

            <form action="auth_process.php" method="POST" id="authForm" autocomplete="off">
                
                <div id="signupFields" class="signup-fields">
                    <div class="form-group">
                        <label class="form-label">Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" name="fullname" class="form-control" placeholder="Juan Dela Cruz">
                            <i class="bi bi-person input-icon"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Mobile Number</label>
                        <div class="input-wrapper">
                            <input type="tel" name="phone" class="form-control" placeholder="0912 345 6789">
                            <i class="bi bi-phone input-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
                        <i class="bi bi-envelope input-icon"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Password</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        <i class="bi bi-lock input-icon"></i>
                    </div>
                </div>

                <input type="hidden" name="action" id="formAction" value="signup">

                <button type="submit" class="btn-main" id="submitBtn">Join the Family</button>

            </form>

            <div class="footer-link">
                <span id="footerText">Already have an account?</span>
                <a onclick="toggleMode()" class="toggle-btn" id="toggleLink">Sign In Here</a>
            </div>

        </div>
    </div>

    <script>
        let isSignup = true; 

        function toggleMode() {
            isSignup = !isSignup;

            // Elements
            const brandTitle = document.getElementById('brandTitle');
            const brandDesc = document.getElementById('brandDesc');
            const pageTitle = document.getElementById('pageTitle');
            const pageSubTitle = document.getElementById('pageSubTitle');
            const signupFields = document.getElementById('signupFields');
            const submitBtn = document.getElementById('submitBtn');
            const footerText = document.getElementById('footerText');
            const toggleLink = document.getElementById('toggleLink');
            const formAction = document.getElementById('formAction');
            const headerIcon = document.getElementById('headerIcon');
            const leftPanel = document.getElementById('leftPanel');

            if (isSignup) {
                // SIGNUP STATE
                brandTitle.innerHTML = "Financial Growth,<br>Simplified.";
                brandDesc.innerHTML = "We are thrilled to have you here. Join our secure platform to manage loans and achieve your dreams.";
                
                // Keep background color static as requested
                leftPanel.style.background = "#10b981"; 

                pageTitle.innerText = "Let's Get Started!";
                pageSubTitle.innerText = "Create an account to join our community.";
                
                signupFields.classList.remove('hidden');
                submitBtn.innerText = "Join the Family";
                footerText.innerText = "Already have an account?";
                toggleLink.innerText = "Sign In Here";
                formAction.value = "signup";
                
                headerIcon.className = "bi bi-person-heart header-icon";
                headerIcon.style.color = "#10b981";

            } else {
                // LOGIN STATE
                brandTitle.innerHTML = "Glad to See You<br>Again, Partner.";
                brandDesc.innerHTML = "Welcome back! Log in to check your loan status, make payments, and stay on track.";
                
                // Keep background color static as requested
                leftPanel.style.background = "#10b981"; 

                pageTitle.innerText = "Welcome Back!";
                pageSubTitle.innerText = "Enter your credentials to access your dashboard.";
                
                signupFields.classList.add('hidden');
                submitBtn.innerText = "Access Dashboard";
                footerText.innerText = "New to MicroFinance?";
                toggleLink.innerText = "Create Account";
                formAction.value = "login";
                
                headerIcon.className = "bi bi-shield-lock-fill header-icon";
                headerIcon.style.color = "#34d399";
            }
        }
    </script>
</body>
</html>