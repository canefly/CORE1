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

let isSubmitting = false;

// THE MAGIC UNIVERSAL FETCH LOGIC
document.addEventListener("DOMContentLoaded", () => {
    const authForm = document.querySelector('.form');
    
    if (authForm) {
        authForm.addEventListener('submit', async (e) => {
            e.preventDefault(); // Stops the annoying page redirect!

            if (isSubmitting) return;
            isSubmitting = true;

            // Automatically grab the target PHP file (login-client-action, register-action, etc.)
            const actionUrl = authForm.getAttribute('action');
            const formData = new FormData(authForm);

            Swal.fire({
                title: "Processing...",
                text: "Please wait",
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const response = await fetch(actionUrl, {
                    method: "POST",
                    body: formData
                });

                // Catch hidden PHP errors so they don't break the JSON parser
                const text = await response.text();
                let result;
                try {
                    result = JSON.parse(text);
                } catch (err) {
                    console.error("PHP Error Output:", text);
                    throw new Error("Server spit out HTML instead of JSON. Check the console.");
                }

                if (result.success) {
                    await Swal.fire({
                        icon: "success",
                        title: "Success!",
                        text: result.message || "Routing...",
                        timer: 1000,
                        showConfirmButton: false
                    });
                    if (result.redirect) {
                        window.location.href = result.redirect;
                    }
                } else {
                    isSubmitting = false;
                    await Swal.fire({
                        icon: "error",
                        title: "Wait a minute...",
                        text: result.message || "Something went wrong.",
                        confirmButtonColor: "#2ca078"
                    });
                }
            } catch (error) {
                isSubmitting = false;
                console.error("System crash:", error);
                await Swal.fire({
                    icon: "error",
                    title: "System Error",
                    text: "Check your DevTools console. The PHP script crashed.",
                    confirmButtonColor: "#2ca078"
                });
            }
        });
    }
});