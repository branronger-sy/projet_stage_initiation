<?php
declare(strict_types=1);
ob_start();

ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'] ?? 0,
    'path'     => $cookieParams['path'] ?? '/',
    'domain'   => $cookieParams['domain'] ?? '',
    'secure'   => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require '../includes/db.php';

$allowed_langs = ['en','ar','fr'];
$allowed_currencies = ['MAD','USD','EUR'];
function safe_header_location(string $url): string {
    return str_replace(["\r", "\n"], '', $url);
}

function is_valid_asset_path(string $p): bool {
    if ($p === '') return false;
    if (preg_match('#[:\\\\]#', $p)) return false;
    if (strpos($p, '..') !== false) return false;
    if (!preg_match('#^[a-zA-Z0-9_\-\/\.]+$#', $p)) return false;
    return true;
}

function sanitize_asset_list(array $list): array {
    $out = [];
    foreach ($list as $item) {
        $item = trim((string)$item);
        $item = ltrim($item, '/');
        $item = str_replace('\\', '/', $item);
        if (is_valid_asset_path($item)) {
            $out[] = $item;
        } else {
            error_log('Blocked invalid asset path: ' . $item);
        }
    }
    return array_values(array_unique($out));
}
$parsedGet = filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING) ?? [];

if (isset($parsedGet['lang'])) {
    $candidate = strtolower(trim($parsedGet['lang']));
    if (in_array($candidate, $allowed_langs, true)) {
        $_SESSION['lang'] = $candidate;
        setcookie('lang', $candidate, [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    $params = $_GET;
    unset($params['lang']);
    $query = http_build_query($params);
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    if (!empty($query)) $redirectUrl .= '?' . $query;
    header('Location: ' . safe_header_location($redirectUrl));
    exit;
}

if (isset($parsedGet['currency'])) {
    $candidate = strtoupper(trim($parsedGet['currency']));
    if (in_array($candidate, $allowed_currencies, true)) {
        $_SESSION['currency'] = $candidate;
        setcookie('currency', $candidate, [
            'expires' => time() + 86400 * 30,
            'path' => '/',
            'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }
    $params = $_GET;
    unset($params['currency']);
    $query = http_build_query($params);
    $redirectUrl = strtok($_SERVER['REQUEST_URI'], '?');
    if (!empty($query)) $redirectUrl .= '?' . $query;
    header('Location: ' . safe_header_location($redirectUrl));
    exit;
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_checkout'])) {
        $posted = $_POST['csrf_token'] ?? '';
        if (!hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$posted)) {
            error_log('CSRF mismatch for start_checkout IP:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
            http_response_code(400);
            exit('Bad request.');
        }
        if (!empty($_SESSION['cart']) && is_array($_SESSION['cart'])) {
            $_SESSION['checkout_progress']['summary'] = true;
            header('Location: index.php?page=summary');
            exit;
        }
    }
}
$query_popular = "
SELECT 
    p.id AS product_id,
    COALESCE(v.var_name_en, p.name_en) AS name_en,
    COALESCE(v.var_name_fr, p.name_fr) AS name_fr,
    COALESCE(v.price, p.price) AS price,
    p.sales_count,
    i.image_url,
    (SELECT COUNT(*) FROM product_variants v2 WHERE v2.product_id = p.id) AS variant_count
FROM products p
LEFT JOIN product_variants v ON p.id = v.product_id AND v.main_variant = 1
LEFT JOIN product_images i ON p.id = i.product_id AND i.is_main = 1
ORDER BY p.sales_count DESC
LIMIT 14
";

$query_last = "
SELECT 
    p.id AS product_id,
    COALESCE(v.var_name_en, p.name_en) AS name_en,
    COALESCE(v.price, p.price) AS price,
    i.image_url,
    (SELECT COUNT(*) FROM product_variants v2 WHERE v2.product_id = p.id) AS variant_count
FROM products p
LEFT JOIN product_variants v ON p.id = v.product_id AND v.main_variant = 1
LEFT JOIN product_images i ON p.id = i.product_id AND i.is_main = 1
ORDER BY p.date_ajout DESC
LIMIT 12
";

try {
    $stmt_last = $pdo->prepare($query_last);
    $stmt_last->execute();
    $last_products = $stmt_last->fetchAll(PDO::FETCH_ASSOC);

    $stmt_popular = $pdo->prepare($query_popular);
    $stmt_popular->execute();
    $popular_products = $stmt_popular->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Database Error (index.php): ' . $e->getMessage());
    die('An error occurred while loading the products.');
}
$allowed_pages = [
    'home' => "../pages/%s/home.php",
    'about' => "../pages/%s/about.php",
    'contact' => "../pages/%s/contact.php",
    'address' => "../pages/%s/address.php",
    'benifits' => "../pages/%s/benifits.php",
    'category' => "../pages/%s/category.php",
    'delivery' => "../pages/%s/delivery.php",
    'favoris' => "../pages/%s/favoris.php",
    'login' => "../pages/%s/login.php",
    'myorders' => "../pages/%s/myorders.php",
    'ourstore' => "../pages/%s/our stores.php",
    'payment' => "../pages/%s/payment.php",
    'personalinfos' => "../pages/%s/personalinfo.php",
    'product' => "../pages/%s/product.php",
    'shipping' => "../pages/%s/shipping.php",
    'summary' => "../pages/%s/summary.php",
    'terms' => "../pages/%s/terms.php",
    'success' => "../pages/%s/order_success.php",
    'search' => "../pages/%s/search.php",
];

$page_styles = [
    'home' => ['style/carousel.css', 'style/main.css', 'style/seo.css'],
    'about' => ['style/about.css'],
    'contact' => ['style/contact.css'],
    'address' => ['style/address.css'],
    'benifits' => ['style/benifitts.css'],
    'category' => ['style/categories.css', 'style/seo.css'],
    'delivery' => ['style/delivery.css'],
    'favoris' => ['style/favoris.css'],
    'login' => ['style/login.css'],
    'myorders' => ['style/myorders.css'],
    'ourstore' => ['style/ourstore.css'],
    'payment' => ['style/payment.css'],
    'personalinfos' => ['style/infos.css'],
    'product' => ['style/product.css'],
    'shipping' => ['style/shipping.css'],
    'summary' => ['style/summary.css'],
    'terms' => ['style/terms.css'],
    'success' => ['style/success.css'],
    'search' => ['style/search.css'],
];

$page_scripts = [
    'home' => ['scripts/carrousel.js', 'scripts/popularproducts.js', 'scripts/header.js', 'scripts/bag.js', 'scripts/categories.js', 'scripts/favoris.js', 'scripts/search.js'],
    'favoris' => ['scripts/header.js', 'scripts/bag.js', 'scripts/favoris.js', 'scripts/search.js'],
    'login' => ['scripts/header.js', 'scripts/bag.js', 'scripts/login.js', 'scripts/search.js'],
    'address' => ['scripts/bag.js', 'scripts/header.js', 'scripts/search.js'],
    'about' => ['scripts/bag.js', 'scripts/header.js', 'scripts/search.js'],
    'contact' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'benifits' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'category' => ['scripts/header.js', 'scripts/bag.js', 'scripts/categories.js', 'scripts/search.js', 'scripts/favoris.js'],
    'delivery' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'myorders' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'ourstore' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'payment' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'personalinfos' => ['scripts/header.js', 'scripts/bag.js' ,'scripts/search.js', 'scripts/infos.js'],
    'product' => ['scripts/header.js', 'scripts/bag.js', 'scripts/product.js', 'scripts/search.js'],
    'shipping' => ['scripts/header.js', 'scripts/bag.js', 'scripts/shipping.js', 'scripts/search.js'],
    'summary' => ['scripts/header.js', 'scripts/bag.js', 'scripts/summary.js', 'scripts/search.js'],
    'terms' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'success' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'search' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
];

$lang = $_SESSION['lang'] ?? 'en';
if (!in_array($lang, $allowed_langs, true)) {
    $lang = 'en';
    $_SESSION['lang'] = $lang;
}

$page = $_GET['page'] ?? 'home';
$page = (string)$page;
if (!array_key_exists($page, $allowed_pages)) {
    $page = 'home';
}
$page_file = sprintf($allowed_pages[$page], $lang);
$page_file_real = realpath(__DIR__ . '/' . $page_file);
$basePagesDir = realpath(__DIR__ . '/../pages/' . $lang);
if ($page_file_real === false || $basePagesDir === false || strpos($page_file_real, $basePagesDir) !== 0 || !is_file($page_file_real)) {
    $page_file_real = realpath(__DIR__ . '/../pages/en/home.php');
    if ($page_file_real === false) die('Page not found.');
}
$css_for_page = sanitize_asset_list($page_styles[$page] ?? []);
$js_for_page = sanitize_asset_list($page_scripts[$page] ?? []);
$assets_to_load = [
    'css' => $css_for_page,
    'js' => $js_for_page
];
$header_file = realpath(__DIR__ . '/../includes/header.php');
$footer_file = realpath(__DIR__ . '/../includes/footer.php');

if ($header_file && is_file($header_file)) include $header_file;
include $page_file_real;
if ($footer_file && is_file($footer_file)) include $footer_file;

ob_end_flush();


?>