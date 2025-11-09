<?php
if (!defined('APP_INIT')) {
    http_response_code(403);
    exit('Forbidden');
}

$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'] ?? '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Strict', // أو 'Lax' إن كنت تحتاج بعض المرونة
];
session_set_cookie_params($cookieParams);
session_start();

require 'db.php'; 
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

if (empty($_SESSION['user_id']) || !ctype_digit((string)$_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Authentication required']);
    exit;
}
$userId = (int)$_SESSION['user_id'];

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf_token'] ?? null);
if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
    exit;
}
try {
    $stmt = $pdo->prepare('DELETE FROM favoris WHERE user_id = ?');
    $stmt->execute([$userId]);
    $deleted = $stmt->rowCount();

    http_response_code(200);
    echo json_encode([
        'success' => true,
        'deleted' => $deleted
    ]);
    exit;
} catch (Throwable $e) {
    error_log('favoris_clear.php error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
    exit;
}
