<?php
error_log("fetch_user_biddings.php started");
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    $error_message = 'Database connection failed: ' . $mysqli->connect_error;
    error_log($error_message);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

$username = $_POST['username'] ?? '';
if (empty($username)) {
    $error_message = 'Username required';
    error_log($error_message);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}

// Get user id
$stmt = $mysqli->prepare('SELECT id FROM users WHERE username = ?');
if ($stmt === false) {
     $error_message = 'Prepare failed: ' . $mysqli->error;
     error_log($error_message);
     echo json_encode(['success' => false, 'message' => $error_message]);
     exit();
}
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->bind_result($user_id);
if (!$stmt->fetch()) {
    $error_message = 'User not found for username: ' . $username;
    error_log($error_message);
    echo json_encode(['success' => false, 'message' => $error_message]);
    exit;
}
$stmt->close();
error_log("User ID fetched: " . $user_id);

// Get all products the user has bid on, showing user's highest bid for each
$query = "
    SELECT p.*, p.status AS product_status,
           (SELECT MAX(amount) FROM bids WHERE product_id = p.id) AS highest_bid,
           (SELECT u.username FROM bids AS bb JOIN users u ON bb.user_id = u.id WHERE bb.product_id = p.id ORDER BY bb.amount DESC, bb.bid_time ASC LIMIT 1) AS highest_bidder,
           -- Get the user's highest bid details for this product
           (SELECT amount FROM bids WHERE product_id = p.id AND user_id = ? ORDER BY amount DESC, bid_time ASC LIMIT 1) AS user_highest_bid_amount,
           (SELECT bid_time FROM bids WHERE product_id = p.id AND user_id = ? ORDER BY amount DESC, bid_time ASC LIMIT 1) AS user_highest_bid_time,
           (SELECT id FROM bids WHERE product_id = p.id AND user_id = ? ORDER BY amount DESC, bid_time ASC LIMIT 1) AS user_highest_bid_id
    FROM products p
    JOIN (SELECT DISTINCT product_id FROM bids WHERE user_id = ?) AS user_bids ON p.id = user_bids.product_id
    ORDER BY p.end_time DESC
";

$stmt = $mysqli->prepare($query);
if ($stmt === false) {
     $error_message = 'Prepare failed for user biddings query: ' . $mysqli->error;
     error_log($error_message);
     echo json_encode(['success' => false, 'message' => $error_message]);
     exit();
}
$stmt->bind_param('iiii', $user_id, $user_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

$biddings = [];
while ($row = $result->fetch_assoc()) {
    // Get all bids for this product (for displaying the list of bids)
    $bids = [];
    $bid_stmt = $mysqli->prepare('SELECT b.amount, b.bid_time, u.username FROM bids b JOIN users u ON b.user_id = u.id WHERE b.product_id = ? ORDER BY b.amount DESC, b.bid_time ASC');
     if ($bid_stmt === false) {
         $error_message = 'Prepare failed for fetching all bids: ' . $mysqli->error;
         error_log($error_message);
         // Continue processing other products, but log the error
     } else {
        $bid_stmt->bind_param('i', $row['id']);
        $bid_stmt->execute();
        $bid_result = $bid_stmt->get_result();
        while ($bid = $bid_result->fetch_assoc()) {
            $bids[] = $bid;
        }
        $bid_stmt->close();
     }

    // Get payment status if user won
    $payment_status = null;
    // Fetch payment status based on the winning bid for this product
    // Note: This assumes the user's highest bid is the winning bid if they won.
    // The previous logic in index.html confirms user is highest_bidder AND status is ended.
    if ($row['product_status'] === 'ended' && $row['highest_bidder'] === $username) {
         // We need the bid_id of the winning bid to check for payment. The highest_bidder check implies the user's highest bid (user_highest_bid_id) is the winning one.
         if ($row['user_highest_bid_id']) {
             $pay_stmt = $mysqli->prepare('SELECT payment_status FROM payments WHERE bid_id = ? LIMIT 1');
              if ($pay_stmt === false) {
                 $error_message = 'Prepare failed for fetching payment status: ' . $mysqli->error;
                 error_log($error_message);
                 // Continue
              } else {
                $pay_stmt->bind_param('i', $row['user_highest_bid_id']);
                $pay_stmt->execute();
                $pay_stmt->bind_result($payment_status);
                $pay_stmt->fetch();
                $pay_stmt->close();
              }
         }
    }

    // Convert image blob to base64
    if ($row['image']) {
        $row['image'] = 'data:image/jpeg;base64,' . base64_encode($row['image']);
    }

    $biddings[] = [
        'product_id' => $row['id'], // Use product id as the main identifier
        'product_name' => $row['name'],
        'image' => $row['image'],
        'user_highest_bid_amount' => $row['user_highest_bid_amount'], // User's highest bid
        'user_highest_bid_time' => $row['user_highest_bid_time'], // Time of user's highest bid
        'end_time' => $row['end_time'],
        'product_status' => $row['product_status'],
        'highest_bid' => $row['highest_bid'],
        'highest_bidder' => $row['highest_bidder'],
        'bids' => $bids, // All bids for this product
        'is_winner' => ($row['product_status'] === 'ended' && $row['highest_bidder'] === $username),
        'payment_status' => $payment_status,
        'user_highest_bid_id' => $row['user_highest_bid_id'] // ID of user's highest bid for payment
    ];
}

$stmt->close();
$mysqli->close();
error_log("fetch_user_biddings.php finished successfully. Found " . count($biddings) . " biddings.");
echo json_encode(['success' => true, 'biddings' => $biddings]); 