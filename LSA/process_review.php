<?php
include 'includes/db_connect.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $application_id = mysqli_real_escape_string($conn, $_POST['application_id']);
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    // Capture the remarks from the textarea
    $remarks = mysqli_real_escape_string($conn, $_POST['remarks']); 

    /** * We update the status and the remarks. 
     * If it's APPROVED, remarks can be empty or "Documents Verified".
     */
    $sql = "UPDATE loan_applications 
            SET status = '$new_status', 
                remarks = '$remarks', 
                updated_at = NOW() 
            WHERE id = '$application_id'";

    if ($conn->query($sql) === TRUE) {
        $msg = ($new_status == 'APPROVED') ? "Verified & Forwarded" : "Returned to Client";
        header("Location: application.php?msg=" . urlencode($msg));
        exit();
    } else {
        echo "Error: " . $conn->error;
    }
}
?>