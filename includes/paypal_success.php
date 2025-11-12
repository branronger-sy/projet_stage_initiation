<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require 'db.php';
header('Content-Type: application/json');

$clientId = "AcwPzd4ufDoHiYZSvY_tDcIkaR2KgNHY3ruNDUmcWCw0QyshN1Rn9l-aD1V1qcNEh7r1tA0whxghHFet";
$secret   = "EGvUliCc-OUIlTjKOdMjVx84y_nGZbIFIB25V7xwlk6SNf0uDG3thw9NMyhC953-qvt28_eC02APIck5";

$data = json_decode(file_get_contents("php://input"), true);
if (!isset($data['orderID'])) {
    echo json_encode(['status' => 'error', 'message' => 'Missing orderID']);
    exit;
}
$token = getPaypalAccessToken($clientId, $secret);
if (!$token) {
    echo json_encode(['status' => 'error', 'message' => 'Could not get PayPal token']);
    exit;
}

$orderId = $data['orderID'];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/" . $orderId);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $token"
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$orderData = json_decode($response, true);
if (!isset($orderData['status']) || $orderData['status'] !== 'COMPLETED') {
    echo json_encode(['status' => 'error', 'message' => 'Payment not completed or invalid orderID']);
    exit;
}
$user_id = $_SESSION['user_id'];
$cart = $_SESSION['cart'] ?? [];
$total_shipping = $_SESSION['shipping_price'] ?? 0;

$total_products = 0;
foreach ($cart as $item) {
    $total_products += $item['price'] * $item['quantity'];
}
$total_price = $total_products + $total_shipping;

$stmt = $pdo->prepare("SELECT id FROM shipping_addresses WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$shipping_address_id = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT id FROM billing_addresses WHERE user_id = ? LIMIT 1");
$stmt->execute([$user_id]);
$billing_address_id = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    INSERT INTO orders (user_id, total_price, payment_status, shipping_method, shipping_address_id, billing_address_id) 
    VALUES (?, ?, 'paid', 'default', ?, ?)
");
$stmt->execute([$user_id, $total_price, $shipping_address_id, $billing_address_id]);
$order_id = $pdo->lastInsertId();

foreach ($cart as $item) {
    $variant_id = !empty($item['variant_id']) ? $item['variant_id'] : null;

    $stmt = $pdo->prepare("
        INSERT INTO order_items (order_id, variant_id, quantity, price) 
        VALUES (?, ?, ?, ?)
    ");
    $stmt->bindValue(1, $order_id, PDO::PARAM_INT);
    if ($variant_id === null) {
        $stmt->bindValue(2, null, PDO::PARAM_NULL);
    } else {
        $stmt->bindValue(2, $variant_id, PDO::PARAM_INT);
    }
    $stmt->bindValue(3, $item['quantity'], PDO::PARAM_INT);
    $stmt->bindValue(4, $item['price']);
    $stmt->execute();
}

$stmt = $pdo->prepare("SELECT id FROM customers WHERE user_id = ?");
$stmt->execute([$user_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);

if ($customer) {
    $stmt = $pdo->prepare("
        UPDATE customers 
        SET total_orders = total_orders + 1, 
            total_spent = total_spent + ?, 
            last_order_date = NOW() 
        WHERE user_id = ?
    ");
    $stmt->execute([$total_price, $user_id]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO customers (user_id, total_orders, total_spent, last_order_date) 
        VALUES (?, 1, ?, NOW())
    ");
    $stmt->execute([$user_id, $total_price]);
}

unset($_SESSION['cart']);
unset($_SESSION['checkout_progress']);

$_SESSION['payment_success'] = true;

echo json_encode([
    'status' => 'success',
    'redirect' => 'index.php?page=success'
]);
function getPaypalAccessToken($clientId, $secret) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v1/oauth2/token");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Accept: application/json",
        "Accept-Language: en_US"
    ]);
    curl_setopt($ch, CURLOPT_USERPWD, $clientId . ":" . $secret);
    curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    $json = json_decode($response, true);
    return $json['access_token'] ?? null;
}
