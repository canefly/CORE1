<!--C:\xampp\htdocs\CORE1\client\send_wallet_payment_to_core2.php-->
<?php

function sendWalletPaymentToCore2(array $paymentData): array
{
    $url = "http://192.168.100.4/CORE2-main/modules/receive_wallet_payment_from_core1.php";
    $logFile = __DIR__ . "/debug_send_wallet_payment_to_core2.log";

    $payloadArray = [
        "token" => "core1_wallet_payment_secret",
        "payment" => $paymentData
    ];

    $payload = json_encode($payloadArray, JSON_UNESCAPED_UNICODE);

    file_put_contents(
        $logFile,
        "[" . date("Y-m-d H:i:s") . "] URL={$url} PAYLOAD={$payload}" . PHP_EOL,
        FILE_APPEND
    );

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    file_put_contents(
        $logFile,
        "[" . date("Y-m-d H:i:s") . "] HTTP={$httpCode} CURL_ERROR={$curlError} RESPONSE={$response}" . PHP_EOL,
        FILE_APPEND
    );

    if ($response === false || $curlError !== '') {
        return [
            "success" => false,
            "message" => "cURL error: " . $curlError
        ];
    }

    $decoded = json_decode($response, true);

    if ($httpCode !== 200) {
        return [
            "success" => false,
            "message" => "Invalid response from CORE2. HTTP {$httpCode}. Raw response: " . $response
        ];
    }

    if (!is_array($decoded)) {
        return [
            "success" => false,
            "message" => "CORE2 returned invalid JSON: " . $response
        ];
    }

    return $decoded;
}