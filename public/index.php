<?php
ob_start();

session_start();
require '../includes/db.php';

$allowed_langs = ['en', 'ar', 'fr'];
$allowed_currencies = ['MAD', 'USD', 'EUR'];

if (isset($_GET['lang'])) {
    $lang = strtolower(trim($_GET['lang']));
    if (in_array($lang, $allowed_langs)) {
        $_SESSION['lang'] = $lang;
    }
    header('Location: index.php');
    exit;
}

if (isset($_GET['currency'])) {
    $currency = strtoupper(trim($_GET['currency']));
    if (in_array($currency, $allowed_currencies)) {
        $_SESSION['currency'] = $currency;
    }
    header('Location: index.php');
    exit;
}

$lang = $_SESSION['lang'] ?? 'en';
if (!in_array($lang, $allowed_langs)) {
    $lang = 'en';
    $_SESSION['lang'] = $lang;
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
    COALESCE(v.var_name_fr, p.name_fr) AS name_fr,
    COALESCE(v.price, p.price) AS price,
    i.image_url,
    (SELECT COUNT(*) FROM product_variants v2 WHERE v2.product_id = p.id) AS variant_count
FROM products p
LEFT JOIN product_variants v ON p.id = v.product_id AND v.main_variant = 1
LEFT JOIN product_images i ON p.id = i.product_id AND i.is_main = 1
ORDER BY p.date_ajout DESC
LIMIT 12
";

$stmt_last = $pdo->prepare($query_last);
$stmt_last->execute();
$last_products = $stmt_last->fetchAll(PDO::FETCH_ASSOC);

$stmt_popular = $pdo->prepare($query_popular);
$stmt_popular->execute();
$popular_products = $stmt_popular->fetchAll(PDO::FETCH_ASSOC);

$allowed_pages = [
    'home' => "../pages/$lang/home.php",
    'about' => "../pages/$lang/about.php",
    'contact' => "../pages/$lang/contact.php",
    'address' => "../pages/$lang/address.php",
    'benifits' => "../pages/$lang/benifits.php",
    'category' => "../pages/$lang/category.php",
    'delivery' => "../pages/$lang/delivery.php",
    'favoris' => "../pages/$lang/favoris.php",
    'login' => "../pages/$lang/login.php",
    'myorders' => "../pages/$lang/myorders.php",
    'ourstore' => "../pages/$lang/our stores.php",
    'payment' => "../pages/$lang/payment.php",
    'personalinfos' => "../pages/$lang/personalinfo.php",
    'product' => "../pages/$lang/product.php",
    'shipping' => "../pages/$lang/shipping.php",
    'summary' => "../pages/$lang/summary.php",
    'terms' => "../pages/$lang/terms.php",
    'success' => "../pages/$lang/order_success.php",
    'search' => "../pages/$lang/search.php",
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
    'personalinfos' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js', 'scripts/infos.js'],
    'product' => ['scripts/header.js', 'scripts/bag.js', 'scripts/product.js', 'scripts/search.js'],
    'shipping' => ['scripts/header.js', 'scripts/bag.js', 'scripts/shipping.js', 'scripts/search.js'],
    'summary' => ['scripts/header.js', 'scripts/bag.js', 'scripts/summary.js', 'scripts/search.js'],
    'terms' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'success' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
    'search' => ['scripts/header.js', 'scripts/bag.js', 'scripts/search.js'],
];

$page = $_GET['page'] ?? 'home';
if (!array_key_exists($page, $allowed_pages)) {
    $page = 'home';
}

$page_file = $allowed_pages[$page];
$page_path = __DIR__ . '/' . $page_file;

if (!file_exists($page_path)) {
    $page_path = __DIR__ . '/../pages/en/home.php';
}

include '../includes/header.php';
include $page_path;
include '../includes/footer.php';

ob_end_flush();
?>
