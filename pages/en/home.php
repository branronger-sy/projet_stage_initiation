<?php

$categories = [];
$stmt = $pdo->prepare("SELECT id, name_en, main_image FROM categories ORDER BY `name_en` ASC LIMIT 100");
$stmt->execute();
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_favorites = [];
if (!empty($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT product_id FROM favoris WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user_favorites = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
}

$selectedCurrency = $selectedCurrency ?? 'MAD';
?>
<section class="carousel">
    <div class="container">
        <img src="images/carouselhome/Openng sales discounts.png" alt="Opening Sales Discounts - Argan Oil Products">
        <img src="images/carouselhome/Openng sales discounts (1).png" alt="Special Offers on Argan Oil - CoopArjana">
    </div>
    <div class="buttons">
        <button onclick="caroul(index-1)">&#10094;</button>
        <button onclick="caroul(index+1)">&#10095;</button>
    </div>
</section>

<main>
    <section class="Categories">
        <div>
            <h2>Explore Our Argan Oil Categories</h2>
        </div>
        <div class="cat">
            <?php foreach ($categories as $cat): ?>
                <div onclick="goToCategory(<?= $cat['id'] ?>)">
                    <img src="<?= $cat['main_image'] ?? 'images/default.jpg' ?>" alt="<?= $cat['name_en'] ?>">
                    <h3><?= $cat['name_en'] ?></h3>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="popular">
        <h2>Popular Products</h2>
        <div class="poproducts">
            <button class="carousel-btn left" onclick="scrollCarousel(-1)"><</button>
            <button class="carousel-btn right" onclick="scrollCarousel(1)">></button>
            <div id="prod-cont">
                <?php
                if (!empty($popular_products)) {
                    foreach ($popular_products as $row) {
                        $name = $row['name_en'];
                        $priceMAD = $row['price'];
                        $convertedPrice = $priceMAD;
                        $price_display = number_format(convertPrice($convertedPrice), 2);
                        $currencyLabel = $selectedCurrency;
                        $image = $row['image_url'] ?: 'images/default.jpg';
                        $product_id = $row['product_id'];
                        $has_variants = !empty($row['variant_count']);
                        $is_favorite = in_array($product_id, $user_favorites);
                        $active_class = $is_favorite ? 'active' : '';
                        ?>
                        <a href="index.php?page=product&id=<?= $product_id ?>" class="product-card-link">
                            <div class="product-card" data-id="<?= $product_id ?>">
                                <div class="image-wrapper">
                                    <img src="<?= $image ?>" alt="<?= $name ?>">
                                    <div class="btns">
                                        <div><div class="quick-view"></div></div>
                                        <div class="wishliste <?= $active_class ?>"
                                             onclick="event.preventDefault(); event.stopPropagation(); addToFavorites(<?= $product_id ?>, this, event)">
                                        </div>
                                    </div>
                                </div>
                                <h3><?= $name ?></h3>
                                <p><?= $price_display . ' ' . $currencyLabel ?></p>

                                <?php if ($has_variants): ?>
                                    <a href="index.php?page=product&id=<?= $product_id ?>"><button>More</button></a>
                                <?php else: ?>
                                    <button class="add-btn"
                                            onclick="event.stopPropagation(); event.preventDefault(); ajouterDepuisProduits(this)">+Add</button>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    echo "<p>No popular products found.</p>";
                }
                ?>
            </div>
        </div>
    </section>

    <section class="last">
        <h2>Last Products</h2>
        <div class="poproducts1">
            <button class="carousel-btn left" onclick="scrollCarousel(-1)"><</button>
            <button class="carousel-btn right" onclick="scrollCarousel(1)">></button>
            <div id="prod-cont1">
                <?php
                if (!empty($last_products)) {
                    foreach ($last_products as $row) {
                        $name = $row['name_en'];
                        $priceMAD = $row['price'];
                        $convertedPrice = $priceMAD;
                        $price_display = number_format($convertedPrice, 2);
                        $currencyLabel = $selectedCurrency;
                        $image = $row['image_url'] ?: 'images/default.jpg';
                        $product_id = $row['product_id'];
                        $has_variants = !empty($row['variant_count']);
                        $is_favorite = in_array($product_id, $user_favorites);
                        $active_class = $is_favorite ? 'active' : '';
                        ?>
                        <a href="index.php?page=product&id=<?= $product_id ?>" class="product-card-link">
                            <div class="product-card" data-id="<?= $product_id ?>">
                                <div class="image-wrapper">
                                    <img src="<?= $image ?>" alt="<?= $name ?>">
                                    <div class="btns">
                                        <div><div class="quick-view"></div></div>
                                        <div class="wishliste <?= $active_class ?>"
                                             onclick="event.preventDefault();addToFavorites(<?= $product_id ?>, this, event)">
                                        </div>
                                    </div>
                                </div>
                                <h3><?= $name ?></h3>
                                <p><?= $price_display . ' ' . $currencyLabel ?></p>
                                <?php if ($has_variants): ?>
                                    <button onclick="event.stopPropagation(); window.location.href='index.php?page=product&id=<?= $product_id ?>'">More</button>
                                <?php else: ?>
                                    <button onclick="event.preventDefault(); ajouterDepuisProduits(this)">+Add</button>
                                <?php endif; ?>
                            </div>
                        </a>
                        <?php
                    }
                } else {
                    echo "<p>No latest products found.</p>";
                }
                ?>
            </div>
        </div>
    </section>
</main>
