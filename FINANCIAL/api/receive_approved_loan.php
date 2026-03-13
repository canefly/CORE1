<?php
session_start();
require_once __DIR__ . "/../config/db.php";

header('Content-Type: application/json; charset=utf-8');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function respond(bool $ok, string $message = '', array $extra = []): void
{
    echo json_encode(array_merge([
        'ok' => $ok,
        'message' => $message
    ], $extra));
    exit;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        respond(false, 'Invalid request method.');
    }

    $raw = file_get_contents("php://input");
    $data = json_decode($raw, true);

    if (!is_array($data)) {
        $data = $_POST;
    }

    $application_id            = (int)($data['application_id'] ?? 0);
    $user_id                   = (int)($data['user_id'] ?? 0);
    $principal_amount          = (float)($data['principal_amount'] ?? 0);
    $term_months               = (int)($data['term_months'] ?? 0);
    $loan_purpose              = trim((string)($data['loan_purpose'] ?? ''));
    $source_of_income          = trim((string)($data['source_of_income'] ?? ''));
    $estimated_monthly_income  = (float)($data['estimated_monthly_income'] ?? 0);
    $interest_rate             = (float)($data['interest_rate'] ?? 0);
    $interest_type             = strtoupper(trim((string)($data['interest_type'] ?? 'MONTHLY')));
    $interest_method           = strtoupper(trim((string)($data['interest_method'] ?? 'FLAT')));
    $total_interest            = isset($data['total_interest']) ? (float)$data['total_interest'] : null;
    $total_payable             = isset($data['total_payable']) ? (float)$data['total_payable'] : null;
    $monthly_due               = isset($data['monthly_due']) ? (float)$data['monthly_due'] : null;

    if ($application_id <= 0 || $user_id <= 0 || $principal_amount <= 0 || $term_months <= 0) {
        respond(false, 'Missing required fields.');
    }

    if (!in_array($interest_type, ['MONTHLY', 'ANNUAL'], true)) {
        $interest_type = 'MONTHLY';
    }

    if (!in_array($interest_method, ['FLAT'], true)) {
        $interest_method = 'FLAT';
    }

    $status = 'WAITING FOR DISBURSEMENT';

    $stmtCheck = $conn->prepare("SELECT id FROM loan_disbursement WHERE application_id = ? LIMIT 1");
    $stmtCheck->bind_param("i", $application_id);
    $stmtCheck->execute();
    $checkRes = $stmtCheck->get_result();
    $existing = $checkRes->fetch_assoc();
    $stmtCheck->close();

    if ($existing) {
        $stmtUpdate = $conn->prepare("
            UPDATE loan_disbursement
            SET user_id = ?,
                principal_amount = ?,
                term_months = ?,
                loan_purpose = ?,
                source_of_income = ?,
                estimated_monthly_income = ?,
                interest_rate = ?,
                interest_type = ?,
                interest_method = ?,
                total_interest = ?,
                total_payable = ?,
                monthly_due = ?,
                status = ?,
                updated_at = NOW()
            WHERE application_id = ?
            LIMIT 1
        ");

        $stmtUpdate->bind_param(
            "idissddsssddsi",
            $user_id,
            $principal_amount,
            $term_months,
            $loan_purpose,
            $source_of_income,
            $estimated_monthly_income,
            $interest_rate,
            $interest_type,
            $interest_method,
            $total_interest,
            $total_payable,
            $monthly_due,
            $status,
            $application_id
        );
        $stmtUpdate->execute();
        $stmtUpdate->close();

        respond(true, 'Loan disbursement updated.', [
            'application_id' => $application_id,
            'mode' => 'update'
        ]);
    }

    $stmtInsert = $conn->prepare("
        INSERT INTO loan_disbursement (
            application_id,
            user_id,
            principal_amount,
            term_months,
            loan_purpose,
            source_of_income,
            estimated_monthly_income,
            interest_rate,
            interest_type,
            interest_method,
            total_interest,
            total_payable,
            monthly_due,
            status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmtInsert->bind_param(
        "iidissddsssdds",
        $application_id,
        $user_id,
        $principal_amount,
        $term_months,
        $loan_purpose,
        $source_of_income,
        $estimated_monthly_income,
        $interest_rate,
        $interest_type,
        $interest_method,
        $total_interest,
        $total_payable,
        $monthly_due,
        $status
    );
    $stmtInsert->execute();
    $newId = $stmtInsert->insert_id;
    $stmtInsert->close();

    respond(true, 'Loan disbursement created.', [
        'id' => $newId,
        'application_id' => $application_id,
        'mode' => 'insert'
    ]);

} catch (Throwable $e) {
    respond(false, 'Error: ' . $e->getMessage());
}