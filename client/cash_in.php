<?php
session_start();
require_once __DIR__ . "/include/config.php";

include __DIR__ . "/include/session_checker.php";

// ==========================================
// PAYMONGO CONFIGURATION
// ==========================================
require_once __DIR__ . '/include/API/api_vault.php';

// CORE 2 CONNECTION
$core2_host = "127.0.0.1";
$core2_user = "root";
$core2_pass = "";
$core2_dbname = "core2_db";

$core2_conn = new mysqli($core2_host, $core2_user, $core2_pass, $core2_dbname);

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_msg = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['amount'])) {
    $amount = floatval($_POST['amount']);

    if ($amount < 100) {
        $error_msg = "Minimum cash-in is ₱100.00";
    }
    else {
        // PREPARE PAYMONGO CHECKOUT SESSION
        $amount_in_cents = $amount * 100; // PayMongo uses cents

        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => "https://api.paymongo.com/v1/checkout_sessions",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode([
                'data' => [
                    'attributes' => [
                        'send_email_receipt' => true,
                        'show_description' => true,
                        'show_line_items' => true,
                        'description' => "Wallet Cash-in for User ID: $user_id",
                        'line_items' => [
                            [
                                'currency' => 'PHP',
                                'amount' => $amount_in_cents,
                                'description' => 'Digital Wallet Top-up',
                                'name' => 'Wallet Cash-in',
                                'quantity' => 1
                            ]
                        ],
                        'payment_method_types' => ['gcash', 'paymaya', 'card'],
                        'success_url' => "http://localhost/your_project/cashin_success.php", // Palitan mo 'to beh
                        'cancel_url' => "http://localhost/your_project/wallet.php"
                    ]
                ]
            ]),
            CURLOPT_HTTPHEADER => [
                "Accept: application/json",
                "Authorization: Basic " . $paymongo_auth_base64,
                "Content-Type: application/json"
            ],
        ]);

        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);

        if ($err) {
            $error_msg = "API Error: " . $err;
        }
        else {
            $data = json_decode($response, true);
            if (isset($data['data']['attributes']['checkout_url'])) {
                $checkout_url = $data['data']['attributes']['checkout_url'];
                header("Location: $checkout_url"); // REDIRECT SA PAYMONGO
                exit;
            }
            else {
                $error_msg = "Failed to create checkout session.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cash In - PayMongo</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/wallet.css">
</head>
<body>
<?php include 'include/sidebar.php'; ?>
<div class="main-content">
    <div class="page-header" style="text-align: center;">
        <h1>Add Balance</h1>
        <p>Secured by PayMongo</p>
    </div>

    <div class="table-card" style="max-width: 450px; margin: 0 auto; text-align: center;">
        <?php if ($error_msg): ?>
            <div style="background:rgba(239,68,68,0.1); color:#f87171; padding:15px; border-radius:8px; margin-bottom:20px;">
                <?php echo $error_msg; ?>
            </div>
        <?php
endif; ?>

        <form method="POST">
            <div style="margin-bottom: 20px; text-align: left;">
                <label style="color:#94a3b8; font-size:14px;">Enter Amount (₱)</label>
                <input type="number" name="amount" style="width:100%; padding:15px; background:#0f172a; border:1px solid #334155; color:#fff; font-size:24px; font-weight:800; border-radius:10px; margin-top:10px;" placeholder="500.00" required>
            </div>
            
            <div style="display: flex; gap: 10px; margin-bottom: 20px;">
                <div style="flex:1; padding:10px; border:1px solid #10b981; border-radius:8px; color:#10b981; font-size:12px;">
                    <i class="bi bi-shield-check"></i> Secure Payment
                </div>
                <div style="flex:1; padding:10px; border:1px solid #334155; border-radius:8px; color:#94a3b8; font-size:12px;">
                    <i class="bi bi-lightning-fill"></i> Instant Credit
                </div>
            </div>

            <button type="submit" class="btn-pay" style="width: 100%; justify-content: center; padding: 18px;">
                Proceed to PayMongo <i class="bi bi-arrow-right"></i>
            </button>
        </form>
    </div>
</div>
</body>
</html>