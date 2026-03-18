<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../assets/vendor/PHPMailer/Exception.php';
require __DIR__ . '/../assets/vendor/PHPMailer/PHPMailer.php';
require __DIR__ . '/../assets/vendor/PHPMailer/SMTP.php';

function sendOTP($toEmail, $subject, $messageBody, $type = 'OTP') {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'mikedabu702@gmail.com'; // <--- GMAIL MO
        $mail->Password   = 'cwjzzdsoqusukutm'; // <--- APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('mikedabu702@gmail.com', 'Microfinance Security');
        $mail->addAddress($toEmail);

        // PROFESSIONAL HTML TEMPLATE
        $htmlContent = "
        <div style='background-color: #f8fafc; padding: 40px; font-family: sans-serif;'>
            <div style='max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05);'>
                <div style='background: #2ca078; padding: 30px; text-align: center;'>
                    <h1 style='color: #ffffff; margin: 0; font-size: 24px;'>MicroFinance Portal</h1>
                </div>
                <div style='padding: 40px; color: #334155; line-height: 1.6;'>
                    <h2 style='color: #1e293b; margin-top: 0;'>Security Notification</h2>
                    <p>Hello,</p>
                    <p>$messageBody</p>
                    <p style='margin-top: 30px; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 20px;'>
                        If you did not request this, please ignore this email or contact our support team immediately. 
                        <b>Never share your OTP with anyone.</b>
                    </p>
                </div>
                <div style='background: #f1f5f9; padding: 20px; text-align: center; color: #64748b; font-size: 12px;'>
                    &copy; 2026 MicroFinance System. All rights reserved.
                </div>
            </div>
        </div>";

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;

        return $mail->send();
    } catch (Exception $e) { return false; }
}