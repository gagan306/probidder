<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get username from POST data
$username = $_POST['username'] ?? '';

if (empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Username is required']);
    exit;
}

// Get user ID from username
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$stmt->close();

// Fetch user's products
$stmt = $mysqli->prepare("SELECT id, name, description, starting_price, current_price, category, end_time, status, image FROM products WHERE added_by = ? ORDER BY end_time DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$products = [];
while ($row = $result->fetch_assoc()) {
    // Convert image blob to base64
    if ($row['image']) {
        $row['image'] = 'data:image/jpeg;base64,' . base64_encode($row['image']);
    }
    $products[] = $row;
}

echo json_encode(['success' => true, 'products' => $products]);
$stmt->close();
$mysqli->close();
?> 