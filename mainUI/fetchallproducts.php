<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// --- Added: Update product status based on end time before fetching ---
$update_sql = "UPDATE products SET status = 'ended' WHERE status = 'active' AND end_time <= NOW()";
$mysqli->query($update_sql);
// Note: Errors during this update are deliberately not checked here to avoid stopping the page load.
// Proper error logging for this update should ideally happen in the dedicated background task script.

// Fetch all active products (now including those just marked as ended)
$stmt = $mysqli->prepare("SELECT p.*, u.username as seller_name 
    FROM products p 
    JOIN users u ON p.added_by = u.id 
    WHERE p.status IN ('upcoming', 'active', 'ended') -- Fetch ended ones too, as they might have just updated
    ORDER BY p.end_time ASC");

if ($stmt === false) {
     // Log error if prepare failed
     // error_log('Prepare failed for fetchallproducts query: ' . $mysqli->error);
     echo json_encode(['success' => false, 'message' => 'Failed to prepare product fetch query.']);
     exit();
}

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