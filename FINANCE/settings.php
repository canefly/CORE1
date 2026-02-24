<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Finance | Admin Configuration</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <link rel="stylesheet" href="assets/css/settings.css">
</head>
<body>

    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        
        <div class="page-header">
            <h1>System Configuration</h1>
            <p>Manage interest rates, penalties, and loan parameters.</p>
        </div>

        <div class="settings-grid">
            
            <div class="config-card">
                <div class="card-head">
                    <div class="icon-box icon-gold"><i class="bi bi-percent"></i></div>
                    <div class="card-title">
                        <h3>Interest Rate</h3>
                        <span>Base calculation for new loans</span>
                    </div>
                </div>
                
                <form>
                    <div class="input-group">
                        <label class="input-label">Default Monthly Interest</label>
                        <div class="input-wrapper">
                            <input type="number" class="input-field" value="3.5" step="0.1">
                            <span class="suffix">%</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Calculation Method</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="calc_type" checked>
                                <span class="radio-box">Diminishing</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="calc_type">
                                <span class="radio-box">Flat Rate</span>
                            </label>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="button" class="btn-save">Update Rate</button>
                    </div>
                </form>
            </div>

            <div class="config-card">
                <div class="card-head">
                    <div class="icon-box icon-red"><i class="bi bi-exclamation-triangle"></i></div>
                    <div class="card-title">
                        <h3>Fees & Penalties</h3>
                        <span>Charges for delays and processing</span>
                    </div>
                </div>
                
                <form>
                    <div class="input-group">
                        <label class="input-label">Late Payment Penalty</label>
                        <div class="input-wrapper">
                            <input type="number" class="input-field" value="5.0" step="0.1">
                            <span class="suffix">% of Due</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Processing Fee (Fixed)</label>
                        <div class="input-wrapper">
                            <input type="number" class="input-field" value="500">
                            <span class="suffix">PHP</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Grace Period (Days)</label>
                        <div class="input-wrapper">
                            <input type="number" class="input-field" value="3">
                            <span class="suffix">Days</span>
                        </div>
                    </div>

                    <div class="card-footer">
                        <button type="button" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>

            <div class="config-card">
                <div class="card-head">
                    <div class="icon-box icon-blue"><i class="bi bi-calendar-range"></i></div>
                    <div class="card-title">
                        <h3>Loan Term Options</h3>
                        <span>Available durations for applicants</span>
                    </div>
                </div>
                
                <form>
                    <div class="toggle-row">
                        <span class="toggle-label">Short Term (3 Months)</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-row">
                        <span class="toggle-label">Standard (6 Months)</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-row">
                        <span class="toggle-label">Medium Term (12 Months)</span>
                        <label class="switch">
                            <input type="checkbox" checked>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="toggle-row">
                        <span class="toggle-label">Long Term (24 Months)</span>
                        <label class="switch">
                            <input type="checkbox"> <span class="slider"></span>
                        </label>
                    </div>

                    <div class="card-footer">
                        <button type="button" class="btn-save">Apply Terms</button>
                    </div>
                </form>
            </div>

        </div>

    </div>

</body>
</html>