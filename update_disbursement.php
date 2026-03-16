<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

// ======================================================
// CORE1 / MICROFINANCE DB CONNECTION
// ======================================================
$host = "localhost";
$user = "root";
$pass = "";
$db   = "microfinance_db"; // palitan kung actual db name mo ay microfinance

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "step" => "db_connect",
        "message" => "CORE1 DB connection failed: " . $conn->connect_error
    ]);
    exit;
}
$conn->set_charset("utf8mb4");

// ======================================================
// CONFIG
// ======================================================
$sharedSecret = "microfinance_secret_2026";

// ======================================================
// HELPER
// ======================================================
function respond($success, $message, $extra = [], $code = 200)
{
    http_response_code($code);
    echo json_encode(array_merge([
        "success" => $success,
        "message" => $message
    ], $extra), JSON_PRETTY_PRINT);
    exit;
}

// ======================================================
// REQUEST CHECK
// ======================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, "Invalid request method.", ["step" => "request_method"], 405);
}

$secretKey = $_POST['secret_key'] ?? '';
if ($secretKey !== $sharedSecret) {
    respond(false, "Unauthorized request.", ["step" => "secret_check"], 403);
}

$applicationId     = isset($_POST['application_id']) ? (int) $_POST['application_id'] : 0;
$userId            = isset($_POST['user_id']) ? (int) $_POST['user_id'] : 0;
$loanAmountFromFin = isset($_POST['loan_amount']) ? (float) $_POST['loan_amount'] : 0;
$financialRecordId = isset($_POST['financial_record_id']) ? (int) $_POST['financial_record_id'] : 0;
$core2DisbId       = isset($_POST['core2_disbursement_id']) ? (int) $_POST['core2_disbursement_id'] : 0;

if ($applicationId <= 0) {
    respond(false, "Missing application_id.", ["step" => "validate_application_id"], 422);
}

if ($userId <= 0) {
    respond(false, "Missing user_id.", ["step" => "validate_user_id"], 422);
}

// ======================================================
// GET LOAN DETAILS FROM CORE1 loan_disbursement
// ======================================================
$disbSql = "
    SELECT
        id,
        application_id,
        user_id,
        principal_amount,
        term_months,
        interest_rate,
        interest_method,
        monthly_due,
        status
    FROM loan_disbursement
    WHERE application_id = ?
    LIMIT 1
";

$disbStmt = $conn->prepare($disbSql);
if (!$disbStmt) {
    respond(false, "Prepare failed: " . $conn->error, [
        "step" => "disb_prepare",
        "query" => $disbSql
    ], 500);
}

$disbStmt->bind_param("i", $applicationId);

if (!$disbStmt->execute()) {
    respond(false, "Execute failed: " . $disbStmt->error, [
        "step" => "disb_execute"
    ], 500);
}

$disbRes = $disbStmt->get_result();
$disbRow = $disbRes->fetch_assoc();
$disbStmt->close();

if (!$disbRow) {
    respond(false, "No matching loan_disbursement record found in CORE1.", [
        "step" => "disb_not_found",
        "application_id" => $applicationId
    ], 404);
}

// ======================================================
// VALIDATE USER MATCH
// ======================================================
$coreUserId = (int)($disbRow['user_id'] ?? 0);
if ($coreUserId > 0 && $coreUserId !== $userId) {
    respond(false, "User mismatch between financial record and CORE1 disbursement.", [
        "step" => "user_mismatch",
        "core_user_id" => $coreUserId,
        "financial_user_id" => $userId
    ], 422);
}

// ======================================================
// RESOLVE VALUES
// ======================================================
$resolvedLoanAmount = (float)($disbRow['principal_amount'] ?? 0);
if ($resolvedLoanAmount <= 0) {
    $resolvedLoanAmount = $loanAmountFromFin;
}

if ($resolvedLoanAmount <= 0) {
    respond(false, "Invalid principal amount.", [
        "step" => "invalid_principal_amount"
    ], 422);
}

$termMonths = (int)($disbRow['term_months'] ?? 0);
if ($termMonths <= 0) {
    $termMonths = 1;
}

$monthlyDue = (float)($disbRow['monthly_due'] ?? 0);
if ($monthlyDue <= 0) {
    $monthlyDue = round($resolvedLoanAmount / max($termMonths, 1), 2);
}

$interestRate = (float)($disbRow['interest_rate'] ?? 0);

$interestMethod = strtoupper(trim((string)($disbRow['interest_method'] ?? 'FLAT')));
if (!in_array($interestMethod, ['FLAT', 'DIMINISHING'])) {
    $interestMethod = 'FLAT';
}

// ======================================================
// CHECK IF LOAN ALREADY EXISTS
// ======================================================
$loanCheckSql = "
    SELECT id, status
    FROM loans
    WHERE application_id = ?
    LIMIT 1
";

$loanCheck = $conn->prepare($loanCheckSql);
if (!$loanCheck) {
    respond(false, "Prepare failed: " . $conn->error, [
        "step" => "loan_check_prepare"
    ], 500);
}

$loanCheck->bind_param("i", $applicationId);

if (!$loanCheck->execute()) {
    respond(false, "Execute failed: " . $loanCheck->error, [
        "step" => "loan_check_execute"
    ], 500);
}

$loanRes = $loanCheck->get_result();
$existingLoan = $loanRes->fetch_assoc();
$loanCheck->close();

// ======================================================
// COMPUTE DATES
// ======================================================
$startDate   = date('Y-m-d');
$nextPayment = date('Y-m-d', strtotime('+1 month'));
$dueDate     = date('Y-m-d', strtotime('+' . $termMonths . ' month'));

// ======================================================
// MAIN TRANSACTION
// ======================================================
$conn->begin_transaction();

try {
    // 1) update CORE1 loan_disbursement status
    $updDisb = $conn->prepare("
        UPDATE loan_disbursement
        SET status = 'DISBURSED'
        WHERE application_id = ?
    ");

    if (!$updDisb) {
        throw new Exception("Prepare failed on loan_disbursement update: " . $conn->error);
    }

    $updDisb->bind_param("i", $applicationId);

    if (!$updDisb->execute()) {
        throw new Exception("Execute failed on loan_disbursement update: " . $updDisb->error);
    }

    $updDisb->close();

    // 2) if loan already exists, update it instead of duplicating
    if ($existingLoan) {
        $loanId = (int)$existingLoan['id'];

        $updLoan = $conn->prepare("
            UPDATE loans
            SET
                user_id = ?,
                loan_amount = ?,
                term_months = ?,
                monthly_due = ?,
                interest_rate = ?,
                interest_method = ?,
                outstanding = ?,
                next_payment = ?,
                due_date = ?,
                start_date = ?,
                status = 'ACTIVE'
            WHERE application_id = ?
        ");

        if (!$updLoan) {
            throw new Exception("Prepare failed on loans update: " . $conn->error);
        }

        $updLoan->bind_param(
            "ididdsdsssi",
            $userId,
            $resolvedLoanAmount,
            $termMonths,
            $monthlyDue,
            $interestRate,
            $interestMethod,
            $resolvedLoanAmount,
            $nextPayment,
            $dueDate,
            $startDate,
            $applicationId
        );

        if (!$updLoan->execute()) {
            throw new Exception("Execute failed on loans update: " . $updLoan->error);
        }

        $updLoan->close();

        $conn->commit();

        respond(true, "Existing loan updated and activated.", [
            "step" => "updated_existing_loan",
            "loan_id" => $loanId,
            "application_id" => $applicationId,
            "financial_record_id" => $financialRecordId,
            "disbursement_status" => "DISBURSED",
            "loan_status" => "ACTIVE"
        ]);
    }

    // 3) insert new active loan
    $insertLoan = $conn->prepare("
        INSERT INTO loans
        (
            user_id,
            application_id,
            loan_amount,
            term_months,
            monthly_due,
            interest_rate,
            interest_method,
            outstanding,
            next_payment,
            due_date,
            start_date,
            status
        )
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'ACTIVE')
    ");

    if (!$insertLoan) {
        throw new Exception("Prepare failed on loans insert: " . $conn->error);
    }

    $insertLoan->bind_param(
        "iididdsdsss",
        $userId,
        $applicationId,
        $resolvedLoanAmount,
        $termMonths,
        $monthlyDue,
        $interestRate,
        $interestMethod,
        $resolvedLoanAmount,
        $nextPayment,
        $dueDate,
        $startDate
    );

    if (!$insertLoan->execute()) {
        throw new Exception("Execute failed on loans insert: " . $insertLoan->error);
    }

    $newLoanId = $insertLoan->insert_id;
    $insertLoan->close();

    $conn->commit();

    respond(true, "Loan inserted and activated successfully.", [
        "step" => "inserted_new_loan",
        "loan_id" => $newLoanId,
        "application_id" => $applicationId,
        "financial_record_id" => $financialRecordId,
        "disbursement_status" => "DISBURSED",
        "loan_status" => "ACTIVE"
    ]);

} catch (Throwable $e) {
    $conn->rollback();
    respond(false, "Transaction failed: " . $e->getMessage(), [
        "step" => "transaction_catch"
    ], 500);
}