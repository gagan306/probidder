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

$ppClientId = getenv('PAYPAL_CLIENT_ID') ?: 'ATAm1BjTXtgr3w72xeZ7BM1D91AoFmphoJdr_nbwghXhQYt0qbzU9sA06VeVVe2FULqATqGfnElcuonB';
$ppSecret = getenv('PAYPAL_SECRET') ?: 'EILJ1JgKmcAXWfoHEPZfW5872r-7AGftNB-hWje54gEWj_U46XspJUjIIzC9Fdly8saGoMjNqUc4qUvN';
$ppBase = 'https://api-m.sandbox.paypal.com';

// If user canceled
if (isset($_GET['cancel'])) {
    header('Location: ' . rtrim(SITE_URL, '/') . '/mainUI/index.html?payment=cancel');
    exit;
}

// JS SDK returns orderID in POST or GET
$orderId = isset($_POST['orderID']) ? trim($_POST['orderID']) : (isset($_GET['token']) ? trim($_GET['token']) : '');
if ($orderId === '') {
    echo 'Missing order token';
    exit;
}

// 1) Access token
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ppBase . '/v1/oauth2/token',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => $ppClientId . ':' . $ppSecret,
    CURLOPT_POSTFIELDS => 'grant_type=client_credentials'
]);
$tokenResp = curl_exec($ch);
$tokenCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($tokenCode !== 200) {
    echo 'Token error';
    exit;
}

$tokenData = json_decode($tokenResp, true);
$accessToken = $tokenData['access_token'] ?? '';
if ($accessToken === '') {
    echo 'Invalid token';
    exit;
}

// 2) Capture order
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $ppBase . '/v2/checkout/orders/' . urlencode($orderId) . '/capture',
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CUSTOMREQUEST => 'POST',
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $accessToken
    ]
]);
$capResp = curl_exec($ch);
$capCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($capCode !== 201 && $capCode !== 200) {
    if (isset($_POST['orderID'])) {
        header('Content-Type: application/json');
        http_response_code(400);
        echo $capResp;
        exit;
    }
    header('Location: ' . rtrim(SITE_URL, '/') . '/mainUI/index.html?payment=failed');
    exit;
}

$capData = json_decode($capResp, true);

// Extract our internal payment id from purchase_units reference_id
$referenceId = '';
if (isset($capData['purchase_units'][0]['reference_id'])) {
    $referenceId = (string) $capData['purchase_units'][0]['reference_id'];
}

if ($referenceId === '') {
    if (isset($_POST['orderID'])) {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Missing reference_id']);
        exit;
    }
    header('Location: ' . rtrim(SITE_URL, '/') . '/mainUI/index.html?payment=failed');
    exit;
}

$paymentId = (int) $referenceId;

// Mark as completed
$stmt = mysqli_prepare($conn, 'UPDATE payments SET payment_status = "completed", payment_time = NOW() WHERE id = ?');
mysqli_stmt_bind_param($stmt, 'i', $paymentId);
mysqli_stmt_execute($stmt);
mysqli_stmt_close($stmt);

if (isset($_POST['orderID'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'COMPLETED']);
    exit;
}

header('Location: ' . rtrim(SITE_URL, '/') . '/mainUI/index.html?payment=success');
exit;
?>


