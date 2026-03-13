<?php
include 'includes/db_connect.php';

// Count for NORMAL New Apps (In-exclude ang Restructure dito)
$q1 = $conn->query("SELECT COUNT(*) as total FROM loan_applications WHERE status = 'PENDING' AND loan_purpose NOT LIKE '%Restructure%' AND loan_purpose NOT LIKE '%Extension%'");
$new_apps = $q1 ? $q1->fetch_assoc()['total'] : 0;

// Count for Restructure (Gamitin ang BAGONG table natin!)
$q2 = $conn->query("SELECT COUNT(*) as total FROM loan_restructure_requests WHERE status = 'PENDING'");
$restructure = $q2 ? $q2->fetch_assoc()['total'] : 0;

echo json_encode([
    'new_apps' => $new_apps,
    'restructure' => $restructure
]);
?>