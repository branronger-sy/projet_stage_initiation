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


ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

if (empty($_SESSION['checkout_progress']['shipping'])) {
    header("Location: index.php?page=home");
    exit;
}

require_once '../includes/config.php';

$_SESSION['checkout_progress']['payment'] = true;
$_SESSION['step'] = 'payment';

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) {
    header("Location: index.php?page=cart");
    exit;
}

$total_products = 0;
$total_shipping = $_SESSION['shipping_price'] ?? 0;

foreach ($cart as $item) {
    $price    = isset($item['price']) ? (float)$item['price'] : 0;
    $quantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
    if ($price < 0 || $quantity < 1) continue;

    $subtotal = $price * $quantity;
    $total_products += $subtotal;
}

$total_mad = $total_products + $total_shipping;
$total_usd = round($total_mad * (RATES['USD'] ?? 0.1), 2);

$total_products_converted = convertPrice((float)$total_products);
$total_shipping_converted = convertPrice((float)$total_shipping);
$total_converted          = convertPrice((float)$total_mad);
$clientId = getenv('PAYPAL_CLIENT_ID');
if (!$clientId) {
    die("PayPal client ID not configured");
}
?>

<main class="container">
  <section class="steps">
    <div class="step">01. Summary</div>
    <div class="step">02. Sign in</div>
    <div class="step">03. Address</div>
    <div class="step">04. Shipping</div>
    <div class="step active">05. Payment</div>
  </section>
  <table>
    <thead>
      <tr>
        <th>Image</th>
        <th>Product</th>
        <th>Availability</th>
        <th>Unit price</th>
        <th>Qty</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($cart as $item): ?>
        <?php
          $price = (float)$item['price'];
          $qty   = (int)$item['quantity'];
          $subtotal_mad       = $price * $qty;
          $unit_converted     = convertPrice($price);
          $subtotal_converted = convertPrice($subtotal_mad);
        ?>
        <tr>
          <td data-label="Image">
            <img src="<?= e($item['image']) ?>" width="60" alt="Product">
          </td>
          <td data-label="Product"><?= e($item['name']) ?></td>
          <td data-label="Availability">In stock</td>
          <td data-label="Unit price">
            <?= number_format($unit_converted, 2) ?> <?= e($selectedCurrency ?? 'MAD') ?>
          </td>
          <td data-label="Qty"><?= $qty ?></td>
          <td data-label="Total">
            <?= number_format($subtotal_converted, 2) ?> <?= e($selectedCurrency ?? 'MAD') ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <section class="summary">
    <div><strong>Total Products:</strong> <?= number_format($total_products_converted, 2) ?> <?= e($selectedCurrency ?? 'MAD') ?></div>
    <div><strong>Total Shipping:</strong> <?= number_format($total_shipping_converted, 2) ?> <?= e($selectedCurrency ?? 'MAD') ?></div>
    <div class="total"><strong>Total:</strong> <?= number_format($total_converted, 2) ?> <?= e($selectedCurrency ?? 'MAD') ?></div>
  </section>

  <section>
    <div id="paypal-button-container"></div>
  </section>
</main>
<script src="https://www.paypal.com/sdk/js?client-id=<?= e($clientId) ?>&currency=USD"></script>
<script>
paypal.Buttons({
  createOrder: function(data, actions) {
    return actions.order.create({
      purchase_units: [{
        amount: { value: '<?= number_format($total_usd, 2, ".", "") ?>' }
      }]
    });
  },
  onApprove: function(data, actions) {
    return actions.order.capture().then(function(details) {
      fetch('../includes/paypal_success.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ orderID: data.orderID })
      })
      .then(res => res.text())
      .then(text => {
        try {
          var response = JSON.parse(text);
          if (response.status === 'success') {
            window.location.href = 'index.php?page=success';
          } else {
            alert('Payment verification failed');
          }
        } catch (e) {
          console.error('JSON parse error:', e, text);
          alert('Invalid server response');
        }
      })
      .catch(err => {
        console.error('Fetch error:', err);
        alert('Could not contact server');
      });
    });
  }
}).render('#paypal-button-container');
</script>
