<?php
// Using your existing connection file
include 'includes/db_connect.php'; 

if (isset($_GET['id'])) {
    // Sanitize input to prevent SQL injection
    $id = mysqli_real_escape_string($conn, $_GET['id']);
    
    // Select documents linked to the specific application ID
    $query = "SELECT doc_type, file_path FROM loan_documents WHERE loan_application_id = '$id'";
    $result = $conn->query($query);
    
    $docs = [];
    if ($result) {
        while ($row = $result->fetch_assoc()) {
          
            $row['file_path'] = '../client/' . $row['file_path'];
            $docs[] = $row;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode($docs);
}
?>