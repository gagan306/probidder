<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $mysqli->connect_error]);
    exit;
}

function respond($success, $message) {
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}

// Validate required fields
$required = ['name', 'description', 'starting_price', 'category', 'start_time', 'end_time', 'status', 'added_by'];
foreach ($required as $field) {
    if (empty($_POST[$field])) {
        respond(false, "Missing field: $field");
    }
}

// Validate image
if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    respond(false, "Image upload failed.");
}

// Lookup user ID from username
$username = $_POST['added_by'];
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    respond(false, "User not found.");
}
$stmt->close();

// Prepare product data
$name = $_POST['name'];
$description = $_POST['description'];
$starting_price = floatval($_POST['starting_price']);
$current_price = 0.00;
$category = $_POST['category'];
$start_time = $_POST['start_time'];
$end_time = $_POST['end_time'];
$status = $_POST['status'];

// Handle image upload
$imageData = file_get_contents($_FILES['image']['tmp_name']);
$imageType = $_FILES['image']['type'];

// Insert product
$stmt = $mysqli->prepare("INSERT INTO products (added_by, name, description, starting_price, current_price, image, category, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    respond(false, "Prepare failed: " . $mysqli->error);
}

// Bind parameters
$stmt->bind_param("issddsssss", $user_id, $name, $description, $starting_price, $current_price, $imageData, $category, $start_time, $end_time, $status);

if ($stmt->execute()) {
    respond(true, "Product added successfully.");
} else {
    respond(false, "Database error: " . $stmt->error);
}
$stmt->close();
$mysqli->close();
?>
