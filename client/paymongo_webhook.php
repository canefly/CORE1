<?php
require_once __DIR__ . "/include/config.php";
require_once __DIR__ . "/include/API/api_vault.php";
require_once __DIR__ . "/receipt_image_generator.php";
require_once __DIR__ . "/send_payment_to_financial.php";

$logFile = __DIR__ . "/debug_webhook.log";

function dbg_webhook(string $message): void {
    global $logFile;
    file_put_contents($logFile, "[" . date("Y-m-d H:i:s") . "] " . $message . PHP_EOL, FILE_APPEND);
}

/**
 * Added only: payment breakdown resolver
 * No existing logic removed
 */
function resolvePaymentBreakdown(mysqli $conn, array $txRow): array
{
    $loanId = (int)($txRow['loan_id'] ?? 0);
    $restructuredLoanId = (int)($txRow['restructured_loan_id'] ?? 0);
    $amount = round((float)($txRow['amount'] ?? 0), 2);

    $finalLoanId = $loanId > 0 ? $loanId : $restructuredLoanId;

    $principalAmount = $amount;
    $interestAmount = 0.00;
    $penaltyAmount = 0.00;
    $paymentType = 'loan_principal';

    if ($finalLoanId <= 0 || $amount <= 0) {
        return [
            'principal_amount' => $principalAmount,
            'interest_amount' => $interestAmount,
            'penalty_amount' => $penaltyAmount,
            'payment_type' => $paymentType
        ];
    }

    $loanStmt = $conn->prepare("
        SELECT monthly_due, interest_rate, interest_method, outstanding
        FROM loans
        WHERE id = ?
        LIMIT 1
    ");
    $loanStmt->bind_param("i", $finalLoanId);
    $loanStmt->execute();
    $loanRow = $loanStmt->get_result()->fetch_assoc();
    $loanStmt->close();

    if ($loanRow) {
        $monthlyDue = round((float)($loanRow['monthly_due'] ?? 0), 2);
        $interestRate = (float)($loanRow['interest_rate'] ?? 0);
        $interestMethod = strtoupper((string)($loanRow['interest_method'] ?? ''));
        $outstanding = round((float)($loanRow['outstanding'] ?? 0), 2);

        if ($monthlyDue > 0 && abs($amount - $monthlyDue) <= 0.05) {
            if ($interestRate > 0) {
                if ($interestMethod === 'FLAT') {
                    $interestAmount = round($outstanding * ($interestRate / 100), 2);
                } else {
                    $interestAmount = round($outstanding * ($interestRate / 100), 2);
                }

                if ($interestAmount > $amount) {
                    $interestAmount = 0.00;
                }

                $principalAmount = round($amount - $interestAmount - $penaltyAmount, 2);

                if ($principalAmount < 0) {
                    $principalAmount = $amount;
                    $interestAmount = 0.00;
                }
            }
        }
    }

    return [
        'principal_amount' => round($principalAmount, 2),
        'interest_amount' => round($interestAmount, 2),
        'penalty_amount' => round($penaltyAmount, 2),
        'payment_type' => $paymentType
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    header("Content-Type: text/plain; charset=utf-8");
    exit("Webhook endpoint is LIVE ✅");
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit("Method not allowed");
}

$raw = file_get_contents("php://input");
if (!$raw) {
    http_response_code(400);
    exit("Empty body");
}

$headers = function_exists("getallheaders") ? getallheaders() : [];
$sigHeader = $headers["Paymongo-Signature"] ?? $headers["paymongo-signature"] ?? ($_SERVER["HTTP_PAYMONGO_SIGNATURE"] ?? null);
if (!$sigHeader) {
    http_response_code(400);
    exit("Missing Paymongo-Signature");
}

$parts = [];
foreach (explode(",", $sigHeader) as $kv) {
    $kv = trim($kv);
    if (strpos($kv, "=") === false) continue;
    [$k, $v] = explode("=", $kv, 2);
    $parts[trim($k)] = trim($v);
}

$t  = $parts["t"] ?? null;
$te = $parts["te"] ?? null;
$li = $parts["li"] ?? null;

if (!$t) {
    http_response_code(400);
    exit("Missing timestamp");
}

$expected = hash_hmac("sha256", $t . "." . $raw, PAYMONGO_WEBHOOK_SECRET);
$given = $te ?: $li;

if (!$given || !hash_equals($expected, $given)) {
    http_response_code(401);
    exit("Invalid signature");
}

$event = json_decode($raw, true);
if (!is_array($event)) {
    http_response_code(400);
    exit("Invalid JSON");
}

$eventType   = $event["data"]["attributes"]["type"] ?? "";
$payloadData = $event["data"]["attributes"]["data"] ?? null;

if (!$eventType || !is_array($payloadData)) {
    http_response_code(400);
    exit("Invalid event");
}

$isPaid   = in_array($eventType, ["payment.paid", "checkout_session.payment.paid"], true);
$isFailed = in_array($eventType, ["payment.failed", "checkout_session.payment.failed"], true);

if (!$isPaid && !$isFailed) {
    http_response_code(200);
    exit("Ignored");
}

function extract_meta(array $payloadData): array {
    if (isset($payloadData["attributes"]["metadata"]) && is_array($payloadData["attributes"]["metadata"])) {
        return $payloadData["attributes"]["metadata"];
    }
    if (
        isset($payloadData["attributes"]["payment_intent"]["attributes"]["metadata"]) &&
        is_array($payloadData["attributes"]["payment_intent"]["attributes"]["metadata"])
    ) {
        return $payloadData["attributes"]["payment_intent"]["attributes"]["metadata"];
    }
    if (
        isset($payloadData["attributes"]["payments"][0]["attributes"]["metadata"]) &&
        is_array($payloadData["attributes"]["payments"][0]["attributes"]["metadata"])
    ) {
        return $payloadData["attributes"]["payments"][0]["attributes"]["metadata"];
    }
    return [];
}

$meta = extract_meta($payloadData);

$tx_id = (int)($meta["tx_id"] ?? 0);
$loan_id = (int)($meta["loan_id"] ?? 0);
$restructured_loan_id = (int)($meta["restructured_loan_id"] ?? 0);
$user_id = (int)($meta["user_id"] ?? 0);

if ($tx_id <= 0 || $user_id <= 0) {
    http_response_code(200);
    exit("Missing metadata");
}

if ($loan_id <= 0 && $restructured_loan_id <= 0) {
    http_response_code(200);
    exit("Missing loan reference");
}

$paymongo_payment_id = null;
$provider_method = null;

if (($payloadData["type"] ?? "") === "payment") {
    $paymongo_payment_id = $payloadData["id"] ?? null;
    $provider_method = $payloadData["attributes"]["source"]["type"] ?? null;
} else {
    $paymongo_payment_id = $payloadData["attributes"]["payments"][0]["id"] ?? null;
    $provider_method = $payloadData["attributes"]["payment_method_used"] ?? null;
}

$provider_method = $provider_method ? strtoupper((string)$provider_method) : null;

$conn->begin_transaction();

try {
    $q = $conn->prepare("SELECT id, user_id, loan_id, restructured_loan_id, amount, status, receipt_number FROM transactions WHERE id=? FOR UPDATE");
    $q->bind_param("i", $tx_id);
    $q->execute();
    $tx = $q->get_result()->fetch_assoc();
    $q->close();

    if (!$tx) {
        $conn->commit();
        http_response_code(200);
        exit("TX not found");
    }

    $currentStatus = strtoupper((string)($tx["status"] ?? "PENDING"));

    if ($currentStatus === "SUCCESS" || $currentStatus === "FAILED") {
        $conn->commit();
        http_response_code(200);
        exit("Already processed");
    }

    $paidAt = date("Y-m-d H:i:s");

    if ($isFailed) {
        $u = $conn->prepare("
            UPDATE transactions
            SET status='FAILED',
                trans_date=?
            WHERE id=?
        ");
        $u->bind_param("si", $paidAt, $tx_id);
        $u->execute();
        $u->close();
    } else {
        $receiptNumber = $tx["receipt_number"] ?: ("RCPT-" . date("Ymd") . "-" . str_pad((string)$tx_id, 6, "0", STR_PAD_LEFT));

        $u = $conn->prepare("
            UPDATE transactions
            SET status='PAID_PENDING',
                trans_date=?,
                user_id = COALESCE(user_id, ?),
                loan_id = CASE WHEN ? > 0 THEN ? ELSE loan_id END,
                restructured_loan_id = CASE WHEN ? > 0 THEN ? ELSE restructured_loan_id END,
                provider_method = COALESCE(?, provider_method),
                paymongo_payment_id = COALESCE(?, paymongo_payment_id),
                receipt_number = COALESCE(?, receipt_number)
            WHERE id=?
        ");
        $u->bind_param(
            "siiiissssi",
            $paidAt,
            $user_id,
            $loan_id,
            $loan_id,
            $restructured_loan_id,
            $restructured_loan_id,
            $provider_method,
            $paymongo_payment_id,
            $receiptNumber,
            $tx_id
        );
        $u->execute();
        $u->close();
    }

    $txq = $conn->prepare("
        SELECT id, user_id, loan_id, restructured_loan_id, amount, status, trans_date, provider_method,
               paymongo_payment_id, paymongo_checkout_id, receipt_number,
               receipt_image_pending_url, receipt_image_final_url
        FROM transactions
        WHERE id=? LIMIT 1
    ");
    $txq->bind_param("i", $tx_id);
    $txq->execute();
    $txRow = $txq->get_result()->fetch_assoc();
    $txq->close();

    if (!$txRow) {
        throw new Exception("Updated transaction not found.");
    }

    $txStatusUpper = strtoupper((string)$txRow["status"]);

    if (in_array($txStatusUpper, ["PAID_PENDING", "FAILED"], true)) {
        $userRow = null;
        $userStmt = $conn->prepare("
            SELECT id, fullname, email, phone
            FROM users
            WHERE id = ?
            LIMIT 1
        ");
        $uid = (int)$txRow["user_id"];
        $userStmt->bind_param("i", $uid);
        $userStmt->execute();
        $userRow = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();

        $loanAppRow = null;
        $loanAppStmt = $conn->prepare("
            SELECT source_of_income, estimated_monthly_income
            FROM loan_applications
            WHERE user_id = ?
            ORDER BY id DESC
            LIMIT 1
        ");
        $loanAppStmt->bind_param("i", $uid);
        $loanAppStmt->execute();
        $loanAppRow = $loanAppStmt->get_result()->fetch_assoc();
        $loanAppStmt->close();

        $finalLoanId = (int)$txRow["loan_id"] > 0
            ? (int)$txRow["loan_id"]
            : (int)$txRow["restructured_loan_id"];

        $breakdown = resolvePaymentBreakdown($conn, $txRow);

        $paymentPayload = [
            "transaction_id" => (int)$txRow["id"],
            "user_id" => (int)$txRow["user_id"],
            "loan_id" => $finalLoanId,
            "restructured_loan_id" => (int)$txRow["restructured_loan_id"],
            "amount" => (float)$txRow["amount"],
            "status" => (string)$txRow["status"],
            "payment_date" => (string)$txRow["trans_date"],
            "provider_method" => (string)$txRow["provider_method"],
            "paymongo_payment_id" => (string)$txRow["paymongo_payment_id"],
            "paymongo_checkout_id" => (string)$txRow["paymongo_checkout_id"],
            "receipt_number" => (string)$txRow["receipt_number"],

            "principal_amount" => (float)$breakdown["principal_amount"],
            "interest_amount" => (float)$breakdown["interest_amount"],
            "penalty_amount" => (float)$breakdown["penalty_amount"],
            "payment_type" => (string)$breakdown["payment_type"],

            "client_full_name" => (string)($userRow["fullname"] ?? ''),
            "client_email" => (string)($userRow["email"] ?? ''),
            "client_phone" => (string)($userRow["phone"] ?? ''),
            "client_occupation" => (string)($loanAppRow["source_of_income"] ?? ''),
            "client_monthly_income" => (float)($loanAppRow["estimated_monthly_income"] ?? 0)
        ];

        dbg_webhook("Payment breakdown=" . json_encode($breakdown));
        dbg_webhook("Sending to FINANCIAL payload=" . json_encode($paymentPayload));

        $financialResponse = sendPaymentToFinancial($paymentPayload);

        dbg_webhook("FINANCIAL response=" . json_encode($financialResponse));

        if (!($financialResponse["success"] ?? false)) {
            throw new Exception("FINANCIAL sync failed: " . ($financialResponse["message"] ?? "Unknown error"));
        }
    }

    $receiptUrl = PUBLIC_BASE_URL . "/CORE1/client/receipt.php?tx_id=" . $tx_id;
    $dir = ensure_receipt_dir();
    $status = strtoupper((string)$txRow["status"]);
    $filenameFinal = $txRow["receipt_number"] . "-" . $status . ".png";
    $filePathFinal = $dir . "/" . $filenameFinal;

    generate_receipt_png($txRow, $receiptUrl, $filePathFinal);

    $finalUrl = PUBLIC_BASE_URL . "/CORE1/client/receipts/" . rawurlencode($filenameFinal);
    $upFinal = $conn->prepare("UPDATE transactions SET receipt_image_final_url=? WHERE id=?");
    $upFinal->bind_param("si", $finalUrl, $tx_id);
    $upFinal->execute();
    $upFinal->close();

    $conn->commit();
    http_response_code(200);
    echo "OK";
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo "ERR: " . $e->getMessage();
}