<button id="themeToggleBtn" class="theme-toggle-btn" title="Toggle Light/Dark Mode">
    <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
</button>

<style>
    /* TOGGLE BUTTON STYLES */
    .theme-toggle-btn {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        background: #1e293b; 
        color: #fbbf24;      
        border: 2px solid #334155;
        border-radius: 50%;
        width: 48px;
        height: 48px;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        transition: all 0.3s ease;
    }
    
    .theme-toggle-btn:hover {
        transform: scale(1.1);
    }

    /* ==========================================================
       SMOOTH THEME FADE TRANSITIONS
       ========================================================== */
    body, .main-content, 
    .status-card, .table-card, .form-card, .calc-container, 
    .active-loan-card, .history-item, .notif-item, .profile-card, 
    .card-box, .notice-card, .request-card, .preview-card, 
    .history-card, .modal-content, .modal-box, .ref-code,
    h1, h2, h3, h4, p, span, div, td, th, a, i, input, select, textarea {
        transition-property: background-color, color, border-color, box-shadow;
        transition-duration: 0.5s;
        transition-timing-function: ease-in-out;
    }

    /* Preserve the sidebar's slide transform while allowing it to fade colors */
    .sidebar {
        transition-property: transform, background-color, border-color, box-shadow;
        transition-duration: 0.3s, 0.5s, 0.5s, 0.5s;
        transition-timing-function: ease-in-out, ease-in-out, ease-in-out, ease-in-out;
    }
    /* ========================================================== */

    /* LIGHT MODE BASE OVERRIDES */
    body.light-mode {
        background-color: #f1f5f9 !important; 
        color: #000000 !important; 
    }
    
    body.light-mode .main-content {
        background-color: transparent !important;
    }

    /* Target specific text tags to turn pure black for maximum visibility */
    body.light-mode h1, 
    body.light-mode h2, 
    body.light-mode h3, 
    body.light-mode h4,
    body.light-mode p, 
    body.light-mode .page-header p,
    body.light-mode .range-label, 
    body.light-mode .monthly-label, 
    body.light-mode .input-label, 
    body.light-mode .form-label, 
    body.light-mode .card-title, 
    body.light-mode .section-title,
    body.light-mode th, 
    body.light-mode td {
        color: #000000 !important;
    }

    /* Make standard cards white */
    body.light-mode .status-card,
    body.light-mode .table-card,
    body.light-mode .form-card,
    body.light-mode .calc-container,
    body.light-mode .active-loan-card,
    body.light-mode .history-item,
    body.light-mode .notif-item,
    body.light-mode .profile-card,
    body.light-mode .card-box,
    body.light-mode .notice-card,
    body.light-mode .request-card,
    body.light-mode .preview-card,
    body.light-mode .history-card,
    body.light-mode .modal-content,
    body.light-mode .modal-box {
        background-color: #ffffff !important; 
        color: #000000 !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e2e8f0 !important;
    }

    /* Target loose div text inside white cards to ensure they are black */
    body.light-mode .status-card div,
    body.light-mode .table-card div,
    body.light-mode .history-item div,
    body.light-mode .active-loan-card .stat-item .val {
        color: #000000;
    }

    /* ==========================================================
       EXCEPTIONS: Protect elements that keep dark/colored backgrounds
       ========================================================== */

    /* 1. Dashboard Hero Card (Green Box) */
    body.light-mode .hero-card h2,
    body.light-mode .hero-card div,
    body.light-mode .hero-card span,
    body.light-mode .hero-card strong,
    body.light-mode .hero-card .amount {
        color: #ffffff !important;
    }

    /* 2. Calculator Result Box (Dark Blue Box) */
    body.light-mode .calc-result span,
    body.light-mode .calc-result div,
    body.light-mode .calc-result .monthly-val {
        color: #ffffff !important;
    }
    body.light-mode .calc-result #bd-interest {
        color: #fbbf24 !important; 
    }

    /* 3. My Loans Active Contract Header (Dark Blue) */
    body.light-mode .active-loan-card .card-header,
    body.light-mode .active-loan-card .card-header div,
    body.light-mode .active-loan-card .card-header span {
        color: #ffffff !important;
    }

    /* 4. Sidebar Profile Badge (Keep text white in the dark box) */
    body.light-mode .sidebar .user-profile,
    body.light-mode .sidebar .user-profile * {
        color: #ffffff !important;
    }

    /* 5. Sidebar Brand Text (Make "MicroFinance" dark) */
    body.light-mode .sidebar .brand h2 {
        color: #000000 !important;
    }

    /* ========================================================== */

    /* Sidebar Links - Make them black */
    body.light-mode .sidebar {
        background-color: #ffffff !important;
        border-right: 1px solid #e2e8f0 !important;
    }
    body.light-mode .sidebar a {
        color: #000000 !important;
        font-weight: 500;
    }
    body.light-mode .sidebar a:hover, 
    body.light-mode .sidebar a.active {
        background-color: #f1f5f9 !important;
        color: #10b981 !important;
    }

    /* Forms, Inputs, & Tables */
    body.light-mode input, 
    body.light-mode select, 
    body.light-mode textarea,
    body.light-mode .input-wrapper-manual {
        background-color: #f8fafc !important;
        color: #000000 !important;
        border: 1px solid #cbd5e1 !important;
    }
    body.light-mode table { border-collapse: collapse; }
    body.light-mode tr { border-bottom: 1px solid #e2e8f0 !important; }
    body.light-mode th { background-color: #f8fafc !important; border-bottom: 2px solid #cbd5e1 !important; color: #000000 !important; }

    /* Fix ALL hidden gray texts! */
    body.light-mode .notif-time, 
    body.light-mode .meta-item,
    body.light-mode .doc-name,
    body.light-mode .doc-status,
    body.light-mode .compare-label,
    body.light-mode .val-old,
    body.light-mode .upload-text,
    body.light-mode [style*="color:#9ca3af"], 
    body.light-mode [style*="color:#94a3b8"],
    body.light-mode [style*="color:#6b7280"],
    body.light-mode [style*="color:#64748b"] { 
        color: #000000 !important; 
        font-weight: 500 !important; 
    }

    /* Transaction Page Reference Codes Fix */
    body.light-mode .ref-code {
        color: #000000 !important;
        background-color: #e2e8f0 !important; 
        border: 1px solid #cbd5e1 !important;
        font-weight: 600 !important;
    }

    /* Specific text color helpers */
    body.light-mode .text-green, body.light-mode .status-verified, body.light-mode [style*="color:#10b981"] { color: #059669 !important; }
    body.light-mode .text-blue, body.light-mode [style*="color:#60a5fa"] { color: #2563eb !important; }
    body.light-mode .text-gold, body.light-mode [style*="color:#fbbf24"] { color: #d97706 !important; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const themeToggleBtn = document.getElementById('themeToggleBtn');
        const themeIcon = document.getElementById('themeIcon');
        const body = document.body;

        const currentTheme = localStorage.getItem('theme');

        if (currentTheme === 'light') {
            body.classList.add('light-mode');
            setLightIcon();
        }

        themeToggleBtn.addEventListener('click', () => {
            body.classList.toggle('light-mode');
            
            if (body.classList.contains('light-mode')) {
                setLightIcon();
                localStorage.setItem('theme', 'light');
            } else {
                setDarkIcon();
                localStorage.setItem('theme', 'dark');
            }
        });

        function setLightIcon() {
            themeIcon.classList.replace('bi-moon-stars-fill', 'bi-sun-fill');
            themeToggleBtn.style.background = '#ffffff';
            themeToggleBtn.style.color = '#f59e0b'; 
            themeToggleBtn.style.borderColor = '#e2e8f0';
        }

        function setDarkIcon() {
            themeIcon.classList.replace('bi-sun-fill', 'bi-moon-stars-fill');
            themeToggleBtn.style.background = '#1e293b';
            themeToggleBtn.style.color = '#fbbf24'; 
            themeToggleBtn.style.borderColor = '#334155';
        }
    });
</script>