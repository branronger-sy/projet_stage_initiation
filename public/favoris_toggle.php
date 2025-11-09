<?php
declare(strict_types=1);

session_start();
require '../includes/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id']) || !ctype_digit((string)$_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'You must be logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$input = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (stripos($contentType, 'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    if (is_array($json)) {
        $input = $json;
    }
} else {
    $input = $_POST;
}

if (empty($input['product_id']) || !ctype_digit((string)$input['product_id'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid product_id']);
    exit;
}

$user_id    = (int) $_SESSION['user_id'];
$product_id = (int) $input['product_id'];

try {
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->execute([$product_id]);
    if (!$stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Product not found']);
        exit;
    }
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
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Database error']);
}
