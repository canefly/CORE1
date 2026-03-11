<?php
// CORE1/LSA/process_review.php
include 'includes/db_connect.php'; 
require_once __DIR__ . '/includes/session_checker.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $application_id = (int)$_POST['application_id'];
    $new_status = $_POST['status']; // 'VERIFIED' or 'REJECTED'
    $remarks = trim($_POST['remarks']); 

    // 1. Kunin muna ang user_id ng application na ito para alam natin kung kanino ipapadala ang notif
    $get_user = $conn->prepare("SELECT user_id FROM loan_applications WHERE id = ?");
    $get_user->bind_param("i", $application_id);
    $get_user->execute();
    $user_res = $get_user->get_result();
    $user_id = 0;
    if ($user_row = $user_res->fetch_assoc()) {
        $user_id = $user_row['user_id'];
    }
    $get_user->close();

    // 2. I-update ang loan application status
    $stmt = $conn->prepare("UPDATE loan_applications SET status = ?, remarks = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("ssi", $new_status, $remarks, $application_id);

    if ($stmt->execute()) {
        
        // 3. I-INSERT ANG NOTIFICATION (Kung may nahanap na user)
        if ($user_id > 0) {
            $notif_title = "";
            $notif_message = "";
            $notif_type = "";
            $notif_icon = "";
            $notif_link = "";

            if ($new_status == 'VERIFIED') {
                $notif_title = "Application Verified";
                $notif_message = "Good news! Your application <strong>#LA-{$application_id}</strong> has been verified by our support team and forwarded to the Loan Officer for final review.";
                $notif_type = "info"; // Kulay Blue
                $notif_icon = "bi-check2-circle";
                $notif_link = "myloans.php?app_id={$application_id}";
            } elseif ($new_status == 'REJECTED') {
                $notif_title = "Application Returned";
                $notif_message = "Your application <strong>#LA-{$application_id}</strong> was returned for corrections. Reason: " . htmlspecialchars($remarks);
                $notif_type = "warning"; // Kulay Orange
                $notif_icon = "bi-exclamation-triangle-fill";
                $notif_link = "myloans.php?app_id={$application_id}"; // Idinagdag ang app_id para diretso sa application details page
            }

            // Execute Notification Insert
            $notif_stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type, icon, link) VALUES (?, ?, ?, ?, ?, ?)");
            $notif_stmt->bind_param("isssss", $user_id, $notif_title, $notif_message, $notif_type, $notif_icon, $notif_link);
            $notif_stmt->execute();
            $notif_stmt->close();
        }

        // Redirect back to application inbox with success message
        $msg = ($new_status == 'VERIFIED') ? "Verified & Forwarded" : "Returned to Client";
        header("Location: application.php?msg=" . urlencode($msg));
        exit();
    } else {
        echo "Error updating record: " . $conn->error;
    }
    
    $stmt->close();
}
?>