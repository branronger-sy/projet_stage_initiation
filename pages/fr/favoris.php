<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || !ctype_digit((string)$_SESSION['user_id'])) {
    header("Location: index.php?page=login");
    exit;
}
$user_id = (int)$_SESSION['user_id'];

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $csrf_token)) {
        die("⚠️ Requête invalide.");
    }
    if (!empty($_POST['remove_id']) && ctype_digit((string)$_POST['remove_id'])) {
        $remove_id = (int)$_POST['remove_id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM favoris WHERE id = :id AND user_id = :uid");
            $stmt->bindValue(':id', $remove_id, PDO::PARAM_INT);
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression du favori : " . $e->getMessage());
        }
    }
    if (isset($_POST['clear_all'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM favoris WHERE user_id = :uid");
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->execute();
        } catch (PDOException $e) {
            error_log("Erreur lors de la suppression de tous les favoris : " . $e->getMessage());
        }
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$favorites = [];
try {
    $stmt = $pdo->prepare("
        SELECT 
            f.id AS fav_id, 
            p.id AS product_id, 
            p.name_fr, 
            p.price AS base_price,
            COALESCE(v.price, p.price) AS final_price,
            pi.image_url,
            (SELECT COUNT(*) FROM product_variants WHERE product_id = p.id) AS variant_count
        FROM favoris f
        JOIN products p ON f.product_id = p.id
        LEFT JOIN product_variants v ON v.product_id = p.id AND v.main_variant = 1
        LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_main = 1
        WHERE f.user_id = :uid
    ");
    $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log("Erreur lors de la récupération des favoris : " . $e->getMessage());
}
?>

<main>
  <section class="wishlist-section">
    <h2>Ma Liste de Souhaits</h2>
    <div class="wishlist-count">
      Vous avez <?= count($favorites) ?> articles
    </div>
    <form method="post" style="display:inline;">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
        <button type="submit" name="clear_all" class="clear-wishlist btn">🗑️ Tout Supprimer</button>
    </form>
  </section>

  <section id="wishlist" class="products-container">
    <?php if (count($favorites) > 0): ?>
      <?php foreach ($favorites as $fav): ?>
        <div class="product-card" data-id="<?= (int)$fav['product_id'] ?>">
          <?php if (!empty($fav['image_url'])): ?>
            <img src="<?= htmlspecialchars($fav['image_url'], ENT_QUOTES, 'UTF-8') ?>" 
                 alt="<?= htmlspecialchars($fav['name_fr'] ?? 'Produit', ENT_QUOTES, 'UTF-8') ?>">
          <?php else: ?>
            <img src="images/placeholder.jpg" alt="Image non disponible">
          <?php endif; ?>

          <h3><?= htmlspecialchars($fav['name_fr'] ?? 'Sans nom', ENT_QUOTES, 'UTF-8') ?></h3>

          <?php 
            $convertedPrice = convertPrice((float)$fav['final_price']);
          ?>
          <p>
            Prix : <?= number_format($convertedPrice, 2) ?> 
            <?= htmlspecialchars($selectedCurrency ?? 'MAD', ENT_QUOTES, 'UTF-8') ?>
          </p>

          <div class="btns-div">
            <?php if (!empty($fav['variant_count'])): ?>
              <button class="btn more-btn" 
                      onclick="window.location.href='index.php?page=product&id=<?= (int)$fav['product_id'] ?>'">
                Plus de détails
              </button>
            <?php else: ?>
              <button class="btn add-btn" onclick="ajouterDepuisProduits(this)">Ajouter</button>
            <?php endif; ?>

            <form method="post" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
              <input type="hidden" name="remove_id" value="<?= (int)$fav['fav_id'] ?>">
              <button type="submit" class="btn remove-btn">❌ Supprimer</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php else: ?>
      <p>Votre liste de souhaits est vide.</p>
    <?php endif; ?>
  </section>
</main>
