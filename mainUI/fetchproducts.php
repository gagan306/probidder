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

// Fetch products added by the user
// Also check payment status and highest bidder info if the product is ended (sold)
$query = "
    SELECT p.*, 
           pa.payment_status AS payment_status,
           (SELECT u.username FROM bids AS b JOIN users u ON b.user_id = u.id WHERE b.product_id = p.id ORDER BY b.amount DESC, b.bid_time ASC LIMIT 1) AS highest_bidder_username
    FROM products p
    LEFT JOIN bids b_highest ON b_highest.product_id = p.id AND b_highest.amount = p.current_price -- Join for payment status only on the highest bid
    LEFT JOIN payments pa ON pa.bid_id = b_highest.id
    WHERE p.added_by = ? 
    GROUP BY p.id -- Group to avoid multiple rows if multiple bids exist
    ORDER BY p.end_time DESC
";

$stmt = $mysqli->prepare($query);
$stmt->bind_param('i', $user_id);
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