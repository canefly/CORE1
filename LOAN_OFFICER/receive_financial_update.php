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

$allowedStatuses = ['DISBURSED', 'REJECTED'];

if ($application_id <= 0 || $core1_disbursement_id <= 0 || !in_array($status, $allowedStatuses, true)) {
    json_out([
        'success' => false,
        'message' => 'Missing or invalid required fields.',
        'received' => [
            'application_id' => $application_id,
            'core1_disbursement_id' => $core1_disbursement_id,
            'status' => $status
        ]
    ], 422);
}

try {
    $pdo->beginTransaction();

    // Lock CORE1 disbursement row
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

    // Update local disbursement status
    $upd = $pdo->prepare("
        UPDATE loan_disbursement
        SET status = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $upd->execute([$status, $core1_disbursement_id]);

    // If rejected, stop here
    if ($status === 'REJECTED') {
        $pdo->commit();

        json_out([
            'success' => true,
            'message' => 'CORE1 disbursement marked as REJECTED.'
        ]);
    }

    // If disbursed, create/update loans record
    $user_id         = (int)$localDisb['user_id'];
    $loan_amount     = (float)$localDisb['principal_amount'];
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

    // Check existing loan by application_id
    $stmtLoan = $pdo->prepare("
        SELECT id
        FROM loans
        WHERE application_id = ?
        LIMIT 1
    ");
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
            $user_id,
            $core1_disbursement_id,
            $loan_amount,
            $term_months,
            $monthly_due,
            $interest_rate,
            $interest_method,
            $outstanding,
            $nextPayment,
            $dueDate,
            $startDate,
            $receipt_no !== '' ? $receipt_no : null,
            $receipt_image !== '' ? $receipt_image : null,
            $released_at,
            $release_notes !== '' ? $release_notes : null,
            $application_id
        ]);

        $loanId = (int)$existingLoan['id'];
        $action = 'updated';
    } else {
        $insLoan = $pdo->prepare("
            INSERT INTO loans (
                user_id,
                application_id,
                loan_disbursement_id,
                loan_amount,
                term_months,
                monthly_due,
                interest_rate,
                interest_method,
                outstanding,
                next_payment,
                due_date,
                last_penalty_date,
                start_date,
                status,
                receipt_no,
                receipt_image,
                released_at,
                release_notes,
                created_at,
                updated_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NULL, ?, 'ACTIVE', ?, ?, ?, ?, NOW(), NOW()
            )
        ");
        $insLoan->execute([
            $user_id,
            $application_id,
            $core1_disbursement_id,
            $loan_amount,
            $term_months,
            $monthly_due,
            $interest_rate,
            $interest_method,
            $outstanding,
            $nextPayment,
            $dueDate,
            $startDate,
            $receipt_no !== '' ? $receipt_no : null,
            $receipt_image !== '' ? $receipt_image : null,
            $released_at,
            $release_notes !== '' ? $release_notes : null
        ]);

        $loanId = (int)$pdo->lastInsertId();
        $action = 'inserted';
    }

    $pdo->commit();

    json_out([
        'success' => true,
        'message' => "CORE1 loan {$action} successfully.",
        'loan_id' => $loanId
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    json_out([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ], 500);
}