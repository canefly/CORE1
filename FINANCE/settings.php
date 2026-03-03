<?php
// 1. Establish Database Connection
$connection_file = __DIR__ . '/includes/db_connect.php';
if (file_exists($connection_file)) {
    require_once $connection_file;
} else {
    die("Error: Connection file not found.");
}

if (!isset($pdo)) {
    die("Fatal Error: \$pdo variable is not defined.");
}

// 2. Handle Form Submission to Update Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    try {
        $pdo->beginTransaction();
        $stmtUpdate = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_key = ?");
        
        // Map form inputs to database keys
        $keys = ['default_interest_rate', 'interest_method', 'penalty_rate', 'processing_fee', 'grace_period'];
        
        foreach ($keys as $key) {
            if (isset($_POST[$key])) {
                $stmtUpdate->execute([$_POST[$key], $key]);
            }
        }
        
        $pdo->commit();
        $success_message = "System settings updated successfully!";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error_message = "Database Error: " . $e->getMessage();
    }
}

// 3. Fetch Current Settings
try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    $settings_raw = $stmt->fetchAll();
    
    // Convert to a simple associative array for easy access
    $config = [];
    foreach($settings_raw as $row) {
        $config[$row['setting_key']] = $row['setting_value'];
    }
    
    // Fallbacks just in case the table is empty
    $int_rate = $config['default_interest_rate'] ?? '3.5';
    $int_method = $config['interest_method'] ?? 'FLAT';
    $penalty = $config['penalty_rate'] ?? '5.0';
    $proc_fee = $config['processing_fee'] ?? '500';
    $grace = $config['grace_period'] ?? '3';

} catch (PDOException $e) {
    die("Query Failed: Please run the SQL command to create the system_settings table first. Error: " . $e->getMessage());
}
?>

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

        <?php if(isset($success_message)): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #34d399; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="bi bi-check-circle-fill"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if(isset($error_message)): ?>
            <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #f87171; padding: 15px; border-radius: 8px; margin-bottom: 20px;">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="settings.php">
            <input type="hidden" name="action" value="update_settings">

            <div class="settings-grid">
                
                <div class="config-card">
                    <div class="card-head">
                        <div class="icon-box icon-gold"><i class="bi bi-percent"></i></div>
                        <div class="card-title">
                            <h3>Interest Rate</h3>
                            <span>Base calculation for new loans</span>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label class="input-label">Default Monthly Interest</label>
                        <div class="input-wrapper">
                            <input type="number" name="default_interest_rate" class="input-field" value="<?php echo htmlspecialchars($int_rate); ?>" step="0.1" required>
                            <span class="suffix">%</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Calculation Method</label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="interest_method" value="DIMINISHING" <?php if($int_method == 'DIMINISHING') echo 'checked'; ?>>
                                <span class="radio-box">Diminishing</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="interest_method" value="FLAT" <?php if($int_method == 'FLAT') echo 'checked'; ?>>
                                <span class="radio-box">Flat Rate</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="config-card">
                    <div class="card-head">
                        <div class="icon-box icon-red"><i class="bi bi-exclamation-triangle"></i></div>
                        <div class="card-title">
                            <h3>Fees & Penalties</h3>
                            <span>Charges for delays and processing</span>
                        </div>
                    </div>
                    
                    <div class="input-group">
                        <label class="input-label">Late Payment Penalty</label>
                        <div class="input-wrapper">
                            <input type="number" name="penalty_rate" class="input-field" value="<?php echo htmlspecialchars($penalty); ?>" step="0.1" required>
                            <span class="suffix">% of Due</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Processing Fee (Fixed)</label>
                        <div class="input-wrapper">
                            <input type="number" name="processing_fee" class="input-field" value="<?php echo htmlspecialchars($proc_fee); ?>" step="1" required>
                            <span class="suffix">PHP</span>
                        </div>
                    </div>

                    <div class="input-group">
                        <label class="input-label">Grace Period (Days)</label>
                        <div class="input-wrapper">
                            <input type="number" name="grace_period" class="input-field" value="<?php echo htmlspecialchars($grace); ?>" step="1" required>
                            <span class="suffix">Days</span>
                        </div>
                    </div>
                </div>

                <div class="config-card">
                    <div class="card-head">
                        <div class="icon-box icon-blue"><i class="bi bi-calendar-range"></i></div>
                        <div class="card-title">
                            <h3>Save Configurations</h3>
                            <span>Apply changes to the system core.</span>
                        </div>
                    </div>
                    
                    <p style="color: #94a3b8; font-size: 13px; margin-bottom: 20px; line-height: 1.5;">
                        Note: Updating these rates will only affect <strong>new</strong> loan applications. Existing active loans will continue to use the rates they were approved with to ensure contract compliance.
                    </p>

                    <div class="card-footer" style="margin-top: auto; padding-top: 20px; border-top: 1px solid #334155;">
                        <button type="submit" class="btn-save" style="width: 100%; background: #10b981; color: white; border: none; padding: 12px; border-radius: 6px; font-weight: bold; cursor: pointer; transition: 0.2s;">
                            <i class="bi bi-floppy"></i> Save Changes
                        </button>
                    </div>
                </div>

            </div>
        </form>

    </div>

</body>
</html>