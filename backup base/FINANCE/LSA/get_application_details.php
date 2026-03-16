<?php
// 1. TAMANG PATHING
require_once '../includes/db_connect.php'; 

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    
    try {
        // 2. PDO PREPARED STATEMENT para safe sa SQL Injection
        $stmt = $pdo->prepare("SELECT doc_type, file_path FROM loan_documents WHERE loan_application_id = ?");
        $stmt->execute([$id]);
        $result = $stmt->fetchAll();
        
        $docs = [];
        if ($result) {
            foreach ($result as $row) {
                // Pag-format ng path para mahanap ang client uploads
                $row['file_path'] = '../client/' . $row['file_path'];
                $docs[] = $row;
            }
        }
        
        header('Content-Type: application/json');
        echo json_encode($docs);

    } catch (PDOException $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['error' => 'Database error']);
    }
}
?>