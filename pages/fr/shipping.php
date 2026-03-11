<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
const MIN_ORDER_MAD = 500.0;
function calc_cart_total_mad_simple(array $cart = []): float {
    $total = 0.0;
    foreach ($cart as $item) {
        $price = isset($item['price']) ? floatval($item['price']) : 0.0;
        $qty = isset($item['quantity']) ? max(0, intval($item['quantity'])) : 0;
        $total += $price * $qty;
    }
    return $total;
}
$cart = $_SESSION['cart'] ?? [];
$totalMAD = calc_cart_total_mad_simple($cart);
if ($totalMAD < MIN_ORDER_MAD) {
    header('Location: index.php?page=summary');
    exit;
}
if (empty($_SESSION['user_id'])) {
    header("Location: index.php?page=login&from=checkout");
    exit;
}
$steps = ['summary', 'login', 'address'];
foreach ($steps as $step) {
    if (empty($_SESSION['checkout_progress'][$step])) {
        $redirectPage = $step === 'summary' ? 'home' : $step;
        header("Location: index.php?page={$redirectPage}");
        exit;
    }
}
$_SESSION['checkout_progress']['shipping'] = true;
$_SESSION['step'] = 'shipping';
$user_id = (int) $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT sa.*, c.name AS country_name, c.id AS country_id 
    FROM shipping_addresses sa
    JOIN countries c ON sa.country_id = c.id
    WHERE sa.user_id = :uid
    ORDER BY sa.id DESC 
    LIMIT 1
");
$stmt->execute([':uid' => $user_id]);
$shipping = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$shipping) {
    echo "<p>Aucune adresse de livraison trouvée.</p>";
    exit;
}
$stmt2 = $pdo->prepare("SELECT shipping_price FROM countries WHERE id = :cid LIMIT 1");
$stmt2->execute([':cid' => $shipping['country_id']]);
$shipping_price = $stmt2->fetchColumn();
if ($shipping_price === false) {
    $shipping_price = 0;
}
$_SESSION['shipping_price'] = $shipping_price;
$converted_shipping_price = convertPrice($shipping_price);
$rate_display = number_format($converted_shipping_price, 2) . " " . htmlspecialchars($selectedCurrency);
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "⚠️ Soumission du formulaire invalide.";
    } elseif (empty($_POST['agree'])) {
        $error = "⚠️ Vous devez accepter les conditions avant de continuer.";
    } else {
        unset($_SESSION['csrf_token']);
        header("Location: index.php?page=payment");
        exit;
    }
}
?>
<main class="container">
  <section class="steps">
    <div class="step">01. Récapitulatif</div>
    <div class="step">02. Connexion</div>
    <div class="step">03. Adresse</div>
    <div class="step active">04. Livraison</div>
    <div class="step">05. Paiement</div>
  </section>
  <h2>Choisissez une option de livraison pour cette adresse :</h2>
  <section id="shipping-address" class="address-box">
    <strong><?= htmlspecialchars($shipping['full_name']) ?></strong><br>
    <?= nl2br(htmlspecialchars($shipping['address'])) ?><br>
    <?= htmlspecialchars($shipping['city']) ?>, <?= htmlspecialchars($shipping['country_name']) ?><br>
    <?= htmlspecialchars($shipping['phone']) ?>
  </section>
  <form method="post">
    <section class="shipping-option">
      <input type="radio" id="ship1" name="shipping" value="standard" checked />
      <div class="shipping-details">
        <strong>Livraison internationale</strong><br />
        Livraison vers : <?= htmlspecialchars($shipping['country_name']) ?>
      </div>
      <div class="shipping-price"><?= $rate_display ?></div>
    </section>
    <section class="terms">
      <input type="checkbox" id="agree" name="agree" value="1" />
      <label for="agree">
        J’accepte les conditions générales de vente et m’engage à les respecter sans réserve.
        <a href="#" target="_blank">Lire les Conditions générales de vente</a>
      </label>
      <?php if (!empty($error)): ?>
        <div style="color:red; font-size:14px; margin-top:5px;">
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
    </section>
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
    <button id="next-btn" type="submit">Suivant</button>
  </form>
</main>