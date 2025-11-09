<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

function uploadImage($file) {
    $uploadDir = __DIR__ . "/../public/uploads/";
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    if (!empty($file['name']) && $file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $filename = uniqid() . "." . $ext;
        $target = $uploadDir . $filename;
        move_uploaded_file($file['tmp_name'], $target);
        return "uploads/" . $filename;
    }
    return null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_category'])) {
    $id = (int)$_POST['delete_category'];
    $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: categories.php?success=deleted");
    exit;
}
if (isset($_POST['add_category'])) {
    $name_fr = $_POST['name_fr'];
    $name_ar = $_POST['name_ar'];
    $name_en = $_POST['name_en'];
    $imagePath = uploadImage($_FILES['main_image']);
    $stmt = $conn->prepare("INSERT INTO categories (name_fr, name_ar, name_en, main_image) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name_fr, $name_ar, $name_en, $imagePath);
    $stmt->execute();
    $stmt->close();
    header("Location: categories.php?success=added");
    exit;
}
if (isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name_fr = $_POST['name_fr'];
    $name_ar = $_POST['name_ar'];
    $name_en = $_POST['name_en'];

    if (!empty($_FILES['main_image']['name'])) {
        $imagePath = uploadImage($_FILES['main_image']);
        $stmt = $conn->prepare("UPDATE categories SET name_fr=?, name_ar=?, name_en=?, main_image=? WHERE id=?");
        $stmt->bind_param("ssssi", $name_fr, $name_ar, $name_en, $imagePath, $id);
    } else {
        $stmt = $conn->prepare("UPDATE categories SET name_fr=?, name_ar=?, name_en=? WHERE id=?");
        $stmt->bind_param("sssi", $name_fr, $name_ar, $name_en, $id);
    }

    $stmt->execute();
    $stmt->close();
    header("Location: categories.php?success=updated");
    exit;
}
$stmt = $conn->prepare("SELECT * FROM categories ORDER BY id DESC");
$stmt->execute();
$categories = $stmt->get_result();
$stmt->close();
?>

<link rel="stylesheet" href="styles/admin.css">
<link rel="stylesheet" href="styles/categories.css">
</head>
<body class="admin">
<div class="layout">
  <aside class="sidebar">
    <div class="brand">My Admin</div>
    <nav class="nav">
      <a href="dashboard.php">Dashboard</a>
      <a href="products.php">Products</a>
      <a href="categories.php" class="active">Categories</a>
      <a href="orders.php">Orders</a>
      <a href="customers.php">Customers</a>
      <a href="messages.php">Messages</a>
    </nav>
  </aside>

  <div class="main">
    <header class="topbar">
      <button class="sidebar-toggle">☰</button>
      <div>Categories</div>
    </header>

    <main class="content">
      <div class="card">
        <h2>Add Category</h2>
        <form method="post" enctype="multipart/form-data" class="form">
          <input type="text" name="name_fr" placeholder="Name (FR)" required>
          <input type="text" name="name_ar" placeholder="Name (AR)" required>
          <input type="text" name="name_en" placeholder="Name (EN)" required>
          <input type="file" name="main_image" accept="image/*">
          <button type="submit" name="add_category" class="btn-sm">➕ Add</button>
        </form>
      </div>

      <div class="card">
        <h2>All Categories</h2>
        <table class="table">
          <tr>
            <th>ID</th>
            <th>Name (FR)</th>
            <th>Name (AR)</th>
            <th>Name (EN)</th>
            <th>Image</th>
            <th>Actions</th>
          </tr>
          <?php if($categories && $categories->num_rows > 0): ?>
            <?php while ($cat = $categories->fetch_assoc()): ?>
              <tr>
                <td><?= $cat['id'] ?></td>
                <td><?= htmlspecialchars($cat['name_fr']) ?></td>
                <td><?= htmlspecialchars($cat['name_ar']) ?></td>
                <td><?= htmlspecialchars($cat['name_en']) ?></td>
                <td>
                  <?php if ($cat['main_image']): ?>
                    <img src="../public/<?= htmlspecialchars($cat['main_image']) ?>" 
                         style="width:60px;height:60px;object-fit:cover;border-radius:6px;">
                  <?php else: ?>
                    <span style="color:#888;">No Image</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button class="btn-sm" onclick="openEdit(<?= $cat['id'] ?>,
                    '<?= htmlspecialchars($cat['name_fr']) ?>',
                    '<?= htmlspecialchars($cat['name_ar']) ?>',
                    '<?= htmlspecialchars($cat['name_en']) ?>')">✏ Edit</button>

                  <form method="post" style="display:inline;" onsubmit="return confirm('Are you sure to delete this category?');">
                    <input type="hidden" name="delete_category" value="<?= $cat['id'] ?>">
                    <button type="submit" class="btn-sm delete">🗑 Delete</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6">No categories found</td></tr>
          <?php endif; ?>
        </table>
      </div>
    </main>
  </div>
</div>

<div id="editModal" class="modal" style="display:none;">
  <div class="modal-content card">
    <h2>Edit Category</h2>
    <form method="post" enctype="multipart/form-data" class="form">
      <input type="hidden" name="id" id="edit_id">
      <input type="text" name="name_fr" id="edit_name_fr" placeholder="Name (FR)" required>
      <input type="text" name="name_ar" id="edit_name_ar" placeholder="Name (AR)" required>
      <input type="text" name="name_en" id="edit_name_en" placeholder="Name (EN)" required>
      <input type="file" name="main_image" accept="image/*">
      <button type="submit" name="edit_category" class="btn-sm">💾 Save</button>
      <button type="button" onclick="closeEdit()" class="btn-sm delete">✖ Cancel</button>
    </form>
  </div>
</div>

<script>
function openEdit(id, fr, ar, en) {
  document.getElementById('edit_id').value = id;
  document.getElementById('edit_name_fr').value = fr;
  document.getElementById('edit_name_ar').value = ar;
  document.getElementById('edit_name_en').value = en;
  document.getElementById('editModal').style.display = 'flex';
}
function closeEdit() {
  document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include "includes/footer.php"; ?>
