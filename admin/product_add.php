<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$categories = $conn->query("SELECT id, name_fr FROM categories");

function clean($value) {
    return htmlspecialchars(trim((string)$value), ENT_QUOTES, 'UTF-8');
}

function isValidImage($filename) {
    $allowed = ['jpg','jpeg','png','gif','webp'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    return in_array($ext, $allowed);
}

function safeFileName($originalName) {
    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    return uniqid("img_", true) . "." . $ext;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $name_fr = clean($_POST['name_fr'] ?? '');
        $name_ar = clean($_POST['name_ar'] ?? '');
        $name_en = clean($_POST['name_en'] ?? '');
        $desc_fr = clean($_POST['description_fr'] ?? '');
        $desc_ar = clean($_POST['description_ar'] ?? '');
        $desc_en = clean($_POST['description_en'] ?? '');
        $price   = ($_POST['price'] !== '') ? floatval($_POST['price']) : null;
        $stock   = intval($_POST['stock'] ?? 0);
        $category_id = intval($_POST['category_id'] ?? 0);
        $has_variants = isset($_POST['has_variants']) ? 1 : 0;

        if ($has_variants) {
            $price = null;
        }

        $stmt = $conn->prepare("INSERT INTO products 
            (category_id, name_fr, name_ar, name_en, description_fr, description_ar, description_en, price, stock) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssssddi", 
            $category_id, $name_fr, $name_ar, $name_en, 
            $desc_fr, $desc_ar, $desc_en, $price, $stock
        );
        $stmt->execute();
        $product_id = $stmt->insert_id;
        $stmt->close();

        $uploadDir = __DIR__ . "/../public/uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!$has_variants && !empty($_FILES['images']['name'][0])) {
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {
                if ($tmp == "") continue;

                $originalName = $_FILES['images']['name'][$key];
                if (!isValidImage($originalName)) continue;

                $fileName = safeFileName($originalName);
                $absolutePath = $uploadDir . $fileName;
                $dbPath = "uploads/" . $fileName;

                if (move_uploaded_file($tmp, $absolutePath)) {
                    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_url, is_main) VALUES (?, ?, 1)");
                    $stmt->bind_param("is", $product_id, $dbPath);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }

        if ($has_variants && !empty($_POST['variant_name_fr'][0])) {
            $main_variant_index = isset($_POST['main_variant']) ? intval($_POST['main_variant']) : -1;

            foreach ($_POST['variant_name_fr'] as $i => $v_name_fr) {
                if (trim($v_name_fr) === '') continue;

                $v_name_fr = clean($v_name_fr);
                $v_name_ar = clean($_POST['variant_name_ar'][$i] ?? '');
                $v_name_en = clean($_POST['variant_name_en'][$i] ?? '');
                $v_size    = clean($_POST['variant_size'][$i] ?? '');
                $v_price   = floatval($_POST['variant_price'][$i] ?? 0);
                $v_weight  = floatval($_POST['variant_weight'][$i] ?? 0);
                $v_bottle  = clean($_POST['variant_bottle'][$i] ?? '');
                $v_stock   = intval($_POST['variant_stock'][$i] ?? 0);
                $v_desc_fr = clean($_POST['variant_description_fr'][$i] ?? '');
                $v_desc_ar = clean($_POST['variant_description_ar'][$i] ?? '');
                $v_desc_en = clean($_POST['variant_description_en'][$i] ?? '');

                $main_variant = ($main_variant_index === $i) ? 1 : 0;

                $stmt = $conn->prepare("INSERT INTO product_variants 
                    (product_id, var_name_fr, var_name_ar, var_name_en, size, price, weight, bottle_type, stock, main_variant, description_var_fr, description_var_ar, description_var_en) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssddsiisss",
                    $product_id, $v_name_fr, $v_name_ar, $v_name_en,
                    $v_size, $v_price, $v_weight, $v_bottle, $v_stock, $main_variant,
                    $v_desc_fr, $v_desc_ar, $v_desc_en
                );
                $stmt->execute();
                $variant_id = $stmt->insert_id;
                $stmt->close();

                if (!empty($_FILES['variant_images']['name'][$i][0])) {
                    foreach ($_FILES['variant_images']['tmp_name'][$i] as $k => $tmp) {
                        if ($tmp == "") continue;

                        $originalName = $_FILES['variant_images']['name'][$i][$k];
                        if (!isValidImage($originalName)) continue;

                        $fileName = safeFileName($originalName);
                        $absolutePath = $uploadDir . $fileName;
                        $dbPath = "uploads/" . $fileName;

                        if (move_uploaded_file($tmp, $absolutePath)) {
                            $is_main = $main_variant ? 1 : 0;
                            $stmt = $conn->prepare("INSERT INTO product_images (product_id, variant_id, image_url, is_main) VALUES (?, ?, ?, ?)");
                            $stmt->bind_param("iisi", $product_id, $variant_id, $dbPath, $is_main);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
                }
            }
        }

        echo "<p style='color:green'>✅ Product added successfully!</p>";
    } catch (Exception $e) {
        echo "<p style='color:red'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<link rel="stylesheet" href="styles/admin.css">
<link rel="stylesheet" href="styles/add_products.css">
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
      <h2>Add Product</h2>
    </header>
    <main class="content">
      <form method="POST" enctype="multipart/form-data" class="form">

        <h3>Product Info</h3>
        <label>Name (FR): <input type="text" name="name_fr" required></label>
        <label>Name (AR): <input type="text" name="name_ar"></label>
        <label>Name (EN): <input type="text" name="name_en"></label>
        <label>Description (FR): <textarea name="description_fr"></textarea></label>
        <label>Description (AR): <textarea name="description_ar"></textarea></label>
        <label>Description (EN): <textarea name="description_en"></textarea></label>
        <label>Base Price: <input type="number" step="0.01" name="price"></label>
        <label>Stock: <input type="number" name="stock" required></label>
        <label>Category:
          <select name="category_id">
            <?php while ($c = $categories->fetch_assoc()): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name_fr']) ?></option>
            <?php endwhile; ?>
          </select>
        </label>
        
        <div id="product-images-field">
          <label>Images: <input type="file" name="images[]" multiple></label>
        </div>

        <h3>Variants Option</h3>
        <label>
          <input type="checkbox" id="has_variants" name="has_variants" onclick="toggleVariants()">
          This product has variants
        </label>

        <div id="variants-section" style="display:none; margin-top:20px;">
          <h3>Variants</h3>
          <div id="variants-container">
            <div class="variant-row">
              <label>Variant Name (FR): <input type="text" name="variant_name_fr[]"></label>
              <label>Variant Name (AR): <input type="text" name="variant_name_ar[]"></label>
              <label>Variant Name (EN): <input type="text" name="variant_name_en[]"></label>
              <label>Size: <input type="text" name="variant_size[]"></label>
              <label>Price: <input type="number" step="0.01" name="variant_price[]"></label>
              <label>Weight (g): <input type="number" step="0.01" name="variant_weight[]"></label>
              <label>Bottle Type: <input type="text" name="variant_bottle[]"></label>
              <label>Stock: <input type="number" name="variant_stock[]"></label>
              <label>Description (FR): <textarea name="variant_description_fr[]"></textarea></label>
              <label>Description (AR): <textarea name="variant_description_ar[]"></textarea></label>
              <label>Description (EN): <textarea name="variant_description_en[]"></textarea></label>
              <label>Main Variant: <input type="radio" name="main_variant" value="0"></label>
              <label>Images: <input type="file" name="variant_images[0][]" multiple></label>
            </div>
          </div>
          <button type="button" onclick="addVariantRow()">+ Add Another Variant</button>
        </div>

        <button type="submit">Add Product</button>
      </form>
    </main>
  </div>
</div>

<script>
function toggleVariants() {
    const checkbox = document.getElementById('has_variants');
    const variantsSection = document.getElementById('variants-section');
    const productImagesField = document.getElementById('product-images-field');

    variantsSection.style.display = checkbox.checked ? 'block' : 'none';
    productImagesField.style.display = checkbox.checked ? 'none' : 'block';
}

function addVariantRow() {
    const container = document.getElementById('variants-container');
    const row = document.querySelector('.variant-row').cloneNode(true);

    let index = container.children.length;

    row.querySelectorAll('input, textarea').forEach(input => {
        if (input.type === 'radio') {
            input.value = index;
            input.checked = false;
        } else if (input.type === 'file') {
            input.name = `variant_images[${index}][]`;
            input.value = '';
        } else {
            input.value = '';
        }
    });

    container.appendChild(row);
}
</script>

<?php include "includes/footer.php"; ?>
