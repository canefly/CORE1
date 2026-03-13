<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="keywords" content="lending, microfinance, loans, Philippines, business loans, personal loans">
    <title>Microfinance</title>
    <script>
    (function() {
      const savedTheme = localStorage.getItem("theme");
      const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
      if (savedTheme === "dark" || (!savedTheme && systemDark)) {
        document.documentElement.classList.add("dark-mode");
      }
    })();
    </script>
    <link rel="icon" type="image/png" href="assets/img/logo.png">
    <link rel="stylesheet" href="assets/css/landing.css">
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="logo">
                 <div class="sidebar-logo-icon">
                    <img src="assets/img/logo.png" alt="Logo" style="width: 24px; height: 24px; object-fit: contain;">
                </div>
            Microfinance
        </div>
        <ul class="nav-menu">
            <li><a onclick="scrollToSection('hero')">Home</a></li>
            <li><a onclick="scrollToSection('services')">Services</a></li>
            <li><a onclick="scrollToSection('features')">Why Us</a></li>
            <li><a onclick="scrollToSection('process')">How It Works</a></li>
            <li><a onclick="scrollToSection('about')">About</a></li>
        </ul>
        <div class="nav-actions">
            <div class="nav-buttons">
                <button class="theme-toggle" onclick="toggleTheme()" title="Toggle dark mode">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="sun-icon">
                        <circle cx="12" cy="12" r="5"></circle>
                        <line x1="12" y1="1" x2="12" y2="3"></line>
                        <line x1="12" y1="21" x2="12" y2="23"></line>
                        <line x1="4.22" y1="4.22" x2="5.64" y2="5.64"></line>
                        <line x1="18.36" y1="18.36" x2="19.78" y2="19.78"></line>
                        <line x1="1" y1="12" x2="3" y2="12"></line>
                        <line x1="21" y1="12" x2="23" y2="12"></line>
                        <line x1="4.22" y1="19.78" x2="5.64" y2="18.36"></line>
                        <line x1="18.36" y1="5.64" x2="19.78" y2="4.22"></line>
                    </svg>
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="moon-icon" style="display:none;">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
                    </svg>
                </button>
                <a class="btn btn-register" href="login/login.php">Register</a>
                <a class="btn btn-login" href="login/login.php">Log In</a>
            </div>
            <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false" aria-controls="mobileMenu">
                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="3" y1="6" x2="21" y2="6"></line>
                    <line x1="3" y1="12" x2="21" y2="12"></line>
                    <line x1="3" y1="18" x2="21" y2="18"></line>
                </svg>
            </button>
        </div>
    </nav>
    <div class="nav-overlay" aria-hidden="true"></div>

    <!-- Hero Section -->
    <section class="hero" id="hero">
        <span class="hero-badge">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            Trusted
        </span>
        <h1>
            Empowering Your Financial <span class="highlight">Dreams</span>
        </h1>
        <p class="hero-subtitle">
            Microfinance provides affordable microfinance solutions tailored for Filipino entrepreneurs, families, and communities. Turn your financial goals into reality with transparent, flexible lending.
        </p>
        <button class="cta-button primary" onclick="handleCTA()">
            <span>Start Your Application</span>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="5" y1="12" x2="19" y2="12"></line>
                <polyline points="12 5 19 12 12 19"></polyline>
            </svg>
        </button>
        <div class="scroll-indicator">
            <span>Scroll to explore</span>
            <a href="#services" class="scroll-down-dude"></a>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats">
        <div class="stat-item">
            <h4>50,000+</h4>
            <p>Happy Borrowers</p>
        </div>
        <div class="stat-item">
            <h4>₱2.5B+</h4>
            <p>Loans Distributed</p>
        </div>
        <div class="stat-item">
            <h4>98%</h4>
            <p>Repayment Rate</p>
        </div>
        <div class="stat-item">
            <h4>24/7</h4>
            <p>Customer Support</p>
        </div>
    </section>

    <!-- Services Section -->
    <section class="services" id="services">
        <h2>Our Loan Services</h2>
        <div class="services-grid">
            <div class="service-box">
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2L2 7v10c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V7l-10-5z"></path>
                    </svg>
                </div>
                <h3>Business Loans</h3>
                <p>Expand your business with loans designed for entrepreneurs and small business owners in the Philippines.</p>
                <button class="service-btn" onclick="handleCTA()">Learn More</button>
            </div>
            <div class="service-box">
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3>Personal Loans</h3>
                <p>Cover education, medical expenses, or any personal needs with flexible personal loan options.</p>
                <button class="service-btn" onclick="handleCTA()">Learn More</button>
            </div>
            <div class="service-box">
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <h3>Agricultural Loans</h3>
                <p>Supporting farmers with funds for seeds, equipment, and seasonal crops to boost productivity.</p>
                <button class="service-btn" onclick="handleCTA()">Learn More</button>
            </div>
            <div class="service-box">
                <div class="service-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                        <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                    </svg>
                </div>
                <h3>Educational Loans</h3>
                <p>Invest in education with student loans for tuition, books, and other educational expenses.</p>
                <button class="service-btn" onclick="handleCTA()">Learn More</button>
            </div>
        </div>
    </section>

    <!-- Why Choose Us Section -->
    <section class="features" id="features">
        <h2>Why Choose Microfinance?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon-container">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <polyline points="22 4 12 14.01 9 11.01 2 17.99 22 4"></polyline>
                        <polyline points="22 4 15 4 15 11"></polyline>
                    </svg>
                </div>
                <h3>Fast Approval</h3>
                <p>Get approved in 24 hours. No lengthy paperwork or complicated processes—just quick financial support.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-container">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="7" width="20" height="14" rx="2" ry="2"></rect>
                        <path d="M16 4V2m-8 2V2M2 11h20"></path>
                    </svg>
                </div>
                <h3>Low Interest Rates</h3>
                <p>Competitive rates that won't break your budget. We believe in fair lending for all Filipinos.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-container">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"></path>
                    </svg>
                </div>
                <h3>Flexible Payments</h3>
                <p>Choose repayment terms that fit your budget. We work with you, not against you.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-container">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <h3>100% Secure</h3>
                <p>Bank-level security protects your data. Your privacy and trust are our top priorities.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon-container">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <h3>Community-Focused</h3>
                <p>Supporting local communities and building financial networks that empower every Filipino.</p>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="process" id="process">
        <h2>Our Simple & Transparent Process</h2>
        <div class="steps-container">
            <div class="step">
                <div class="step-number">1</div>
                <h3>Submit Application</h3>
                <p>Fill out our online form or visit a branch. Takes less than 5 minutes.</p>
            </div>
            <div class="step">
                <div class="step-number">2</div>
                <h3>Loan Assessment</h3>
                <p>We review your documents and verify your information to confirm eligibility.</p>
            </div>
            <div class="step">
                <div class="step-number">3</div>
                <h3>Approval & Agreement</h3>
                <p>Receive your loan decision within the same day. Review and sign the loan terms.</p>
            </div>
            <div class="step">
                <div class="step-number">4</div>
                <h3>Receive Your Funds</h3>
                <p>Your loan is released via cash, bank transfer, or e-wallet. Start using it right away.</p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <h2>Trusted by Professionals Across Industries</h2>
        <p style="text-align:center; color:var(--label-text-light); margin-bottom:2.5rem; font-size:1.15rem;">Our clients include business owners, educators, and community leaders who rely on Microfinance for their financial growth.</p>
        <div class="testimonials-grid">
            <div class="testimonial-card">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                    <img src="assets/img/profile.png" alt="Maria Santos" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--brand-green);background:#fff;">
                    <div>
                        <div class="testimonial-author">Maria Santos</div>
                        <div style="font-size:0.95rem;color:var(--label-text-light);font-weight:500;">Owner, Santos Retail Group</div>
                    </div>
                </div>
                <p>"Microfinance's expertise and professionalism made our business expansion seamless. Their team provided tailored solutions and transparent advice every step of the way."</p>
                <div class="stars">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                </div>
            </div>
            <div class="testimonial-card">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                    <img src="assets/img/profile.png" alt="Juan Dela Cruz" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--brand-green);background:#fff;">
                    <div>
                        <div class="testimonial-author">Juan Dela Cruz</div>
                        <div style="font-size:0.95rem;color:var(--label-text-light);font-weight:500;">Principal, Dela Cruz Academy</div>
                    </div>
                </div>
                <p>"I trust Microfinance for our school's financial needs. Their support and clear communication have helped us fund new programs and scholarships for our students."</p>
                <div class="stars">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                </div>
            </div>
            <div class="testimonial-card">
                <div style="display:flex;align-items:center;gap:1rem;margin-bottom:1rem;">
                    <img src="assets/img/profile.png" alt="Pedro Reyes" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--brand-green);background:#fff;">
                    <div>
                        <div class="testimonial-author">Pedro Reyes</div>
                        <div style="font-size:0.95rem;color:var(--label-text-light);font-weight:500;">President, Reyes Agricultural Cooperative</div>
                    </div>
                </div>
                <p>"With Microfinance, our cooperative accessed flexible financing to modernize our equipment and improve productivity. Their professionalism is unmatched in the industry."</p>
                <div class="stars">
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"></path></svg>
                </div>
            </div>
        </div>
    </section>
    </section>

    <!-- About Us Section -->
    <section class="about" id="about">
        <h2>About Microfinance </h2>
        <div class="about-content">
            <p style="text-align: center; color: var(--label-text-light); line-height: 1.8; margin-bottom: 2rem;">Microfinance is a trusted microfinance institution committed to empowering Filipino communities through affordable and accessible lending solutions. Our mission is to transform lives by providing financial opportunities to entrepreneurs, students, farmers, and families.</p>
            <div class="about-grid">
                <div class="about-item">
                    <h3>Our Mission</h3>
                    <p>Providing affordable microfinance to every Filipino, turning dreams into reality through transparent and ethical lending.</p>
                </div>
                <div class="about-item">
                    <h3>Our Vision</h3>
                    <p>A Philippines where financial opportunities are accessible to all, regardless of background or economic status.</p>
                </div>
                <div class="about-item">
                    <h3>10+ Years</h3>
                    <p>Serving Filipino communities with integrity, transparency, and commitment to financial inclusion.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Loan Eligibility Section -->
    <section class="eligibility">
        <h2>Loan Eligibility Requirements</h2>
        <div class="eligibility-grid">
            <div class="requirement-box">
                <div class="requirement-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="12" y1="19" x2="12" y2="11"></line>
                        <line x1="9" y1="16" x2="15" y2="16"></line>
                    </svg>
                </div>
                <div>
                    <h3>Valid ID</h3>
                    <p>Philippine government-issued ID such as passport, driver's license, or national ID.</p>
                </div>
            </div>
            <div class="requirement-box">
                <div class="requirement-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    </svg>
                </div>
                <div>
                    <h3>Proof of Income</h3>
                    <p>Business registration, payslips, tax returns, or bank statements showing regular income.</p>
                </div>
            </div>
            <div class="requirement-box">
                <div class="requirement-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path>
                    </svg>
                </div>
                <div>
                    <h3>Identification Photos</h3>
                    <p>Recent clear photos of yourself and any supporting business or property documents if applicable.</p>
                </div>
            </div>
            <div class="requirement-box">
                <div class="requirement-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                        <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                    </svg>
                </div>
                <div>
                    <h3>Application Form</h3>
                    <p>Complete our online or physical application form with accurate personal and business information.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Final CTA Section -->
    <section class="cta-section">
        <div class="cta-pattern"></div>
        <div class="cta-content">
            <div class="cta-text">
                <span class="cta-subtitle">Your Financial Freedom Awaits</span>
                <h2>Ready to Unlock Your Next Opportunity?</h2>
                <p>Join over 50,000 Filipinos who have already taken control of their financial future with Microfinance. Our fast, transparent process means you could be approved for up to ₱100,000 in as little as 24 hours.</p>
                
                <div class="benefits-grid">
                    <div class="benefit-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>No Collateral Required</span>
                    </div>
                    <div class="benefit-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>Lowest Interest Rates</span>
                    </div>
                    <div class="benefit-item">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                            <polyline points="22 4 12 14.01 9 11.01"></polyline>
                        </svg>
                        <span>Flexible Payment Terms</span>
                    </div>
                </div>
                
                <div class="cta-buttons">
                    <button class="cta-button primary" onclick="handleCTA()">
                        <span>Apply Now - It's Free</span>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </button>
                    <a href="tel:+639123456789" class="cta-button secondary">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
                        </svg>
                        <span>Call Us Now</span>
                    </a>
                </div>
                
                <div class="trust-badges">
                    <div class="trust-badge">
                        <div class="trust-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                            </svg>
                        </div>
                        <span>100% Secure & Confidential</span>
                    </div>
                    <div class="trust-badge">
                        <div class="trust-icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"></circle>
                                <path d="M16 8h-6a1 1 0 0 0-1 1v6a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V9a1 1 0 0 0-1-1z"></path>
                            </svg>
                        </div>
                        <span>24/7 Customer Support</span>
                    </div>
                </div>
            </div>
            <div class="cta-image">
                <img src="assets/img/atm.png" alt="Happy customer" loading="lazy">
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="footer-content">
            <div class="footer-section">
                <h4>About Us</h4>
                <p>Empowering Filipino communities through accessible financial solutions and fostering economic growth.</p>
                <div class="social-links">
                    <a href="#" aria-label="Facebook">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path>
                        </svg>
                    </a>
                    <a href="#" aria-label="Twitter">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"></path>
                        </svg>
                    </a>
                    <a href="#" aria-label="Instagram">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="2" width="20" height="20" rx="5" ry="5"></rect>
                            <path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"></path>
                            <line x1="17.5" y1="6.5" x2="17.51" y2="6.5"></line>
                        </svg>
                    </a>
                    <a href="#" aria-label="LinkedIn">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"></path>
                            <rect x="2" y="9" width="4" height="12"></rect>
                            <circle cx="4" cy="4" r="2"></circle>
                        </svg>
                    </a>
                </div>
            </div>
            
            <div class="footer-section">
                <h4>Quick Links</h4>
                <a href="#">Home</a>
                <a href="#about">About Us</a>
                <a href="#services">Services</a>
                <a href="#features">Features</a>
                <a href="#testimonials">Testimonials</a>
            </div>
            
            <div class="footer-section">
                <h4>Our Services</h4>
                <a href="#">Business Loans</a>
                <a href="#">Personal Loans</a>
                <a href="#">Agricultural Loans</a>
                <a href="#">Educational Loans</a>
                <a href="#">Emergency Loans</a>
            </div>
            
            <div class="footer-section">
                <h4>Contact Info</h4>
                <p><i class="fas fa-map-marker-alt"></i> 123 Financial Street, Metro Manila, Philippines 1000</p>
                <p><i class="fas fa-phone"></i> +63 0 0123 4567</p>
                <p><i class="fas fa-mobile-alt"></i> +63 901 123 4567</p>
                <p><i class="fas fa-envelope"></i> Lemon.corporation@gmail.com</p>
                <p><i class="fas fa-clock"></i> Mon - Fri: 9:00 AM - 6:00 PM</p>
            </div>
        </div>
        
        <div class="footer-bottom">
            <p>&copy; 2025 Microfinance All rights reserved. | 
              <a href="#">Privacy Policy</a> | 
              <a href="#">Terms & Conditions</a> | 
              <a href="#">Sitemap</a>
            </p>
        </div>
    </footer>
    
    <!-- Back to Top Button -->
    <div class="back-to-top" id="backToTop" aria-label="Back to top">
        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M18 15l-6-6-6 6"/>
        </svg>
    </div>

    <script src="assets/js/landing.js"></script>
</body>
</html>
