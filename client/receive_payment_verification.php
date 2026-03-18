<?php
declare(strict_types=1);

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

$logFile = __DIR__ . '/debug_receive_payment_verification.log';

function dbg_verify(string $message): void
{
    global $logFile;
    @file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function fail_response(int $httpCode, string $message, array $extra = []): void
{
    http_response_code($httpCode);
    echo json_encode(array_merge([
        'success' => false,
        'message' => $message,
    ], $extra));
    exit;
}

function success_response(string $message, array $extra = []): void
{
    http_response_code(200);
    echo json_encode(array_merge([
        'success' => true,
        'message' => $message,
    ], $extra));
    exit;
}

function getCore1Connection(): mysqli
{
    $configPath = __DIR__ . '/include/config.php';

    if (!file_exists($configPath)) {
        throw new Exception('Database config not found: ' . $configPath);
    }

    require $configPath;

    if (!isset($conn) || !($conn instanceof mysqli)) {
        throw new Exception('No valid MySQLi connection found from include/config.php');
    }

    if ($conn->connect_error) {
        throw new Exception('MySQLi connection failed: ' . $conn->connect_error);
    }

    $conn->set_charset('utf8mb4');

    return $conn;
}

function tableExists(mysqli $conn, string $table): bool
{
    $safeTable = $conn->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '{$safeTable}'";
    $result = $conn->query($sql);
    return $result instanceof mysqli_result && $result->num_rows > 0;
}

function getTableColumns(mysqli $conn, string $table): array
{
    $columns = [];

    $safeTable = str_replace('`', '``', $table);
    $result = $conn->query("SHOW COLUMNS FROM `{$safeTable}`");

    if (!$result) {
        return $columns;
    }

    while ($row = $result->fetch_assoc()) {
        $columns[$row['Field']] = true;
    }

    $result->free();

    return $columns;
}

function hasColumn(array $columns, string $name): bool
{
    return isset($columns[$name]);
}

function parseTransactionIdFromPaymentNumber(?string $paymentNumber): int
{
    $paymentNumber = trim((string)$paymentNumber);

    if ($paymentNumber === '') {
        return 0;
    }

    if (preg_match('/CORE1-TXN-(\d+)/i', $paymentNumber, $matches)) {
        return (int)$matches[1];
    }

    return 0;
}

function normalizeDateTime(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }

    return date('Y-m-d H:i:s', $ts);
}

function normalizeStatus(?string $status): string
{
    $status = strtoupper(trim((string)$status));

    $map = [
        'SUCCESS' => 'SUCCESS',
        'COMPLETED' => 'SUCCESS',
        'PAID' => 'SUCCESS',
        'PAID_PENDING' => 'PAID_PENDING',
        'PENDING' => 'PENDING',
        'FAILED' => 'FAILED',
    ];

    return $map[$status] ?? 'SUCCESS';
}

function normalizeDateOnly(?string $value): ?string
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $ts = strtotime($value);
    if ($ts === false) {
        return null;
    }

    return date('Y-m-d', $ts);
}

function fetchTransactionById(mysqli $conn, int $transactionId): ?array
{
    $stmt = $conn->prepare("SELECT * FROM transactions WHERE id = ? LIMIT 1");
    if (!$stmt) {
        throw new Exception('Prepare failed while fetching transaction by id: ' . $conn->error);
    }

    $stmt->bind_param('i', $transactionId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    return $row ?: null;
}

function fetchTransactionFallback(
    mysqli $conn,
    array $transactionColumns,
    int $loanId,
    float $amount,
    ?string $receiptNumber,
    ?string $paymentDate
): ?array {
    $conditions = [];
    $types = '';
    $params = [];

    if ($loanId > 0 && hasColumn($transactionColumns, 'loan_id')) {
        $conditions[] = 'loan_id = ?';
        $types .= 'i';
        $params[] = $loanId;
    }

    if ($amount > 0 && hasColumn($transactionColumns, 'amount')) {
        $conditions[] = 'amount = ?';
        $types .= 'd';
        $params[] = $amount;
    }

    if (!empty($receiptNumber) && hasColumn($transactionColumns, 'receipt_number')) {
        $conditions[] = 'receipt_number = ?';
        $types .= 's';
        $params[] = $receiptNumber;
    }

    if (!empty($paymentDate) && hasColumn($transactionColumns, 'trans_date')) {
        $conditions[] = 'DATE(trans_date) = DATE(?)';
        $types .= 's';
        $params[] = $paymentDate;
    }

    if (empty($conditions)) {
        return null;
    }

    $sql = "SELECT * FROM transactions WHERE " . implode(' AND ', $conditions) . " ORDER BY id DESC LIMIT 1";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception('Prepare failed while fallback searching transaction: ' . $conn->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;

    $stmt->close();

    return $row ?: null;
}

function updateTransaction(
    mysqli $conn,
    array $transactionColumns,
    int $transactionId,
    string $status,
    ?string $receiptNumber,
    ?string $paymentDateTime,
    ?string $referenceNumber,
    int $financialPaymentId
): void {
    $setParts = [];
    $types = '';
    $params = [];

    if (hasColumn($transactionColumns, 'status')) {
        $setParts[] = 'status = ?';
        $types .= 's';
        $params[] = $status;
    }

    if ($receiptNumber !== null && $receiptNumber !== '' && hasColumn($transactionColumns, 'receipt_number')) {
        $setParts[] = 'receipt_number = ?';
        $types .= 's';
        $params[] = $receiptNumber;
    }

    if ($paymentDateTime !== null && hasColumn($transactionColumns, 'trans_date')) {
        $setParts[] = 'trans_date = ?';
        $types .= 's';
        $params[] = $paymentDateTime;
    }

    if ($referenceNumber !== null && $referenceNumber !== '') {
        if (hasColumn($transactionColumns, 'paymongo_payment_id')) {
            $setParts[] = 'paymongo_payment_id = ?';
            $types .= 's';
            $params[] = $referenceNumber;
        } elseif (hasColumn($transactionColumns, 'provider_reference')) {
            $setParts[] = 'provider_reference = ?';
            $types .= 's';
            $params[] = $referenceNumber;
        }
    }

    if ($financialPaymentId > 0 && hasColumn($transactionColumns, 'financial_payment_id')) {
        $setParts[] = 'financial_payment_id = ?';
        $types .= 'i';
        $params[] = $financialPaymentId;
    }

    if (hasColumn($transactionColumns, 'updated_at')) {
        $setParts[] = 'updated_at = NOW()';
    }

    if (empty($setParts)) {
        throw new Exception('transactions table has no compatible columns to update.');
    }

    $sql = "UPDATE transactions SET " . implode(', ', $setParts) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $transactionId;

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception('Prepare failed while updating transaction: ' . $conn->error);
    }

    $stmt->bind_param($types, ...$params);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Failed to update CORE1 transaction: ' . $error);
    }

    $stmt->close();
}

function updateLoanAfterVerifiedPayment(
    mysqli $conn,
    array $loanColumns,
    int $loanId,
    float $paymentAmount
): void {
    if ($loanId <= 0) {
        throw new Exception('Invalid loan_id for loan update.');
    }

    if ($paymentAmount <= 0) {
        throw new Exception('Invalid payment amount for loan update.');
    }

    $stmt = $conn->prepare("SELECT * FROM loans WHERE id = ? LIMIT 1 FOR UPDATE");
    if (!$stmt) {
        throw new Exception('Prepare failed while fetching loan: ' . $conn->error);
    }

    $stmt->bind_param('i', $loanId);
    $stmt->execute();

    $result = $stmt->get_result();
    $loan = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$loan) {
        throw new Exception('Loan not found for loan_id=' . $loanId);
    }

    $currentOutstanding = isset($loan['outstanding']) ? (float)$loan['outstanding'] : 0.00;
    $currentNextPayment = isset($loan['next_payment']) ? trim((string)$loan['next_payment']) : '';
    $monthlyDue = isset($loan['monthly_due']) ? (float)$loan['monthly_due'] : 0.00;

    $newOutstanding = $currentOutstanding - $paymentAmount;
    if ($newOutstanding < 0) {
        $newOutstanding = 0.00;
    }

    $newStatus = $newOutstanding <= 0 ? 'COMPLETED' : 'ACTIVE';
    $newNextPayment = $currentNextPayment !== '' ? $currentNextPayment : null;

    if ($newOutstanding <= 0) {
        $newNextPayment = null;
    } else {
        if (
            $monthlyDue > 0 &&
            $paymentAmount >= $monthlyDue &&
            $currentNextPayment !== '' &&
            $currentNextPayment !== '0000-00-00'
        ) {
            $monthsAdvance = (int)floor($paymentAmount / $monthlyDue);
            if ($monthsAdvance < 1) {
                $monthsAdvance = 1;
            }

            $baseDate = strtotime($currentNextPayment);
            if ($baseDate === false) {
                $baseDate = time();
            }

            $newNextPayment = date('Y-m-d', strtotime('+' . $monthsAdvance . ' month', $baseDate));
        }
    }

    $setParts = [];
    $types = '';
    $params = [];

    if (hasColumn($loanColumns, 'outstanding')) {
        $setParts[] = 'outstanding = ?';
        $types .= 'd';
        $params[] = $newOutstanding;
    }

    if (hasColumn($loanColumns, 'next_payment')) {
        $setParts[] = 'next_payment = ?';
        $types .= 's';
        $params[] = $newNextPayment;
    }

    if (hasColumn($loanColumns, 'status')) {
        $setParts[] = 'status = ?';
        $types .= 's';
        $params[] = $newStatus;
    }

    if (hasColumn($loanColumns, 'updated_at')) {
        $setParts[] = 'updated_at = NOW()';
    }

    if (empty($setParts)) {
        throw new Exception('loans table has no compatible columns to update.');
    }

    $sql = "UPDATE loans SET " . implode(', ', $setParts) . " WHERE id = ?";
    $types .= 'i';
    $params[] = $loanId;

    $upd = $conn->prepare($sql);
    if (!$upd) {
        throw new Exception('Prepare failed while updating loan: ' . $conn->error);
    }

    $upd->bind_param($types, ...$params);

    if (!$upd->execute()) {
        $error = $upd->error;
        $upd->close();
        throw new Exception('Failed to update loan balances: ' . $error);
    }

    $upd->close();

    dbg_verify(
        'REGULAR LOAN UPDATED loan_id=' . $loanId .
        ', old_outstanding=' . number_format($currentOutstanding, 2, '.', '') .
        ', payment=' . number_format($paymentAmount, 2, '.', '') .
        ', new_outstanding=' . number_format($newOutstanding, 2, '.', '') .
        ', new_status=' . $newStatus .
        ', new_next_payment=' . ($newNextPayment ?? 'NULL')
    );
}

function updateRestructuredLoanAfterVerifiedPayment(
    mysqli $conn,
    int $restructuredLoanId,
    float $paymentAmount
): void {
    if ($restructuredLoanId <= 0) {
        throw new Exception('Invalid restructured_loan_id for restructured loan update.');
    }

    if ($paymentAmount <= 0) {
        throw new Exception('Invalid payment amount for restructured loan update.');
    }

    $stmt = $conn->prepare("SELECT * FROM restructured_loans WHERE id = ? LIMIT 1 FOR UPDATE");
    if (!$stmt) {
        throw new Exception('Prepare failed while fetching restructured loan: ' . $conn->error);
    }

    $stmt->bind_param('i', $restructuredLoanId);
    $stmt->execute();

    $result = $stmt->get_result();
    $loan = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$loan) {
        throw new Exception('Restructured loan not found for id=' . $restructuredLoanId);
    }

    $currentOutstanding = isset($loan['outstanding']) ? (float)$loan['outstanding'] : 0.00;
    $currentNextPayment = isset($loan['next_payment']) ? trim((string)$loan['next_payment']) : '';
    $monthlyDue = isset($loan['monthly_due']) ? (float)$loan['monthly_due'] : 0.00;

    $newOutstanding = $currentOutstanding - $paymentAmount;
    if ($newOutstanding < 0) {
        $newOutstanding = 0.00;
    }

    $newStatus = $newOutstanding <= 0 ? 'COMPLETED' : 'ACTIVE';
    $newNextPayment = $currentNextPayment !== '' ? $currentNextPayment : null;

    if ($newOutstanding <= 0) {
        $newNextPayment = null;
    } else {
        if (
            $monthlyDue > 0 &&
            $paymentAmount >= $monthlyDue &&
            $currentNextPayment !== '' &&
            $currentNextPayment !== '0000-00-00'
        ) {
            $monthsAdvance = (int)floor($paymentAmount / $monthlyDue);
            if ($monthsAdvance < 1) {
                $monthsAdvance = 1;
            }

            $baseDate = strtotime($currentNextPayment);
            if ($baseDate === false) {
                $baseDate = time();
            }

            $newNextPayment = date('Y-m-d', strtotime('+' . $monthsAdvance . ' month', $baseDate));
        }
    }

    $sql = "UPDATE restructured_loans SET outstanding = ?, next_payment = ?, status = ? WHERE id = ?";
    $upd = $conn->prepare($sql);
    if (!$upd) {
        throw new Exception('Prepare failed while updating restructured loan: ' . $conn->error);
    }

    $upd->bind_param('dssi', $newOutstanding, $newNextPayment, $newStatus, $restructuredLoanId);

    if (!$upd->execute()) {
        $error = $upd->error;
        $upd->close();
        throw new Exception('Failed to update restructured loan balances: ' . $error);
    }

    $upd->close();

    dbg_verify(
        'RESTRUCTURED LOAN UPDATED restructured_loan_id=' . $restructuredLoanId .
        ', old_outstanding=' . number_format($currentOutstanding, 2, '.', '') .
        ', payment=' . number_format($paymentAmount, 2, '.', '') .
        ', new_outstanding=' . number_format($newOutstanding, 2, '.', '') .
        ', new_status=' . $newStatus .
        ', new_next_payment=' . ($newNextPayment ?? 'NULL')
    );
}

/* =========================
   ADDED WALLET SUPPORT ONLY
   ========================= */

function fetchWalletTransactionByReference(
    mysqli $conn,
    string $referenceNo
): ?array {
    if ($referenceNo === '') {
        return null;
    }

    $stmt = $conn->prepare("
        SELECT *
        FROM wallet_transactions
        WHERE reference_no = ?
        ORDER BY id DESC
        LIMIT 1
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed while fetching wallet transaction by reference: ' . $conn->error);
    }

    $stmt->bind_param('s', $referenceNo);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function updateWalletTransactionSyncStatus(
    mysqli $conn,
    int $walletTransactionId,
    string $syncStatus,
    ?string $syncError = null
): void {
    if ($walletTransactionId <= 0) {
        return;
    }

    $stmt = $conn->prepare("
        UPDATE wallet_transactions
        SET sync_status = ?, sync_error = ?, updated_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed while updating wallet transaction sync status: ' . $conn->error);
    }

    $stmt->bind_param('ssi', $syncStatus, $syncError, $walletTransactionId);

    if (!$stmt->execute()) {
        $error = $stmt->error;
        $stmt->close();
        throw new Exception('Failed to update wallet transaction sync status: ' . $error);
    }

    $stmt->close();
}

function walletReversalAlreadyExists(
    mysqli $conn,
    string $referenceNo
): bool {
    if ($referenceNo === '') {
        return false;
    }

    $reversalRef = $referenceNo . '-REVERSAL';

    $stmt = $conn->prepare("
        SELECT id
        FROM wallet_transactions
        WHERE reference_no = ?
        LIMIT 1
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed while checking wallet reversal: ' . $conn->error);
    }

    $stmt->bind_param('s', $reversalRef);
    $stmt->execute();

    $result = $stmt->get_result();
    $exists = $result && $result->num_rows > 0;
    $stmt->close();

    return $exists;
}

function reverseWalletPaymentIfNeeded(
    mysqli $conn,
    array $transaction,
    float $amount
): void {
    $providerMethod = strtoupper(trim((string)($transaction['provider_method'] ?? '')));
    if ($providerMethod !== 'WALLET') {
        dbg_verify('Wallet reversal skipped: provider_method is not WALLET.');
        return;
    }

    $receiptNumber = trim((string)($transaction['receipt_number'] ?? ''));
    if ($receiptNumber === '') {
        dbg_verify('Wallet reversal skipped: receipt_number missing.');
        return;
    }

    $walletTx = fetchWalletTransactionByReference($conn, $receiptNumber);
    if (!$walletTx) {
        dbg_verify('Wallet reversal skipped: original wallet transaction not found for reference=' . $receiptNumber);
        return;
    }

    $walletTransactionId = (int)($walletTx['id'] ?? 0);
    $walletAccountId = (int)($walletTx['wallet_account_id'] ?? 0);
    $walletUserId = (int)($walletTx['user_id'] ?? 0);

    if ($walletTransactionId <= 0 || $walletAccountId <= 0 || $walletUserId <= 0) {
        dbg_verify('Wallet reversal skipped: invalid wallet transaction/account/user metadata.');
        return;
    }

    if (walletReversalAlreadyExists($conn, $receiptNumber)) {
        dbg_verify('Wallet reversal skipped: reversal already exists for reference=' . $receiptNumber);
        return;
    }

    $stmt = $conn->prepare("
        SELECT id, balance
        FROM wallet_accounts
        WHERE id = ?
        LIMIT 1
        FOR UPDATE
    ");
    if (!$stmt) {
        throw new Exception('Prepare failed while locking wallet account for reversal: ' . $conn->error);
    }

    $stmt->bind_param('i', $walletAccountId);
    $stmt->execute();

    $result = $stmt->get_result();
    $walletAccount = $result ? $result->fetch_assoc() : null;
    $stmt->close();

    if (!$walletAccount) {
        throw new Exception('Wallet account not found for reversal. wallet_account_id=' . $walletAccountId);
    }

    $currentBalance = (float)($walletAccount['balance'] ?? 0.00);
    $newBalance = $currentBalance + $amount;

    $upd = $conn->prepare("
        UPDATE wallet_accounts
        SET balance = ?, updated_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    if (!$upd) {
        throw new Exception('Prepare failed while updating wallet balance reversal: ' . $conn->error);
    }

    $upd->bind_param('di', $newBalance, $walletAccountId);

    if (!$upd->execute()) {
        $error = $upd->error;
        $upd->close();
        throw new Exception('Failed to reverse wallet balance: ' . $error);
    }
    $upd->close();

    $transactionType = 'ADJUSTMENT';
    $runningBalance = $newBalance;
    $referenceNo = $receiptNumber . '-REVERSAL';
    $remarks = 'Auto reversal after failed/rejected financial verification for wallet payment';
    $status = 'SUCCESS';
    $syncStatus = 'FAILED';
    $syncError = 'Financial verification failed/rejected; wallet amount returned to client.';
    $loanId = isset($transaction['loan_id']) && (int)$transaction['loan_id'] > 0 ? (int)$transaction['loan_id'] : null;
    $restructuredLoanId = isset($transaction['restructured_loan_id']) && (int)$transaction['restructured_loan_id'] > 0 ? (int)$transaction['restructured_loan_id'] : null;

    $ins = $conn->prepare("
        INSERT INTO wallet_transactions (
            wallet_account_id,
            user_id,
            loan_id,
            restructured_loan_id,
            transaction_type,
            amount,
            running_balance,
            reference_no,
            remarks,
            status,
            sync_status,
            sync_error
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$ins) {
        throw new Exception('Prepare failed while inserting wallet reversal transaction: ' . $conn->error);
    }

    $ins->bind_param(
        'iiiisddsssss',
        $walletAccountId,
        $walletUserId,
        $loanId,
        $restructuredLoanId,
        $transactionType,
        $amount,
        $runningBalance,
        $referenceNo,
        $remarks,
        $status,
        $syncStatus,
        $syncError
    );

    if (!$ins->execute()) {
        $error = $ins->error;
        $ins->close();
        throw new Exception('Failed to insert wallet reversal transaction: ' . $error);
    }
    $ins->close();

    updateWalletTransactionSyncStatus(
        $conn,
        $walletTransactionId,
        'FAILED',
        'Financial verification failed/rejected; wallet payment reversed automatically.'
    );

    dbg_verify(
        'WALLET PAYMENT REVERSED wallet_tx_id=' . $walletTransactionId .
        ', wallet_account_id=' . $walletAccountId .
        ', amount=' . number_format($amount, 2, '.', '') .
        ', old_balance=' . number_format($currentBalance, 2, '.', '') .
        ', new_balance=' . number_format($newBalance, 2, '.', '') .
        ', reference=' . $receiptNumber
    );
}

/* =========================
   END ADDED WALLET SUPPORT
   ========================= */

try {
    $conn = getCore1Connection();
    dbg_verify('Receiver started successfully.');
} catch (Throwable $e) {
    dbg_verify('DB connection failed: ' . $e->getMessage());
    fail_response(500, 'Database connection failed: ' . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail_response(405, 'Method not allowed. Use POST.');
}

try {
    if (!tableExists($conn, 'transactions')) {
        throw new Exception('transactions table not found in CORE1 database.');
    }

    if (!tableExists($conn, 'loans')) {
        throw new Exception('loans table not found in CORE1 database.');
    }

    if (!tableExists($conn, 'restructured_loans')) {
        throw new Exception('restructured_loans table not found in CORE1 database.');
    }

    if (!tableExists($conn, 'wallet_accounts')) {
        dbg_verify('wallet_accounts table not found. Wallet reversal features will be skipped if not needed.');
    }

    if (!tableExists($conn, 'wallet_transactions')) {
        dbg_verify('wallet_transactions table not found. Wallet sync/reversal features will be skipped if not needed.');
    }

    $transactionColumns = getTableColumns($conn, 'transactions');
    $loanColumns = getTableColumns($conn, 'loans');

    $rawInput = file_get_contents('php://input');
    dbg_verify('RAW INPUT: ' . ($rawInput !== false ? $rawInput : 'false'));

    if ($rawInput === false || trim($rawInput) === '') {
        throw new Exception('Empty request body.');
    }

    $data = json_decode($rawInput, true);

    if (!is_array($data)) {
        throw new Exception('Invalid JSON payload.');
    }

    $token = trim((string)($data['token'] ?? ''));
    $expectedToken = 'core1_financial_payment_verify_secret_key';

    if ($token !== $expectedToken) {
        fail_response(401, 'Invalid token.');
    }

    $financialPaymentId = (int)($data['payment_id'] ?? 0);
    $paymentNumber = trim((string)($data['payment_number'] ?? ''));
    $receiptNumber = trim((string)($data['receipt_number'] ?? ''));
    $referenceNumber = trim((string)($data['reference_number'] ?? ''));
    $loanId = (int)($data['loan_id'] ?? 0);
    $clientId = (int)($data['client_id'] ?? 0);
    $amount = (float)($data['amount'] ?? 0);
    $paymentDateRaw = trim((string)($data['payment_date'] ?? ''));
    $statusRaw = trim((string)($data['status'] ?? ''));
    $financialPaymentStatus = trim((string)($data['financial_payment_status'] ?? ''));

    $transactionId = parseTransactionIdFromPaymentNumber($paymentNumber);
    $normalizedStatus = normalizeStatus($statusRaw);
    $paymentDateTime = normalizeDateTime($paymentDateRaw);
    $paymentDateOnly = normalizeDateOnly($paymentDateRaw);

    dbg_verify(
        'Parsed payload => payment_id=' . $financialPaymentId .
        ', payment_number=' . $paymentNumber .
        ', transaction_id=' . $transactionId .
        ', loan_id=' . $loanId .
        ', client_id=' . $clientId .
        ', amount=' . number_format($amount, 2, '.', '') .
        ', status=' . $normalizedStatus .
        ', financial_payment_status=' . $financialPaymentStatus
    );

    $conn->begin_transaction();

    $transaction = null;

    if ($transactionId > 0) {
        $transaction = fetchTransactionById($conn, $transactionId);
    }

    if (!$transaction) {
        $transaction = fetchTransactionFallback(
            $conn,
            $transactionColumns,
            $loanId,
            $amount,
            $receiptNumber !== '' ? $receiptNumber : null,
            $paymentDateOnly
        );
    }

    if (!$transaction) {
        throw new Exception(
            'No matching CORE1 transaction found. payment_number=' . $paymentNumber .
            ', loan_id=' . $loanId .
            ', amount=' . number_format($amount, 2, '.', '')
        );
    }

    $resolvedTransactionId = (int)$transaction['id'];
    $oldTransactionStatus = strtoupper(trim((string)($transaction['status'] ?? '')));
    $providerMethod = strtoupper(trim((string)($transaction['provider_method'] ?? '')));

    dbg_verify(
        'Matched transaction => id=' . $resolvedTransactionId .
        ', loan_id=' . ((isset($transaction['loan_id']) && $transaction['loan_id'] !== null) ? (string)$transaction['loan_id'] : 'NULL') .
        ', restructured_loan_id=' . ((isset($transaction['restructured_loan_id']) && $transaction['restructured_loan_id'] !== null) ? (string)$transaction['restructured_loan_id'] : 'NULL') .
        ', current_status=' . ((string)($transaction['status'] ?? 'UNKNOWN')) .
        ', provider_method=' . ($providerMethod !== '' ? $providerMethod : 'UNKNOWN')
    );

    updateTransaction(
        $conn,
        $transactionColumns,
        $resolvedTransactionId,
        $normalizedStatus,
        $receiptNumber !== '' ? $receiptNumber : null,
        $paymentDateTime,
        $referenceNumber !== '' ? $referenceNumber : null,
        $financialPaymentId
    );

    $resolvedLoanId = 0;
    $resolvedRestructuredLoanId = 0;

    if (isset($transaction['loan_id']) && (int)$transaction['loan_id'] > 0) {
        $resolvedLoanId = (int)$transaction['loan_id'];
    } elseif ($loanId > 0) {
        $resolvedLoanId = $loanId;
    }

    if (isset($transaction['restructured_loan_id']) && (int)$transaction['restructured_loan_id'] > 0) {
        $resolvedRestructuredLoanId = (int)$transaction['restructured_loan_id'];
    }

    /* ==========================================
       ADDED: WALLET TRANSACTION SYNC STATUS UPDATE
       ========================================== */
    if ($providerMethod === 'WALLET' && tableExists($conn, 'wallet_transactions')) {
        $walletReference = $receiptNumber !== '' ? $receiptNumber : trim((string)($transaction['receipt_number'] ?? ''));

        if ($walletReference !== '') {
            $walletTx = fetchWalletTransactionByReference($conn, $walletReference);

            if ($walletTx) {
                $walletTxId = (int)($walletTx['id'] ?? 0);

                if ($normalizedStatus === 'SUCCESS') {
                    updateWalletTransactionSyncStatus($conn, $walletTxId, 'SYNCED', null);
                    dbg_verify('Wallet transaction marked SYNCED. wallet_tx_id=' . $walletTxId . ', reference=' . $walletReference);
                } elseif ($normalizedStatus === 'FAILED') {
                    updateWalletTransactionSyncStatus(
                        $conn,
                        $walletTxId,
                        'FAILED',
                        'Financial verification returned FAILED.'
                    );
                    dbg_verify('Wallet transaction marked FAILED. wallet_tx_id=' . $walletTxId . ', reference=' . $walletReference);
                }
            } else {
                dbg_verify('Wallet transaction not found for sync update. reference=' . $walletReference);
            }
        }
    }

    /* ==========================================
       ADDED: IDEMPOTENCY GUARD
       Prevent double loan deduction on repeated SUCCESS callbacks
       ========================================== */
    $shouldApplyLoanBalanceUpdate = true;

    if ($normalizedStatus === 'SUCCESS' && $oldTransactionStatus === 'SUCCESS') {
        $shouldApplyLoanBalanceUpdate = false;
        dbg_verify(
            'Idempotency guard hit: transaction already SUCCESS before this callback. ' .
            'Skipping loan/restructured balance update for transaction_id=' . $resolvedTransactionId
        );
    }

    if ($normalizedStatus === 'SUCCESS' && $amount > 0 && $shouldApplyLoanBalanceUpdate) {
        if ($resolvedRestructuredLoanId > 0) {
            dbg_verify(
                'Detected restructured payment. transaction_id=' . $resolvedTransactionId .
                ', restructured_loan_id=' . $resolvedRestructuredLoanId .
                ', amount=' . number_format($amount, 2, '.', '')
            );

            updateRestructuredLoanAfterVerifiedPayment(
                $conn,
                $resolvedRestructuredLoanId,
                $amount
            );
        } elseif ($resolvedLoanId > 0) {
            dbg_verify(
                'Detected regular loan payment. transaction_id=' . $resolvedTransactionId .
                ', loan_id=' . $resolvedLoanId .
                ', amount=' . number_format($amount, 2, '.', '')
            );

            updateLoanAfterVerifiedPayment(
                $conn,
                $loanColumns,
                $resolvedLoanId,
                $amount
            );
        } else {
            dbg_verify(
                'SUCCESS payment but no resolved loan target found. transaction_id=' . $resolvedTransactionId .
                ', payload_loan_id=' . $loanId .
                ', client_id=' . $clientId
            );
        }
    }

    /* ==========================================
       ADDED: WALLET REVERSAL ON FAILED
       Only for provider_method = WALLET
       ========================================== */
    if ($normalizedStatus === 'FAILED' && $amount > 0 && $providerMethod === 'WALLET') {
        if (tableExists($conn, 'wallet_accounts') && tableExists($conn, 'wallet_transactions')) {
            reverseWalletPaymentIfNeeded($conn, $transaction, $amount);
        } else {
            dbg_verify('Wallet reversal skipped because wallet tables do not exist.');
        }
    }

    $conn->commit();

    dbg_verify(
        'SUCCESS transaction_id=' . $resolvedTransactionId .
        ', payment_id=' . $financialPaymentId .
        ', payment_number=' . $paymentNumber .
        ', status=' . $normalizedStatus .
        ', receipt_number=' . ($receiptNumber !== '' ? $receiptNumber : 'NULL') .
        ', reference_number=' . ($referenceNumber !== '' ? $referenceNumber : 'NULL') .
        ', resolved_loan_id=' . $resolvedLoanId .
        ', resolved_restructured_loan_id=' . $resolvedRestructuredLoanId .
        ', provider_method=' . ($providerMethod !== '' ? $providerMethod : 'UNKNOWN')
    );

    success_response('Payment verification synced to CORE1 successfully.', [
        'transaction_id' => $resolvedTransactionId,
        'payment_id' => $financialPaymentId,
        'payment_number' => $paymentNumber,
        'status' => $normalizedStatus,
        'financial_payment_status' => $financialPaymentStatus !== '' ? $financialPaymentStatus : 'Completed',
        'loan_id' => $resolvedLoanId,
        'restructured_loan_id' => $resolvedRestructuredLoanId,
        'provider_method' => $providerMethod !== '' ? $providerMethod : 'UNKNOWN',
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn instanceof mysqli) {
        try {
            $conn->rollback();
        } catch (Throwable $rollbackError) {
        }
    }

    dbg_verify('ERROR: ' . $e->getMessage());
    fail_response(500, $e->getMessage());
}