<?php
// db.php
$host = 'localhost';
$user = 'root';        // default XAMPP
$pass = '';            // default XAMPP
$db   = 'motomatch';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Optional: set charset
$conn->set_charset('utf8mb4');
?>