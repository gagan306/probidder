<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$bid_id = $_POST['bid_id'] ?? 0;
$user_id = $_POST['user_id'] ?? 0;
$product_id = $_POST['product_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$khalti_token = $_POST['khalti_token'] ?? '';

if (empty($bid_id) || empty($user_id) || empty($product_id) || empty($amount) || empty($khalti_token)) {
    echo json_encode(['success' => false, 'message' => 'Missing payment data']);
    exit;
}

// Insert or update payment
$stmt = $mysqli->prepare('REPLACE INTO payments (bid_id, user_id, product_id, amount, payment_status, payment_method, payment_time, khalti_token) VALUES (?, ?, ?, ?, "completed", "khalti", NOW(), ?)');
$stmt->bind_param('iiids', $bid_id, $user_id, $product_id, $amount, $khalti_token);
if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Payment recorded']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to record payment']);
}
$stmt->close();
$mysqli->close(); 