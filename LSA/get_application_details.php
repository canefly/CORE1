<?php
// Using your existing connection file
include 'includes/db_connect.php';

if (isset($_GET['id'])) {

    // Get ID from request
    $id = $_GET['id'];

    // Select documents linked to the specific application ID
    $query = "SELECT doc_type, file_path FROM loan_documents WHERE loan_application_id = ?";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$id]);

    $docs = [];
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        foreach ($rows as $row) {

            // Keep your original path logic
            $row['file_path'] = '../client/' . $row['file_path'];

            $docs[] = $row;
        }
    }

    header('Content-Type: application/json');
    echo json_encode($docs);
}
?>