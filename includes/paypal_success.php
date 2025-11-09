<?php
if (session_status() === PHP_SESSION_NONE) session_start();

require 'db.php';
require 'config.php';

header('Content-Type: application/json');

$clientId = getenv('PAYPAL_CLIENT_ID');
$secret   = getenv('PAYPAL_SECRET');
$apiBase  = getenv('PAYPAL_API_BASE') ?: "https://api-m.sandbox.paypal.com";

if (!$clientId || !$secret) {
    echo json_encode(['status' => 'error', 'message' => 'PayPal credentials missing']);
    exit;
}
$input = json_decode(file_get_contents("php://input"), true);
if (!isset($input['orderID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing orderID']);
    exit;
}
$orderId = $input['orderID'];
function getPaypalAccessToken($clientId, $secret, $apiBase) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiBase . "/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Accept-Language: en_US"
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    if ($response === false) {
        curl_close($ch);
        return null;
    }
    curl_close($ch);

    $json = json_decode($response, true);
    return $json['access_token'] ?? null;
}
$token = getPaypalAccessToken($clientId, $secret, $apiBase);
if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'Could not get PayPal token']);
    exit;
}
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiBase . "/v2/checkout/orders/" . urlencode($orderId));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
if ($response === false) {
    echo json_encode(['status' => 'error', 'message' => 'PayPal request failed: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

$orderData = json_decode($response, true);
if (!$orderData) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid PayPal response']);
    exit;
}
if (($orderData['status'] ?? null) !== 'COMPLETED') {
    echo json_encode(['status' => 'error', 'message' => 'Order not completed']);
    exit;
}
$purchaseUnits = $orderData['purchase_units'] ?? [];
if (empty($purchaseUnits) || !isset($purchaseUnits[0]['payments']['captures'][0])) {
    echo json_encode(['status' => 'error', 'message' => 'No capture found']);
    exit;
}

$capture         = $purchaseUnits[0]['payments']['captures'][0];
$captureStatus   = $capture['status'] ?? null;
$captureId       = $capture['id'] ?? null;
$paypalAmount    = $capture['amount']['value'] ?? null;
$paypalCurrency  = $capture['amount']['currency_code'] ?? null;
$payerEmail      = $orderData['payer']['email_address'] ?? null;

if ($captureStatus !== 'COMPLETED') {
    echo json_encode(['status' => 'error', 'message' => 'Capture not completed']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$total_shipping = $_SESSION['shipping_price'] ?? 0.0;

$total_products = 0.0;
foreach ($cart as $item) {
    $total_products += ((float)$item['price']) * ((int)$item['quantity']);
}
$total_price_mad = $total_products + (float)$total_shipping;

$rateUsd = defined('RATES') && isset(RATES['USD']) ? (float)RATES['USD'] : 0.1;
$expected_usd = round($total_price_mad * $rateUsd, 2);
$paypalAmountFloat = (float)$paypalAmount;
if ($paypalCurrency !== 'USD') {
    error_log("Currency mismatch: expected USD, got $paypalCurrency");
    echo json_encode(['status' => 'error', 'message' => 'Currency mismatch']);
    exit;
}
if (abs($paypalAmountFloat - $expected_usd) > 0.05) {
    error_log("Amount mismatch: expected $expected_usd, got $paypalAmountFloat");
    echo json_encode(['status' => 'error', 'message' => 'Amount mismatch']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM orders WHERE paypal_order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'success', 'message' => 'Order already processed']);
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM shipping_addresses WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$shipping_address_id = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id FROM billing_addresses WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$billing_address_id = $stmt->fetchColumn();

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO orders (user_id, shipping_address_id, billing_address_id, shipping_method, total_price, payment_status, created_at, order_status, paypal_order_id, paypal_capture_id, payer_email, paypal_response)
        VALUES (?, ?, ?, 'paypal', ?, 'paid', NOW(), 'new', ?, ?, ?, ?)
    ");
    $paypal_response_text = json_encode($orderData);
    $stmt->execute([
        $user_id,
        $shipping_address_id,
        $billing_address_id,
        $total_price_mad,
        $orderId,
        $captureId,
        $payerEmail,
        $paypal_response_text
    ]);
    $order_id = $pdo->lastInsertId();

    $insertItem = $pdo->prepare("
        INSERT INTO order_items (order_id, product_id, variant_id, quantity, price)
        VALUES (?, ?, ?, ?, ?)
    ");
    foreach ($cart as $item) {
        $variant_id = $item['variant_id'] ?? null;
        $product_id = $item['id'] ?? null;
        $insertItem->execute([
            $order_id,
            $product_id,
            $variant_id,
            (int)$item['quantity'],
            (float)$item['price']
        ]);

        if ($product_id) {
            $stmtUpd = $pdo->prepare("
                UPDATE products SET stock = GREATEST(stock - ?, 0), sales_count = sales_count + ? WHERE id = ?
            ");
            $stmtUpd->execute([(int)$item['quantity'], (int)$item['quantity'], $product_id]);
        }
    }

    $pdo->commit();
    unset($_SESSION['cart']);
    unset($_SESSION['checkout_progress']);
    $_SESSION['payment_success'] = true;

    echo json_encode(['status' => 'success', 'redirect' => 'index.php?page=success', 'order_id' => $order_id]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("PayPal processing error: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
}
