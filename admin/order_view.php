<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($order_id <= 0) {
    die("<p style='color:red'>Invalid Order ID.</p>");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_order'])) {
        $stmt = $conn->prepare("DELETE FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $stmt->close();

        header("Location: orders.php?msg=deleted");
        exit;
    }

    if (isset($_POST['order_status'])) {
        $new_status = strtolower(trim($_POST['order_status']));
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->bind_param("si", $new_status, $order_id);
            $stmt->execute();
            $stmt->close();
            header("Location: order_view.php?id=" . $order_id);
            exit;
    }
}
$stmt = $conn->prepare("
    SELECT o.*, u.full_name, u.email 
    FROM orders o
    JOIN users u ON u.id = o.user_id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    die("<p style='color:red'>Order not found.</p>");
}
$shipping = null;
if (!empty($order['shipping_address_id'])) {
    $stmt = $conn->prepare("
        SELECT sa.*, c.name AS country_name
        FROM shipping_addresses sa
        LEFT JOIN countries c ON c.id = sa.country_id
        WHERE sa.id = ?
    ");
    $stmt->bind_param("i", $order['shipping_address_id']);
    $stmt->execute();
    $shipping = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$billing = null;
if (!empty($order['billing_address_id'])) {
    $stmt = $conn->prepare("
        SELECT ba.*, c.name AS country_name
        FROM billing_addresses ba
        LEFT JOIN countries c ON c.id = ba.country_id
        WHERE ba.id = ?
    ");
    $stmt->bind_param("i", $order['billing_address_id']);
    $stmt->execute();
    $billing = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

$stmt = $conn->prepare("
    SELECT 
        oi.id, oi.quantity, oi.price AS unit_price,
        pv.id AS variant_id, pv.var_name_fr, pv.var_name_ar, pv.var_name_en,
        pv.size, pv.bottle_type, pv.weight,
        p.id AS product_id, p.name_fr AS product_name_fr, p.name_ar AS product_name_ar, p.name_en AS product_name_en
    FROM order_items oi
    LEFT JOIN product_variants pv ON pv.id = oi.variant_id
    LEFT JOIN products p ON p.id = oi.product_id OR p.id = pv.product_id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$items_rs = $stmt->get_result();
$stmt->close();

$items = [];
$items_subtotal = 0.0;
$shipping_label = $order['shipping_method'] ?? '';
$shipping_price = isset($order['shipping_price']) ? (float)$order['shipping_price'] : 0;

while ($row = $items_rs->fetch_assoc()) {
    $row['line_total'] = (float)$row['unit_price'] * (int)$row['quantity'];
    $items_subtotal += $row['line_total'];
    $items[] = $row;
}

$computed_total = $items_subtotal + (float)$shipping_price;
?>
<link rel="stylesheet" href="styles/admin.css">
<link rel="stylesheet" href="styles/orders.css">
</head>
<body class="admin">
<div class="layout">
  <aside class="sidebar">
    <div class="brand">My Admin</div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="products.php">Products</a>
      <a href="categories.php">Categories</a>
      <a href="orders.php" class="active">Orders</a>
      <a href="customers.php">Customers</a>
      <a href="messages.php">Messages</a>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="sidebar-toggle">☰</button>
      <div>Welcome, Admin</div>
    </header>

    <main class="content">
      <div class="order-grid">
        <section class="order-section">
          <h3>Customer</h3>
          <p><b><?= htmlspecialchars($order['full_name']) ?></b><br>
             <small><?= htmlspecialchars($order['email']) ?></small></p>
          <p><b>Created:</b> <?= htmlspecialchars($order['created_at']) ?></p>
          <p><span class="badge <?= strtolower($order['payment_status']) ?>"><?= htmlspecialchars($order['payment_status']) ?></span></p>
          <form method="post" style="margin-top:10px;">
            <label><b>Order Status:</b></label><br>
            <select name="order_status">
              <?php foreach (["pending","shipped","delivered","cancelled"] as $st): ?>
                <option value="<?= $st ?>" <?= strtolower($order['order_status']) === $st ? 'selected':'' ?>><?= ucfirst($st) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit">Update</button>
          </form>
          <form method="post" onsubmit="return confirm('Are you sure you want to permanently delete this order?');" style="margin-top:10px;">
            <input type="hidden" name="delete_order" value="1">
            <button type="submit" style="background:#c00; color:#fff; padding:5px 10px; border:none; border-radius:4px;">🗑️ Delete Order</button>
          </form>
        </section>

        <section class="order-section">
          <h3>Shipping Address</h3>
          <?php if ($shipping): ?>
            <p>
              <b><?= htmlspecialchars($shipping['full_name']) ?></b><br>
              <?= htmlspecialchars($shipping['address']) ?><br>
              <?= htmlspecialchars($shipping['city']) ?> <?= htmlspecialchars($shipping['zip_code']) ?><br>
              <?= htmlspecialchars($shipping['country_name'] ?: '—') ?><br>
              <?= htmlspecialchars($shipping['phone']) ?>
            </p>
          <?php else: ?>
            <p>—</p>
          <?php endif; ?>
        </section>

        <section class="order-section">
          <h3>Billing Address</h3>
          <?php if ($billing): ?>
            <p>
              <b><?= htmlspecialchars($billing['full_name']) ?></b><br>
              <?= htmlspecialchars($billing['address']) ?><br>
              <?= htmlspecialchars($billing['city']) ?> <?= htmlspecialchars($billing['zip_code']) ?><br>
              <?= htmlspecialchars($billing['country_name'] ?: '—') ?><br>
              <?= htmlspecialchars($billing['phone']) ?>
            </p>
          <?php else: ?>
            <p>—</p>
          <?php endif; ?>
        </section>

        <section class="order-section">
          <h3>Shipping</h3>
          <p><b>Method:</b> <?= htmlspecialchars($shipping_label ?: '—') ?></p>
          <p><b>Shipping Price:</b> <?= number_format($shipping_price, 2) ?> DH</p>
          <p><b>Order Total (DB):</b> <?= number_format((float)$items_subtotal, 2) ?> DH</p>
          <p><b>Recalculated:</b> <?= number_format($computed_total, 2) ?> DH</p>
        </section>
      </div>

      <section class="order-section">
        <h3>Items</h3>
        <div style="overflow-x:auto;">
          <table class="orders-table">
            <thead>
              <tr>
                <th>Product (ID)</th>
                <th>Variant (ID)</th>
                <th>Specs</th>
                <th>Unit Price</th>
                <th>Qty</th>
                <th>Line Total</th>
              </tr>
            </thead>
            <tbody>
            <?php if (count($items)): foreach ($items as $it): ?>
              <tr>
                <td><?= htmlspecialchars($it['product_name_fr'] ?: $it['product_name_ar'] ?: $it['product_name_en'] ?: ("#".$it['product_id'])) ?> (ID: <?= (int)$it['product_id'] ?>)</td>
                <td><?= $it['variant_id'] ? htmlspecialchars($it['var_name_fr'] ?: $it['var_name_ar'] ?: $it['var_name_en'] ?: ("#".$it['variant_id'])) . " (ID: ".(int)$it['variant_id'].")" : "—" ?></td>
                <td>
                  <?php
                    $specs = [];
                    if (!empty($it['size'])) $specs[] = "Size: " . htmlspecialchars($it['size']);
                    if (!empty($it['bottle_type'])) $specs[] = "Bottle: " . htmlspecialchars($it['bottle_type']);
                    if (!empty($it['weight'])) $specs[] = "Weight: " . htmlspecialchars($it['weight']) . " g";
                    echo $specs ? implode(" • ", $specs) : "—";
                  ?>
                </td>
                <td><?= number_format((float)$it['unit_price'], 2) ?> DH</td>
                <td><?= (int)$it['quantity'] ?></td>
                <td><?= number_format((float)$it['line_total'], 2) ?> DH</td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6">No items found.</td></tr>
            <?php endif; ?>
            </tbody>
            <tfoot>
              <tr><th colspan="5" style="text-align:right">Items Subtotal</th><th><?= number_format($items_subtotal, 2) ?> DH</th></tr>
              <tr><th colspan="5" style="text-align:right">Shipping</th><th><?= number_format($shipping_price, 2) ?> DH</th></tr>
              <tr><th colspan="5" style="text-align:right">Grand Total</th><th><?= number_format($computed_total, 2) ?> DH</th></tr>
            </tfoot>
          </table>
        </div>
      </section>
    </main>
  </div>
</div>
<?php include "includes/footer.php"; ?>
