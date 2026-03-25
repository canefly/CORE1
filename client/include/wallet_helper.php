<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('wallet_h')) {
    function wallet_h($value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('generateWalletAccountNumber')) {
    function generateWalletAccountNumber(mysqli $conn, int $userId): string
    {
        do {
            $accountNumber = 'WAL-' . date('Ymd') . '-' . $userId . '-' . random_int(1000, 9999);

            $stmt = $conn->prepare("SELECT id FROM wallet_accounts WHERE account_number = ? LIMIT 1");
            if (!$stmt) {
                throw new Exception("Failed to prepare wallet account number check.");
            }

            $stmt->bind_param("s", $accountNumber);
            $stmt->execute();
            $result = $stmt->get_result();
            $exists = $result->fetch_assoc();
            $stmt->close();

        } while ($exists);

        return $accountNumber;
    }
}

if (!function_exists('generateWalletReference')) {
    function generateWalletReference(string $prefix = 'WAL'): string
    {
        return strtoupper($prefix) . '-' . date('YmdHis') . '-' . random_int(1000, 9999);
    }
}

if (!function_exists('getUserFullName')) {
    function getUserFullName(mysqli $conn, int $userId): string
    {
        $fullName = 'Unknown User';

        $stmt = $conn->prepare("SELECT fullname FROM users WHERE id = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $fullName = trim((string)($row['fullname'] ?? 'Unknown User'));
            }
            $stmt->close();
        }

        return $fullName;
    }
}



if (!function_exists('getWalletByUserId')) {
    function getWalletByUserId(mysqli $conn, int $userId): ?array
    {
        $stmt = $conn->prepare("
            SELECT id, user_id, account_number, balance, loan_wallet_balance, status, created_at, updated_at
            FROM wallet_accounts
            WHERE user_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare wallet lookup.");
        }

        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $wallet = $result->fetch_assoc() ?: null;
        $stmt->close();

        return $wallet;
    }
}

if (!function_exists('createWalletForUser')) {
    function createWalletForUser(mysqli $conn, int $userId): array
    {
        $accountNumber = generateWalletAccountNumber($conn, $userId);

        $stmt = $conn->prepare("
            INSERT INTO wallet_accounts (user_id, account_number, balance, loan_wallet_balance, status)
            VALUES (?, ?, 0.00, 0.00, 'ACTIVE')
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare wallet creation.");
        }

        $stmt->bind_param("is", $userId, $accountNumber);

        if (!$stmt->execute()) {
            $stmt->close();
            throw new Exception("Failed to create wallet.");
        }

        $stmt->close();

        $wallet = getWalletByUserId($conn, $userId);
        return $wallet;
    }
}

if (!function_exists('getOrCreateWallet')) {
    function getOrCreateWallet(mysqli $conn, int $userId): array
    {
        $wallet = getWalletByUserId($conn, $userId);
        if ($wallet) {
            return $wallet;
        }

        return createWalletForUser($conn, $userId);
    }
}

if (!function_exists('getWalletBalance')) {
    function getWalletBalance(mysqli $conn, int $userId): float
    {
        $wallet = getOrCreateWallet($conn, $userId);
        return (float)($wallet['balance'] ?? 0);
    }
}

if (!function_exists('getActiveRestructuredLoanDetails')) {
    function getActiveRestructuredLoanDetails(mysqli $conn, int $userId): ?array
    {
        $possibleQueries = [
            "
                SELECT id, user_id, monthly_due, outstanding, next_payment, status
                FROM restructured_loans
                WHERE user_id = ?
                  AND status IN ('ACTIVE','APPROVED','ONGOING','RESTRUCTURED')
                ORDER BY id DESC
                LIMIT 1
            ",
            "
                SELECT id, user_id, monthly_due, outstanding, due_date AS next_payment, status
                FROM restructured_loans
                WHERE user_id = ?
                  AND status IN ('ACTIVE','APPROVED','ONGOING','RESTRUCTURED')
                ORDER BY id DESC
                LIMIT 1
            "
        ];

        foreach ($possibleQueries as $sql) {
            $stmt = @$conn->prepare($sql);
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param("i", $userId);
            $ok = $stmt->execute();
            if (!$ok) {
                $stmt->close();
                continue;
            }

            $result = $stmt->get_result();
            $row = $result->fetch_assoc() ?: null;
            $stmt->close();

            if ($row) {
                return [
                    'id'           => (int)$row['id'],
                    'user_id'      => (int)$row['user_id'],
                    'monthly_due'  => (float)($row['monthly_due'] ?? 0),
                    'outstanding'  => (float)($row['outstanding'] ?? 0),
                    'next_payment' => $row['next_payment'] ?? null,
                    'status'       => (string)($row['status'] ?? '')
                ];
            }
        }

        return null;
    }
}

if (!function_exists('getActiveLoanDetails')) {
    function getActiveLoanDetails(mysqli $conn, int $userId): ?array
    {
        $possibleQueries = [
            "
                SELECT id, user_id, monthly_due, outstanding, next_payment, status
                FROM loans
                WHERE user_id = ?
                  AND status IN ('ACTIVE','RESTRUCTURED')
                ORDER BY id DESC
                LIMIT 1
            ",
            "
                SELECT id, user_id, monthly_due, outstanding, due_date AS next_payment, status
                FROM loans
                WHERE user_id = ?
                  AND status IN ('ACTIVE','RESTRUCTURED')
                ORDER BY id DESC
                LIMIT 1
            "
        ];

        foreach ($possibleQueries as $sql) {
            $stmt = @$conn->prepare($sql);
            if (!$stmt) {
                continue;
            }

            $stmt->bind_param("i", $userId);
            $ok = $stmt->execute();
            if (!$ok) {
                $stmt->close();
                continue;
            }

            $result = $stmt->get_result();
            $row = $result->fetch_assoc() ?: null;
            $stmt->close();

            if ($row) {
                return [
                    'id'           => (int)$row['id'],
                    'user_id'      => (int)$row['user_id'],
                    'monthly_due'  => (float)($row['monthly_due'] ?? 0),
                    'outstanding'  => (float)($row['outstanding'] ?? 0),
                    'next_payment' => $row['next_payment'] ?? null,
                    'status'       => (string)($row['status'] ?? '')
                ];
            }
        }

        return null;
    }
}

if (!function_exists('getEffectiveLoanContext')) {
    function getEffectiveLoanContext(mysqli $conn, int $userId): array
    {
        $restructured = getActiveRestructuredLoanDetails($conn, $userId);
        if ($restructured) {
            return [
                'has_loan'             => true,
                'loan_type'            => 'RESTRUCTURED',
                'loan_id'              => null,
                'restructured_loan_id' => (int)$restructured['id'],
                'monthly_due'          => (float)$restructured['monthly_due'],
                'outstanding'          => (float)$restructured['outstanding'],
                'next_payment'         => $restructured['next_payment'],
                'status'               => $restructured['status']
            ];
        }

        $loan = getActiveLoanDetails($conn, $userId);
        if ($loan) {
            return [
                'has_loan'             => true,
                'loan_type'            => 'NORMAL',
                'loan_id'              => (int)$loan['id'],
                'restructured_loan_id' => null,
                'monthly_due'          => (float)$loan['monthly_due'],
                'outstanding'          => (float)$loan['outstanding'],
                'next_payment'         => $loan['next_payment'],
                'status'               => $loan['status']
            ];
        }

        return [
            'has_loan'             => false,
            'loan_type'            => null,
            'loan_id'              => null,
            'restructured_loan_id' => null,
            'monthly_due'          => 0.00,
            'outstanding'          => 0.00,
            'next_payment'         => null,
            'status'               => null
        ];
    }
}

if (!function_exists('getReservedAmount')) {
    function getReservedAmount(mysqli $conn, int $userId): float
    {
        $loanContext = getEffectiveLoanContext($conn, $userId);

        if (!$loanContext['has_loan']) {
            return 0.00;
        }

        return max(0, (float)$loanContext['monthly_due']);
    }
}

if (!function_exists('getWithdrawableAmount')) {
    function getWithdrawableAmount(mysqli $conn, int $userId): float
    {
        $walletBalance = getWalletBalance($conn, $userId);
        $reserved      = getReservedAmount($conn, $userId);

        $withdrawable = $walletBalance - $reserved;
        return $withdrawable > 0 ? $withdrawable : 0.00;
    }
}

if (!function_exists('updateWalletBalance')) {
    function updateWalletBalance(mysqli $conn, int $walletId, float $newBalance): bool
    {
        $stmt = $conn->prepare("
            UPDATE wallet_accounts
            SET balance = ?, updated_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception("Failed to prepare wallet balance update.");
        }

        $stmt->bind_param("di", $newBalance, $walletId);
        $ok = $stmt->execute();
        $stmt->close();

        return $ok;
    }
}

if (!function_exists('recordWalletTransaction')) {
    function recordWalletTransaction(mysqli $conn, array $data): int
    {
        $walletAccountId      = (int)($data['wallet_account_id'] ?? 0);
        $userId               = (int)($data['user_id'] ?? 0);
        $loanId               = isset($data['loan_id']) ? (int)$data['loan_id'] : null;
        $restructuredLoanId   = isset($data['restructured_loan_id']) ? (int)$data['restructured_loan_id'] : null;
        $transactionType      = strtoupper(trim((string)($data['transaction_type'] ?? '')));
        $amount               = (float)($data['amount'] ?? 0);
        $runningBalance       = (float)($data['running_balance'] ?? 0);
        $referenceNo          = trim((string)($data['reference_no'] ?? ''));
        $remarks              = trim((string)($data['remarks'] ?? ''));
        $status               = strtoupper(trim((string)($data['status'] ?? 'SUCCESS')));
        $syncStatus           = strtoupper(trim((string)($data['sync_status'] ?? 'PENDING')));
        $syncError            = isset($data['sync_error']) ? (string)$data['sync_error'] : null;

        $stmt = $conn->prepare("
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

        if (!$stmt) {
            throw new Exception("Failed to prepare wallet transaction insert.");
        }

        $stmt->bind_param(
            "iiiisddsssss",
            $walletAccountId,
            $userId,
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

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            throw new Exception("Failed to record wallet transaction: " . $error);
        }

        $insertId = (int)$stmt->insert_id;
        $stmt->close();

        return $insertId;
    }
}

if (!function_exists('getRecentWalletTransactions')) {
    function getRecentWalletTransactions(mysqli $conn, int $userId, int $limit = 10): array
    {
        $limit = max(1, (int)$limit);
        $wallet = getOrCreateWallet($conn, $userId);

        $sql = "
            SELECT id, wallet_account_id, user_id, loan_id, restructured_loan_id, transaction_type,
                   amount, running_balance, reference_no, remarks, status, sync_status, sync_error, created_at
            FROM wallet_transactions
            WHERE wallet_account_id = ?
            ORDER BY id DESC
            LIMIT {$limit}
        ";

        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Failed to prepare wallet transaction history query.");
        }

        $walletId = (int)$wallet['id'];
        $stmt->bind_param("i", $walletId);
        $stmt->execute();
        $result = $stmt->get_result();

        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }

        $stmt->close();
        return $rows;
    }
}

if (!function_exists('formatWalletDate')) {
    function formatWalletDate(?string $date): string
    {
        if (!$date || $date === '0000-00-00' || $date === '0000-00-00 00:00:00') {
            return 'N/A';
        }

        $ts = strtotime($date);
        if (!$ts) {
            return 'N/A';
        }

        return date('M d, Y', $ts);
    }
}
?>