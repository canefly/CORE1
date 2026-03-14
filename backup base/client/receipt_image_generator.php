<?php
// CLIENT/receipt_image_generator.php

function ensure_receipt_dir(): string {
  // Since this file is in CLIENT root, receipts should be CLIENT/receipts
  $dir = __DIR__ . "/receipts";
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true); // 0777 for dev; you can change to 0775 later
  }
  return $dir;
}

function generate_receipt_png(array $tx, string $receiptUrl, string $filePath): bool {
  // GD required
  if (!function_exists('imagecreatetruecolor')) {
    error_log("GD missing: imagecreatetruecolor() not available.");
    return false;
  }

  $status = strtoupper((string)($tx['status'] ?? 'PENDING'));
  $loan_id = (int)($tx['loan_id'] ?? 0);
  $tx_id = (int)($tx['id'] ?? 0);

  $contract = "LN-" . str_pad((string)$loan_id, 4, "0", STR_PAD_LEFT);
  $amount = number_format((float)($tx['amount'] ?? 0), 2);

  $method = strtoupper((string)($tx['provider_method'] ?? 'TO_BE_CONFIRMED'));
  if ($method === 'TO_BE_CONFIRMED' || $method === '') $method = "TO BE CONFIRMED";

  // Proof: prefer payment id, otherwise checkout id
  $proof = $tx['paymongo_payment_id'] ?? null;
  $proofLabel = "PayMongo Payment ID";
  if (!$proof) {
    $proof = $tx['paymongo_checkout_id'] ?? '-';
    $proofLabel = "PayMongo Checkout ID";
  }
  if (!$proof) $proof = "-";

  $rcptNo = (string)($tx['receipt_number'] ?? '-');

  $date = "-";
  if (!empty($tx['trans_date'])) {
    $date = date("M d, Y h:i A", strtotime($tx['trans_date']));
  }

  // Ensure directory exists
  $dir = dirname($filePath);
  if (!is_dir($dir)) {
    @mkdir($dir, 0777, true);
  }

  // QR generator (external)
  $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=" . urlencode($receiptUrl);

  $w = 1000; $h = 600;
  $img = imagecreatetruecolor($w, $h);

  $white = imagecolorallocate($img, 255,255,255);
  $black = imagecolorallocate($img, 15,23,42);
  $gray  = imagecolorallocate($img, 100,116,139);
  $green = imagecolorallocate($img, 16,185,129);
  $amber = imagecolorallocate($img, 245,158,11);
  $red   = imagecolorallocate($img, 239,68,68);

  imagefilledrectangle($img, 0, 0, $w, $h, $white);
  imagerectangle($img, 10, 10, $w-10, $h-10, $black);

  imagestring($img, 5, 30, 30, "MICROFINANCE PAYMENT RECEIPT", $black);
  imagestring($img, 3, 30, 60, "Receipt No: " . $rcptNo, $black);

  // Badge color by status
  $badgeText = $status;
  $badgeColor = $amber;
  if ($status === 'SUCCESS') $badgeColor = $green;
  if ($status === 'FAILED')  $badgeColor = $red;

  imagefilledrectangle($img, $w-180, 30, $w-30, 70, $badgeColor);
  imagestring($img, 5, $w-150, 40, $badgeText, $white);

  $y = 110;
  imagestring($img, 4, 30, $y, "Contract: " . $contract, $black); $y += 35;
  imagestring($img, 4, 30, $y, "Transaction ID: " . $tx_id, $black); $y += 35;
  imagestring($img, 4, 30, $y, "Amount: PHP " . $amount, $black); $y += 35;
  imagestring($img, 4, 30, $y, "Payment Method: " . $method, $black); $y += 35;
  imagestring($img, 4, 30, $y, $proofLabel . ": " . $proof, $black); $y += 35;
  imagestring($img, 4, 30, $y, "Date: " . $date, $black); $y += 35;

  imagestring($img, 3, 30, $h-80, "Scan QR to verify receipt online:", $gray);
  imagestring($img, 3, 30, $h-55, $receiptUrl, $gray);

  $qrData = @file_get_contents($qrUrl);
  if ($qrData) {
    $qrImg = @imagecreatefromstring($qrData);
    if ($qrImg) {
      imagecopyresampled($img, $qrImg, $w-240, $h-240, 0, 0, 180, 180, imagesx($qrImg), imagesy($qrImg));
      imagedestroy($qrImg);
    }
  }

  $ok = @imagepng($img, $filePath);
  imagedestroy($img);

  if (!$ok) {
    error_log("Receipt PNG save failed. path=" . $filePath);
    error_log("Last PHP error: " . print_r(error_get_last(), true));
    return false;
  }

  return file_exists($filePath);
}