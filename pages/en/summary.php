<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

const MIN_ORDER_MAD = 500.0;

$cart = $_SESSION['cart'] ?? [];

function calc_cart_total_mad(array $cart): float {
    $total = 0.0;
    foreach ($cart as $item) {
        $price = isset($item['price']) ? floatval($item['price']) : 0.0;
        $qty = isset($item['quantity']) ? max(0, intval($item['quantity'])) : 0;
        $total += $price * $qty;
    }
    return $total;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['remove_item'])) {
        $idx = intval($_POST['remove_item']);
        if (isset($_SESSION['cart'][$idx])) {
            unset($_SESSION['cart'][$idx]);
            $_SESSION['cart'] = array_values($_SESSION['cart']);
        }
        $_SESSION['cart_total_mad'] = calc_cart_total_mad($_SESSION['cart']);
        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }

    if (isset($_POST['update_cart'])) {
        $quantities = $_POST['quantity'] ?? [];
        if (is_array($quantities)) {
            foreach ($quantities as $index => $qty) {
                $index = intval($index);
                $qty = max(1, intval($qty));
                if (isset($_SESSION['cart'][$index])) {
                    $_SESSION['cart'][$index]['quantity'] = $qty;
                }
            }
        }
        $_SESSION['cart_total_mad'] = calc_cart_total_mad($_SESSION['cart']);

        if ($_SESSION['cart_total_mad'] >= MIN_ORDER_MAD) {
            $_SESSION['checkout_progress']['summary'] = true;
            $_SESSION['step'] = 'summary';
        }

        header('Location: ' . $_SERVER['REQUEST_URI']);
        exit;
    }
}

$totalMAD = calc_cart_total_mad($cart);
$_SESSION['cart_total_mad'] = $totalMAD;
?>

<main class="container">
  <section class="steps">
    <div class="step active">01. Summary</div>
    <div class="step">02. Sign in</div>
    <div class="step">03. Address</div>
    <div class="step">04. Shipping</div>
    <div class="step">05. Payment</div>
  </section>

  <form id="cart-form" method="post" action="<?= $_SERVER['REQUEST_URI'] ?>">
    <table class="cart-summary-table">
      <thead>
        <tr>
          <th>Image</th>
          <th>Product</th>
          <th>Availability</th>
          <th>Unit price</th>
          <th>Qty</th>
          <th>Total</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($cart as $index => $item): 
            $priceMAD = isset($item['price']) ? floatval($item['price']) : 0.0;
            $quantity = isset($item['quantity']) ? intval($item['quantity']) : 0;
            $subtotalMAD = $priceMAD * $quantity;
        ?>
        <tr>
          <td data-label="Image">
            <img src="<?= $item['image'] ?? '' ?>" width="60" alt="<?= $item['name'] ?? '' ?>">
          </td>
          <td data-label="Product"><?= $item['name'] ?? '' ?></td>
          <td data-label="Availability"><span class="instock">In stock</span></td>
          <td data-label="Unit price"><?= number_format($priceMAD, 2) ?> MAD</td>
          <td data-label="Qty">
            <input type="number" name="quantity[<?= $index ?>]" value="<?= max(1, $quantity) ?>" min="1" style="width:60px;">
          </td>
          <td data-label="Total"><?= number_format($subtotalMAD, 2) ?> MAD</td>
          <td data-label="Action">
            <button type="submit" class="remove" name="remove_item" value="<?= $index ?>" onclick="return confirm('Are you sure you want to remove this product?');">
              Remove
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" class="text-right">TOTAL:</td>
          <td class="total-amount"><?= number_format($totalMAD, 2) ?> MAD</td>
          <td></td>
        </tr>
      </tfoot>
    </table>

    <div class="btn-container">
      <?php if (!empty($cart)): ?>
        <button type="submit" id="update-btn" name="update_cart" class="btn-update">Update Quantities</button>

        <?php if ($totalMAD >= MIN_ORDER_MAD): ?>
          <a href="index.php?page=login&from=checkout" class="btn-proceed">Proceed to checkout</a>
        <?php else: ?>
          <div class="notice">
            Minimum order amount is <?= number_format(MIN_ORDER_MAD, 2) ?> MAD — your current order total is <?= number_format($totalMAD, 2) ?> MAD.
          </div>
          <a href="index.php?page=home" class="btn-back">Back to shopping</a>
        <?php endif; ?>
      <?php endif; ?>
   </div>
  </form>
</main>
