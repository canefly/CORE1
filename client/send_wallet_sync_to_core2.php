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
    $errno = curl_errno($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        if ($errno === 7) {
            return ['success' => false, 'message' => '🚨 CONNECTION REFUSED: Is Core 2 XAMPP running? Is the IP correct?'];
        }
        if ($errno === 28) {
            return ['success' => false, 'message' => '🚨 TIMEOUT: Network congested or Core 2 took too long.'];
        }
        return ['success' => false, 'message' => '🚨 CURL ERROR [' . $errno . ']: ' . $error];
    }

    if ($httpCode !== 200) {
        return ['success' => false, 'message' => '🚨 HTTP ERROR [' . $httpCode . ']: Core 2 rejected the payload.'];
    }

    $decoded = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return ['success' => false, 'message' => $response];
    }

    return $decoded;
}
