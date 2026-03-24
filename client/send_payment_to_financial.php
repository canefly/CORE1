<?php
//10.112.107.207
function sendPaymentToFinancial(array $paymentData): array
{
    $url = "http://192.168.1.11/microfinancee/modules/collections/receive_payment_from_core1.php";
    $logFile = __DIR__ . "/debug_send_to_financial.log";

    $payloadArray = [
        "token"   => "core1_financial_payment_secret",
        "payment" => $paymentData
    ];

    $payload = json_encode($payloadArray, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    file_put_contents(
        $logFile,
        "[" . date("Y-m-d H:i:s") . "] URL={$url} PAYLOAD={$payload}" . PHP_EOL,
        FILE_APPEND
    );

    if ($payload === false) {
        $jsonError = json_last_error_msg();
        file_put_contents(
            $logFile,
            "[" . date("Y-m-d H:i:s") . "] JSON_ENCODE_ERROR={$jsonError}" . PHP_EOL,
            FILE_APPEND
        );

        return [
            "success" => false,
            "message" => "JSON encode error: " . $jsonError
        ];
    }

    $attempt = 0;
    $maxAttempts = 2;
    $lastHttpCode = 0;
    $lastResponse = '';
    $lastCurlError = '';

    while ($attempt < $maxAttempts) {
        $attempt++;

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Content-Type: application/json",
                "Accept: application/json"
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
        ]);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $lastHttpCode = $httpCode;
        $lastResponse = is_string($response) ? $response : '';
        $lastCurlError = $curlError;

        file_put_contents(
            $logFile,
            "[" . date("Y-m-d H:i:s") . "] ATTEMPT={$attempt} HTTP={$httpCode} CURL_ERROR={$curlError} RESPONSE={$lastResponse}" . PHP_EOL,
            FILE_APPEND
        );

        if ($response === false || $curlError !== '') {
            continue;
        }

        $decoded = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            continue;
        }

        if (!is_array($decoded)) {
            return [
                "success" => false,
                "message" => "FINANCIAL returned invalid JSON: " . $response
            ];
        }

        return $decoded;
    }

    if ($lastResponse === '' && $lastCurlError !== '') {
        return [
            "success" => false,
            "message" => "cURL error: " . $lastCurlError
        ];
    }

    if ($lastHttpCode < 200 || $lastHttpCode >= 300) {
        return [
            "success" => false,
            "message" => "Invalid response from FINANCIAL. HTTP {$lastHttpCode}. Raw response: " . $lastResponse
        ];
    }

    return [
        "success" => false,
        "message" => "FINANCIAL returned invalid or empty response: " . $lastResponse
    ];
}
