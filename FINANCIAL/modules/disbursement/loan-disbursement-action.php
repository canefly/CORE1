<?php
session_start();

/*
    Adjust mo ito sa actual DB include mo.
    Example:
    require_once __DIR__ . "/../../config/db.php";
*/
require_once __DIR__ . "/../../config/db.php";

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

function peso($value): string
{
    return number_format((float)$value, 2, '.', '');
}

function ensureDisbursedAtColumn(mysqli $conn): void
{
    $check = $conn->query("SHOW COLUMNS FROM loan_disbursement LIKE 'disbursed_at'");
    if ($check && $check->num_rows === 0) {
        $conn->query("ALTER TABLE loan_disbursement ADD COLUMN disbursed_at DATETIME NULL AFTER status");
    }
}

try {
    ensureDisbursedAtColumn($conn);

    $action = $_GET['action'] ?? $_POST['action'] ?? '';

    if ($action === 'stats') {
        $stats = [
            'pending_amount'   => 0,
            'disbursed_today'  => 0,
            'waiting_count'    => 0,
        ];

        $sqlPending = "
            SELECT 
                COALESCE(SUM(principal_amount), 0) AS total_amount,
                COUNT(*) AS total_count
            FROM loan_disbursement
            WHERE status = 'WAITING FOR DISBURSEMENT'
        ";
        $resPending = $conn->query($sqlPending);
        $rowPending = $resPending->fetch_assoc();

        $sqlToday = "
            SELECT COALESCE(SUM(principal_amount), 0) AS total_today
            FROM loan_disbursement
            WHERE status = 'DISBURSED'
              AND DATE(disbursed_at) = CURDATE()
        ";
        $resToday = $conn->query($sqlToday);
        $rowToday = $resToday->fetch_assoc();

        $stats['pending_amount']  = (float)($rowPending['total_amount'] ?? 0);
        $stats['waiting_count']   = (int)($rowPending['total_count'] ?? 0);
        $stats['disbursed_today'] = (float)($rowToday['total_today'] ?? 0);

        respond(true, 'Stats loaded.', ['stats' => $stats]);
    }

    if ($action === 'list_pending') {
        $search = trim($_GET['search'] ?? '');

        $sql = "
            SELECT 
                ld.id,
                ld.application_id,
                ld.user_id,
                ld.principal_amount,
                ld.term_months,
                ld.monthly_due,
                ld.status,
                ld.created_at,
                COALESCE(u.fullname, CONCAT('User #', ld.user_id)) AS borrower_name
            FROM loan_disbursement ld
            LEFT JOIN users u ON u.id = ld.user_id
            WHERE ld.status = 'WAITING FOR DISBURSEMENT'
        ";

        $types = '';
        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.fullname LIKE ? OR CAST(ld.application_id AS CHAR) LIKE ?)";
            $searchLike = "%{$search}%";
            $types .= 'ss';
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $sql .= " ORDER BY ld.created_at DESC, ld.id DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();

        respond(true, 'Pending list loaded.', ['rows' => $rows]);
    }

    if ($action === 'list_history') {
        $search = trim($_GET['search'] ?? '');

        $sql = "
            SELECT 
                ld.id,
                ld.application_id,
                ld.user_id,
                ld.principal_amount,
                ld.term_months,
                ld.monthly_due,
                ld.status,
                ld.disbursed_at,
                COALESCE(u.fullname, CONCAT('User #', ld.user_id)) AS borrower_name
            FROM loan_disbursement ld
            LEFT JOIN users u ON u.id = ld.user_id
            WHERE ld.status = 'DISBURSED'
        ";

        $types = '';
        $params = [];

        if ($search !== '') {
            $sql .= " AND (u.fullname LIKE ? OR CAST(ld.application_id AS CHAR) LIKE ?)";
            $searchLike = "%{$search}%";
            $types .= 'ss';
            $params[] = $searchLike;
            $params[] = $searchLike;
        }

        $sql .= " ORDER BY ld.disbursed_at DESC, ld.id DESC";

        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();

        respond(true, 'History loaded.', ['rows' => $rows]);
    }

    if ($action === 'view') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            respond(false, 'Invalid disbursement ID.');
        }

        $stmt = $conn->prepare("
            SELECT 
                ld.*,
                COALESCE(u.fullname, CONCAT('User #', ld.user_id)) AS borrower_name
            FROM loan_disbursement ld
            LEFT JOIN users u ON u.id = ld.user_id
            WHERE ld.id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            respond(false, 'Disbursement record not found.');
        }

        $loanId = null;
        $stmtLoan = $conn->prepare("SELECT id, status FROM loans WHERE application_id = ? LIMIT 1");
        $stmtLoan->bind_param("i", $row['application_id']);
        $stmtLoan->execute();
        $loanRes = $stmtLoan->get_result();
        $loanRow = $loanRes->fetch_assoc();
        $stmtLoan->close();

        if ($loanRow) {
            $loanId = $loanRow['id'];
        }

        respond(true, 'Details loaded.', [
            'row' => $row,
            'loan_id' => $loanId
        ]);
    }

    if ($action === 'mark_disbursed') {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            respond(false, 'Invalid request method.');
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            respond(false, 'Invalid disbursement ID.');
        }

        $conn->begin_transaction();

        $stmt = $conn->prepare("
            SELECT *
            FROM loan_disbursement
            WHERE id = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $disb = $result->fetch_assoc();
        $stmt->close();

        if (!$disb) {
            $conn->rollback();
            respond(false, 'Disbursement record not found.');
        }

        if ($disb['status'] === 'DISBURSED') {
            $stmtLoan = $conn->prepare("SELECT id FROM loans WHERE application_id = ? LIMIT 1");
            $stmtLoan->bind_param("i", $disb['application_id']);
            $stmtLoan->execute();
            $loanRes = $stmtLoan->get_result();
            $loan = $loanRes->fetch_assoc();
            $stmtLoan->close();

            $conn->commit();

            respond(true, 'Already disbursed.', [
                'loan_id' => $loan['id'] ?? null
            ]);
        }

        $newStatus = 'DISBURSED';
        $stmtUpdateDisb = $conn->prepare("
            UPDATE loan_disbursement
            SET status = ?, disbursed_at = NOW(), updated_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");
        $stmtUpdateDisb->bind_param("si", $newStatus, $id);
        $stmtUpdateDisb->execute();
        $stmtUpdateDisb->close();

        $stmtCheckLoan = $conn->prepare("
            SELECT id
            FROM loans
            WHERE application_id = ?
            LIMIT 1
            FOR UPDATE
        ");
        $stmtCheckLoan->bind_param("i", $disb['application_id']);
        $stmtCheckLoan->execute();
        $loanResult = $stmtCheckLoan->get_result();
        $existingLoan = $loanResult->fetch_assoc();
        $stmtCheckLoan->close();

        $startDate = date('Y-m-d');
        $activeStatus = 'ACTIVE';
        $loanAmount = (float)$disb['principal_amount'];
        $outstanding = (float)$disb['principal_amount'];

        if ($existingLoan) {
            $stmtUpdateLoan = $conn->prepare("
                UPDATE loans
                SET user_id = ?,
                    loan_amount = ?,
                    term_months = ?,
                    monthly_due = ?,
                    interest_rate = ?,
                    interest_method = ?,
                    outstanding = ?,
                    start_date = ?,
                    status = ?
                WHERE application_id = ?
                LIMIT 1
            ");

            $stmtUpdateLoan->bind_param(
                "ididdssdsi",
                $disb['user_id'],
                $loanAmount,
                $disb['term_months'],
                $disb['monthly_due'],
                $disb['interest_rate'],
                $disb['interest_method'],
                $outstanding,
                $startDate,
                $activeStatus,
                $disb['application_id']
            );
            $stmtUpdateLoan->execute();
            $loanId = $existingLoan['id'];
            $stmtUpdateLoan->close();
        } else {
            $stmtInsertLoan = $conn->prepare("
                INSERT INTO loans (
                    user_id,
                    application_id,
                    loan_amount,
                    term_months,
                    monthly_due,
                    interest_rate,
                    interest_method,
                    outstanding,
                    start_date,
                    status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtInsertLoan->bind_param(
                "iididdsdss",
                $disb['user_id'],
                $disb['application_id'],
                $loanAmount,
                $disb['term_months'],
                $disb['monthly_due'],
                $disb['interest_rate'],
                $disb['interest_method'],
                $outstanding,
                $startDate,
                $activeStatus
            );
            $stmtInsertLoan->execute();
            $loanId = $stmtInsertLoan->insert_id;
            $stmtInsertLoan->close();
        }

        $conn->commit();

        respond(true, 'Loan disbursed successfully. Loan is now ACTIVE.', [
            'loan_id' => $loanId
        ]);
    }

    respond(false, 'Unknown action.');
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
        }
    }

    respond(false, 'Error: ' . $e->getMessage());
}