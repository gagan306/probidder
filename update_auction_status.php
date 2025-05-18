<?php
// This script should be run periodically (e.g., via cron job) to update auction statuses.

// Database connection details
$servername = "localhost";
$username = "root"; // Replace with your database username
$password = ""; // Replace with your database password
$dbname = "cafe_db"; // Replace with your database name

// Create connection
$mysqli = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($mysqli->connect_error) {
    // In a real application, log this error instead of echoing
    // error_log("Database Connection failed in update_auction_status.php: " . $mysqli->connect_error);
    exit(); // Exit if connection fails
}

// Find products whose end_time is in the past and status is still 'active'
$sql = "UPDATE products SET status = 'ended' WHERE status = 'active' AND end_time <= NOW()";

if ($mysqli->query($sql) === TRUE) {
    // In a real application, log the number of updated rows
    // error_log("update_auction_status.php updated " . $mysqli->affected_rows . " records successfully");
} else {
     // In a real application, log this error
    // error_log("Error updating record in update_auction_status.php: " . $mysqli->error);
}

$mysqli->close();
?> 