<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Fetch all active products
$stmt = $mysqli->prepare("
    SELECT p.*, u.username as seller_name 
    FROM products p 
    JOIN users u ON p.added_by = u.id 
    WHERE p.status IN ('upcoming', 'active') 
    ORDER BY p.end_time ASC
");
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