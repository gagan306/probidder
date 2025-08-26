<?php
session_start();
require_once '../config/database.php';

// Optional .env
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('Dotenv\\Dotenv')) {
        $dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
        try { $dotenv->load(); } catch (Throwable $e) {}
    }
}

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$userId = (int) $_SESSION['user_id'];
$bidId = isset($_POST['bid_id']) ? (int) $_POST['bid_id'] : 0;
$productId = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$amount = isset($_POST['amount']) ? (float) $_POST['amount'] : 0.0; // decimal amount
$currency = isset($_POST['currency']) ? strtoupper(trim($_POST['currency'])) : 'USD';

if ($bidId <= 0 || $productId <= 0 || $amount <= 0) {
    http_response_code(400);
    echo 'Invalid request parameters';
    exit;
}

// Create pending payment
$stmt = mysqli_prepare(
    $conn,
    'INSERT INTO payments (bid_id, user_id, product_id, amount, payment_status, payment_method, payment_time, khalti_token) VALUES (?, ?, ?, ?, "pending", "paypal", NOW(), NULL)'
);
mysqli_stmt_bind_param($stmt, 'iiid', $bidId, $userId, $productId, $amount);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo 'Failed to create payment record';
    exit;
}
$paymentId = mysqli_insert_id($conn);
mysqli_stmt_close($stmt);

// PayPal sandbox credentials
$ppClientId = getenv('PAYPAL_CLIENT_ID') ?: 'ATAm1BjTXtgr3w72xeZ7BM1D91AoFmphoJdr_nbwghXhQYt0qbzU9sA06VeVVe2FULqATqGfnElcuonB';
$ppSecret = getenv('PAYPAL_SECRET') ?: 'EILJ1JgKmcAXWfoHEPZfW5872r-7AGftNB-hWje54gEWj_U46XspJUjIIzC9Fdly8saGoMjNqUc4qUvN';
$ppBase = 'https://api-m.sandbox.paypal.com';

// 1) Get access token
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ppBase . '/v1/oauth2/token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $ppClientId . ':' . $ppSecret,
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials'
]);
$tokenResp = curl_exec($ch);
$tokenErr = curl_error($ch);
$tokenCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenErr || $tokenCode !== 200) {
    // Mark failed
    $stmt = mysqli_prepare($conn, 'UPDATE payments SET payment_status = "failed" WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $paymentId, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    http_response_code(502);
    echo 'PayPal token error: ' . $tokenResp;
    exit;
}

$tokenData = json_decode($tokenResp, true);
$accessToken = $tokenData['access_token'] ?? '';
if ($accessToken === '') {
    http_response_code(502);
    echo 'Invalid PayPal token response';
    exit;
}

// 2) Create order
$returnUrl = rtrim(SITE_URL, '/') . '/mainUI/paypal_capture.php';
$cancelUrl = rtrim(SITE_URL, '/') . '/mainUI/paypal_capture.php?cancel=1';

$orderPayload = [
    'intent' => 'CAPTURE',
    'purchase_units' => [[
        'reference_id' => (string) $paymentId,
        'amount' => [
            'currency_code' => $currency,
            'value' => number_format($amount, 2, '.', '')
        ]
    ]],
    'application_context' => [
        'return_url' => $returnUrl,
        'cancel_url' => $cancelUrl,
        'brand_name' => 'ProBidder',
        'shipping_preference' => 'NO_SHIPPING',
        'user_action' => 'PAY_NOW'
    ]
];

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ppBase . '/v2/checkout/orders',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ],
    CURLOPT_POSTFIELDS => json_encode($orderPayload)
]);
$orderResp = curl_exec($ch);
$orderErr = curl_error($ch);
$orderCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($orderErr || ($orderCode !== 201 && $orderCode !== 200)) {
    $stmt = mysqli_prepare($conn, 'UPDATE payments SET payment_status = "failed" WHERE id = ? AND user_id = ?');
    mysqli_stmt_bind_param($stmt, 'ii', $paymentId, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    http_response_code(400);
    echo 'PayPal order error: ' . $orderResp;
    exit;
}

$orderData = json_decode($orderResp, true);
$orderId = $orderData['id'] ?? '';

if ($orderId === '') {
    http_response_code(400);
    echo 'Missing PayPal order id';
    exit;
}

// Save PayPal order id into token column
$stmt = mysqli_prepare($conn, 'UPDATE payments SET khalti_token = ? WHERE id = ? AND user_id = ?');
mysqli_stmt_bind_param($stmt, 'sii', $orderId, $paymentId, $userId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

// Find approval link
$approval = '';
if (isset($orderData['links']) && is_array($orderData['links'])) {
    foreach ($orderData['links'] as $link) {
        if (($link['rel'] ?? '') === 'approve' && !empty($link['href'])) {
            $approval = $link['href'];
            break;
        }
    }
}

// If SDK mode requested, return JSON instead of redirect
if (isset($_POST['sdk']) || isset($_GET['sdk'])) {
    header('Content-Type: application/json');
    echo json_encode($orderData);
    exit;
}

if ($approval === '') {
    http_response_code(400);
    echo 'No approval link in response';
    exit;
}

header('Location: ' . $approval);
exit;
?>


