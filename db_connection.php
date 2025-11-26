<?php
// Database connection credentials
$host = 'localhost'; // Database host (usually localhost)
$db = 'wedding_application'; // Your database name
$user = 'root'; // Your database username
$password = ''; // Your database password (leave empty for default XAMPP/WAMP)

// Create a connection
$conn = new mysqli($host, $user, $password, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// If you need additional settings for your connection, such as charset
$conn->set_charset("utf8");

// Optionally, include a success message for debugging
// echo "Database connected successfully!";
?>
