<?php

function sendApprovedLoanToFinancial(array $payload): array
{
    // PALITAN kung iba ang IP/folder name ng FINANCIAL server
    $financialUrl = 'http://192.168.100.4/microfinancee/modules/disbursement/receive_core1_disbursement.php';
    $apiKey = 'CORE1_FINANCIAL_SECRET_123';

    $ch = curl_init($financialUrl);

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'X-API-KEY: ' . $apiKey
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
            'success' => false,
            'message' => 'Connection failed: ' . $curlError
        ];
    }

    $decoded = json_decode($response, true);

    if (!is_array($decoded)) {
        return [
            'success' => false,
            'message' => 'Invalid response from Financial server. Raw response: ' . $response,
            'http_code' => $httpCode,

        ];
    }

    $decoded['http_code'] = $httpCode;
    return $decoded;
}