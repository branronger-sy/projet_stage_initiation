<?php
include "includes/auth.php";
include "includes/header.php";
include "includes/db.php";

$id = $_GET['id'] ?? 0;
if (!$id) {
    echo "Invalid product ID.";
    include "includes/footer.php"; exit;
}

$categories = $conn->query("SELECT id, name_fr FROM categories");

$stmt = $conn->prepare("SELECT * FROM products WHERE id=?");
$stmt->bind_param("i",$id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) { echo "Product not found."; include "includes/footer.php"; exit; }

$has_variants = (int)$product['has_variants'];

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $name_fr = trim($_POST['name_fr'] ?? '');
    $name_ar = trim($_POST['name_ar'] ?? '');
    $name_en = trim($_POST['name_en'] ?? '');
    $desc_fr = trim($_POST['description_fr'] ?? '');
    $desc_ar = trim($_POST['description_ar'] ?? '');
    $desc_en = trim($_POST['description_en'] ?? '');
    $price   = ($_POST['price']!=='') ? floatval($_POST['price']) : null;
    $stock   = intval($_POST['stock'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $has_variants = isset($_POST['has_variants']) ? 1 : 0;

    if ($has_variants) {
        $price = null;
        $stock = 0;
    }

    $stmt = $conn->prepare("UPDATE products SET 
        category_id=?, name_fr=?, name_ar=?, name_en=?,
        description_fr=?, description_ar=?, description_en=?,
        price=?, stock=?, has_variants=? WHERE id=?");
    $stmt->bind_param("isssssddiii",
        $category_id,$name_fr,$name_ar,$name_en,
        $desc_fr,$desc_ar,$desc_en,$price,$stock,
        $has_variants,$id
    );
    $stmt->execute();
    $stmt->close();

    if ($has_variants) {
        $main_variant_id = intval($_POST['main_variant'] ?? 0);

        foreach ($_POST['variant_name_fr'] as $i => $v_name_fr) {
            $variant_id = intval($_POST['variant_id'][$i] ?? 0);
            if ($v_name_fr === '') continue;

            $v_name_ar = $_POST['variant_name_ar'][$i] ?? '';
            $v_name_en = $_POST['variant_name_en'][$i] ?? '';
            $v_size    = $_POST['variant_size'][$i] ?? '';
            $v_price   = floatval($_POST['variant_price'][$i] ?? 0);
            $v_weight  = floatval($_POST['variant_weight'][$i] ?? 0);
            $v_bottle  = $_POST['variant_bottle'][$i] ?? '';
            $v_stock   = intval($_POST['variant_stock'][$i] ?? 0);
            $v_desc_fr = $_POST['variant_description_fr'][$i] ?? '';
            $v_desc_ar = $_POST['variant_description_ar'][$i] ?? '';
            $v_desc_en = $_POST['variant_description_en'][$i] ?? '';
            $is_main   = ($main_variant_id == $variant_id || $main_variant_id == $i) ? 1 : 0;

            if ($variant_id) {
                $stmt = $conn->prepare("UPDATE product_variants SET 
                    var_name_fr=?, var_name_ar=?, var_name_en=?, size=?, price=?, weight=?, bottle_type=?, stock=?, main_variant=?, description_var_fr=?, description_var_ar=?, description_var_en=?
                    WHERE id=? AND product_id=?");
                $stmt->bind_param("ssssddsiisssii",
                    $v_name_fr,$v_name_ar,$v_name_en,$v_size,
                    $v_price,$v_weight,$v_bottle,$v_stock,$is_main,
                    $v_desc_fr,$v_desc_ar,$v_desc_en,$variant_id,$id
                );
                $stmt->execute();
                $stmt->close();
            } else {
                $stmt = $conn->prepare("INSERT INTO product_variants 
                    (product_id, var_name_fr, var_name_ar, var_name_en, size, price, weight, bottle_type, stock, main_variant, description_var_fr, description_var_ar, description_var_en) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issssddsiisss",
                    $id, $v_name_fr,$v_name_ar,$v_name_en,$v_size,
                    $v_price,$v_weight,$v_bottle,$v_stock,$is_main,
                    $v_desc_fr,$v_desc_ar,$v_desc_en
                );
                $stmt->execute();
                $variant_id = $stmt->insert_id;
                $stmt->close();
            }

            if (!empty($_FILES['variant_images']['name'][$i][0])) {
                $uploadDir = __DIR__ . "/../public/uploads/";
                if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
                foreach ($_FILES['variant_images']['tmp_name'][$i] as $k => $tmp) {
                    if (!$tmp) continue;
                    $originalName = $_FILES['variant_images']['name'][$i][$k];
                    $fileName = uniqid() . "_" . $originalName;
                    $abs = $uploadDir . $fileName;
                    $dbPath = "uploads/".$fileName;
                    if (move_uploaded_file($tmp,$abs)) {
                        $stmt=$conn->prepare("INSERT INTO product_images (product_id,variant_id,image_url,is_main) VALUES (?,?,?,?)");
                        $stmt->bind_param("iisi",$id,$variant_id,$dbPath,$is_main);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }
        }
    } else {
        if (!empty($_FILES['images']['name'][0])) {
            $uploadDir = __DIR__ . "/../public/uploads/";
            if (!is_dir($uploadDir)) mkdir($uploadDir,0755,true);
            foreach ($_FILES['images']['tmp_name'] as $k=>$tmp) {
                if (!$tmp) continue;
                $original = $_FILES['images']['name'][$k];
                $fileName = uniqid() . "_" . $original;
                $abs = $uploadDir.$fileName;
                $dbPath = "uploads/".$fileName;
                if (move_uploaded_file($tmp,$abs)) {
                    $stmt=$conn->prepare("INSERT INTO product_images (product_id,image_url,is_main) VALUES (?,?,0)");
                    $stmt->bind_param("is",$id,$dbPath);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    $stmt=$conn->prepare("SELECT * FROM products WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $has_variants = (int)$product['has_variants'];
}

$images = $conn->query("SELECT * FROM product_images WHERE product_id=$id AND variant_id IS NULL");
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
    <header class="topbar"><h2>Edit Product</h2></header>
    <main class="content">
      <form method="POST" enctype="multipart/form-data" class="form">
        <h3>Product Info</h3>
        <label>Name (FR): <input type="text" name="name_fr" value="<?=$product['name_fr']?>" required></label>
        <label>Name (AR): <input type="text" name="name_ar" value="<?=$product['name_ar']?>"></label>
        <label>Name (EN): <input type="text" name="name_en" value="<?=$product['name_en']?>"></label>
        <label>Description (FR): <textarea name="description_fr"><?=$product['description_fr']?></textarea></label>
        <label>Description (AR): <textarea name="description_ar"><?=$product['description_ar']?></textarea></label>
        <label>Description (EN): <textarea name="description_en"><?=$product['description_en']?></textarea></label>
        <label>Base Price: <input type="number" step="0.01" name="price" value="<?=$product['price']?>"></label>
        <label>Stock: <input type="number" name="stock" value="<?=$product['stock']?>"></label>
        <label>Category:
          <select name="category_id">
            <?php while($c=$categories->fetch_assoc()): ?>
              <option value="<?=$c['id']?>" <?=$c['id']==$product['category_id']?'selected':''?>>
                <?=$c['name_fr']?>
              </option>
            <?php endwhile; ?>
          </select>
        </label>

        <label>
          <input type="checkbox" id="has_variants" name="has_variants" value="1" <?= $has_variants ? 'checked' : '' ?> onclick="toggleVariants()">
          This product has variants
        </label>

        <div id="product-images-field" style="<?= $has_variants ? 'display:none' : '' ?>">
          <h3>Current Images</h3>
          <?php if ($images->num_rows > 0): ?>
              <div style="display:flex;flex-wrap:wrap;gap:10px;">
                  <?php while ($img = $images->fetch_assoc()): ?>
                      <div style="position:relative;display:inline-block;">
                          <img src="../public/<?= $img['image_url'] ?>" 
                               style="width:90px;height:90px;object-fit:cover;border:1px solid #ccc;border-radius:6px;">
                          <a href="includes/delete_image.php?id=<?= $img['id'] ?>"
                             onclick="return confirm('Delete this image?');"
                             style="position:absolute;top:0;right:0;background:#e53935;color:#fff;padding:2px 6px;font-size:12px;border-radius:0 6px 0 6px;text-decoration:none;">✕</a>
                      </div>
                  <?php endwhile; ?>
              </div>
          <?php else: ?>
              <p>No images</p>
          <?php endif; ?>
          <label>Add New Images: <input type="file" name="images[]" multiple></label>
        </div>

        <div id="variants-section" style="display: <?= $has_variants ? 'block' : 'none' ?>;margin-top:20px;">
          <h3>Variants</h3>
          <div id="variants-container">
            <?php
            if ($has_variants) {
                $variants = $conn->query("SELECT * FROM product_variants WHERE product_id=$id");
                while ($v = $variants->fetch_assoc()): ?>
                  <div class="variant-row">
                    <input type="hidden" name="variant_id[]" value="<?= $v['id'] ?>">
                    <label>Name (FR): <input type="text" name="variant_name_fr[]" value="<?= $v['var_name_fr'] ?>"></label>
                    <label>Size: <input type="text" name="variant_size[]" value="<?= $v['size'] ?>"></label>
                    <label>Price: <input type="number" step="0.01" name="variant_price[]" value="<?= $v['price'] ?>"></label>
                    <label>Stock: <input type="number" name="variant_stock[]" value="<?= $v['stock'] ?>"></label>
                    <label>Main: <input type="radio" name="main_variant" value="<?= $v['id'] ?>" <?= $v['main_variant']?'checked':'' ?>></label>
                    <a href="includes/delete_variant.php?id=<?= $v['id'] ?>" onclick="return confirm('Delete this variant?')">Delete</a>
                  </div>
            <?php endwhile; } ?>
          </div>
          <button type="button" onclick="addVariantRow()">+ Add Variant</button>
        </div>

        <button type="submit">Update Product</button>
      </form>
    </main>
  </div>
</div>

<script>
function toggleVariants(){
    const checkbox = document.getElementById('has_variants');
    document.getElementById('variants-section').style.display = checkbox.checked ? 'block' : 'none';
    document.getElementById('product-images-field').style.display = checkbox.checked ? 'none' : 'block';
}

function addVariantRow() {
    const container = document.getElementById('variants-container');
    const row = document.createElement('div');
    row.className = 'variant-row';
    const index = container.children.length;

    row.innerHTML = `
      <input type="hidden" name="variant_id[]" value="0">
      <label>Name (FR): <input type="text" name="variant_name_fr[]"></label>
      <label>Size: <input type="text" name="variant_size[]"></label>
      <label>Price: <input type="number" step="0.01" name="variant_price[]"></label>
      <label>Stock: <input type="number" name="variant_stock[]"></label>
      <label>Main: <input type="radio" name="main_variant" value="${index}"></label>
      <label>Images: <input type="file" name="variant_images[${index}][]" multiple></label>
    `;
    container.appendChild(row);
}
</script>

<?php include "includes/footer.php"; ?>
