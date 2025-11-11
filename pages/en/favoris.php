<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}

$user_id = (int)$_SESSION['user_id'];

// إزالة عنصر واحد من المفضلة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['remove_id'])) {
        $remove_id = (int)$_POST['remove_id'];
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE id = ? AND user_id = ?");
        $stmt->execute([$remove_id, $user_id]);
    }

    // حذف جميع العناصر
    if (isset($_POST['clear_all'])) {
        $stmt = $pdo->prepare("DELETE FROM favoris WHERE user_id = ?");
        $stmt->execute([$user_id]);
    }
}

// جلب المفضلات
$stmt = $pdo->prepare("
    SELECT 
        f.id AS fav_id, 
        p.id AS product_id, 
        p.name_en, 
        p.price AS base_price,
        COALESCE(v.price, p.price) AS final_price,
        pi.image_url,
        (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) AS variant_count
    FROM favoris f
    JOIN products p ON f.product_id = p.id
    LEFT JOIN product_variants v ON v.product_id = p.id AND v.main_variant = 1
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
    WHERE f.user_id = ?
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
?>

<main>
  <section class="wishlist-section">
    <h2>My Wishlist</h2>
    <div class="wishlist-count">
      You have <?= count($favorites) ?> items
    </div>
    <form method="post" style="display:inline;">
        <button type="submit" name="clear_all" class="clear-wishlist btn">Clear All</button>
    </form>
  </section>

  <section id="wishlist" class="products-container">
    <?php if (count($favorites) > 0): ?>
      <?php foreach ($favorites as $fav): ?>
        <div class="product-card" data-id="<?= (int)$fav['product_id'] ?>">
          <?php if (!empty($fav['image_url'])): ?>
            <img src="<?= htmlspecialchars($fav['image_url'], ENT_QUOTES, 'UTF-8') ?>" 
                 alt="<?= htmlspecialchars($fav['name_en'] ?? 'Product', ENT_QUOTES, 'UTF-8') ?>">
          <?php else: ?>
            <img src="images/placeholder.jpg" alt="No Image Available">
          <?php endif; ?>

          <h3><?= htmlspecialchars($fav['name_en'] ?? 'Unnamed', ENT_QUOTES, 'UTF-8') ?></h3>

          <?php 
            $convertedPrice = convertPrice((float)$fav['final_price']);
          ?>
          <p>
            Price: <?= number_format($convertedPrice, 2) ?> 
            <?= htmlspecialchars($selectedCurrency ?? 'MAD', ENT_QUOTES, 'UTF-8') ?>
          </p>

          <div>
            <?php if (!empty($fav['variant_count'])): ?>
              <button class="btn more-btn" 
                      onclick="window.location.href='index.php?page=product&id=<?= (int)$fav['product_id'] ?>'">
                More
              </button>
            <?php else: ?>
              <button class="btn add-btn" onclick="ajouterDepuisProduits(this)">Add</button>
            <?php endif; ?>

            <form method="post" style="display:inline;">
              <input type="hidden" name="remove_id" value="<?= (int)$fav['fav_id'] ?>">
              <button type="submit" class="btn remove-btn">Remove</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Your wishlist is empty.</p>
    <?php endif; ?>
  </section>
</main>
