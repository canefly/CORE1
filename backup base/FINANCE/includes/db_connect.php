<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "microfinance_db";

try {
    // We create $pdo here so dashboard.php can find it
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    
    // Set error mode to exception so we can see SQL errors clearly
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Set default fetch mode to Associative Array
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Connection Failed: " . $e->getMessage());
}
?>