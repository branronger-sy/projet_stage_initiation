<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$cart = $_SESSION['cart'] ?? [];


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
    WHERE sa.user_id = ?
    ORDER BY sa.id DESC 
    LIMIT 1
");
$stmt->execute([$user_id]);
$shipping = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$shipping) {
    echo "<p>No shipping address found.</p>";
    exit;
}

$stmt2 = $pdo->prepare("SELECT shipping_price FROM countries WHERE id = ? LIMIT 1");
$stmt2->execute([$shipping['country_id']]);
$shipping_price = $stmt2->fetchColumn();

if ($shipping_price === false) {
    $shipping_price = 0;
}

$_SESSION['shipping_price'] = $shipping_price;

$converted_shipping_price = convertPrice($shipping_price);
$rate_display = number_format($converted_shipping_price, 2) . " " . htmlspecialchars($selectedCurrency);


$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header("Location: index.php?page=payment");
    exit;
}
?>
<main class="container">
  <section class="steps">
    <div class="step">01. Summary</div>
    <div class="step">02. Sign in</div>
    <div class="step">03. Address</div>
    <div class="step active">04. Shipping</div>
    <div class="step">05. Payment</div>
  </section>

  <h2>Choose a shipping option for this address:</h2>
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
        <strong>International Delivery</strong><br />
        Delivery to: <?= htmlspecialchars($shipping['country_name']) ?>
      </div>
      <div class="shipping-price"><?= $rate_display ?></div>
    </section>

    <section class="terms">
      <input type="checkbox" id="agree" name="agree" value="1" />
      <label for="agree">
        I agree to the terms of service and will adhere to them unconditionally.
        <a href="#" target="_blank">Read the Terms of Service</a>
      </label>
    </section>

    <button id="next-btn" type="submit">Next</button>
  </form>
</main>
