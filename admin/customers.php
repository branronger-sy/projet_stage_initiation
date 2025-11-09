<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$countries = [];
$stmtCountries = $conn->prepare("SELECT id, name FROM countries");
$stmtCountries->execute();
$resCountries = $stmtCountries->get_result();
while ($rowC = $resCountries->fetch_assoc()) {
    $countries[$rowC['id']] = $rowC['name'];
}
$stmtCountries->close();
$sql = "
    SELECT u.id, u.full_name, u.email, u.created_at,
           c.total_orders, c.total_spent, c.last_order_date, c.notes,
           sa.full_name AS shipping_name, sa.country_id AS shipping_country, sa.city AS shipping_city, 
           sa.zip_code AS shipping_zip, sa.address AS shipping_address, sa.phone AS shipping_phone,
           ba.full_name AS billing_name, ba.country_id AS billing_country, ba.city AS billing_city, 
           ba.zip_code AS billing_zip, ba.address AS billing_address, ba.phone AS billing_phone
    FROM users u
    LEFT JOIN customers c ON u.id = c.user_id
    LEFT JOIN shipping_addresses sa ON sa.id = (
        SELECT MAX(s1.id) FROM shipping_addresses s1 WHERE s1.user_id = u.id
    )
    LEFT JOIN billing_addresses ba ON ba.id = (
        SELECT MAX(b1.id) FROM billing_addresses b1 WHERE b1.user_id = u.id
    )
    ORDER BY u.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();
$stmt->close();
?>

<link rel="stylesheet" href="styles/admin.css">
<link rel="stylesheet" href="styles/customers.css">

<body class="admin">
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="brand">My Admin</div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="products.php">Products</a>
      <a href="categories.php">Categories</a>
      <a href="orders.php">Orders</a>
      <a href="customers.php" class="active">Customers</a>
      <a href="messages.php">Messages</a>
    </nav>
  </aside>
  
  <div class="main">
    <header class="topbar">
      <button class="sidebar-toggle">☰</button>
      <div>Customers</div>
    </header>
    <main class="content">
      <h1>Customers</h1>
      <table class="table">
        <thead>
          <tr>
            <th>User ID</th>
            <th>User</th>
            <th>Email</th>
            <th>Joined</th>
            <th>Total Orders</th>
            <th>Total Spent</th>
            <th>Last Order</th>
            <th>Shipping Address</th>
            <th>Billing Address</th>
            <th>Notes</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= htmlspecialchars($row['full_name']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
                <td><?= htmlspecialchars($row['created_at']) ?></td>
                <td><?= (int)$row['total_orders'] ?></td>
                <td><?= number_format((float)$row['total_spent'], 2) ?> DH</td>
                <td><?= htmlspecialchars($row['last_order_date']) ?></td>
                <td>
                  <?php if ($row['shipping_address']): ?>
                    <?= htmlspecialchars($row['shipping_name']) ?><br>
                    <?= htmlspecialchars($row['shipping_address']) ?><br>
                    <?= htmlspecialchars($row['shipping_city']) ?>,
                    <?= htmlspecialchars($row['shipping_zip']) ?><br>
                    <?= htmlspecialchars($countries[$row['shipping_country']] ?? 'Unknown') ?><br>
                    <?= htmlspecialchars($row['shipping_phone']) ?>
                  <?php else: ?>
                    <em>No Shipping Address</em>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if ($row['billing_address']): ?>
                    <?= htmlspecialchars($row['billing_name']) ?><br>
                    <?= htmlspecialchars($row['billing_address']) ?><br>
                    <?= htmlspecialchars($row['billing_city']) ?>,
                    <?= htmlspecialchars($row['billing_zip']) ?><br>
                    <?= htmlspecialchars($countries[$row['billing_country']] ?? 'Unknown') ?><br>
                    <?= htmlspecialchars($row['billing_phone']) ?>
                  <?php else: ?>
                    <em>No Billing Address</em>
                  <?php endif; ?>
                </td>
                <td><?= nl2br(htmlspecialchars($row['notes'])) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="10"><em>No customers found</em></td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </main>
  </div>
</div>
</body>
</html>
