<?php
declare(strict_types=1);

ini_set('display_errors', '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
    ini_set('session.cookie_secure', '0');
    ini_set('session.cookie_samesite', 'Lax');
    session_start();
}

require 'db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');

$env = getenv('APP_ENV') ?: ($_SERVER['APP_ENV'] ?? 'prod');
$isDev = ($env === 'dev');

set_exception_handler(function ($e) use ($isDev) {
    error_log('[ajax_search EXCEPTION] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    http_response_code(500);
    $payload = ['status' => 'error', 'message' => $isDev ? $e->getMessage() : 'server_error'];
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
});
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log('[ajax_search] Missing or invalid $pdo instance');
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $isDev ? 'Invalid database connection' : 'server_error']);
    exit;
}

$maxRequests = 20;
$windowSeconds = 60;
$now = time();

if (!isset($_SESSION['search_rate'])) {
    $_SESSION['search_rate'] = [];
}
$_SESSION['search_rate'] = array_filter($_SESSION['search_rate'], fn($ts) => ($now - $ts) <= $windowSeconds);

if (count($_SESSION['search_rate']) >= $maxRequests) {
    http_response_code(429);
    echo json_encode(['status' => 'rate_limited', 'message' => 'try_later']);
    exit;
}
$_SESSION['search_rate'][] = $now;

$lang = $_SESSION['lang'] ?? 'en';
$allowed_langs = ['en', 'fr'];
if (!in_array($lang, $allowed_langs, true)) {
    $lang = 'en';
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['status' => 'method_not_allowed']);
    exit;
}

$term = isset($_GET['term']) ? (string)$_GET['term'] : '';
$term = trim($term);

if ($term === '') {
    echo json_encode([]);
    exit;
}

$maxTermLen = 100;
if (function_exists('mb_strlen')) {
    if (mb_strlen($term) > $maxTermLen) {
        $term = mb_substr($term, 0, $maxTermLen);
    }
} else {
    if (strlen($term) > $maxTermLen) {
        $term = substr($term, 0, $maxTermLen);
    }
}

$term = preg_replace('/[\x00-\x1F\x7F]/u', '', $term);
$like = '%' . $term . '%';

$sql = "
SELECT DISTINCT p.id,
       COALESCE(v.var_name_en, p.name_en) AS name_en,
       COALESCE(v.var_name_fr, p.name_fr) AS name_fr
FROM products p
LEFT JOIN product_variants v ON p.id = v.product_id
WHERE p.name_en LIKE :term1
   OR p.name_fr LIKE :term2
   OR p.description_en LIKE :term3
   OR p.description_fr LIKE :term4
   OR v.var_name_en LIKE :term5
   OR v.var_name_fr LIKE :term6
LIMIT 10
";

try {
    $stmt = $pdo->prepare($sql);

    $stmt->bindValue(':term1', $like, PDO::PARAM_STR);
    $stmt->bindValue(':term2', $like, PDO::PARAM_STR);
    $stmt->bindValue(':term3', $like, PDO::PARAM_STR);
    $stmt->bindValue(':term4', $like, PDO::PARAM_STR);
    $stmt->bindValue(':term5', $like, PDO::PARAM_STR);
    $stmt->bindValue(':term6', $like, PDO::PARAM_STR);

    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $results = [];
    foreach ($rows as $r) {
        if (!isset($r['id'])) continue;
        $id = (int)$r['id'];
        $name_en = (string)($r['name_en'] ?? '');
        $name_fr = (string)($r['name_fr'] ?? '');

        $name = ($lang === 'fr') ? ($name_fr ?: $name_en) : ($name_en ?: $name_fr);
        if (function_exists('mb_substr')) {
            $name = mb_substr(trim($name), 0, 200);
        } else {
            $name = substr(trim($name), 0, 200);
        }

        $results[] = ['id' => $id, 'name' => $name];
    }

    echo json_encode($results, JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    error_log('[ajax_search ERROR] ' . $e->getMessage() . ' | term=' . $term);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $isDev ? $e->getMessage() : 'server_error']);
    exit;
}
