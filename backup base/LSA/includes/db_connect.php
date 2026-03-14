<?php
// includes/db_connect.php

$servername = "127.0.0.1"; // Or "localhost"
$username = "root";        // Default username for XAMPP/WAMP
$password = "";            // Default password is usually blank for local dev
$dbname = "microfinance_db"; // The name from your SQL dump

// Create the connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if it actually connected or if it's acting up
if ($conn->connect_error) {
    die("Database connection totally bricked: " . $conn->connect_error);
}

// Optional: Set charset to handle special characters properly
$conn->set_charset("utf8mb4");
?>