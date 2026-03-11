<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$stmt = $conn->prepare("
    SELECT p.id, p.name_fr, p.price, p.stock, 
           c.name_fr AS category,
           pi.image_url
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi 
           ON pi.product_id = p.id AND pi.is_main = 1
    GROUP BY p.id
    ORDER BY p.id DESC
");
$stmt->execute();
$result = $stmt->get_result();
?>
<link rel="stylesheet" href="styles/admin.css">
<link rel="stylesheet" href="styles/products.css">
</head>
<body class="admin">

<div class="layout">
  <aside class="sidebar">
    <div class="brand">My Admin</div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="products.php" class="active">Products</a>
      <a href="categories.php">Categories</a>
      <a href="orders.php">Orders</a>
      <a href="customers.php">Customers</a>
      <a href="messages.php">Messages</a>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="sidebar-toggle">☰</button>
      <div>Products Management</div>
    </header>

    <main class="content">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:15px;">
          <h2>Products</h2>
          <a href="product_add.php" class="btn-primary">+ Add Product</a>
        </div>

        <table class="table">
          <thead>
            <tr>
              <th>#ID</th>
              <th>Image</th>
              <th>Name (FR)</th>
              <th>Category</th>
              <th>Price</th>
              <th>Stock</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td><?= (int)$row['id'] ?></td>
                <td>
                  <?php if (!empty($row['image_url'])): ?>
                    <img src="../public/<?= htmlspecialchars($row['image_url'], ENT_QUOTES, 'UTF-8') ?>" 
                         alt="product image" 
                         style="width:50px;height:50px;object-fit:cover;">
                  <?php else: ?>
                    <span>No image</span>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($row['name_fr'] ?? '') ?></td>
                <td><?= htmlspecialchars($row['category'] ?? '-') ?></td>
                <td>
                  <?= $row['price'] !== null ? number_format((float)$row['price'], 2) . " DH" : "<em>N/A</em>" ?>
                </td>
                <td><?= (int)$row['stock'] ?></td>
                <td>
                  <a href="product_view.php?id=<?= (int)$row['id'] ?>" class="btn-sm edit">View</a>
                  <a href="includes/product_delete.php?id=<?= (int)$row['id'] ?>" 
                     class="btn-sm delete" 
                     onclick="return confirm('Are you sure you want to delete this product?');">
                     Delete
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="7">No products found</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </main>
<?php include "includes/footer.php"; ?>
