<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Handle DELETE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $product_id = $_POST['product_id'] ?? 0;
    $username = $_POST['username'] ?? '';

    if (empty($username) || empty($product_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Verify product belongs to user
    $stmt = $mysqli->prepare("SELECT p.id FROM products p JOIN users u ON p.added_by = u.id WHERE p.id = ? AND u.username = ?");
    $stmt->bind_param("is", $product_id, $username);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unauthorized']);
        exit;
    }

    // Delete product
    $stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product']);
    }
    exit;
}

// Handle UPDATE request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $product_id = $_POST['product_id'] ?? 0;
    $username = $_POST['username'] ?? '';
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($username) || empty($product_id)) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Verify product belongs to user
    $stmt = $mysqli->prepare("SELECT p.id FROM products p JOIN users u ON p.added_by = u.id WHERE p.id = ? AND u.username = ?");
    $stmt->bind_param("is", $product_id, $username);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        echo json_encode(['success' => false, 'message' => 'Product not found or unauthorized']);
        exit;
    }

    // Check if new image is uploaded
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($_FILES['image']['tmp_name']);
        // Update product with new image
        $stmt = $mysqli->prepare("UPDATE products SET name = ?, description = ?, category = ?, end_time = ?, status = ?, image = ? WHERE id = ?");
        $stmt->bind_param("ssssssi", $name, $description, $category, $end_time, $status, $imageData, $product_id);
    } else {
        // Update product without changing image
        $stmt = $mysqli->prepare("UPDATE products SET name = ?, description = ?, category = ?, end_time = ?, status = ? WHERE id = ?");
        $stmt->bind_param("sssssi", $name, $description, $category, $end_time, $status, $product_id);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?> 