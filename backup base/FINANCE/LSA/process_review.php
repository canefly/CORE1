<?php
// 1. TAMANG PATHING
require_once '../includes/db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $application_id = $_POST['application_id'];
    $new_status = $_POST['status'];
    $remarks = $_POST['remarks'] ?? ''; 

    try {
        // 2. PDO PREPARED STATEMENT para sa pag-update
        $stmt = $pdo->prepare("
            UPDATE loan_applications 
            SET status = ?, 
                remarks = ?, 
                updated_at = NOW() 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$new_status, $remarks, $application_id])) {
            $msg = ($new_status == 'VERIFIED') ? "Verified & Forwarded" : "Returned to Client";
            header("Location: application.php?msg=" . urlencode($msg));
            exit();
        }

    } catch (PDOException $e) {
        die("Error processing review: " . $e->getMessage());
    }
}
?>