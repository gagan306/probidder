<?php
header('Content-Type: application/json');
require_once '../config/database.php';

// Check if admin is logged in
session_start();
if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get product ID from POST data
$product_id = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
    exit;
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First delete related records from bids table
    $delete_bids = "DELETE FROM bids WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $delete_bids);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Then delete related records from payments table
    $delete_payments = "DELETE FROM payments WHERE product_id = ?";
    $stmt = mysqli_prepare($conn, $delete_payments);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Finally delete the product
    $delete_product = "DELETE FROM products WHERE id = ?";
    $stmt = mysqli_prepare($conn, $delete_product);
    mysqli_stmt_bind_param($stmt, "i", $product_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // If everything is successful, commit the transaction
    mysqli_commit($conn);
    echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
} catch (Exception $e) {
    // If there's an error, rollback the transaction
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'message' => 'Error deleting product: ' . $e->getMessage()]);
}

mysqli_close($conn);
?> 