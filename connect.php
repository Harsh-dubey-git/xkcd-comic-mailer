<?php
$host = 'localhost';
$db = 'xkcd_email_system';
$user = 'root';  // default for XAMPP
$pass = '';      // default is blank

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
