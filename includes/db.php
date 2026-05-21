<?php
// =============================================
// Database Configuration
// =============================================
define('DB_HOST', 'localhost');
define('DB_USER', 'root');       // Change if needed
define('DB_PASS', '');           // Change if needed
define('DB_NAME', 'ocarang_db');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['error' => 'Connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}
?>
