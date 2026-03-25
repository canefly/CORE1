<?php
session_start();

// Portable Database Connection (for debug script)
$host = "localhost";
$user = "root";
$pass = "";
$db   = "microfinance_db";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}

$message = '';
$messageType = '';

if (isset($_SESSION['debug_msg'])) {
    $message = $_SESSION['debug_msg'];
    $messageType = $_SESSION['debug_msg_type'] ?? 'info';
    unset($_SESSION['debug_msg'], $_SESSION['debug_msg_type']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_user') {
    $user_id = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
    if ($user_id) {
        try {
            $pdo->beginTransaction();

            // --- CHILD TABLES FIRST (Bottom-Up Deletion) ---
            
            // 1. wallet_sync_logs (linked via wallet_transactions)
            $stmt = $pdo->prepare("DELETE FROM wallet_sync_logs WHERE wallet_transaction_id IN (SELECT id FROM wallet_transactions WHERE user_id = :uid)");
            $stmt->execute([':uid' => $user_id]);

            // 2. wallet_transactions
            $stmt = $pdo->prepare("DELETE FROM wallet_transactions WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 3. wallet_accounts
            $stmt = $pdo->prepare("DELETE FROM wallet_accounts WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 4. transactions_backup
            $stmt = $pdo->prepare("DELETE FROM transactions_backup WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 5. transactions
            $stmt = $pdo->prepare("DELETE FROM transactions WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 6. restructured_loans
            $stmt = $pdo->prepare("DELETE FROM restructured_loans WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 7. loan_restructure_requests
            $stmt = $pdo->prepare("DELETE FROM loan_restructure_requests WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 8. loans (depends on loan_applications & loan_disbursement)
            $stmt = $pdo->prepare("DELETE FROM loans WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 9. loan_disbursement (depends on loan_applications)
            $stmt = $pdo->prepare("DELETE FROM loan_disbursement WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 10. loan_documents (linked via loan_applications)
            $stmt = $pdo->prepare("DELETE FROM loan_documents WHERE loan_application_id IN (SELECT id FROM loan_applications WHERE user_id = :uid)");
            $stmt->execute([':uid' => $user_id]);

            // 11. loan_applications
            $stmt = $pdo->prepare("DELETE FROM loan_applications WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // --- STANDALONE / OTHER TABLES ---
            
            // 12. notifications
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 13. chat_support_messages
            $stmt = $pdo->prepare("DELETE FROM chat_support_messages WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 14. user_devices
            $stmt = $pdo->prepare("DELETE FROM user_devices WHERE user_id = :uid");
            $stmt->execute([':uid' => $user_id]);

            // 15. Finally, the user record itself
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = :uid");
            $stmt->execute([':uid' => $user_id]);

            $pdo->commit();
            $_SESSION['debug_msg'] = "User ID: $user_id and all associated records have been completely erased.";
            $_SESSION['debug_msg_type'] = 'success';
            header("Location: debug.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['debug_msg'] = "Error erasing user: " . $e->getMessage();
            $_SESSION['debug_msg_type'] = 'danger';
            header("Location: debug.php");
            exit;
        }
    } else {
        $_SESSION['debug_msg'] = "Invalid User ID.";
        $_SESSION['debug_msg_type'] = 'warning';
        header("Location: debug.php");
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'nuke_all_users') {
    try {
        $pdo->beginTransaction();

        // Nuke all tables linked to users, in bottom-up order, without WHERE clause
        $pdo->exec("DELETE FROM wallet_sync_logs");
        $pdo->exec("DELETE FROM wallet_transactions");
        $pdo->exec("DELETE FROM wallet_accounts");
        $pdo->exec("DELETE FROM transactions_backup");
        $pdo->exec("DELETE FROM transactions");
        $pdo->exec("DELETE FROM restructured_loans");
        $pdo->exec("DELETE FROM loan_restructure_requests");
        $pdo->exec("DELETE FROM loans");
        $pdo->exec("DELETE FROM loan_disbursement");
        $pdo->exec("DELETE FROM loan_documents");
        $pdo->exec("DELETE FROM loan_applications");
        
        $pdo->exec("DELETE FROM notifications");
        $pdo->exec("DELETE FROM chat_support_messages");
        $pdo->exec("DELETE FROM user_devices");
        
        $pdo->exec("DELETE FROM users");

        $pdo->commit();
        $_SESSION['debug_msg'] = "GLOBAL NUCLEAR LAUNCH EXECUTED: All users and linked data have been completely eradicated from the system.";
        $_SESSION['debug_msg_type'] = 'success';
        header("Location: debug.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $_SESSION['debug_msg'] = "Global Nuclear Abort: " . $e->getMessage();
        $_SESSION['debug_msg_type'] = 'danger';
        header("Location: debug.php");
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'purge_chats') {
    try {
        $pdo->exec("DELETE FROM chat_support_messages");
        $_SESSION['debug_msg'] = "CHAT PURGE EXECUTED: All chat support messages have been permanently obliterated.";
        $_SESSION['debug_msg_type'] = 'success';
        header("Location: debug.php");
        exit;
    } catch (Exception $e) {
        $_SESSION['debug_msg'] = "Chat Purge Failed: " . $e->getMessage();
        $_SESSION['debug_msg_type'] = 'danger';
        header("Location: debug.php");
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'spawn_user') {
    // Generate dummy user
    $firstNames = ['Skibidi', 'Rizz', 'Sigma', 'Beta', 'Giga', 'Chad', 'Based', 'Cringe', 'Brat', 'Aura', 'Yeet', 'Boomer', 'Zoomer', 'Bussin', 'Phantom', 'Ghost'];
    $lastNames = ['Fox', 'Wolf', 'Bear', 'Lion', 'Hawk', 'Shark', 'Dragon', 'Tiger', 'Viper', 'Panda', 'Doggo', 'Kitty'];
    
    $fn = $firstNames[array_rand($firstNames)];
    $ln = $lastNames[array_rand($lastNames)];
    $fullname = $fn . ' ' . $ln;
    
    // Gen Z email style
    $email = strtolower($fn) . '.' . strtolower($ln) . '.' . rand(1000, 9999) . '@debug.local';
    
    // The strict requirement password
    $raw_password = 'password123';
    $password_hash = password_hash($raw_password, PASSWORD_DEFAULT);
    
    // Generate a random Philippine phone number (starts with '09' + 9 digits)
    $phone = '09' . str_pad((string)rand(0, 999999999), 9, '0', STR_PAD_LEFT);
    
    try {
        $stmt = $pdo->prepare("INSERT INTO users (fullname, phone, email, password, account_status) VALUES (:fn, :ph, :em, :pw, 'ACTIVE')");
        $stmt->execute([
            ':fn' => $fullname,
            ':ph' => $phone,
            ':em' => $email,
            ':pw' => $password_hash
        ]);
        
        $newId = $pdo->lastInsertId();
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'id' => $newId,
            'fullname' => $fullname,
            'email' => $email,
            'password' => $raw_password,
            'phone' => $phone
        ]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'fetch_otps') {
    try {
        // Fetch OTPs from users table where otp_code is active
        $stmt = $pdo->query("SELECT id, fullname, email, otp_code, otp_expiry FROM users WHERE otp_code IS NOT NULL AND otp_code != '' ORDER BY otp_expiry DESC LIMIT 15");
        $otps = $stmt->fetchAll();
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'otps' => $otps]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        exit;
    }
}

// Fetch all users for the dropdown
$users = [];
try {
    $stmt = $pdo->query("SELECT id, fullname, email, phone FROM users ORDER BY id ASC");
    $users = $stmt->fetchAll();
} catch (Exception $e) {
    $message = "Error fetching users: " . $e->getMessage();
    $messageType = 'danger';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuclear User Deletion (Debug)</title>
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #121212;
            color: #e0e0e0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .card {
            background-color: #1e1e1e;
            border: 1px solid #333;
            margin-top: 50px;
        }
        .form-select, .form-control {
            background-color: #2b2b2b;
            color: #fff;
            border: 1px solid #444;
        }
        .form-select:focus, .form-control:focus {
            background-color: #333;
            color: #fff;
            border-color: #dc3545;
            box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
        }
        .text-warning {
            color: #ffc107 !important;
        }
        .nuclear-btn {
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .nuclear-btn.disabled-timer {
            cursor: not-allowed;
            opacity: 0.7;
            background-color: #444;
            border-color: #444;
            color: #ccc;
        }
    </style>
</head>
<body>

<div class="container-fluid px-5">
    <div class="row justify-content-center">
        <!-- Nuclear Deletion Section -->
        <div class="col-md-7 mb-4">
            <div class="card shadow-lg h-100">
                <div class="card-header bg-danger text-white d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill fs-4 me-2"></i>
                    <h4 class="mb-0">Nuclear User Deletion (Debug)</h4>
                </div>
                <div class="card-body p-4">
                    <p class="text-warning mb-4 fs-5">
                        <i class="bi bi-shield-exclamation"></i> <strong>WARNING:</strong> This action is strictly irreversible. It will completely eradicate the user and all their linked histories (loans, transactions, wallets, devices, documents, etc.) across the CORE 1 database.
                    </p>

                    <?php if ($message): ?>
                        <div class="alert alert-<?= $messageType ?> alert-dismissible fade show" role="alert">
                            <strong><?= $messageType === 'success' ? 'Success:' : 'Notice:' ?></strong> <?= htmlspecialchars($message) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <form id="deleteUserForm" method="POST" action="">
                        <input type="hidden" name="action" value="delete_user">
                        
                        <div class="mb-4">
                            <label for="user_id" class="form-label text-light fs-5">Select User Context to Terminate</label>
                            <select class="form-select form-select-lg" id="user_id" name="user_id" required>
                                <option value="" selected disabled>-- Select a Target --</option>
                                <?php foreach ($users as $u): ?>
                                    <option value="<?= $u['id'] ?>">
                                        ID: <?= $u['id'] ?> | <?= htmlspecialchars($u['fullname'] ?? 'Unknown') ?> | <?= htmlspecialchars($u['email'] ?? 'No Email') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" id="initiateBtn" class="btn btn-outline-danger btn-lg nuclear-btn">
                                <i class="bi bi-trash3-fill"></i> Initiate Deletion Sequence
                            </button>
                            <button type="submit" id="confirmBtn" class="btn btn-danger btn-lg nuclear-btn d-none disabled-timer" disabled>
                                Confirm Permanent Deletion (Wait <span id="countdown">3</span>s)
                            </button>
                            <button type="button" id="cancelBtn" class="btn btn-secondary btn-lg mt-2 d-none">
                                Cancel Operation
                            </button>
                        </div>
                    </form>

                    <hr class="border-secondary my-4">
                    
                    <div class="alert alert-warning text-dark fw-bold mb-3 shadow-sm" style="border: 2px solid #ffc107; background-color: #ffda6a;" role="alert">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-radioactive text-danger me-2" style="font-size: 2rem;"></i>
                            <h5 class="mb-0 text-danger fw-bolder">GLOBAL NUKE WARNING</h5>
                        </div>
                        This will obliterate <strong>ALL USERS</strong> in the database, including all their child records and parented keys across every module. This is the absolute atomic option.
                    </div>
                    
                    <form id="nukeAllUsersForm" method="POST" action="">
                        <input type="hidden" name="action" value="nuke_all_users">
                        <div class="d-grid gap-2">
                            <button type="button" id="initiateNukeAllBtn" class="btn btn-outline-warning btn-lg nuclear-btn shadow-sm fw-bold">
                                <i class="bi bi-radioactive"></i> Initiate Global Nuke (ALL USERS)
                            </button>
                            <button type="submit" id="confirmNukeAllBtn" class="btn btn-warning btn-lg nuclear-btn d-none disabled-timer text-dark fw-bold shadow-sm" disabled>
                                Confirm Global Eradication (Wait <span id="nukeAllCountdown">5</span>s)
                            </button>
                            <button type="button" id="cancelNukeAllBtn" class="btn btn-secondary btn-lg mt-2 d-none">
                                Abort Global Nuke
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Spawn Dummy User Section -->
        <div class="col-md-5 mb-4">
            <div class="card shadow-lg h-100" style="border-color: #0d6efd; border-width: 2px;">
                <div class="card-header bg-primary text-white d-flex align-items-center">
                    <i class="bi bi-person-plus-fill fs-4 me-2"></i>
                    <h4 class="mb-0">Spawn Dummy User</h4>
                </div>
                <div class="card-body p-4 text-center d-flex flex-column align-items-center">
                    <p class="text-light mb-4 text-start w-100 fs-6">
                        Instantly generates an active user natively in the database. Skips OTPs and manual signups.
                    </p>
                    <button type="button" id="spawnBtn" class="btn btn-primary btn-lg w-100 py-3 mb-4 fw-bold shadow">
                        <i class="bi bi-lightning-charge-fill"></i> Execute Spawn
                    </button>
                    
                    <div id="spawnAlertContainer" class="w-100 mb-2"></div>
                    
                    <!-- Dedicated output boxes -->
                    <div class="text-start w-100 mt-2">
                        <label class="form-label text-light fw-bold"><i class="bi bi-envelope-fill text-info"></i> Login Email Address</label>
                        <div class="input-group mb-3 shadow-sm">
                            <input type="text" id="genEmail" class="form-control bg-dark text-info fs-5" style="border-color: #444;" readonly placeholder="Waiting for spawn...">
                            <button class="btn btn-outline-info copy-single px-3" type="button" data-target="genEmail" title="Copy Email">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>

                        <label class="form-label text-light fw-bold"><i class="bi bi-key-fill text-info"></i> Account Password</label>
                        <div class="input-group mb-3 shadow-sm">
                            <input type="text" id="genPassword" class="form-control bg-dark text-info fs-5" style="border-color: #444;" readonly placeholder="Waiting for spawn...">
                            <button class="btn btn-outline-info copy-single px-3" type="button" data-target="genPassword" title="Copy Password">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- OTP Listener Row -->
    <div class="row justify-content-center">
        <div class="col-md-12 mb-4">
            <div class="card shadow-lg" style="border-color: #ffc107; border-width: 2px;">
                <div class="card-header bg-warning text-dark d-flex align-items-center fw-bold">
                    <i class="bi bi-broadcast me-2 fs-4"></i>
                    <h4 class="mb-0">Live OTP Listener</h4>
                    <span class="badge bg-dark text-warning ms-auto fs-6" id="otpCountBadge">0 Detected</span>
                </div>
                <div class="card-body p-0" style="max-height: 400px; overflow-y: auto; background-color: #1a1a1a;">
                    <table class="table table-dark table-hover mb-0 text-center">
                        <thead class="table-secondary text-dark sticky-top">
                            <tr>
                                <th>User ID</th>
                                <th>Full Name</th>
                                <th>Email</th>
                                <th>OTP Code</th>
                                <th class="d-none d-lg-table-cell">Expiry Time</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="otpTableBody">
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary fs-5">
                                    <div class="spinner-grow spinner-grow-sm text-warning me-2" role="status"></div>
                                    Listening for incoming OTPs via database...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Chat Support Purge Row -->
    <div class="row justify-content-center">
        <div class="col-md-12 mb-4">
            <div class="card shadow-lg" style="border-color: #6f42c1; border-width: 2px;">
                <div class="card-header text-white d-flex align-items-center fw-bold" style="background-color: #6f42c1;">
                    <i class="bi bi-chat-dots-fill me-2 fs-4"></i>
                    <h4 class="mb-0">Purge Chat Support Messages</h4>
                </div>
                <div class="card-body p-4 text-center">
                    <p class="text-light mb-4 fs-6">
                        Instantly wipes the entire <strong>chat_support_messages</strong> table. Extremely useful for clearing out debug spam or old support tickets during testing phases.
                    </p>
                    <form id="purgeChatForm" method="POST" action="">
                        <input type="hidden" name="action" value="purge_chats">
                        <div class="d-grid gap-2 col-md-8 mx-auto">
                            <button type="button" id="initiatePurgeChatBtn" class="btn btn-outline-light btn-lg fw-bold shadow-sm" style="color: #d63384; border-color: #d63384;">
                                <i class="bi bi-trash-fill"></i> Initiate Chat Purge
                            </button>
                            <button type="submit" id="confirmPurgeChatBtn" class="btn btn-danger btn-lg d-none disabled-timer fw-bold shadow-sm" disabled>
                                Confirm Purge (Wait <span id="purgeChatCountdown">3</span>s)
                            </button>
                            <button type="button" id="cancelPurgeChatBtn" class="btn btn-secondary btn-lg mt-2 d-none">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const initiateBtn = document.getElementById('initiateBtn');
        const confirmBtn = document.getElementById('confirmBtn');
        const cancelBtn = document.getElementById('cancelBtn');
        const countdownSpan = document.getElementById('countdown');
        const userSelect = document.getElementById('user_id');
        
        let timerId = null;

        function resetUI() {
            initiateBtn.classList.remove('d-none');
            confirmBtn.classList.add('d-none');
            cancelBtn.classList.add('d-none');
            confirmBtn.disabled = true;
            confirmBtn.classList.add('disabled-timer');
            confirmBtn.innerHTML = 'Confirm Permanent Deletion (Wait <span id="countdown">3</span>s)';
            if (timerId !== null) clearInterval(timerId);
        }

        initiateBtn.addEventListener('click', function() {
            if (!userSelect.value) {
                alert('Please select a user to terminate from the dropdown.');
                return;
            }

            initiateBtn.classList.add('d-none');
            confirmBtn.classList.remove('d-none');
            cancelBtn.classList.remove('d-none');

            let timeLeft = 3;
            // update the span immediately since it's freshly shown or recreated
            document.getElementById('countdown').textContent = timeLeft;

            timerId = setInterval(() => {
                timeLeft--;
                if (timeLeft > 0) {
                    document.getElementById('countdown').textContent = timeLeft;
                } else {
                    clearInterval(timerId);
                    confirmBtn.disabled = false;
                    confirmBtn.classList.remove('disabled-timer');
                    confirmBtn.innerHTML = '<i class="bi bi-exclamation-triangle-fill"></i> Execute Nuclear Deletion';
                }
            }, 1000);
        });

        cancelBtn.addEventListener('click', function() {
            resetUI();
        });

        userSelect.addEventListener('change', function() {
            resetUI();
        });

        // Nuke All Users Logic
        const initiateNukeAllBtn = document.getElementById('initiateNukeAllBtn');
        const confirmNukeAllBtn = document.getElementById('confirmNukeAllBtn');
        const cancelNukeAllBtn = document.getElementById('cancelNukeAllBtn');
        const nukeAllCountdownSpan = document.getElementById('nukeAllCountdown');
        
        let nukeAllTimerId = null;

        function resetNukeAllUI() {
            initiateNukeAllBtn.classList.remove('d-none');
            confirmNukeAllBtn.classList.add('d-none');
            cancelNukeAllBtn.classList.add('d-none');
            confirmNukeAllBtn.disabled = true;
            confirmNukeAllBtn.classList.add('disabled-timer');
            confirmNukeAllBtn.innerHTML = 'Confirm Global Eradication (Wait <span id="nukeAllCountdown">5</span>s)';
            if (nukeAllTimerId !== null) clearInterval(nukeAllTimerId);
        }

        if (initiateNukeAllBtn) {
            initiateNukeAllBtn.addEventListener('click', function() {
                initiateNukeAllBtn.classList.add('d-none');
                confirmNukeAllBtn.classList.remove('d-none');
                cancelNukeAllBtn.classList.remove('d-none');

                let timeLeft = 5;
                document.getElementById('nukeAllCountdown').textContent = timeLeft;

                nukeAllTimerId = setInterval(() => {
                    timeLeft--;
                    if (timeLeft > 0) {
                        document.getElementById('nukeAllCountdown').textContent = timeLeft;
                    } else {
                        clearInterval(nukeAllTimerId);
                        confirmNukeAllBtn.disabled = false;
                        confirmNukeAllBtn.classList.remove('disabled-timer');
                        confirmNukeAllBtn.innerHTML = '<i class="bi bi-radioactive"></i> Execute Global Nuke (NO UNDO)';
                    }
                }, 1000);
            });

            cancelNukeAllBtn.addEventListener('click', function() {
                resetNukeAllUI();
            });
        }

        // Purge Chat Messages Logic
        const initiatePurgeChatBtn = document.getElementById('initiatePurgeChatBtn');
        const confirmPurgeChatBtn = document.getElementById('confirmPurgeChatBtn');
        const cancelPurgeChatBtn = document.getElementById('cancelPurgeChatBtn');
        const purgeChatCountdownSpan = document.getElementById('purgeChatCountdown');
        
        let purgeChatTimerId = null;

        function resetPurgeChatUI() {
            initiatePurgeChatBtn.classList.remove('d-none');
            confirmPurgeChatBtn.classList.add('d-none');
            cancelPurgeChatBtn.classList.add('d-none');
            confirmPurgeChatBtn.disabled = true;
            confirmPurgeChatBtn.classList.add('disabled-timer');
            confirmPurgeChatBtn.innerHTML = 'Confirm Purge (Wait <span id="purgeChatCountdown">3</span>s)';
            if (purgeChatTimerId !== null) clearInterval(purgeChatTimerId);
        }

        if (initiatePurgeChatBtn) {
            initiatePurgeChatBtn.addEventListener('click', function() {
                initiatePurgeChatBtn.classList.add('d-none');
                confirmPurgeChatBtn.classList.remove('d-none');
                cancelPurgeChatBtn.classList.remove('d-none');

                let timeLeft = 3;
                document.getElementById('purgeChatCountdown').textContent = timeLeft;

                purgeChatTimerId = setInterval(() => {
                    timeLeft--;
                    if (timeLeft > 0) {
                        document.getElementById('purgeChatCountdown').textContent = timeLeft;
                    } else {
                        clearInterval(purgeChatTimerId);
                        confirmPurgeChatBtn.disabled = false;
                        confirmPurgeChatBtn.classList.remove('disabled-timer');
                        confirmPurgeChatBtn.innerHTML = '<i class="bi bi-trash-fill"></i> Execute Chat Purge';
                    }
                }, 1000);
            });

            cancelPurgeChatBtn.addEventListener('click', function() {
                resetPurgeChatUI();
            });
        }

        // Spawn Dummy User Logic
        const spawnBtn = document.getElementById('spawnBtn');
        const spawnAlertContainer = document.getElementById('spawnAlertContainer');
        const genEmail = document.getElementById('genEmail');
        const genPassword = document.getElementById('genPassword');
        
        if (spawnBtn) {
            spawnBtn.addEventListener('click', async function() {
                // visual feedback
                const originalText = spawnBtn.innerHTML;
                spawnBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Spawning...';
                spawnBtn.disabled = true;
                spawnAlertContainer.innerHTML = '';

                try {
                    const formData = new FormData();
                    formData.append('action', 'spawn_user');

                    // specifically post to debug.php instead of empty string
                    const response = await fetch('debug.php', { 
                        method: 'POST',
                        body: formData
                    });

                    const textData = await response.text();
                    let data;
                    try {
                        data = JSON.parse(textData);
                    } catch (e) {
                        throw new Error("Invalid response from server: " + textData.substring(0, 150) + "...");
                    }

                    if (data.success) {
                        genEmail.value = data.email;
                        genPassword.value = data.password;

                        spawnAlertContainer.innerHTML = `
                            <div class="alert alert-success py-2 text-start shadow-sm" role="alert" style="border: 1px solid #28a745;">
                                <i class="bi bi-check-circle-fill"></i> System Spawned ID: <strong>${data.id} (${data.fullname})</strong>
                            </div>
                        `;
                        
                        // Dynamically add the new user to the dropdown without reloading
                        if (userSelect) {
                            const newOption = document.createElement("option");
                            newOption.value = data.id;
                            newOption.text = `ID: ${data.id} | ${data.fullname} | ${data.email}`;
                            userSelect.appendChild(newOption);
                        }
                    } else {
                        spawnAlertContainer.innerHTML = `<div class="alert alert-danger" role="alert"><strong>DB Error:</strong> ${data.error}</div>`;
                    }
                } catch (err) {
                    spawnAlertContainer.innerHTML = `<div class="alert alert-danger" role="alert"><strong>Execution Error:</strong> ${err.message}</div>`;
                } finally {
                    spawnBtn.innerHTML = originalText;
                    spawnBtn.disabled = false;
                }
            });
        }

        // Dedicated copy buttons logic
        document.querySelectorAll('.copy-single').forEach(btn => {
            btn.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const textToCopy = document.getElementById(targetId).value;
                if (!textToCopy) return;

                navigator.clipboard.writeText(textToCopy).then(() => {
                    const icon = this.querySelector('i');
                    icon.className = 'bi bi-check2-all text-success';
                    setTimeout(() => {
                        icon.className = 'bi bi-clipboard';
                    }, 2000);
                }).catch(err => {
                    alert('Copy failed. Please manually select the text.');
                    console.error('Clipboard write error:', err);
                });
            });
        });

        // Live OTP Listener Logic
        const otpTableBody = document.getElementById('otpTableBody');
        const otpCountBadge = document.getElementById('otpCountBadge');
        
        async function fetchOTPs() {
            try {
                const res = await fetch('debug.php?action=fetch_otps');
                if (!res.ok) return;
                
                const data = await res.json();
                
                if (data.success) {
                    const otps = data.otps;
                    otpCountBadge.innerText = `${otps.length} Detected`;
                    
                    if (otps.length === 0) {
                        otpTableBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="text-center py-5 text-secondary fs-5">
                                    <i class="bi bi-check-circle" style="font-size: 2rem;"></i><br>
                                    No active OTPs found in the database.
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    let rows = '';
                    otps.forEach(otp => {
                        rows += `
                            <tr class="align-middle">
                                <td><span class="badge bg-secondary fs-6">#${otp.id}</span></td>
                                <td class="fw-bold text-light">${otp.fullname}</td>
                                <td class="text-info">${otp.email}</td>
                                <td>
                                    <span class="badge bg-warning text-dark fs-5 font-monospace shadow-sm" id="otpCode_${otp.id}">${otp.otp_code}</span>
                                </td>
                                <td class="text-muted d-none d-lg-table-cell"><small>${otp.otp_expiry || 'No expiry set'}</small></td>
                                <td>
                                    <button class="btn btn-sm btn-outline-warning copy-otp fw-bold" data-target="otpCode_${otp.id}" title="Copy OTP">
                                        <i class="bi bi-clipboard"></i> Copy
                                    </button>
                                </td>
                            </tr>
                        `;
                    });
                    
                    otpTableBody.innerHTML = rows;
                    
                    // Attach dynamic copy handlers for OTPs
                    document.querySelectorAll('.copy-otp').forEach(btn => {
                        btn.addEventListener('click', function() {
                            const targetId = this.getAttribute('data-target');
                            const textToCopy = document.getElementById(targetId).innerText;
                            if (!textToCopy) return;

                            navigator.clipboard.writeText(textToCopy).then(() => {
                                const originalHtml = this.innerHTML;
                                this.innerHTML = '<i class="bi bi-clipboard-check"></i> Copied!';
                                this.classList.replace('btn-outline-warning', 'btn-warning');
                                this.classList.add('text-dark');
                                setTimeout(() => {
                                    this.innerHTML = originalHtml;
                                    this.classList.replace('btn-warning', 'btn-outline-warning');
                                    this.classList.remove('text-dark');
                                }, 1500);
                            });
                        });
                    });

                }
            } catch (err) {
                console.error("OTP Listener Polling Error:", err);
            }
        }

        // Initialize and poll every 3 seconds
        fetchOTPs();
        setInterval(fetchOTPs, 3000);
    });
</script>
</body>
</html>
