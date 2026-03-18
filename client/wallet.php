<?php
session_start();
require_once __DIR__ . "/include/config.php";
include __DIR__ . "/include/session_checker.php";

if (!isset($conn)) {
    die("Error: Variable \$conn is not defined in config.php. Please check your connection file.");
}

$mysqli = $conn;

// ==========================================
// CORE2 REMOTE CONFIG
// HUWAG localhost kung magkaibang PC
// Palitan ng actual reachable IP/hostname ng CORE2 PC
// Example: http://192.168.1.50/core2_api
// ==========================================
define('CORE2_API_BASE', 'http://192.168.1.50/core2_api');
define('CORE2_API_TIMEOUT', 15);

// Optional shared token kung gusto niyo lagyan ng simple auth
define('CORE2_API_TOKEN', 'YOUR_SHARED_SECRET_TOKEN');

// ==========================================
// HELPERS
// ==========================================
function h($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect_with_query(string $query): void
{
    header("Location: wallet.php" . ($query ? "?{$query}" : ""));
    exit;
}

function core2_api_post(string $endpoint, array $payload = []): array
{
    $url = rtrim(CORE2_API_BASE, '/') . '/' . ltrim($endpoint, '/');

    $ch = curl_init($url);
    if ($ch === false) {
        return [
            'success' => false,
            'message' => 'Failed to initialize cURL.'
        ];
    }

    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'X-API-KEY: ' . CORE2_API_TOKEN
    ];

    curl_setopt_array($ch, [
        CURLOPT_POST            => true,
        CURLOPT_RETURNTRANSFER  => true,
        CURLOPT_HTTPHEADER      => $headers,
        CURLOPT_POSTFIELDS      => json_encode($payload),
        CURLOPT_CONNECTTIMEOUT  => 5,
        CURLOPT_TIMEOUT         => CORE2_API_TIMEOUT,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($response === false || $curlErr) {
        return [
            'success' => false,
            'message' => 'CORE2 connection failed: ' . $curlErr
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Invalid response from CORE2.',
            'raw'     => $response,
            'http'    => $httpCode
        ];
    }

    $decoded['_http_code'] = $httpCode;
    return $decoded;
}

function generate_wallet_account_number(int $userId): string
{
    return 'WAL-' . date('Y') . '-' . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);
}

function generate_reference(string $prefix = 'PAY'): string
{
    return $prefix . '-' . date('YmdHis') . '-' . random_int(1000, 9999);
}

function get_user_fullname(mysqli $mysqli, int $userId): string
{
    $full_name = "Unknown User";

    $stmt = $mysqli->prepare("SELECT fullname FROM users WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $full_name = $row['fullname'] ?? $full_name;
        }
        $stmt->close();
    }

    return $full_name;
}

function get_active_loan(mysqli $mysqli, int $userId): array
{
    $data = [
        'has_active_loan' => false,
        'loan_id'         => null,
        'monthly_due'     => 0.00,
        'next_payment'    => 'N/A',
        'raw_next_payment'=> null,
        'status'          => null,
        'outstanding'     => 0.00
    ];

    $stmt = $mysqli->prepare("
        SELECT id, monthly_due, next_payment, status, outstanding
        FROM loans
        WHERE user_id = ?
          AND status IN ('ACTIVE', 'RESTRUCTURED')
        ORDER BY id DESC
        LIMIT 1
    ");

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            $data['has_active_loan'] = true;
            $data['loan_id'] = (int)$row['id'];
            $data['monthly_due'] = (float)$row['monthly_due'];
            $data['raw_next_payment'] = $row['next_payment'];
            $data['next_payment'] = !empty($row['next_payment'])
                ? date('M d, Y', strtotime($row['next_payment']))
                : 'N/A';
            $data['status'] = $row['status'] ?? null;
            $data['outstanding'] = isset($row['outstanding']) ? (float)$row['outstanding'] : 0.00;
        }

        $stmt->close();
    }

    return $data;
}

function get_wallet_from_core2(int $userId): array
{
    $result = core2_api_post('wallet/get_wallet.php', [
        'user_id' => $userId
    ]);

    if (!($result['success'] ?? false)) {
        return [
            'success'           => false,
            'has_wallet'        => false,
            'wallet_account_id' => null,
            'wallet_account_num'=> '',
            'wallet_balance'    => 0.00,
            'message'           => $result['message'] ?? 'Unable to fetch wallet from CORE2.'
        ];
    }

    $wallet = $result['wallet'] ?? [];

    return [
        'success'            => true,
        'has_wallet'         => !empty($wallet),
        'wallet_account_id'  => $wallet['id'] ?? null,
        'wallet_account_num' => $wallet['account_number'] ?? '',
        'wallet_balance'     => isset($wallet['current_balance']) ? (float)$wallet['current_balance'] : 0.00,
        'wallet_status'      => $wallet['status'] ?? '',
        'message'            => $result['message'] ?? ''
    ];
}

function get_wallet_history_from_core2($walletAccountId): array
{
    if (!$walletAccountId) {
        return [];
    }

    $result = core2_api_post('wallet/get_history.php', [
        'account_id' => $walletAccountId,
        'limit'      => 5
    ]);

    if (!($result['success'] ?? false)) {
        return [];
    }

    return is_array($result['history'] ?? null) ? $result['history'] : [];
}

// ==========================================
// KICK OUT IF NOT LOGGED IN
// ==========================================
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$full_name = get_user_fullname($mysqli, $user_id);

$loanData = get_active_loan($mysqli, $user_id);
$has_active_loan = $loanData['has_active_loan'];
$loan_id         = $loanData['loan_id'];
$monthly_due     = $loanData['monthly_due'];
$next_payment    = $loanData['next_payment'];
$loan_status     = $loanData['status'];
$outstanding     = $loanData['outstanding'];

$walletData = get_wallet_from_core2($user_id);
$has_wallet        = $walletData['has_wallet'];
$wallet_account_id = $walletData['wallet_account_id'];
$wallet_balance    = $walletData['wallet_balance'];
$wallet_account_num= $walletData['wallet_account_num'];

$error_message = '';
if (!$walletData['success'] && $has_active_loan) {
    $error_message = $walletData['message'] ?? 'Unable to connect to CORE2 wallet service.';
}

// ==========================================
// HANDLE POST REQUESTS
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // --------------------------------------
    // ACTION: ACTIVATE WALLET
    // CORE1 asks CORE2 to create wallet
    // --------------------------------------
    if ($action === 'activate') {
        if (!$has_active_loan) {
            redirect_with_query('error=no_active_loan');
        }

        if ($has_wallet) {
            redirect_with_query('error=wallet_exists');
        }

        $new_acct_num = generate_wallet_account_number($user_id);

        $activateResult = core2_api_post('wallet/create_wallet.php', [
            'user_id'        => $user_id,
            'full_name'      => $full_name,
            'account_number' => $new_acct_num,
            'account_type'   => 'Digital Wallet',
            'status'         => 'active'
        ]);

        if ($activateResult['success'] ?? false) {
            redirect_with_query('success=activated');
        }

        $msg = urlencode($activateResult['message'] ?? 'Wallet activation failed.');
        redirect_with_query("error_msg={$msg}");
    }

    // --------------------------------------
    // ACTION: PAY LOAN
    // FLOW:
    // 1) validate loan in CORE1
    // 2) ask CORE2 to debit wallet + record wallet transaction
    // 3) if success, update CORE1 loans + transactions
    // --------------------------------------
    if ($action === 'pay_loan') {
        if (!$has_active_loan || !$loan_id) {
            redirect_with_query('error=no_active_loan');
        }

        if (!$has_wallet || !$wallet_account_id) {
            redirect_with_query('error=no_wallet');
        }

        if ($monthly_due <= 0) {
            redirect_with_query('error=invalid_due');
        }

        if ($wallet_balance < $monthly_due) {
            redirect_with_query('error=insufficient');
        }

        $ref_num = generate_reference('PAY');

        // Step 1: Debit wallet in CORE2
        $debitResult = core2_api_post('wallet/pay_loan.php', [
            'user_id'          => $user_id,
            'account_id'       => $wallet_account_id,
            'loan_id'          => $loan_id,
            'amount'           => $monthly_due,
            'reference_number' => $ref_num,
            'description'      => "Paid Monthly Loan Due (Loan ID: {$loan_id})"
        ]);

        if (!($debitResult['success'] ?? false)) {
            $msg = urlencode($debitResult['message'] ?? 'CORE2 wallet debit failed.');
            redirect_with_query("error_msg={$msg}");
        }

        // Step 2: Update CORE1 loan and transaction
        $mysqli->begin_transaction();

        try {
            $newOutstanding = max(0, $outstanding - $monthly_due);

            // Optional sample next payment logic only
            // Replace with your actual amortization/schedule logic later
            $computedNextPayment = null;
            if (!empty($loanData['raw_next_payment'])) {
                $computedNextPayment = date('Y-m-d', strtotime($loanData['raw_next_payment'] . ' +1 month'));
            }

            if ($newOutstanding <= 0) {
                $loanUpdate = $mysqli->prepare("
                    UPDATE loans
                    SET outstanding = 0,
                        status = 'COMPLETED'
                    WHERE id = ?
                    LIMIT 1
                ");
                $loanUpdate->bind_param("i", $loan_id);
            } else {
                if ($computedNextPayment) {
                    $loanUpdate = $mysqli->prepare("
                        UPDATE loans
                        SET outstanding = ?,
                            next_payment = ?
                        WHERE id = ?
                        LIMIT 1
                    ");
                    $loanUpdate->bind_param("dsi", $newOutstanding, $computedNextPayment, $loan_id);
                } else {
                    $loanUpdate = $mysqli->prepare("
                        UPDATE loans
                        SET outstanding = ?
                        WHERE id = ?
                        LIMIT 1
                    ");
                    $loanUpdate->bind_param("di", $newOutstanding, $loan_id);
                }
            }

            if (!$loanUpdate || !$loanUpdate->execute()) {
                throw new Exception("Failed to update CORE1 loans table.");
            }
            $loanUpdate->close();

            $txStmt = $mysqli->prepare("
                INSERT INTO transactions
                (user_id, loan_id, amount, status, trans_date, provider_method, receipt_number)
                VALUES (?, ?, ?, 'SUCCESS', NOW(), 'WALLET', ?)
            ");

            if (!$txStmt) {
                throw new Exception("Failed to prepare CORE1 transaction insert.");
            }

            $txStmt->bind_param("iids", $user_id, $loan_id, $monthly_due, $ref_num);

            if (!$txStmt->execute()) {
                throw new Exception("Failed to record CORE1 transaction.");
            }
            $txStmt->close();

            $mysqli->commit();
            redirect_with_query('success=paid');
        } catch (Throwable $e) {
            $mysqli->rollback();

            // NOTE:
            // Since successful na ang debit sa CORE2 dito, ideally meron kayong:
            // 1) rollback endpoint sa CORE2, or
            // 2) pending verification queue / reconciliation log
            // Sa ngayon structure muna ito gaya ng hiningi mo.

            $msg = urlencode('Payment debited from wallet but CORE1 update failed: ' . $e->getMessage());
            redirect_with_query("error_msg={$msg}");
        }
    }
}

// refresh history after actions/view load
$wallet_history = $has_wallet ? get_wallet_history_from_core2($wallet_account_id) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Digital Wallet - Microfinance</title>

    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@400;500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/wallet.css">
</head>
<body>

<?php include 'include/sidebar.php'; ?>
<?php include 'include/theme_toggle.php'; ?>

<div class="main-content">

    <?php if (!empty($error_message)): ?>
        <div style="background:#7f1d1d;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;text-align:center;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo h($error_message); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error_msg'])): ?>
        <div style="background:#7f1d1d;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;text-align:center;">
            <i class="bi bi-exclamation-triangle-fill"></i> <?php echo h($_GET['error_msg']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'paid'): ?>
        <div style="background:#065f46;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;text-align:center;">
            <i class="bi bi-check-circle-fill"></i> Loan payment successful! Deducted from your digital wallet.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cashin'): ?>
        <div style="background:#065f46;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;text-align:center;">
            <i class="bi bi-check-circle-fill"></i> Cash In successful! Your wallet balance has been updated.
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'activated'): ?>
        <div style="background:#065f46;color:#fff;padding:15px;border-radius:8px;margin-bottom:20px;text-align:center;">
            <i class="bi bi-check-circle-fill"></i> Wallet activated successfully.
        </div>
    <?php endif; ?>

    <?php
    // ==========================================
    // SCREEN 1: NO ACTIVE LOAN
    // ==========================================
    if (!$has_active_loan):
    ?>
    <div class="activation-wrapper">
        <div class="table-card text-center" style="max-width: 400px; margin: 0 auto; padding: 40px 30px;">
            <div class="activation-icon" style="color: #f87171; background: rgba(239, 68, 68, 0.15);">
                <i class="bi bi-lock-fill"></i>
            </div>
            <h2 style="color: #fff; margin-bottom: 10px; font-size: 22px;">Wallet Locked</h2>
            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6;">
                You need an approved and active loan to activate the Digital Wallet feature. Apply for a loan first.
            </p>
        </div>
    </div>

    <?php
    // ==========================================
    // SCREEN 2: HAS LOAN, BUT NO WALLET YET
    // ==========================================
    elseif ($has_active_loan && !$has_wallet):
    ?>
    <div id="activationScreen" class="activation-wrapper">
        <div class="table-card text-center" style="max-width: 400px; margin: 0 auto; padding: 40px 30px;">
            <div class="activation-icon">
                <i class="bi bi-wallet2"></i>
            </div>
            <h2 style="color: #fff; margin-bottom: 10px; font-size: 22px;">Activate Wallet</h2>
            <p style="color: #94a3b8; font-size: 14px; line-height: 1.6; margin-bottom: 25px;">
                Enable your digital wallet to manage funds and pay your loans easily.
            </p>

            <div class="user-box">
                <small>Account Owner</small>
                <h3><?php echo h($full_name); ?></h3>
            </div>

            <ul class="wallet-rules">
                <li><i class="bi bi-check-circle-fill text-green"></i> Real-time Loan Repayment</li>
                <li><i class="bi bi-check-circle-fill text-green"></i> Secure Savings Tracking</li>
                <li class="strict-rule">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>STRICTLY NO CASHOUT.</strong><br>Funds are for loan repayment only.
                </li>
            </ul>

            <form method="POST">
                <input type="hidden" name="action" value="activate">
                <button type="submit" class="btn-pay" style="width: 100%; justify-content: center; margin-top: 20px;">
                    Activate Wallet Now <i class="bi bi-arrow-right"></i>
                </button>
            </form>
        </div>
    </div>

    <?php
    // ==========================================
    // SCREEN 3: HAS LOAN AND WALLET IS ACTIVE
    // ==========================================
    else:
    ?>
    <div id="mainWalletScreen" style="animation: fadeIn 0.4s ease-in-out;">
        <div class="page-header">
            <h1>Digital Wallet</h1>
            <p>Manage your funds for loan repayments</p>
        </div>

        <div class="hero-card">
            <div class="hero-info">
                <h2>Available Balance</h2>
                <div class="amount">₱ <?php echo number_format($wallet_balance, 2); ?></div>
                <div class="due-date">
                    <i class="bi bi-person-badge"></i>
                    <?php echo h($full_name); ?> | <?php echo h($wallet_account_num); ?>
                </div>
                <div class="due-date" style="margin-top:8px;">
                    <i class="bi bi-credit-card-2-front"></i>
                    Loan Status: <?php echo h($loan_status); ?> |
                    Due: ₱ <?php echo number_format($monthly_due, 2); ?> |
                    Next: <?php echo h($next_payment); ?>
                </div>
            </div>
            <div class="hero-actions wallet-mobile-actions">
                <button class="btn-secondary" onclick="window.location.href='cash_in.php'">
                    <i class="bi bi-plus-circle-fill"></i> Add Balance
                </button>
                <button class="btn-pay" onclick="checkPaymentLogic()">
                    <i class="bi bi-credit-card-fill"></i> Pay Loan
                </button>
            </div>
        </div>

        <div class="table-card">
            <div class="section-head">
                <h3>Recent Activity</h3>
            </div>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Transaction</th>
                            <th>Date & Time</th>
                            <th>Amount</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($wallet_history)): ?>
                            <?php foreach ($wallet_history as $hist): ?>
                                <?php
                                    $type = strtolower((string)($hist['transaction_type'] ?? ''));
                                    $amount = isset($hist['amount']) ? (float)$hist['amount'] : 0.00;
                                    $created_at = $hist['created_at'] ?? '';
                                    $is_deposit = in_array($type, ['deposit', 'cash_in'], true);
                                ?>
                                <tr>
                                    <td>
                                        <div style="display:flex;align-items:center;gap:10px;">
                                            <?php if ($is_deposit): ?>
                                                <i class="bi bi-arrow-down-left-circle-fill" style="color:#34d399;font-size:18px;"></i>
                                                <strong style="color:#fff;">Cash In</strong>
                                            <?php else: ?>
                                                <i class="bi bi-arrow-up-right-circle-fill" style="color:#fbbf24;font-size:18px;"></i>
                                                <strong style="color:#fff;">Loan Payment</strong>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo $created_at ? date('M d, Y • h:i A', strtotime($created_at)) : 'N/A'; ?>
                                    </td>
                                    <td style="color: <?php echo $is_deposit ? '#34d399' : '#fff'; ?>; font-weight:700;">
                                        <?php echo $is_deposit ? '+' : '-'; ?> ₱ <?php echo number_format($amount, 2); ?>
                                    </td>
                                    <td><span class="badge bg-green">Completed</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" style="text-align:center;">No recent transactions.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

</div>

<div id="modalPayNow" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-icon icon-success"><i class="bi bi-check-lg"></i></div>
        <h3>Ready to Pay?</h3>
        <p>
            You have enough balance to pay your upcoming due of
            <strong>₱<?php echo number_format($monthly_due, 2); ?></strong>
            for <strong><?php echo h($next_payment); ?></strong>.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="pay_loan">
            <div class="modal-actions">
                <button type="button" class="btn-secondary" onclick="closeModal('modalPayNow')">Cancel</button>
                <button type="submit" class="btn-pay">Pay Now</button>
            </div>
        </form>
    </div>
</div>

<div id="modalInsufficient" class="modal-overlay">
    <div class="modal-content">
        <div class="modal-icon icon-error"><i class="bi bi-exclamation-triangle-fill"></i></div>
        <h3>Insufficient Balance</h3>
        <p>
            Your balance is only <strong>₱<?php echo number_format($wallet_balance, 2); ?></strong>.
            Your monthly due is <strong>₱<?php echo number_format($monthly_due, 2); ?></strong>.
            Please Add Balance first.
        </p>
        <div class="modal-actions">
            <button class="btn-secondary" onclick="closeModal('modalInsufficient')" style="width:100%;">Close</button>
        </div>
    </div>
</div>

<script>
const walletBalance = <?php echo json_encode((float)$wallet_balance); ?>;
const monthlyDue    = <?php echo json_encode((float)$monthly_due); ?>;

function checkPaymentLogic() {
    if (walletBalance >= monthlyDue && monthlyDue > 0) {
        document.getElementById('modalPayNow').style.display = 'flex';
    } else {
        document.getElementById('modalInsufficient').style.display = 'flex';
    }
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}
</script>

</body>
</html>