<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Get bid data
$product_id = $_POST['product_id'] ?? 0;
$username = $_POST['username'] ?? '';
$amount = floatval($_POST['amount'] ?? 0);

if (empty($username) || empty($product_id) || $amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid bid data']);
    exit;
}

// Get user ID
$stmt = $mysqli->prepare("SELECT id FROM users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'User not found']);
    exit;
}
$stmt->close();

// Get product info and check if user is the creator
$stmt = $mysqli->prepare("SELECT current_price, end_time, status, added_by FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

if ($product['status'] !== 'active') {
    echo json_encode(['success' => false, 'message' => 'Auction is not active']);
    exit;
}

if (new DateTime($product['end_time']) < new DateTime()) {
    echo json_encode(['success' => false, 'message' => 'Auction has ended']);
    exit;
}

if ($amount <= $product['current_price']) {
    echo json_encode(['success' => false, 'message' => 'Bid must be higher than current price']);
    exit;
}

// Prevent product creator from bidding
if ($user_id == $product['added_by']) {
    echo json_encode(['success' => false, 'message' => 'You cannot bid on your own product']);
    exit;
}

// Update product price and insert bid atomically
$mysqli->begin_transaction();
try {
    // Update product price
    $stmt = $mysqli->prepare("UPDATE products SET current_price = ? WHERE id = ?");
    $stmt->bind_param("di", $amount, $product_id);
    if (!$stmt->execute()) {
        throw new Exception('Failed to update product price');
    }
    $stmt->close();

    // Insert bid
    $stmt = $mysqli->prepare("INSERT INTO bids (product_id, user_id, amount, bid_time) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iid", $product_id, $user_id, $amount);
    if (!$stmt->execute()) {
        throw new Exception('Failed to record bid (possible conflict)');
    }
    $stmt->close();

    $mysqli->commit();
    echo json_encode(['success' => true, 'message' => 'Bid placed successfully']);
} catch (Exception $e) {
    $mysqli->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$mysqli->close();
?> 