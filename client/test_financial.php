<?php

$url = "http://192.168.1.5/microfinancee/modules/collections/receive_payment_from_core1.php";

$data = [
    "token" => "core1_financial_payment_secret",
    "payment" => [
        "transaction_id" => 999,
        "user_id" => 1,
        "loan_id" => 1,
        "amount" => 100,
        "status" => "SUCCESS",
        "provider_method" => "GCASH"
    ]
];

$ch = curl_init($url);

curl_setopt_array($ch,[
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
    CURLOPT_POSTFIELDS => json_encode($data)
]);

$response = curl_exec($ch);

echo $response;