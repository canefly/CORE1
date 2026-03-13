<?php

function sendApprovedLoanToFinancial(array $payload): array
{
    $financialUrl = "http://172.20.10.2/DISBURSEMENT/receive_approved_loan.php";
    $apiKey = "CORE2_FINANCIAL_SECRET_123";

    $ch = curl_init($financialUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json",
            "X-API-KEY: " . $apiKey
        ],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 5
    ]);

    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    if ($response === false || $curlError) {
        return [
            "success" => false,
            "message" => "Connection failed: " . $curlError,
            "http_code" => $httpCode
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            "success" => false,
            "message" => "Invalid JSON response from Financial server.",
            "http_code" => $httpCode,
            "raw_response" => $response
        ];
    }

    $decoded['http_code'] = $httpCode;
    return $decoded;
}