<?php
if (!empty($_SESSION['user_id'])) {
  $stmt = $pdo->prepare("SELECT product_id FROM favoris WHERE user_id = ?");
  $stmt->execute([(int)$_SESSION['user_id']]);
  $user_favorites = $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
}

$category_id = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$category    = null;
$products    = [];

if ($category_id > 0) {
  $stmt = $pdo->prepare("SELECT id, name_en FROM categories WHERE id = ?");
  $stmt->execute([$category_id]);
  $category = $stmt->fetch(PDO::FETCH_ASSOC);

  if ($category) {
      $stmt = $pdo->prepare("
          SELECT 
              p.id AS product_id,
              COALESCE(v.var_name_en, p.name_en) AS name,
              COALESCE(v.price, p.price) AS price,
              i.image_url,
              (SELECT COUNT(*) FROM product_variants v2 WHERE v2.product_id = p.id) AS variant_count
          FROM products p
          LEFT JOIN product_variants v 
              ON p.id = v.product_id AND v.main_variant = 1
          LEFT JOIN product_images i 
              ON p.id = i.product_id AND i.is_main = 1
          WHERE p.category_id = ?
      ");
      $stmt->execute([$category_id]);
      $products = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }
}

?>
<main>
  <?php if (!empty($category)): ?>
    <section>
      <h1 id="category-title">
        <?= htmlspecialchars($category['name_en'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
      </h1>
      <p id="category-description">
        Discover our best products in the 
        <?= htmlspecialchars($category['name_en'] ?? '', ENT_QUOTES, 'UTF-8'); ?> 
        category.
      </p>
    </section>

    <section id="products" class="products-container">
      <?php if (!empty($products)): ?>
        <?php foreach ($products as $p):
            $product_id   = (int)($p['product_id'] ?? 0);
            $name         = htmlspecialchars($p['name'] ?? 'Unnamed', ENT_QUOTES, 'UTF-8');
            $priceMAD     = (float)($p['price'] ?? 0);
            $converted    = number_format(convertPrice($priceMAD), 2);
            $image        = !empty($p['image_url']) 
                              ? htmlspecialchars($p['image_url'], ENT_QUOTES, 'UTF-8') 
                              : 'images/default.jpg';
            $has_variants = !empty($p['variant_count']) && $p['variant_count'] > 0;
            $is_favorite  = in_array($product_id, $user_favorites, true);
            $active_class = $is_favorite ? 'active' : '';
        ?>
          <div class="product-card" 
               data-id="<?= $product_id ?>" 
               onclick="window.location.href='index.php?page=product&id=<?= $product_id ?>'">
            <div class="image-wrapper">
              <img src="<?= $image ?>" alt="<?= $name ?>">
              <div class="btns">
                <div><div class="quick-view"></div></div>
                <div class="wishliste <?= $active_class ?>" 
                     onclick="event.stopPropagation(); addToFavorites(<?= $product_id ?>, this, event)">
                </div>
              </div>
            </div>
            <h3><?= $name ?></h3>
            <p><?= $converted . ' ' . htmlspecialchars($selectedCurrency ?? 'MAD', ENT_QUOTES, 'UTF-8') ?></p>
            <?php if ($has_variants): ?>
              <button class="more" 
                      onclick="window.location.href='index.php?page=product&id=<?= $product_id ?>'">
                More
              </button>
            <?php else: ?>
              <button class="add-to-cart" 
                      onclick="event.stopPropagation(); ajouterDepuisProduits(this)">
                +Add
              </button>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p>No products found in this category.</p>
      <?php endif; ?>
    </section>
  <?php else: ?>
    <section>
      <h1>Category not found</h1>
      <p>Please return to the home page and choose a valid category.</p>
    </section>
  <?php endif; ?>
</main>
