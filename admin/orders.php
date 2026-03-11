<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$stmt = $conn->prepare("
    SELECT o.*, u.full_name, u.email 
    FROM orders o 
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
$stmt->execute();
$orders = $stmt->get_result();
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
      <h2>Orders</h2>
    </header>

    <main class="content">
      <table class="orders-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Total</th>
            <th>Payment</th>
            <th>Status</th>
            <th>Created</th>
            <th>Details</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($orders->num_rows > 0): ?>
          <?php while ($o = $orders->fetch_assoc()): ?>
            <tr>
              <td>#<?= (int)$o['id'] ?></td>
              <td>
                <?= htmlspecialchars($o['full_name']) ?><br>
                <small><?= htmlspecialchars($o['email']) ?></small>
              </td>
              <td><?= number_format((float)$o['total_price'], 2) ?> DH</td>
              <td>
                <span class="badge <?= $o['payment_status'] ?>">
                  <?= htmlspecialchars($o['payment_status']) ?>
                </span>
              </td>
              <td>
                <span class="badge <?= $o['order_status'] ?>">
                  <?= htmlspecialchars($o['order_status']) ?>
                </span>
              </td>
              <td><?= htmlspecialchars($o['created_at']) ?></td>
              <td>
                <a class="btn-details" 
                   href="order_view.php?id=<?= (int)$o['id'] ?>">View</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" style="text-align:center;">No orders available.</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>

<?php include "includes/footer.php"; ?>