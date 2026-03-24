<?php
header('Content-Type: application/json');

require_once __DIR__ . '/includes/db_connect.php';

function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

try {
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database connection unavailable.');
    }
} catch (Throwable $e) {
    json_out([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage()
    ], 500);
}

$expectedToken = 'CORE1_FINANCIAL_RELEASE_SECRET_456';

$headers = function_exists('getallheaders') ? getallheaders() : [];
$token = $headers['X-API-KEY'] ?? $headers['x-api-key'] ?? '';

if ($token !== $expectedToken) {
    json_out([
        'success' => false,
        'message' => 'Unauthorized request.'
    ], 401);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    json_out([
        'success' => false,
        'message' => 'Invalid JSON payload.'
    ], 400);
}

$application_id        = (int)($data['application_id'] ?? 0);
$core1_disbursement_id = (int)($data['core1_disbursement_id'] ?? 0);
$status                = trim((string)($data['status'] ?? ''));
$receipt_no            = trim((string)($data['receipt_no'] ?? ''));
$receipt_image         = trim((string)($data['receipt_image'] ?? ''));
$released_at           = trim((string)($data['released_at'] ?? ''));
$release_notes         = trim((string)($data['release_notes'] ?? ''));
$loan_amount_received  = (float)($data['amount'] ?? 0); // Amount mula sa Financial PC

$allowedStatuses = ['DISBURSED', 'REJECTED'];

if ($application_id <= 0 || $core1_disbursement_id <= 0 || !in_array($status, $allowedStatuses, true)) {
    json_out([
        'success' => false,
        'message' => 'Missing or invalid required fields.'
    ], 422);
}

try {
    $pdo->beginTransaction();

    // 1. Lock CORE1 disbursement row
    $stmt = $pdo->prepare("
        SELECT *
        FROM loan_disbursement
        WHERE id = ? AND application_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$core1_disbursement_id, $application_id]);
    $localDisb = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$localDisb) {
        throw new Exception('CORE1 loan_disbursement record not found.');
    }

    // 2. Update local disbursement status
    $upd = $pdo->prepare("
        UPDATE loan_disbursement
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$status, $core1_disbursement_id]);

    if ($status === 'REJECTED') {
        $pdo->commit();
        json_out([
            'success' => true,
            'message' => 'CORE1 disbursement marked as REJECTED.'
        ]);
    }

    // 3. Prepare variables for Loan and Wallet
    $user_id         = (int)$localDisb['user_id'];
    $loan_amount     = $loan_amount_received > 0 ? $loan_amount_received : (float)$localDisb['principal_amount']; 
    $term_months     = (int)$localDisb['term_months'];
    $monthly_due     = (float)$localDisb['monthly_due'];
    $interest_rate   = (float)$localDisb['interest_rate'];
    $interest_method = (string)$localDisb['interest_method'];
    $outstanding     = (float)$localDisb['total_payable'];

    if ($released_at !== '') {
        $startDate = date('Y-m-d', strtotime($released_at));
    } else {
        $startDate = date('Y-m-d');
        $released_at = date('Y-m-d H:i:s');
    }

    $nextPayment = date('Y-m-d', strtotime($startDate . ' +1 month'));
    $dueDate     = date('Y-m-d', strtotime($startDate . ' +' . $term_months . ' months'));

    // 4. Create/Update loans table (ORIGINAL LOGIC - WALANG BINAGO)
    $stmtLoan = $pdo->prepare("SELECT id FROM loans WHERE application_id = ? LIMIT 1");
    $stmtLoan->execute([$application_id]);
    $existingLoan = $stmtLoan->fetch(PDO::FETCH_ASSOC);

    if ($existingLoan) {
        $updLoan = $pdo->prepare("
            UPDATE loans
            SET
                user_id = ?,
                loan_disbursement_id = ?,
                loan_amount = ?,
                term_months = ?,
                monthly_due = ?,
                interest_rate = ?,
                interest_method = ?,
                outstanding = ?,
                next_payment = ?,
                due_date = ?,
                start_date = ?,
                status = 'ACTIVE',
                receipt_no = ?,
                receipt_image = ?,
                released_at = ?,
                release_notes = ?,
                updated_at = NOW()
            WHERE application_id = ?
        ");
        $updLoan->execute([
            $user_id, $core1_disbursement_id, $loan_amount, $term_months, $monthly_due,
            $interest_rate, $interest_method, $outstanding, $nextPayment, $dueDate,
            $startDate, ($receipt_no ?: null), ($receipt_image ?: null), $released_at, ($release_notes ?: null), $application_id
        ]);
        $loanId = (int)$existingLoan['id'];
        $action = 'updated';
    } else {
        $insLoan = $pdo->prepare("
            INSERT INTO loans (
                user_id, application_id, loan_disbursement_id, loan_amount, term_months,
                monthly_due, interest_rate, interest_method, outstanding, next_payment,
                due_date, last_penalty_date, start_date, status, receipt_no,
                receipt_image, released_at, release_notes, created_at, updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 'ACTIVE', ?, ?, ?, ?, NOW(), NOW()
            )
        ");
        $insLoan->execute([
            $user_id, $application_id, $core1_disbursement_id, $loan_amount, $term_months,
            $monthly_due, $interest_rate, $interest_method, $outstanding, $nextPayment,
            $dueDate, $startDate, ($receipt_no ?: null), ($receipt_image ?: null), $released_at, ($release_notes ?: null)
        ]);
        $loanId = (int)$pdo->lastInsertId();
        $action = 'inserted';
    }

    // 5. WALLET INTEGRATION (NEW - WALANG BINAWAS SA TAAS)
    $stmtW = $pdo->prepare("SELECT id FROM wallet_accounts WHERE user_id = ? FOR UPDATE");
    $stmtW->execute([$user_id]);
    $wallet = $stmtW->fetch(PDO::FETCH_ASSOC);

    if (!$wallet) {
        $accountNo = 'WAL-' . date('Ymd') . '-' . $user_id . '-' . rand(1000, 9999);
        $insW = $pdo->prepare("INSERT INTO wallet_accounts (user_id, account_number, balance, loan_wallet_balance, status, created_at) VALUES (?, ?, 0, 0, 'ACTIVE', NOW())");
        $insW->execute([$user_id, $accountNo]);
        $walletAccountId = $pdo->lastInsertId();
    } else {
        $walletAccountId = $wallet['id'];
    }

    // Update Loan Wallet Principal
    $updW = $pdo->prepare("UPDATE wallet_accounts SET loan_wallet_balance = loan_wallet_balance + ?, updated_at = NOW() WHERE id = ?");
    $updW->execute([$loan_amount, $walletAccountId]);

    // Insert Transaction History
    $insTx = $pdo->prepare("
        INSERT INTO wallet_transactions (
            wallet_account_id, user_id, loan_id, transaction_type, amount, 
            running_balance, reference_no, remarks, status, created_at
        ) VALUES (
            ?, ?, ?, 'LOAN_DISBURSEMENT', ?, 
            (SELECT loan_wallet_balance FROM wallet_accounts WHERE id = ?), 
            ?, 'Loan Principal Disbursed', 'SUCCESS', NOW()
        )
    ");
    $insTx->execute([$walletAccountId, $user_id, $loanId, $loan_amount, $walletAccountId, ($receipt_no ?: 'REF-'.$application_id)]);

    $pdo->commit();

    json_out([
        'success' => true,
        'message' => "CORE1 loan {$action} successfully and funds added to wallet.",
        'loan_id' => $loanId
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_out(['success' => false, 'message' => $e->getMessage()], 500);
}