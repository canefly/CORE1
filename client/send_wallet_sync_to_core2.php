<?php
function sendWalletSyncToCore2(array $payload): array
{
    $url = 'http://192.168.100.4/CORE2-main/modules/receive_wallet_sync.php';

    $ch = curl_init($url);
    $jsonData = json_encode($payload);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'X-API-KEY: CORE1_CORE2_WALLET_SECRET_789',
        'Content-Length: ' . strlen($jsonData)
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['success' => false, 'message' => $error];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => 'Invalid JSON from CORE2: ' . $response];
    }

    return $decoded;
}
