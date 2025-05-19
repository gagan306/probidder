<?php
session_start();

// Database connection
$conn = mysqli_connect('localhost', 'root', '', 'cafe_db');

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to utf8mb4
mysqli_set_charset($conn, "utf8mb4");

// Debug: Check if bidAdmin table exists
$table_check = mysqli_query($conn, "SHOW TABLES LIKE 'bidAdmin'");
if (mysqli_num_rows($table_check) == 0) {
    // Create bidAdmin table if it doesn't exist
    $create_table = "CREATE TABLE IF NOT EXISTS bidAdmin (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    mysqli_query($conn, $create_table);

    // Insert default admin user (username: admin, password: admin123)
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO bidAdmin (username, password) VALUES ('admin', '$default_password')";
    mysqli_query($conn, $insert_admin);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = strtolower(mysqli_real_escape_string($conn, $_POST['username']));
    $password = $_POST['password'];

    // Debug: Print the username being checked
    error_log("Attempting login with username: " . $username);

    // First, let's check what's in the database
    $check_query = "SELECT * FROM bidAdmin";
    $check_result = mysqli_query($conn, $check_query);
    while($row = mysqli_fetch_assoc($check_result)) {
        error_log("Found in database - Username: " . $row['username'] . ", Password hash: " . $row['password']);
    }

    $query = "SELECT * FROM bidAdmin WHERE LOWER(username) = '$username'";
    $result = mysqli_query($conn, $query);

    if ($result && mysqli_num_rows($result) > 0) {
        $admin = mysqli_fetch_assoc($result);
        // Debug: Check if password verification is working
        error_log("Password verification result: " . (password_verify($password, $admin['password']) ? 'true' : 'false'));
        error_log("Input password: " . $password);
        error_log("Stored hash: " . $admin['password']);
        
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: admin.php');
            exit();
        }
    } else {
        error_log("No user found with username: " . $username);
    }

    // Let's recreate the admin user to ensure it exists
    $delete_query = "DELETE FROM bidAdmin WHERE username = 'admin'";
    mysqli_query($conn, $delete_query);
    
    $default_password = password_hash('admin123', PASSWORD_DEFAULT);
    $insert_admin = "INSERT INTO bidAdmin (username, password) VALUES ('admin', '$default_password')";
    mysqli_query($conn, $insert_admin);
    
    error_log("Recreated admin user with password hash: " . $default_password);

    header('Location: admin.php?error=1');
    exit();
}
?> 