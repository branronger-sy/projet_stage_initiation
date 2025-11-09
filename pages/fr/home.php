<?php
$cookieParams = session_get_cookie_params();
session_set_cookie_params([
    'lifetime' => $cookieParams['lifetime'],
    'path' => $cookieParams['path'],
    'domain' => $cookieParams['domain'],
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax'
]);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
ini_set('display_errors', '0');
error_reporting(E_ALL);
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: no-referrer-when-downgrade");
header("Content-Security-Policy: default-src 'self'; 
        script-src 'self' https://cdnjs.cloudflare.com; 
        style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com; 
        img-src 'self' data:;");
function ensureCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
function validateCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || !$token) return false;
    return hash_equals($_SESSION['csrf_token'], (string)$token);
}
function sanitize_image_src(string $src): string {
    return trim($src);
}
function json_response(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
if (!isset($pdo) || !($pdo instanceof PDO)) {
    error_log('PDO instance not available in home.php');
    http_response_code(500);
    exit('Erreur serveur');
}
$categories = [];
try {
    $stmt = $pdo->prepare("SELECT id, name_fr, main_image FROM categories ORDER BY `name_fr` ASC LIMIT 100");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log("DB Error fetching categories: " . $e->getMessage());
    $categories = [];
}
$user_favorites = [];
if (!empty($_SESSION['user_id']) && ctype_digit((string)$_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT product_id FROM favoris WHERE user_id = ?");
        $stmt->execute([ (int)$_SESSION['user_id'] ]);
        $user_favorites = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
        $user_favorites = array_map('intval', $user_favorites);
    } catch (PDOException $e) {
        error_log("DB Error fetching favorites: " . $e->getMessage());
        $user_favorites = [];
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['action']) && $_POST['action'] === 'add_favorite')) {
    $originOk = true;
    if (!empty($_SERVER['HTTP_ORIGIN'])) {
        $allowed = parse_url((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST']);
        $origin = parse_url($_SERVER['HTTP_ORIGIN']);
        $originOk = ($origin['host'] ?? '') === ($allowed['host'] ?? '');
    }
    if (!$originOk) {
        json_response(['success' => false, 'message' => 'Origine non autorisée'], 403);
    }
    $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!validateCsrfToken($csrfToken)) {
        json_response(['success' => false, 'message' => 'Token CSRF invalide'], 400);
    }
    if (empty($_SESSION['user_id']) || !ctype_digit((string)$_SESSION['user_id'])) {
        json_response(['success' => false, 'message' => 'Authentification requise'], 401);
    }
    $product_id = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
    if (!$product_id || $product_id <= 0) {
        json_response(['success' => false, 'message' => 'ID produit invalide'], 400);
    }
    $user_id = (int)$_SESSION['user_id'];
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favoris WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$user_id, $product_id]);
        $exists = (int)$stmt->fetchColumn() > 0;
        if ($exists) {
            json_response(['success' => false, 'message' => 'Déjà dans les favoris'], 409);
        }
        $stmt = $pdo->prepare("INSERT INTO favoris (user_id, product_id) VALUES (?, ?)");
        $stmt->execute([$user_id, $product_id]);
        json_response(['success' => true, 'message' => 'Ajouté aux favoris'], 201);
    } catch (PDOException $e) {
        error_log("DB Error inserting favorite: " . $e->getMessage());
        json_response(['success' => false, 'message' => 'Erreur base de données'], 500);
    }
}
if (!function_exists('convertPrice')) {
    function convertPrice($price) {
        return (float)$price;
    }
}
$selectedCurrency = $selectedCurrency ?? 'MAD'; 
$csrf_for_view = ensureCsrfToken();
?>
<meta name="csrf-token" content="<?= htmlspecialchars($csrf_for_view, ENT_QUOTES, 'UTF-8') ?>">
<section class="carousel">
    <div class="container">
        <img src="<?= sanitize_image_src('images/carouselhome/Openng sales discounts.png') ?>" alt="Promotions d’ouverture - Produits à base d’huile d’argan">
        <img src="<?= sanitize_image_src('images/carouselhome/Openng sales discounts (1).png') ?>" alt="Offres spéciales sur l’huile d’argan - CoopArjana">
    </div>
    <div class="buttons">
        <button onclick="caroul(index-1)">&#10094;</button>
        <button onclick="caroul(index+1)">&#10095;</button>
    </div>
</section>
<main>
    <section class="Categories">
        <div>
            <h2>Découvrez nos catégories d’huile d’argan</h2>
        </div>
        <div class="cat">
            <?php foreach ($categories as $cat): ?>
                <div onclick="goToCategory(<?= (int)$cat['id'] ?>)">
                    <img src="<?= sanitize_image_src($cat['main_image'] ?? 'images/default.jpg') ?>" 
                         alt="<?= htmlspecialchars($cat['name_fr'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <h3><?= htmlspecialchars($cat['name_fr'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
    <section class="popular">
        <h2>Produits populaires</h2>
        <div class="poproducts">
            <button class="carousel-btn left" onclick="scrollCarousel(-1)"><</button>
            <button class="carousel-btn right" onclick="scrollCarousel(1)">></button>
            <div id="prod-cont">
                <?php
                if (!empty($popular_products) && is_array($popular_products)) {
                    foreach ($popular_products as $row) {
                        $name = htmlspecialchars($row['name_fr'] ?? '', ENT_QUOTES, 'UTF-8');
                        $priceMAD = (float)($row['price'] ?? 0);
                        $convertedPrice = convertPrice($priceMAD);
                        $price_display = number_format($convertedPrice, 2);
                        $currencyLabel = htmlspecialchars($selectedCurrency ?? 'MAD', ENT_QUOTES, 'UTF-8');
                        $image = !empty($row['image_url']) ? sanitize_image_src($row['image_url']) : 'images/default.jpg';
                        $product_id = (int)($row['product_id'] ?? 0);
                        $has_variants = !empty($row['variant_count']) && $row['variant_count'] > 0;
                        $is_favorite = in_array($product_id, $user_favorites, true);
                        $active_class = $is_favorite ? 'active' : '';
                        ?>
                        <a href="index.php?page=product&id=<?= $product_id ?>" class="product-card-link" rel="noopener noreferrer">
                            <div class="product-card" data-id="<?= $product_id ?>">
                                <div class="image-wrapper">
                                    <img src="<?= $image ?>" alt="<?= $name ?>">
                                    <div class="btns">
                                        <div><div class="quick-view" aria-hidden="true"></div></div>
                                        <div class="wishliste <?= $active_class ?>"
                                             onclick="event.preventDefault(); event.stopPropagation(); addToFavorites(<?= $product_id ?>, this, event)">
                                        </div>
                                    </div>
                                </div>
                                <h3><?= $name ?></h3>
                                <p><?= $price_display . ' ' . $currencyLabel ?></p>
                                <?php if ($has_variants): ?>
                                    <a href="index.php?page=product&amp;id=<?= $product_id ?>"><button>Plus</button></a>
                                <?php else: ?>
                                    <button class="add-btn"
                                            onclick="event.stopPropagation(); event.preventDefault(); ajouterDepuisProduits(this)">+Ajouter</button>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    echo "<p>Aucun produit populaire trouvé.</p>";
                }
                ?>
            </div>
        </div>
    </section>
    <section class="last">
        <h2>Derniers produits</h2>
        <div class="poproducts1">
            <button class="carousel-btn left" onclick="scrollCarousel1(-1)"><</button>
            <button class="carousel-btn right" onclick="scrollCarousel1(1)">></button>
            <div id="prod-cont1">
                <?php
                if (!empty($last_products) && is_array($last_products)) {
                    foreach ($last_products as $row) {
                        $name = htmlspecialchars($row['name_fr'] ?? '', ENT_QUOTES, 'UTF-8');
                        $priceMAD = (float)($row['price'] ?? 0);
                        $convertedPrice = convertPrice($priceMAD);
                        $price_display = number_format($convertedPrice, 2);
                        $currencyLabel = htmlspecialchars($selectedCurrency ?? 'MAD', ENT_QUOTES, 'UTF-8');
                        $image = !empty($row['image_url']) ? sanitize_image_src($row['image_url']) : 'images/default.jpg';
                        $product_id = (int)($row['product_id'] ?? 0);
                        $has_variants = !empty($row['variant_count']) && $row['variant_count'] > 0;
                        $is_favorite = in_array($product_id, $user_favorites, true);
                        $active_class = $is_favorite ? 'active' : '';
                        ?>
                        <a href="index.php?page=product&id=<?= $product_id ?>" class="product-card-link" rel="noopener noreferrer">
                            <div class="product-card" data-id="<?= $product_id ?>">
                                <div class="image-wrapper">
                                    <img src="<?= $image ?>" alt="<?= $name ?>">
                                    <div class="btns">
                                        <div><div class="quick-view" aria-hidden="true"></div></div>
                                        <div class="wishliste <?= $active_class ?>"
                                             onclick="event.preventDefault(); event.stopPropagation(); addToFavorites(<?= $product_id ?>, this, event)">
                                        </div>
                                    </div>
                                </div>
                                <h3><?= $name ?></h3>
                                <p><?= $price_display . ' ' . $currencyLabel ?></p>
                                <?php if ($has_variants): ?>
                                    <button onclick="event.stopPropagation(); window.location.href='index.php?page=product&id=<?= $product_id ?>'">Plus</button>
                                <?php else: ?>
                                    <button onclick="event.stopPropagation(); event.preventDefault(); ajouterDepuisProduits(this)">+Ajouter</button>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    echo "<p>Aucun nouveau produit trouvé.</p>";
                }
                ?>
            </div>
        </div>
    </section>
</main>
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
    async function addToFavorites(productId, el, ev) {
        try {
            const form = new FormData();
            form.append('action', 'add_favorite');
            form.append('product_id', productId);
            form.append('csrf_token', csrfToken);
            const resp = await fetch(window.location.href, {
                method: 'POST',
                credentials: 'same-origin',
                body: form,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const data = await resp.json();
            if (data.success) {
                el.classList.add('active');
            } else {
                alert(data.message || 'Erreur');
            }
        } catch (err) {
            console.error(err);
            alert('Erreur réseau');
        }
    }
</script>
