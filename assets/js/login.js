// Initialize Lucide icons
console.log('Login.js loaded');
if (typeof lucide !== "undefined") lucide.createIcons();

// Theme Toggle Logic
const themeToggle = document.getElementById("themeToggle");
const htmlElement = document.documentElement;

if (localStorage.getItem("theme") === "dark") {
    htmlElement.classList.add("dark-mode");
}

const updateThemeToggles = () => {
    const isDark = htmlElement.classList.contains('dark-mode');
    const sunIcon = document.querySelector('.sun-icon');
    const moonIcon = document.querySelector('.moon-icon');
    
    if (sunIcon) sunIcon.style.display = isDark ? 'none' : 'block';
    if (moonIcon) moonIcon.style.display = isDark ? 'block' : 'none';
};

if (themeToggle) {
    themeToggle.addEventListener("click", () => {
        htmlElement.classList.toggle("dark-mode");
        localStorage.setItem("theme", htmlElement.classList.contains("dark-mode") ? "dark" : "light");
        updateThemeToggles();
    });
    updateThemeToggles();
}

// Password Toggle
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const button = input.parentElement.querySelector(".toggle-password");
    const icon = button.querySelector(".eye-icon");

    if (input.type === "password") {
        input.type = "text";
        icon.setAttribute("data-lucide", "eye-off");
    } else {
        input.type = "password";
        icon.setAttribute("data-lucide", "eye");
    }
    window.lucide.createIcons();
}


function checkPasswordStrength(password) {
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');
    const submitBtn = document.getElementById('submitBtn');

    // Requirements logic
    const requirements = {
        length: password.length >= 8,
        upper: /[A-Z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[@$!%*?&]/.test(password)
    };

    // Update UI for each requirement
    document.getElementById('req-length').className = requirements.length ? 'valid' : 'invalid';
    document.getElementById('req-upper').className = requirements.upper ? 'valid' : 'invalid';
    document.getElementById('req-number').className = requirements.number ? 'valid' : 'invalid';
    document.getElementById('req-special').className = requirements.special ? 'valid' : 'invalid';

    // Calculate score
    let score = 0;
    if (requirements.length) score += 25;
    if (requirements.upper) score += 25;
    if (requirements.number) score += 25;
    if (requirements.special) score += 25;

    // Update Bar Color & Width
    bar.style.width = score + "%";
    
    if (score <= 25) {
        bar.style.backgroundColor = "#ef4444"; // Red
        text.innerText = "Strength: Weak";
    } else if (score <= 50) {
        bar.style.backgroundColor = "#f59e0b"; // Orange
        text.innerText = "Strength: Fair";
    } else if (score <= 75) {
        bar.style.backgroundColor = "#fbbf24"; // Yellow
        text.innerText = "Strength: Good";
    } else {
        bar.style.backgroundColor = "#10b981"; // Green
        text.innerText = "Strength: Strong";
    }

    // Disable submit button if not strong enough
    submitBtn.disabled = score < 100;
    submitBtn.style.opacity = score < 100 ? "0.5" : "1";
}