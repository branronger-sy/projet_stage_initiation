<?php
session_start();
require '../includes/db.php';


if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in']);
    exit;
}
$product_id = (int) ($_POST['product_id'] ?? 0);


$user_id = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
$stmt->execute([$product_id]);

$stmt = $pdo->prepare("SELECT id FROM favoris WHERE user_id = ? AND product_id = ?");
$stmt->execute([$user_id, $product_id]);
$exists = $stmt->fetchColumn();

if ($exists) {
    $stmt = $pdo->prepare("DELETE FROM favoris WHERE user_id = ? AND product_id = ?");
    $stmt->execute([$user_id, $product_id]);
    $action = 'removed';
} else {
    $stmt = $pdo->prepare("INSERT INTO favoris (user_id, product_id) VALUES (?, ?)");
    $stmt->execute([$user_id, $product_id]);
    $action = 'added';
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE user_id = ?");
$stmt->execute([$user_id]);
$count = (int) $stmt->fetchColumn();

echo json_encode([
    'success' => true,
    'action'  => $action,
    'count'   => $count
]);
