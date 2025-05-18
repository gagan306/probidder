<?php
header('Content-Type: application/json');

$mysqli = new mysqli('localhost', 'root', '', 'cafe_db');
if ($mysqli->connect_errno) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

$action = $_POST['action'] ?? '';
$product_id = $_POST['product_id'] ?? 0;
$username = $_POST['username'] ?? '';

if (empty($action) || empty($product_id) || empty($username)) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
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

// --- Check if product exists and is owned by the user ---
$stmt = $mysqli->prepare("SELECT status FROM products WHERE id = ? AND added_by = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();
$stmt->bind_result($product_status);

if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Product not found or you do not own this product']);
    exit;
}
$stmt->close();

// --- CORE FIX: Prevent updates or deletion if auction has ended ---
if ($product_status === 'ended') {
    echo json_encode(['success' => false, 'message' => 'Cannot modify an auction that has ended.']);
    exit;
}

// --- Handle Update Action ---
if ($action === 'update') {
    $name = $_POST['name'] ?? '';
    $description = $_POST['description'] ?? '';
    $category = $_POST['category'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $status = $_POST['status'] ?? '';

    // Basic validation
    if (empty($name) || empty($description) || empty($category) || empty($end_time) || empty($status)) {
        echo json_encode(['success' => false, 'message' => 'Missing update fields']);
        exit;
    }

    // Prepare update query
    $query = "UPDATE products SET name = ?, description = ?, category = ?, end_time = ?, status = ?";
    $params = ['sssss', $name, $description, $category, $end_time, $status];

    // Handle image update
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $image_data = file_get_contents($_FILES['image']['tmp_name']);
        $query .= ", image = ?";
        $params[0] .= 'b'; // Add blob type
        $params[] = $image_data;
    }

    $query .= " WHERE id = ? AND added_by = ?";
    $params[0] .= 'ii'; // Add integer types for WHERE clause
    $params[] = $product_id;
    $params[] = $user_id;

    $stmt = $mysqli->prepare($query);
    if ($stmt === false) {
         echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $mysqli->error]);
         exit();
    }

    // Bind parameters dynamically
    call_user_func_array([$stmt, 'bind_param'], refValues($params));

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Product updated successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update product: ' . $stmt->error]);
    }
    $stmt->close();

} elseif ($action === 'delete') {
    // --- Handle Delete Action ---
    $stmt = $mysqli->prepare("DELETE FROM products WHERE id = ? AND added_by = ?");
    if ($stmt === false) {
         echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $mysqli->error]);
         exit();
    }
    $stmt->bind_param("ii", $product_id, $user_id);
    if ($stmt->execute()) {
        if ($mysqli->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Product deleted successfully']);
        } else {
             echo json_encode(['success' => false, 'message' => 'Product not found or already deleted']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to delete product: ' . $stmt->error]);
    }
    $stmt->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

$mysqli->close();

// Helper function for dynamic bind_param
function refValues($arr){
    if (strnatcmp(phpversion(),'5.3') >= 0) //Reference is required for PHP 5.3+ for call_user_func_array
    {
        $refs = array();
        foreach($arr as $key => $value)
            $refs[$key] = & $arr[$key];
        return $refs;
    }
    return $arr;
}
?> 