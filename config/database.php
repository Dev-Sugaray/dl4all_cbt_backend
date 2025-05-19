<?php

// Database configuration
$host = 'localhost';
$db = 'cbt_platform';
$user = 'root'; // Replace with your database user
$pass = ''; // Replace with your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Log the error and return a generic error message
    // In a production environment, avoid exposing detailed error information
    error_log("Database Connection Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Database connection failed.']);
    exit();
}

// $pdo is now available for use in other files that include this one.

?>