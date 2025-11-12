<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'db.php';

$allowedCurrencies = ['MAD', 'USD', 'EUR'];
$allowedLangs = ['en', 'fr'];

$selectedCurrency = $_SESSION['selectedCurrency'] ?? 'MAD';
if (isset($_GET['currency']) && in_array($_GET['currency'], $allowedCurrencies, true)) {
    $selectedCurrency = $_GET['currency'];
    $_SESSION['selectedCurrency'] = $selectedCurrency;
}

$lang = $_SESSION['lang'] ?? 'en';
if (isset($_GET['lang']) && in_array($_GET['lang'], $allowedLangs, true)) {
    $lang = $_GET['lang'];
    $_SESSION['lang'] = $lang;
}

require 'lang.php';
$texts = $texts ?? [];
    $stmt = $pdo->query("SELECT id, name_en, name_fr FROM categories");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
$user_favorites = [];
$favorites_count = 0;
if (!empty($_SESSION['user_id'])) {
        $stmt = $pdo->prepare("SELECT product_id FROM favoris WHERE user_id = :uid LIMIT 1000");
        $stmt->execute([':uid' => $_SESSION['user_id']]);
        $user_favorites = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $favorites_count = count($user_favorites);

}

require 'currency.php';
function e($str) {
    return htmlspecialchars((string)$str, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <link rel="stylesheet" href="style/defaultmargin.css" />
    <link rel="stylesheet" href="style/header.css" />
    <link rel="stylesheet" href="style/footer.css" />

    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css"
          crossorigin="anonymous" />

    <title><?= e('CoopArjana') ?></title>

    <?php
    if (isset($page, $page_styles) && is_string($page)) {
        $safePage = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
        if (!empty($page_styles[$safePage]) && is_array($page_styles[$safePage])) {
            foreach ($page_styles[$safePage] as $cssFile) {
                echo '<link rel="stylesheet" href="' . e($cssFile) . '" />' . "\n";
            }
        }
    }
    ?>
</head>
<body>
    <header class="navbar">
        <h1 style="display:none;"><?= e('CoopArjana | Natural Argan Oil Cooperative in Morocco') ?></h1>

        <div class="logonav">
            <div class="logo">
                <a href="index.php?page=home">
                    <img src="images/logo/logo (2).png"
                         alt="CoopArjana logo"
                         title="CoopArjana | Natural Argan Oil from Morocco"
                         loading="lazy" />
                </a>
            </div>

            <div class="drop">
                <div class="currency-switcher">
                    <form method="get" action="index.php" autocomplete="off">
                        <?php
                        $allowedHidden = ['page', 'category_id', 'q'];
                        foreach ($_GET as $key => $value) {
                            if (!in_array($key, $allowedHidden, true) || $key === 'currency') continue;
                            echo '<input type="hidden" name="' . e($key) . '" value="' . e($value) . '">';
                        }
                        ?>
                        <select name="currency" onchange="this.form.submit()">
                            <?php
                            foreach ($allowedCurrencies as $cur) {
                                $sel = ($selectedCurrency === $cur) ? 'selected' : '';
                                echo '<option value="' . e($cur) . '" ' . $sel . '>' . e($cur) . '</option>';
                            }
                            ?>
                        </select>
                    </form>
                </div>

                <div class="language-switcher">
                    <form method="get" action="index.php" autocomplete="off">
                        <?php
                        foreach ($_GET as $key => $value) {
                            if ($key === 'lang') continue;
                            if (!in_array($key, ['page', 'category_id', 'q'], true)) continue;
                            echo '<input type="hidden" name="' . e($key) . '" value="' . e($value) . '">';
                        }
                        ?>
                        <select name="lang" onchange="this.form.submit()">
                            <option value="en" <?= ($lang === 'en') ? 'selected' : '' ?>>EN</option>
                            <option value="fr" <?= ($lang === 'fr') ? 'selected' : '' ?>>FR</option>
                        </select>
                    </form>
                </div>
            </div>

            <nav class="nav-links" role="navigation">
                <ul>
                    <li><a href="index.php?page=home"><?= e($texts[$lang]['home'] ?? 'Home') ?></a></li>
                    <li class="prod">
                        <a href="index.php?page=category"><?= e($texts[$lang]['products'] ?? 'Products') ?>
                            <i class="fas fa-chevron-down" style="font-size:0.6em;"></i>
                        </a>
                        <ul class="bar2">
                            <?php foreach($categories as $cat): ?>
                                <li>
                                    <a href="index.php?page=category&amp;category_id=<?= (int) $cat['id'] ?>">
                                        <?= e($lang === 'fr' ? $cat['name_fr'] : $cat['name_en']) ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li><a href="index.php?page=benifits"><?= e($texts[$lang]['benefits'] ?? 'Benefits') ?></a></li>
                    <li><a href="index.php?page=about"><?= e($texts[$lang]['about'] ?? 'About') ?></a></li>
                    <li><a href="index.php?page=contact"><?= e($texts[$lang]['contact'] ?? 'Contact') ?></a></li>
                    <li><a href="index.php?page=terms"><?= e($texts[$lang]['terms'] ?? 'Terms') ?></a></li>
                </ul>
            </nav>
        </div>

        <div class="autre-comp">
            <div class="search-box">
                <form id="search-form" action="index.php" method="get" role="search" autocomplete="off">
                    <input type="hidden" name="page" value="search">
                    <input class="search-input" type="text" name="q"
                           placeholder="<?= e($texts[$lang]['search_placeholder'] ?? 'Search...') ?>"
                           required>
                    <button type="submit" class="search-btn" style="background:none;border:none;cursor:pointer;padding:0;">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>

            <div class="wishlist" onclick="window.location.href='index.php?page=favoris'">
                <i class="fa fa-heart heart-icon"></i>
                <span id="fav-count"><?= (int) $favorites_count ?></span>
            </div>

            <div class="user" onclick="window.location.href='index.php?page=personalinfos'"></div>

            <div class="bag" onclick="afficher()" role="button"><span id="bag-count">0</span></div>

            <div class="cart-popup" id="cart-popup">
                <h4><?= e($texts[$lang]['your_cart'] ?? 'Your Cart') ?></h4>
                <ul class="cart-items" id="cart-items"></ul>
                <div class="cart-total">
                    <span><?= e($texts[$lang]['total'] ?? 'Total') ?></span>
                    <span id="cart-total">0 <?= e($selectedCurrency) ?></span>
                </div>
                <form method="POST" action="index.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrf_token) ?>">
                    <input type="hidden" name="start_checkout" value="1">
                    <button type="submit"><?= e('Checkout') ?></button>
                </form>
            </div>

            <div class="menu-toggle" id="menu-toggle" onclick="toggle()">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </header>
    <hr />
</body>
</html>
