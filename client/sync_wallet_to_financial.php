<?php
session_start();
date_default_timezone_set('Asia/Manila');

require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/session_checker.php";
require_once __DIR__ . "/send_payment_to_financial.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection is not available.");
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function syncWalletLog(string $message): void
{
    $logFile = __DIR__ . '/debug_sync_wallet_to_financial.log';
    @file_put_contents(
        $logFile,
        '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
        FILE_APPEND
    );
}

function tableExists(mysqli $conn, string $table): bool
{
    $safe = $conn->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '{$safe}'";
    $res = $conn->query($sql);
    return $res instanceof mysqli_result && $res->num_rows > 0;
}

function getUserRow(mysqli $conn, int $userId): array
{
    $stmt = $conn->prepare("SELECT id, fullname, email, phone FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? ($res->fetch_assoc() ?: []) : [];
    $stmt->close();

    return $row;
}

function getLoanApplicationExtras(mysqli $conn, int $loanId): array
{
    $default = [
        'source_of_income' => '',
        'estimated_monthly_income' => 0
    ];

    $stmt = $conn->prepare("
        SELECT application_id
        FROM loans
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return $default;
    }

    $stmt->bind_param("i", $loanId);
    $stmt->execute();
    $res = $stmt->get_result();
    $loan = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    $applicationId = (int)($loan['application_id'] ?? 0);
    if ($applicationId <= 0) {
        return $default;
    }

    $stmt2 = $conn->prepare("
        SELECT source_of_income, estimated_monthly_income
        FROM loan_applications
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt2) {
        return $default;
    }

    $stmt2->bind_param("i", $applicationId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    $row = $res2 ? ($res2->fetch_assoc() ?: []) : [];
    $stmt2->close();

    return [
        'source_of_income' => (string)($row['source_of_income'] ?? ''),
        'estimated_monthly_income' => (float)($row['estimated_monthly_income'] ?? 0)
    ];
}

function updateWalletSyncStatus(
    mysqli $conn,
    int $walletTransactionId,
    string $syncStatus,
    ?string $syncError = null
): void {
    $stmt = $conn->prepare("
        UPDATE wallet_transactions
        SET sync_status = ?, sync_error = ?, updated_at = NOW()
        WHERE id = ?
        LIMIT 1
    ");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param("ssi", $syncStatus, $syncError, $walletTransactionId);
    $stmt->execute();
    $stmt->close();
}

function upsertLocalSyncLog(
    mysqli $conn,
    int $walletTransactionId,
    int $transactionId,
    string $syncAction,
    array $payload,
    array $response,
    string $status,
    ?string $errorMessage = null
): void {
    if (!tableExists($conn, 'wallet_sync_logs')) {
        return;
    }

    $payloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);
    $responseJson = json_encode($response, JSON_UNESCAPED_UNICODE);

    $stmt = $conn->prepare("
        INSERT INTO wallet_sync_logs (
            wallet_transaction_id,
            sync_action,
            payload_json,
            response_json,
            status,
            error_message
        ) VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        return;
    }

    $stmt->bind_param(
        "isssss",
        $walletTransactionId,
        $syncAction,
        $payloadJson,
        $responseJson,
        $status,
        $errorMessage
    );
    $stmt->execute();
    $stmt->close();
}

function findLinkedTransaction(mysqli $conn, array $walletTx): ?array
{
    $userId = (int)($walletTx['user_id'] ?? 0);
    $loanId = isset($walletTx['loan_id']) ? (int)$walletTx['loan_id'] : 0;
    $restructuredLoanId = isset($walletTx['restructured_loan_id']) ? (int)$walletTx['restructured_loan_id'] : 0;
    $referenceNo = trim((string)($walletTx['reference_no'] ?? ''));

    if ($referenceNo !== '') {
        $stmt = $conn->prepare("
            SELECT *
            FROM transactions
            WHERE receipt_number = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $referenceNo);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            return $row;
        }
    }

    if ($restructuredLoanId > 0) {
        $stmt = $conn->prepare("
            SELECT *
            FROM transactions
            WHERE user_id = ?
              AND restructured_loan_id = ?
              AND status IN ('PAID_PENDING', 'SUCCESS', 'FAILED')
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->bind_param("ii", $userId, $restructuredLoanId);
    } else {
        $stmt = $conn->prepare("
            SELECT *
            FROM transactions
            WHERE user_id = ?
              AND loan_id = ?
              AND status IN ('PAID_PENDING', 'SUCCESS', 'FAILED')
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->bind_param("ii", $userId, $loanId);
    }

    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    return $row ?: null;
}

function buildWalletPaymentPayload(mysqli $conn, array $walletTx, array $transaction): array
{
    $userId = (int)($walletTx['user_id'] ?? 0);
    $loanId = isset($walletTx['loan_id']) ? (int)$walletTx['loan_id'] : 0;
    $restructuredLoanId = isset($walletTx['restructured_loan_id']) ? (int)$walletTx['restructured_loan_id'] : 0;

    $userRow = getUserRow($conn, $userId);
    $loanExtras = getLoanApplicationExtras($conn, $loanId);

    return [
        "transaction_id" => (int)($transaction['id'] ?? 0),
        "user_id" => $userId,
        "loan_id" => $loanId,
        "restructured_loan_id" => $restructuredLoanId > 0 ? $restructuredLoanId : null,
        "amount" => (float)($transaction['amount'] ?? $walletTx['amount'] ?? 0),
        "status" => (string)($transaction['status'] ?? 'PAID_PENDING'),
        "payment_date" => (string)($transaction['trans_date'] ?? $walletTx['created_at'] ?? date('Y-m-d H:i:s')),
        "provider_method" => (string)($transaction['provider_method'] ?? 'WALLET'),
        "paymongo_payment_id" => (string)($transaction['paymongo_payment_id'] ?? ''),
        "paymongo_checkout_id" => (string)($transaction['paymongo_checkout_id'] ?? ''),
        "receipt_number" => (string)($transaction['receipt_number'] ?? $walletTx['reference_no'] ?? ''),

        "principal_amount" => (float)($transaction['principal_amount'] ?? 0),
        "interest_amount" => (float)($transaction['interest_amount'] ?? 0),
        "penalty_amount" => (float)($transaction['penalty_amount'] ?? 0),
        "payment_type" => $restructuredLoanId > 0 ? "restructured_wallet" : "wallet",

        "client_full_name" => (string)($userRow['fullname'] ?? ''),
        "client_email" => (string)($userRow['email'] ?? ''),
        "client_phone" => (string)($userRow['phone'] ?? ''),
        "client_occupation" => (string)($loanExtras['source_of_income'] ?? ''),
        "client_monthly_income" => (float)($loanExtras['estimated_monthly_income'] ?? 0)
    ];
}

$results = [];
$sentCount = 0;
$failedCount = 0;

try {
    $query = "
        SELECT *
        FROM wallet_transactions
        WHERE transaction_type IN ('LOAN_PAYMENT', 'RESTRUCTURED_PAYMENT')
          AND sync_status IN ('PENDING', 'FAILED')
        ORDER BY id ASC
    ";

    $res = $conn->query($query);
    $walletTxRows = [];

    while ($row = $res->fetch_assoc()) {
        $walletTxRows[] = $row;
    }

    foreach ($walletTxRows as $walletTx) {
        $walletTransactionId = (int)($walletTx['id'] ?? 0);

        try {
            $linkedTransaction = findLinkedTransaction($conn, $walletTx);

            if (!$linkedTransaction) {
                $msg = 'No linked transactions row found for wallet sync.';
                updateWalletSyncStatus($conn, $walletTransactionId, 'FAILED', $msg);

                $results[] = [
                    'wallet_transaction_id' => $walletTransactionId,
                    'status' => 'FAILED',
                    'message' => $msg
                ];
                $failedCount++;
                syncWalletLog("FAILED wallet_tx_id={$walletTransactionId}: {$msg}");
                continue;
            }

            $payload = buildWalletPaymentPayload($conn, $walletTx, $linkedTransaction);

            syncWalletLog("Sending wallet sync payload wallet_tx_id={$walletTransactionId}: " . json_encode($payload));

            $response = sendPaymentToFinancial($payload);

            syncWalletLog("Response wallet_tx_id={$walletTransactionId}: " . json_encode($response));

            if (!empty($response['success'])) {
                updateWalletSyncStatus($conn, $walletTransactionId, 'SYNCED', null);

                upsertLocalSyncLog(
                    $conn,
                    $walletTransactionId,
                    (int)($linkedTransaction['id'] ?? 0),
                    'SYNC_WALLET_PAYMENT',
                    $payload,
                    $response,
                    'SUCCESS',
                    null
                );

                $results[] = [
                    'wallet_transaction_id' => $walletTransactionId,
                    'transaction_id' => (int)($linkedTransaction['id'] ?? 0),
                    'status' => 'SYNCED',
                    'message' => (string)($response['message'] ?? 'Synced successfully.')
                ];
                $sentCount++;
            } else {
                $msg = (string)($response['message'] ?? 'Unknown Financial sync error.');
                updateWalletSyncStatus($conn, $walletTransactionId, 'FAILED', $msg);

                upsertLocalSyncLog(
                    $conn,
                    $walletTransactionId,
                    (int)($linkedTransaction['id'] ?? 0),
                    'SYNC_WALLET_PAYMENT',
                    $payload,
                    $response,
                    'FAILED',
                    $msg
                );

                $results[] = [
                    'wallet_transaction_id' => $walletTransactionId,
                    'transaction_id' => (int)($linkedTransaction['id'] ?? 0),
                    'status' => 'FAILED',
                    'message' => $msg
                ];
                $failedCount++;
            }
        } catch (Throwable $txEx) {
            $msg = $txEx->getMessage();
            updateWalletSyncStatus($conn, $walletTransactionId, 'FAILED', $msg);

            $results[] = [
                'wallet_transaction_id' => $walletTransactionId,
                'status' => 'FAILED',
                'message' => $msg
            ];
            $failedCount++;
            syncWalletLog("EXCEPTION wallet_tx_id={$walletTransactionId}: {$msg}");
        }
    }
} catch (Throwable $e) {
    die("Sync failed: " . e($e->getMessage()));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sync Wallet to Financial</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background: #0b1730;
            color: #fff;
        }
        .wrap {
            max-width: 1100px;
            margin: 30px auto;
            padding: 20px;
        }
        .card {
            background: #132140;
            border-radius: 16px;
            padding: 20px;
            margin-bottom: 20px;
        }
        h1, h2 {
            margin-top: 0;
        }
        .ok { color: #41d392; }
        .bad { color: #ff8b8b; }
        table {
            width: 100%;
            border-collapse: collapse;
            background: #10203c;
            border-radius: 12px;
            overflow: hidden;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid rgba(255,255,255,0.08);
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #18294a;
        }
        a.btn {
            display: inline-block;
            padding: 10px 14px;
            background: #41d392;
            color: #062117;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 700;
        }
    </style>
</head>
<body>
<div class="wrap">
    <div class="card">
        <h1>Wallet Sync to Financial</h1>
        <p>Processed: <strong><?php echo e(count($results)); ?></strong></p>
        <p class="ok">Synced: <strong><?php echo e($sentCount); ?></strong></p>
        <p class="bad">Failed: <strong><?php echo e($failedCount); ?></strong></p>
        <p><a class="btn" href="wallet.php">Back to Wallet</a></p>
    </div>

    <div class="card">
        <h2>Sync Results</h2>
        <table>
            <thead>
                <tr>
                    <th>Wallet TX ID</th>
                    <th>Transaction ID</th>
                    <th>Status</th>
                    <th>Message</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($results)): ?>
                <?php foreach ($results as $row): ?>
                    <tr>
                        <td><?php echo e($row['wallet_transaction_id'] ?? ''); ?></td>
                        <td><?php echo e($row['transaction_id'] ?? ''); ?></td>
                        <td><?php echo e($row['status'] ?? ''); ?></td>
                        <td><?php echo e($row['message'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">No pending or failed wallet payment sync records found.</td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>