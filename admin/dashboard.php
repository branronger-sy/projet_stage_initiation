<?php
  // include "includes/auth.php"; // désactivé pour la soutenance
  include "includes/header.php";
  include "includes/db.php";
?>
<link rel="stylesheet" href="styles/admin.css">
</head>
<body class="admin">

<div class="layout">
  <aside class="sidebar">
    <div class="brand">My Admin</div>
    <nav class="nav">
      <a href="dashboard.php" class="active">Dashboard</a>
      <a href="products.php">Products</a>
      <a href="categories.php">Categories</a>
      <a href="orders.php">Orders</a>
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

<?php
// simple version without try/catch
$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM orders");
$stmt->execute();
$orders_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM products");
$stmt->execute();
$products_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

$stmt = $conn->prepare("SELECT COUNT(*) AS c FROM customers");
$stmt->execute();
$customers_count = $stmt->get_result()->fetch_assoc()['c'] ?? 0;

$stmt = $conn->prepare("SELECT IFNULL(SUM(total_price),0) AS total FROM orders WHERE payment_status='paid'");
$stmt->execute();
$revenue_row = $stmt->get_result()->fetch_assoc();
$revenue = $revenue_row['total'] ?? 0;

$stmt = $conn->prepare("
  SELECT o.id, o.total_price, o.payment_status, o.created_at, o.order_status, b.full_name
  FROM orders o
  LEFT JOIN billing_addresses b ON o.billing_address_id = b.id
  ORDER BY o.created_at DESC
  LIMIT 5
");
$stmt->execute();
$latest_orders = $stmt->get_result();
?>

      <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:15px;margin-bottom:20px;">
        <div class="card"><h3>Orders</h3><p><strong><?= (int)$orders_count ?></strong></p></div>
        <div class="card"><h3>Products</h3><p><strong><?= (int)$products_count ?></strong></p></div>
        <div class="card"><h3>Customers</h3><p><strong><?= (int)$customers_count ?></strong></p></div>
        <div class="card"><h3>Revenue</h3><p><strong><?= number_format((float)$revenue, 2) ?> MAD</strong></p></div>
      </div>

      <div class="card">
        <h3>Latest Orders</h3>
        <table class="table">
          <thead><tr><th>#ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
          <tbody>
            <?php if($latest_orders->num_rows > 0): ?>
              <?php while($row = $latest_orders->fetch_assoc()): ?>
                <tr>
                  <td>#<?= (int)$row['id'] ?></td>
                  <td><?= htmlspecialchars($row['full_name'] ?? 'Unknown') ?></td>
                  <td><?= number_format((float)$row['total_price'], 2) ?> MAD</td>
                  <td><?= htmlspecialchars(ucfirst($row['order_status'])) ?></td>
                  <td><?= htmlspecialchars(date("Y-m-d H:i", strtotime($row['created_at']))) ?></td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="5">No recent orders found</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    </main>

<?php include "includes/footer.php"; ?>
