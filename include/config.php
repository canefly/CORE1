<?php

$host = 'localhost';
$db   = 'microfinance_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
try {
     $pdo = new PDO($dsn, $user, $pass); 
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>