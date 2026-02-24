<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | MicroFinance</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/profile.css">
</head>
<body>

    <?php include 'include/sidebar.php'; ?>
    <?php include 'include/theme_toggle.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>Account Settings</h1>
            <p>Manage your personal information and security preferences.</p>
        </div>

        <div class="profile-card">
            <div class="profile-avatar">
                <div class="avatar-img">JD</div>
                <div class="edit-avatar" title="Change Profile Picture">
                    <i class="bi bi-camera-fill"></i>
                </div>
            </div>
            <div class="profile-info">
                <h2>Juan Dela Cruz</h2>
                <div class="profile-meta">
                    <div class="meta-item">
                        <i class="bi bi-envelope"></i> juan.delacruz@gmail.com
                    </div>
                    <div class="meta-item">
                        <i class="bi bi-geo-alt"></i> Caloocan City, Metro Manila
                    </div>
                    <div class="meta-item" style="color:#10b981; font-weight:600;">
                        <i class="bi bi-patch-check-fill"></i> Verified Borrower
                    </div>
                </div>
            </div>
        </div>

        <div class="settings-container">
            
            <div class="left-col">
                
                <div class="card-box">
                    <div class="card-title">Personal Information</div>
                    <form>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-input" value="Juan">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-input" value="Dela Cruz">
                            </div>
                        </div>

                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">Phone Number</label>
                                <input type="text" class="form-input" value="0917 123 4567">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Birthdate</label>
                                <input type="date" class="form-input" value="1990-05-15">
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Complete Address</label>
                            <input type="text" class="form-input" value="Block 5 Lot 2, Camella Homes, Caloocan City">
                        </div>

                        <div style="text-align:right;">
                            <button type="button" class="btn-save" onclick="alert('Changes Saved!')">Save Changes</button>
                        </div>
                    </form>
                </div>

                <div class="card-box">
                    <div class="card-title">Security Settings</div>
                    <form>
                        <div class="form-group">
                            <label class="form-label">Current Password</label>
                            <input type="password" class="form-input" placeholder="••••••••">
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-input">
                            </div>
                            <div class="form-group">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-input">
                            </div>
                        </div>

                        <div style="text-align:right;">
                            <button type="button" class="btn-save" style="background:#fbbf24; color:#0f172a;">Update Password</button>
                        </div>
                    </form>
                </div>

            </div>

            <div class="right-col">
                
                <div class="card-box">
                    <div class="card-title">Uploaded Documents (KYC)</div>
                    
                    <div class="doc-item">
                        <div class="doc-info">
                            <i class="bi bi-person-badge doc-icon"></i>
                            <div>
                                <div class="doc-name">UMID ID Card</div>
                                <div class="doc-status">Verified</div>
                            </div>
                        </div>
                        <i class="bi bi-eye btn-view"></i>
                    </div>

                    <div class="doc-item">
                        <div class="doc-info">
                            <i class="bi bi-file-earmark-text doc-icon"></i>
                            <div>
                                <div class="doc-name">Proof of Billing</div>
                                <div class="doc-status">Verified</div>
                            </div>
                        </div>
                        <i class="bi bi-eye btn-view"></i>
                    </div>

                    <div class="doc-item">
                        <div class="doc-info">
                            <i class="bi bi-camera doc-icon"></i>
                            <div>
                                <div class="doc-name">Selfie with ID</div>
                                <div class="doc-status">Verified</div>
                            </div>
                        </div>
                        <i class="bi bi-eye btn-view"></i>
                    </div>

                    <button style="width:100%; margin-top:15px; background:transparent; border:1px dashed #374151; color:#9ca3af; padding:10px; border-radius:6px; cursor:pointer;">
                        <i class="bi bi-upload"></i> Upload New Document
                    </button>
                </div>

                <div class="card-box">
                    <div class="card-title">Account Status</div>
                    
                    <div style="text-align:center; padding:10px;">
                        <div style="font-size:40px; color:#10b981;">
                            <i class="bi bi-shield-check"></i>
                        </div>
                        <h3 style="color:#fff; margin-top:10px;">Good Standing</h3>
                        <p style="color:#9ca3af; font-size:13px; margin-top:5px;">
                            You have no active violations or overdue penalties.
                        </p>
                    </div>

                    <div class="security-alert">
                        <div class="alert-text">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>Last login: Today, 10:45 AM from Chrome on Windows.</span>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

</body>
</html>