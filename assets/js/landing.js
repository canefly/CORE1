
document.addEventListener('DOMContentLoaded', () => {
    // Dark Mode Toggle

        // 1. Initialize Icons
    if (typeof lucide !== "undefined") lucide.createIcons();

    // 2. Theme Toggle Logic
    const themeToggle = document.getElementById("themeToggle");
    const html = document.documentElement;

    // Sync button on load
    if (localStorage.getItem("theme") === "dark") {
        html.classList.add("dark-mode");
    }

    if (themeToggle) {
        themeToggle.addEventListener("click", () => {
            html.classList.toggle("dark-mode");
            localStorage.setItem("theme", html.classList.contains("dark-mode") ? "dark" : "light");
        });
    }
    const htmlElement = document.documentElement;
    
    // Function to update all theme toggles in the document
    const updateThemeToggles = () => {
        const isDark = htmlElement.classList.contains('dark-mode');
        const themeToggles = document.querySelectorAll('.theme-toggle');
        
        themeToggles.forEach(toggle => {
            const sunIcon = toggle.querySelector('.sun-icon');
            const moonIcon = toggle.querySelector('.moon-icon');
            
            if (sunIcon) sunIcon.style.display = isDark ? 'none' : 'block';
            if (moonIcon) moonIcon.style.display = isDark ? 'block' : 'none';
        });
    };

    // Toggle theme function
    function toggleTheme() {
        const isDark = htmlElement.classList.contains('dark-mode');
        
        if (isDark) {
            htmlElement.classList.remove('dark-mode');
            localStorage.setItem('theme', 'light');
        } else {
            htmlElement.classList.add('dark-mode');
            localStorage.setItem('theme', 'dark');
        }
        
        updateThemeToggles();
    }

    // Load theme preference on page load
    const savedTheme = localStorage.getItem('theme') || 'light';
    if (savedTheme === 'dark') {
        htmlElement.classList.add('dark-mode');
    } else {
        htmlElement.classList.remove('dark-mode');
    }
    
    // Initialize all theme toggles
    updateThemeToggles();
    
    // Expose for inline handlers
    window.toggleTheme = toggleTheme;
    
    // Global click handler for theme toggles (including dynamically added ones)
    document.addEventListener('click', (e) => {
        if (e.target.closest('.theme-toggle')) {
            e.preventDefault();
            e.stopPropagation();
            toggleTheme();
            return false;
        }
    });

    // CTA/navigation helpers
    window.handleCTA = function() {
        window.location.href = 'index.html';
    };

    // --- Mobile Menu Toggle ---
        (function setupMobileNav() {
            const navToggle = document.querySelector('.nav-toggle');
            const nav = document.querySelector('nav');
            const overlay = document.querySelector('.nav-overlay');
            if (!nav || !navToggle) return;

            function buildPanel() {
                let panel = nav.querySelector('.nav-panel');
                if (panel) return panel;

                panel = document.createElement('div');
                panel.className = 'nav-panel';
                panel.id = 'mobileMenu';
                panel.setAttribute('role', 'dialog');
                panel.setAttribute('aria-modal', 'true');

                const closeBtn = document.createElement('button');
                closeBtn.className = 'close-panel';
                closeBtn.type = 'button';
                closeBtn.setAttribute('aria-label', 'Close menu');
                closeBtn.innerHTML = '<svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>';
                panel.appendChild(closeBtn);

                const menu = nav.querySelector('.nav-menu');
                if (menu) {
                    const menuClone = menu.cloneNode(true);
                    menuClone.style.display = '';
                    panel.appendChild(menuClone);
                }

                const buttons = nav.querySelector('.nav-buttons');
                if (buttons) {
                    const buttonsClone = buttons.cloneNode(true);
                    buttonsClone.style.display = '';
                    const toggleInside = buttonsClone.querySelector('.nav-toggle');
                    if (toggleInside) toggleInside.remove();
                    panel.appendChild(buttonsClone);
                }

                nav.appendChild(panel);

                closeBtn.addEventListener('click', closeMobile);

                return panel;
            }

            function openMobile() {
                const panel = buildPanel();
                nav.classList.add('mobile-open');
                requestAnimationFrame(() => panel.classList.add('open'));
                navToggle.setAttribute('aria-expanded', 'true');
                document.body.style.overflow = 'hidden';
                if (overlay) overlay.classList.add('visible');
                document.addEventListener('keydown', keyHandler);
            }

            function closeMobile() {
                const panel = nav.querySelector('.nav-panel');
                if (panel) panel.classList.remove('open');
                nav.classList.remove('mobile-open');
                navToggle.setAttribute('aria-expanded', 'false');
                document.body.style.overflow = '';
                if (overlay) overlay.classList.remove('visible');
                document.removeEventListener('keydown', keyHandler);
            }

            function keyHandler(e) {
                if (e.key === 'Escape') closeMobile();
                if (e.key === 'Tab') {
                    const panel = nav.querySelector('.nav-panel');
                    if (!panel) return;
                    const focusable = panel.querySelectorAll('a,button,input,select,textarea');
                    if (!focusable.length) return;
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (e.shiftKey && document.activeElement === first) {
                        e.preventDefault();
                        last.focus();
                    } else if (!e.shiftKey && document.activeElement === last) {
                        e.preventDefault();
                        first.focus();
                    }
                }
            }

            navToggle.addEventListener('click', () => {
                if (nav.classList.contains('mobile-open')) closeMobile(); else openMobile();
            });

            // Close mobile menu when clicking outside panel
            document.addEventListener('click', (e) => {
                if (!nav.classList.contains('mobile-open')) return;
                const panel = nav.querySelector('.nav-panel');
                if (!panel) return;
                if (!nav.contains(e.target) && !panel.contains(e.target) && e.target !== navToggle) {
                    closeMobile();
                }
            });

            if (overlay) {
                overlay.addEventListener('click', closeMobile);
            }

            // Close mobile after clicking a link inside the nav (delegation)
            nav.addEventListener('click', (e) => {
                const target = e.target.closest('a');
                if (!target) return;
                if (nav.classList.contains('mobile-open')) {
                    // small delay to allow link navigation/onclick to run
                    setTimeout(() => closeMobile(), 120);
                }
            });

            // Delegate theme toggle clicks (works for cloned buttons)
            nav.addEventListener('click', (e) => {
                if (e.target.closest('.theme-toggle')) {
                    toggleTheme();
                }
            });

            // Close mobile menu when resizing to desktop
            window.addEventListener('resize', () => {
                if (window.innerWidth >= 993) {
                    closeMobile();
                    const panel = nav.querySelector('.nav-panel');
                    if (panel) {
                        panel.classList.remove('open');
                        panel.style.display = 'none';
                    }
                    if (overlay) overlay.classList.remove('visible');
                    nav.classList.remove('mobile-open');
                    navToggle.setAttribute('aria-expanded', 'false');
                    document.body.style.overflow = '';
                }
            });
        })();

    // --- Smooth Scrolling for Anchor Links ---
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            const href = this.getAttribute('href');
            if (href !== '#' && href !== '#home') {
                e.preventDefault();
                const target = document.querySelector(href);
                if (target) {
                    const offsetTop = target.offsetTop - 70; 
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            }
        });
    });

    window.scrollToSection = function(sectionId) {
        const el = document.getElementById(sectionId);
        if (!el) return;
        const rect = el.getBoundingClientRect();
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        const targetY = rect.top + scrollTop - 70;
        window.scrollTo({ top: targetY, behavior: 'smooth' });
    };

    // --- Navbar Scroll Effect ---
    let lastScroll = 0;
    const navbar = document.querySelector('nav');

    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;

        if (currentScroll > 100) {
            navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.15)';
        } else {
            navbar.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.1)';
        }

        lastScroll = currentScroll;
    });

    // --- Intersection Observer for Animations ---
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe service cards, step items, and testimonial cards
    document.querySelectorAll('.service-card, .step-item, .testimonial-card, .feature-item').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
        observer.observe(el);
    });

    // --- Back to Top Button ---
    const backToTopButton = document.getElementById('backToTop');
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            backToTopButton.classList.add('visible');
        } else {
            backToTopButton.classList.remove('visible');
        }
    });
    backToTopButton.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    const animateCounter = (element, target, duration = 2000) => {
        let start = 0;
        const increment = target / (duration / 16);
        const timer = setInterval(() => {
            start += increment;
            if (start >= target) {
                element.textContent = formatNumber(target);
                clearInterval(timer);
            } else {
                element.textContent = formatNumber(Math.floor(start));
            }
        }, 16);
    };

    const formatNumber = (num) => {
        if (num >= 1000000) {
            return (num / 1000000).toFixed(1) + 'M+';
        } else if (num >= 1000) {
            return (num / 1000).toFixed(0) + 'K+';
        }
        return num + '%';
    };

    // Observe stats and animate when visible
    const statsObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting && !entry.target.classList.contains('animated')) {
                entry.target.classList.add('animated');
                const statNumber = entry.target.querySelector('.stat-number');
                if (statNumber) {
                    const text = statNumber.textContent;
                    let target = 0;
                    
                    if (text.includes('K+')) {
                        target = parseFloat(text) * 1000;
                    } else if (text.includes('M+')) {
                        target = parseFloat(text) * 1000000;
                    } else if (text.includes('%')) {
                        target = parseFloat(text);
                    }
                    
                    if (target > 0) {
                        statNumber.textContent = '0';
                        animateCounter(statNumber, target);
                    }
                }
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.stat-item').forEach(stat => {
        statsObserver.observe(stat);
    });
});
