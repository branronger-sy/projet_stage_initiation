<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

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

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer-when-downgrade');

if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    header('Strict-Transport-Security: max-age=63072000; includeSubDomains; preload');
}

if (!isset($_SESSION['user_id'])) {
    if (empty($_SESSION['checkout_progress']['summary'])) {
        header("Location: index.php?page=home");
        exit;
    } else {
        header("Location: index.php?page=login&from=checkout");
        exit;
    }
}

if (
    !isset($_SESSION['checkout_progress']['summary']) ||
    !isset($_SESSION['checkout_progress']['login']) ||
    ($_SESSION['step'] !== 'login' && $_SESSION['step'] !== 'address')
) {
    header("Location: index.php?page=home");
    exit;
}

$_SESSION['checkout_progress']['address'] = true;
$_SESSION['step'] = 'address';

$user_id = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare("SELECT id, name FROM countries ORDER BY name ASC");
    $stmt->execute();
    $countries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("countries load error: " . $e->getMessage());
    $countries = [];
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            s.full_name AS s_full_name, s.address AS s_address, s.city AS s_city, s.zip_code AS s_zip, s.country_id AS s_country, s.phone AS s_phone,
            b.full_name AS b_full_name, b.address AS b_address, b.city AS b_city, b.zip_code AS b_zip, b.country_id AS b_country, b.phone AS b_phone
        FROM users u
        LEFT JOIN shipping_addresses s ON s.user_id = u.id
        LEFT JOIN billing_addresses b ON b.user_id = u.id
        WHERE u.id = ? LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $address = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
} catch (PDOException $e) {
    error_log("address load error: " . $e->getMessage());
    $address = [];
}

$hasAddress = !empty($address) && !empty($address['s_full_name']);

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];
?>

<main class="container">
  <section class="steps" aria-hidden="false">
    <div class="step">01. Résumé</div>
    <div class="step">02. Connexion</div>
    <div class="step active">03. Adresse</div>
    <div class="step">04. Livraison</div>
    <div class="step">05. Paiement</div>
  </section>

  <section class="address-form" aria-live="polite">
    <?php if ($hasAddress): ?>
      <div id="address_view">
        <h2>Votre adresse de livraison</h2>
        <p><strong><?= htmlspecialchars($address['s_full_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p><?= htmlspecialchars($address['s_address'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($address['s_city'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($address['s_zip'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= htmlspecialchars($address['s_phone'], ENT_QUOTES, 'UTF-8') ?></p>

        <h2>Votre adresse de facturation</h2>
        <p><strong><?= htmlspecialchars($address['b_full_name'], ENT_QUOTES, 'UTF-8') ?></strong></p>
        <p><?= htmlspecialchars($address['b_address'], ENT_QUOTES, 'UTF-8') ?>, <?= htmlspecialchars($address['b_city'], ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($address['b_zip'], ENT_QUOTES, 'UTF-8') ?></p>
        <p><?= htmlspecialchars($address['b_phone'], ENT_QUOTES, 'UTF-8') ?></p>

        <div class="actions">
          <button id="changeAddressBtn" class="btn" type="button">Modifier l’adresse</button>
          <button type="button" id="continueShippingBtn" class="btn btn-primary">Continuer vers la livraison</button>
        </div>
      </div>
    <?php endif; ?>

    <div id="address_form" style="<?= $hasAddress ? 'display:none;' : '' ?>">
      <h2><?= $hasAddress ? 'Modifier l’adresse' : 'Saisir l’adresse' ?></h2>
      <form id="addressForm" method="post" novalidate>
        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

        <!-- Adresse de livraison -->
        <label for="shipping_fullName">Nom complet</label>
        <input id="shipping_fullName" type="text" name="shipping_fullName" required maxlength="150" value="<?= htmlspecialchars($address['s_full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="shipping_address">Adresse</label>
        <input id="shipping_address" type="text" name="shipping_address" required maxlength="255" value="<?= htmlspecialchars($address['s_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="shipping_city">Ville</label>
        <input id="shipping_city" type="text" name="shipping_city" required maxlength="100" value="<?= htmlspecialchars($address['s_city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="shipping_zip">Code postal</label>
        <input id="shipping_zip" type="text" name="shipping_zip" required maxlength="20" value="<?= htmlspecialchars($address['s_zip'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <label for="shipping_country">Pays</label>
        <select id="shipping_country" name="shipping_country" required>
          <option value="" disabled <?= empty($address['s_country']) ? 'selected' : '' ?>>Choisir un pays</option>
          <?php foreach ($countries as $country): ?>
            <option value="<?= (int)$country['id'] ?>" <?= (isset($address['s_country']) && (int)$address['s_country'] === (int)$country['id']) ? 'selected' : '' ?>>
              <?= htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>

        <label for="shipping_phone">Téléphone</label>
        <input id="shipping_phone" type="text" name="shipping_phone" required maxlength="20" value="<?= htmlspecialchars($address['s_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <hr>

        <h2>Adresse de facturation</h2>
        <label>
          <input type="checkbox" name="same_as_shipping" id="same_as_shipping" onclick="toggleBilling()"> Identique à l’adresse de livraison
        </label>

        <div id="billing_fields">
          <label for="billing_fullName">Nom complet</label>
          <input id="billing_fullName" type="text" name="billing_fullName" maxlength="150" value="<?= htmlspecialchars($address['b_full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <label for="billing_address">Adresse</label>
          <input id="billing_address" type="text" name="billing_address" maxlength="255" value="<?= htmlspecialchars($address['b_address'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <label for="billing_city">Ville</label>
          <input id="billing_city" type="text" name="billing_city" maxlength="100" value="<?= htmlspecialchars($address['b_city'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <label for="billing_zip">Code postal</label>
          <input id="billing_zip" type="text" name="billing_zip" maxlength="20" value="<?= htmlspecialchars($address['b_zip'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

          <label for="billing_country">Pays</label>
          <select id="billing_country" name="billing_country">
            <option value="" disabled <?= empty($address['b_country']) ? 'selected' : '' ?>>Choisir un pays</option>
            <?php foreach ($countries as $country): ?>
              <option value="<?= (int)$country['id'] ?>" <?= (isset($address['b_country']) && (int)$address['b_country'] === (int)$country['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($country['name'], ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>

          <label for="billing_phone">Téléphone</label>
          <input id="billing_phone" type="text" name="billing_phone" maxlength="20" value="<?= htmlspecialchars($address['b_phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <button type="submit"><?= $hasAddress ? 'Mettre à jour l’adresse' : 'Continuer vers la livraison' ?></button>
      </form>
    </div>
  </section>
</main>

<script>
function toggleBilling() {
  const billingFields = document.getElementById('billing_fields');
  billingFields.style.display = document.getElementById('same_as_shipping').checked ? 'none' : 'block';
}
window.onload = function() {
  toggleBilling();
};
document.addEventListener("DOMContentLoaded", function() {
  const changeBtn = document.getElementById("changeAddressBtn");
  const formDiv = document.getElementById("address_form");
  const viewDiv = document.getElementById("address_view");
  const form = document.getElementById("addressForm");
  const continueBtn = document.getElementById("continueShippingBtn");

  if (changeBtn) {
    changeBtn.addEventListener("click", function() {
      viewDiv.style.display = "none";
      formDiv.style.display = "block";
    });
  }

  if (continueBtn) {
    continueBtn.addEventListener("click", function() {
      window.location.href = "index.php?page=shipping";
    });
  }

  form.addEventListener("submit", async function(e) {
    e.preventDefault();
    const formData = new FormData(form);
    try {
      const response = await fetch("../includes/save_address.php", {
        method: "POST",
        body: formData,
        credentials: "same-origin",
        headers: { "X-Requested-With": "XMLHttpRequest" }
      });
      if (!response.ok) {
        throw new Error("Erreur réseau");
      }
      const result = await response.json();
      if (result.status === "success") {
        window.location.href = result.redirect;
      } else {
        alert(result.message || "Erreur lors de l’enregistrement de l’adresse");
      }
    } catch (err) {
      console.error("Erreur d’enregistrement de l’adresse:", err);
      alert("Une erreur inattendue est survenue.");
    }
  });
});
</script>
