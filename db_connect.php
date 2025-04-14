<?php
// Database connection
$servername = "localhost";
$username = "root"; // Replace with actual database username
$password = ""; // Replace with actual database password
$dbname = "AllVinylsMarket";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>