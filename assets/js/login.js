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
