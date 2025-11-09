<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    echo "<p style='color:red'>Invalid product ID.</p>";
    include "includes/footer.php";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
if (!$product) {
    echo "<p style='color:red'>Product not found.</p>";
    include "includes/footer.php";
    exit;
}

$stmt = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$images = $stmt->get_result();

$stmt = $conn->prepare("SELECT * FROM product_variants WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$variants = $stmt->get_result();
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
      <div>Product Details</div>
    </header>

    <main class="content">
      <div class="card">
        <div style="display:flex;justify-content:space-between;align-items:center;">
          <h2><?= htmlspecialchars($product['name_fr']) ?></h2>
          <a href="includes/product_delete.php?id=<?= (int)$product['id'] ?>" 
             class="btn-sm delete" 
             onclick="return confirm('Are you sure you want to permanently delete this product?');">
             Delete Product
          </a>
          <a href="product_edit.php?id=<?= (int)$product['id'] ?>" class="btn-sm">Edit Product</a>
        </div>

        <p>
          Base Price: 
          <?= $product['price'] !== null ? number_format((float)$product['price'], 2) . " DH" : "<em>N/A</em>" ?> 
          | Stock: <?= (int)$product['stock'] ?>
        </p>

        <h3>Images</h3>
        <?php if ($images && $images->num_rows > 0): ?>
          <?php while($img = $images->fetch_assoc()): ?>
            <?php 
              $imgPath = htmlspecialchars($img['image_url']);
              $imgPath = str_replace('\\', '/', $imgPath);
              $finalPath = "../public/" . $imgPath;
            ?>
              <img src="<?= $finalPath ?>" 
                   style="width:80px;height:80px;object-fit:cover;margin:5px;">
          <?php endwhile; ?>
        <?php else: ?>
          <p>No images available</p>
        <?php endif; ?>

        <h3>Variants</h3>
        <table class="table">
          <tr>
            <th>Variant (FR)</th>
            <th>Size</th>
            <th>Price</th>
            <th>Weight</th>
            <th>Bottle Type</th>
            <th>Stock</th>
          </tr>
          <?php if ($variants && $variants->num_rows > 0): ?>
            <?php while($v = $variants->fetch_assoc()): ?>
              <tr>
                <td><?= htmlspecialchars($v['var_name_fr']) ?></td>
                <td><?= htmlspecialchars($v['size']) ?></td>
                <td><?= number_format((float)$v['price'], 2) ?> DH</td>
                <td><?= htmlspecialchars($v['weight']) ?> g</td>
                <td><?= htmlspecialchars($v['bottle_type']) ?></td>
                <td><?= (int)$v['stock'] ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6">No variants available</td></tr>
          <?php endif; ?>
        </table>
      </div>
    </main>
  </div>
</div>
<?php include "includes/footer.php"; ?>
