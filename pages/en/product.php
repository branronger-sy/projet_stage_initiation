<?php
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log('product.php: $pdo not available');
    echo "<h2>Internal error (DB connection).</h2>";
    exit;
}
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<h2>Invalid product ID.</h2>";
    exit;
}
$product_id = (int) $_GET['id'];
$stmt = $pdo->prepare("
  SELECT p.*, i.image_url,
    (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) AS variant_count 
  FROM products p
  LEFT JOIN product_images i ON p.id = i.product_id AND i.is_main = 1
  WHERE p.id = ? LIMIT 1
");
$stmt->execute([$product_id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if ($product === false) {
    echo "<h2>Product not found.</h2>";
    exit;
}
$images_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 0");
$images_stmt->execute([$product_id]);
$other_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);
$variants = [];
if (!empty($product['variant_count']) && $product['variant_count'] > 0) {
    $variant_stmt = $pdo->prepare("
      SELECT 
        v.*, 
        vi.image_url AS variant_image, 
        pi.image_url AS product_image
      FROM product_variants v
      LEFT JOIN product_images vi ON vi.variant_id = v.id
      LEFT JOIN product_images pi ON pi.product_id = v.product_id AND pi.is_main = 1
      WHERE v.product_id = ?
    ");
    $variant_stmt->execute([$product_id]);
    $variants = $variant_stmt->fetchAll(PDO::FETCH_ASSOC);
}
function fullPath($relativePath) {
    if (!$relativePath) return '';
    $relativePath = str_replace('\\', '/', $relativePath);

    if (preg_match('#^https?://#i', $relativePath)) {
        return $relativePath;
    }

    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    $basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');

    return $protocol . "://" . $host . $basePath . "/" . ltrim($relativePath, '/');
}
function convert_price_safe($rawPrice) {
    if ($rawPrice === null || $rawPrice === '' || !is_numeric($rawPrice)) {
        return null;
    }
    $price = (float) $rawPrice;
    if (function_exists('convertPriceToSelected')) {
        return convertPriceToSelected($price);
    }
    if (function_exists('convertPrice')) {
        return convertPrice($price);
    }
    if (defined('RATES')) {
        $currency = 'MAD';
        if (function_exists('getSelectedCurrency')) {
            $currency = getSelectedCurrency();
        } elseif (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['currency'])) {
            $currency = $_SESSION['currency'];
        }
        $rate = RATES[$currency] ?? 1.0;
        return round($price * $rate, 2);
    }
    return round($price, 2);
}
function get_currency_safe() {
    if (function_exists('getSelectedCurrency')) {
        return getSelectedCurrency();
    }
    if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['currency'])) {
        return $_SESSION['currency'];
    }
    return 'MAD';
}
$possiblePriceKeys = ['price', 'price_mad', 'price_value', 'price_ttc'];
$productPriceRaw = null;
foreach ($possiblePriceKeys as $k) {
    if (array_key_exists($k, $product) && $product[$k] !== null && $product[$k] !== '') {
        $productPriceRaw = $product[$k];
        break;
    }
}
if ($productPriceRaw === null && !empty($variants) && isset($variants[0]['price']) && $variants[0]['price'] !== null && $variants[0]['price'] !== '') {
    $productPriceRaw = $variants[0]['price'];
}

$convertedProductPrice = convert_price_safe($productPriceRaw);
$currencyLabel = htmlspecialchars(get_currency_safe(), ENT_QUOTES, 'UTF-8');

if ($convertedProductPrice === null) {
    error_log("product.php: product id {$product_id} has no numeric price in DB. product keys: " . implode(',', array_keys($product)));
}
$cat_stmt = $pdo->prepare("SELECT name_en FROM categories WHERE id = ?");
$cat_stmt->execute([$product['category_id']]);
$category = $cat_stmt->fetchColumn();
$breadcrumb = "Home > " . htmlspecialchars($category) . " > " . htmlspecialchars($product['name_en']);
?>

<main class="product-container" data-selected-variant="">
  <section class="gallery">
    <div class="main-image">
      <img id="main-img" src="<?= htmlspecialchars(fullPath($product['image_url']), ENT_QUOTES, 'UTF-8') ?>" alt="Main Product Image">
    </div>
    <div class="thumbnail-container">
      <?php if (!empty($product['image_url'])): ?>
        <img src="<?= htmlspecialchars(fullPath($product['image_url']), ENT_QUOTES, 'UTF-8') ?>" onclick="changeImage(this)" alt="Thumbnail" class="active">
      <?php endif; ?>
      <?php foreach ($other_images as $img): ?>
        <img src="<?= htmlspecialchars(fullPath($img['image_url']), ENT_QUOTES, 'UTF-8') ?>" onclick="changeImage(this)" alt="Thumbnail">
      <?php endforeach; ?>
    </div>
  </section>

  <section class="product-info">
    <p class="breadcrumb"><?= $breadcrumb ?></p>
    <h1><?= htmlspecialchars($product['name_en']) ?></h1>
    <div class="price">
  <?php if ($convertedProductPrice === null): ?>
    <span class="no-price">Price on request</span>
  <?php else: ?>
    <?= number_format($convertedProductPrice, 2) ?> <?= $currencyLabel ?>
  <?php endif; ?>
</div>

<div class="description">
  <h2>Description</h2>
  <p><?= htmlspecialchars($product['description_en'] ?? '') ?></p>
</div>

<?php if (count($variants) > 0): ?>
  <div class="sizes">
    <?php $i = 0; foreach ($variants as $v):
        $dataImage = fullPath($v['variant_image'] ?: $v['product_image']);
        $variantRaw = $v['price'] ?? null;
        $variantConverted = convert_price_safe($variantRaw);
        $variantStock = (int)($v['stock'] ?? 0);
    ?>
      <button
        <?= $i === 0 ? 'id="default-variant"' : '' ?>
        data-variant-id="<?= (int)$v['id'] ?>"
        data-image="<?= htmlspecialchars($dataImage, ENT_QUOTES, 'UTF-8') ?>"
        data-price="<?= htmlspecialchars($variantRaw, ENT_QUOTES, 'UTF-8') ?>"
        data-price-display="<?= $variantConverted === null ? 'N/A' : (number_format($variantConverted, 2) . ' ' . $currencyLabel) ?>"
        data-type="<?= htmlspecialchars($v['bottle_type'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        data-description="<?= htmlspecialchars($v['description_var_en'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
        data-stock="<?= $variantStock ?>"
        onclick="selectVariant(this)"
        <?= $variantStock <= 0 ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : '' ?>
      >
        <?= htmlspecialchars($v['var_name_en'] ?? 'Variant', ENT_QUOTES, 'UTF-8') ?>
        <?= !empty($v['size']) ? '(' . htmlspecialchars($v['size']) . ')' : '' ?> - 
        <?= $variantConverted === null ? 'N/A' : (number_format($variantConverted, 2) . ' ' . $currencyLabel) ?>
        <?= $variantStock <= 0 ? ' - Out of Stock' : '' ?>
      </button>
    <?php $i++; endforeach; ?>
  </div>
<?php endif; ?>

<div class="qty-selector">
  <button onclick="updateQty(-1)">-</button>
  <input type="text" value="1" id="qty" readonly>
  <button onclick="updateQty(1)">+</button>
</div>

<?php if ((int)$product['stock'] > 0): ?>
  <button class="add-to-cart"
    data-id="<?= (int)$product['id'] ?>"
    data-variant-id=""
    data-stock="<?= (int)$product['stock'] ?>"
    onclick="ajouterDepuisProduits1(this)">
    Add to Cart
  </button>
<?php else: ?>
  <div class="out-of-stock-msg" style="font-size: 1.3em; color: #178768">The product is currently unavailable and will be available soon.</div>
<?php endif; ?>


<div class="product-meta">
  <p><strong>Availability:</strong> 
    <?= ((int)$product['stock'] > 0) ? 'In Stock' : 'Out of Stock' ?>
  </p>
  <p><strong>Type:</strong> <span id="product-type"><?= htmlspecialchars($variants[0]['bottle_type'] ?? 'N/A') ?></span></p>
</div>

  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const defaultBtn = document.getElementById('default-variant');
  if (defaultBtn) { selectVariant(defaultBtn); }
});
</script>
