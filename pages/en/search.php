<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$q = $_GET['q'] ?? '';
$q = trim($q);

if ($q === '') {
    echo "<p>No results found.</p>";
    echo "q value is: [" . htmlspecialchars($q) . "]";
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT p.id, 
               p.name_en AS product_name,
               v.var_name_en AS variant_name,
               CASE 
                   WHEN p.price IS NULL OR p.price = 0 THEN v.price
                   ELSE p.price
               END AS price,
               pi.image_url
        FROM products p
        LEFT JOIN product_images pi 
            ON p.id = pi.product_id AND pi.is_main = 1
        LEFT JOIN product_variants v 
            ON p.id = v.product_id
        WHERE LOWER(p.name_en) LIKE LOWER(:q1)
           OR LOWER(p.description_en) LIKE LOWER(:q2)
           OR LOWER(v.var_name_en) LIKE LOWER(:q3)
        GROUP BY p.id, product_name, variant_name, price, pi.image_url
        LIMIT 20
    ");
    $stmt->execute([
        'q1' => "%$q%",
        'q2' => "%$q%",
        'q3' => "%$q%",
    ]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$products) {
        echo "<p>No results found.</p>";
        exit;
    }

    // عرض النتائج
    echo '<div class="products-grid">';

    // مصفوفة لتتبع ما عرضناه (لتفادي التكرار)
$shown = [];

foreach ($products as $p) {
    // إذا عنده variant مختلف عن المنتج نعرضه، غير كذا نعرض المنتج
    $name = !empty($p['variant_name']) ? $p['variant_name'] : $p['product_name'];

    // إذا الاسم يساوي اسم المنتج بالضبط، نعرض مرة واحدة فقط
    $key = $p['id'] . '|' . strtolower($name);
    if (isset($shown[$key])) {
        continue; // تخطّى التكرار
    }
    $shown[$key] = true;

    $name = htmlspecialchars($name);
    $image = $p['image_url'] ?: "placeholder.png";
    $price = (is_null($p['price']) || $p['price'] == 0)
        ? "—"
        : number_format((float)$p['price'], 2) . " $";

        echo '<div class="product-card" onclick="window.location.href=\'index.php?page=product&id=' . $p['id'] . '\'">';
        echo    '<img src="' . htmlspecialchars($image) . '" alt="' . $name . '">';
        echo    '<h3>' . $name . '</h3>';
        echo    '<p>' . $price . '</p>';
        echo '</div>';
        
}


    echo '</div>';

} catch (PDOException $e) {
    http_response_code(500);
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>