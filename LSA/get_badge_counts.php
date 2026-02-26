<?php
include 'includes/db_connect.php';

// Count for New Apps
$q1 = $conn->query("SELECT COUNT(*) as total FROM loan_applications WHERE status = 'PENDING'");
$new_apps = $q1->fetch_assoc()['total'];

// Count for Restructure
$q2 = $conn->query("SELECT COUNT(*) as total FROM loan_applications 
                    WHERE (loan_purpose LIKE '%Restructure%' OR loan_purpose LIKE '%Extension%')
                    AND status = 'PENDING'");
$restructure = $q2->fetch_assoc()['total'];

echo json_encode([
    'new_apps' => $new_apps,
    'restructure' => $restructure
]);
?>