<?php
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

if (!$product) {
    echo "<h2>Product not found.</h2>";
    exit;
}

$images_stmt = $pdo->prepare("SELECT image_url FROM product_images WHERE product_id = ? AND is_main = 0");
$images_stmt->execute([$product_id]);
$other_images = $images_stmt->fetchAll(PDO::FETCH_ASSOC);

$variants = [];
if (!empty($product['variant_count'])) {
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

function fullPath($path) {
    if (!$path) return '';
    $path = str_replace('\\','/',$path);
    return ltrim($path,'/');
}


$productPriceRaw = $product['price'] ?? ($variants[0]['price'] ?? null);
$convertedProductPrice = convertPrice($productPriceRaw);
$currencyLabel = 'MAD';

$cat_stmt = $pdo->prepare("SELECT name_en FROM categories WHERE id = ?");
$cat_stmt->execute([$product['category_id']]);
$category = $cat_stmt->fetchColumn();
$breadcrumb = "Home > " . htmlspecialchars($category) . " > " . htmlspecialchars($product['name_en']);
?>
<main class="product-container" data-selected-variant="">
  <section class="gallery">
    <div class="main-image">
      <img id="main-img" src="<?= htmlspecialchars(fullPath($product['image_url']), ENT_QUOTES) ?>" alt="Main Product Image">
    </div>
    <div class="thumbnail-container">
      <?php if (!empty($product['image_url'])): ?>
        <img src="<?= htmlspecialchars(fullPath($product['image_url']), ENT_QUOTES) ?>" onclick="changeImage(this)" class="active">
      <?php endif; ?>
      <?php foreach ($other_images as $img): ?>
        <img src="<?= htmlspecialchars(fullPath($img['image_url']), ENT_QUOTES) ?>" onclick="changeImage(this)">
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
        <?= number_format($convertedProductPrice,2) ?> <?= $currencyLabel ?>
      <?php endif; ?>
    </div>

    <div class="description">
      <h2>Description</h2>
      <p><?= htmlspecialchars($product['description_en'] ?? '') ?></p>
    </div>

    <?php if ($variants): ?>
      <div class="sizes">
      <?php $i=0; foreach($variants as $v):
          $dataImage = fullPath($v['variant_image'] ?: $v['product_image']);
          $variantPrice = convertPrice($v['price'] ?? null);
          $variantStock = (int)($v['stock'] ?? 0);
      ?>
        <button <?= $i===0?'id="default-variant"':'' ?>
                data-variant-id="<?= (int)$v['id'] ?>"
                data-image="<?= htmlspecialchars($dataImage, ENT_QUOTES) ?>"
                data-price="<?= htmlspecialchars($v['price'] ?? '', ENT_QUOTES) ?>"
                data-price-display="<?= $variantPrice===null?'N/A':number_format($variantPrice,2).' '.$currencyLabel ?>"
                data-type="<?= htmlspecialchars($v['bottle_type'] ?? '', ENT_QUOTES) ?>"
                data-description="<?= htmlspecialchars($v['description_var_en'] ?? '', ENT_QUOTES) ?>"
                data-stock="<?= $variantStock ?>"
                onclick="selectVariant(this)"
                <?= $variantStock<=0?'disabled style="opacity:0.5;cursor:not-allowed;"':'' ?>
        >
          <?= htmlspecialchars($v['var_name_en'] ?? 'Variant', ENT_QUOTES) ?>
          <?= !empty($v['size'])?'('.htmlspecialchars($v['size'],ENT_QUOTES).')':'' ?> - 
          <?= $variantPrice===null?'N/A':number_format($variantPrice,2).' '.$currencyLabel ?>
          <?= $variantStock<=0?' - Out of Stock':'' ?>
        </button>
      <?php $i++; endforeach; ?>
      </div>
    <?php endif; ?>

    <div class="qty-selector">
      <button onclick="updateQty(-1)">-</button>
      <input type="text" value="1" id="qty" readonly>
      <button onclick="updateQty(1)">+</button>
    </div>

    <?php if ((int)$product['stock']>0): ?>
      <button class="add-to-cart" data-id="<?= (int)$product['id'] ?>" data-variant-id="" data-stock="<?= (int)$product['stock'] ?>" onclick="ajouterDepuisProduits1(this)">
        Add to Cart
      </button>
    <?php else: ?>
      <div class="out-of-stock-msg" style="font-size:1.3em;color:#178768">The product is currently unavailable.</div>
    <?php endif; ?>

    <div class="product-meta">
      <p><strong>Availability:</strong> <?= ((int)$product['stock']>0)?'In Stock':'Out of Stock' ?></p>
      <p><strong>Type:</strong> <span id="product-type"><?= htmlspecialchars($variants[0]['bottle_type'] ?? 'N/A') ?></span></p>
    </div>
  </section>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const defaultBtn = document.getElementById('default-variant');
  if(defaultBtn){ selectVariant(defaultBtn); }
});
</script>
