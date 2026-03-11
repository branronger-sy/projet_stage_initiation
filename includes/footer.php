<?php
declare(strict_types=1);

function esc(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$lang = $lang ?? 'en';
$texts = $texts ?? [];

$allowed_pages = [
    'ourstore'      => 'ourstore',
    'delivery'      => 'delivery',
    'terms'         => 'terms',
    'myorders'      => 'myorders',
    'personalinfos' => 'personalinfos'
];

function page_url(string $pageKey): string {
    global $allowed_pages;
    if (!isset($allowed_pages[$pageKey])) {
        return 'index.php';
    }
    return 'index.php?page=' . rawurlencode($allowed_pages[$pageKey]);
}

$contact_email = isset($texts[$lang]['contact_email']) ? $texts[$lang]['contact_email'] : 'cooparjana@hotmail.com';
if (!filter_var($contact_email, FILTER_VALIDATE_EMAIL)) {
    $contact_email = 'cooparjana@hotmail.com';
}
$contact_phone = isset($texts[$lang]['contact_phone']) ? $texts[$lang]['contact_phone'] : '+212 766 77 28 03';

?>
<footer class="footer" role="contentinfo" aria-label="<?= esc($texts[$lang]['footer_aria_label'] ?? 'Footer') ?>">
    <div class="footer-container">
        <div class="footer-section about">
            <h3><?= esc($texts[$lang]['info'] ?? 'Info') ?></h3>
            <ul>
                <li><a href="<?= esc(page_url('ourstore')) ?>"><?= esc($texts[$lang]['our_stores'] ?? 'Our Stores') ?></a></li>
                <li><a href="<?= esc(page_url('delivery')) ?>"><?= esc($texts[$lang]['delivery'] ?? 'Delivery') ?></a></li>
                <li><a href="<?= esc(page_url('terms')) ?>"><?= esc($texts[$lang]['terms_conditions'] ?? 'Terms') ?></a></li>
            </ul>
        </div>

        <div class="footer-section links">
            <h3><?= esc($texts[$lang]['account'] ?? 'Account') ?></h3>
            <ul>
                <li><a href="<?= esc(page_url('myorders')) ?>"><?= esc($texts[$lang]['my_orders'] ?? 'My Orders') ?></a></li>
                <li><a href="<?= esc(page_url('personalinfos')) ?>"><?= esc($texts[$lang]['personal_infos'] ?? 'Personal Info') ?></a></li>
            </ul>
        </div>

        <div class="footer-section contact">
            <h3><?= esc($texts[$lang]['contact_title'] ?? 'Contact') ?></h3>
            <p><i class="fas fa-map-marker-alt" aria-hidden="true"></i> <?= esc($texts[$lang]['contact_address'] ?? 'Coopérative Arjana – Essaouira, Morocco') ?></p>
            <p><i class="fas fa-phone" aria-hidden="true"></i> <?= esc($contact_phone) ?></p>
            <p><i class="fas fa-envelope" aria-hidden="true"></i>
                <a href="mailto:<?= rawurlencode($contact_email) ?>"><?= esc($contact_email) ?></a>
            </p>
        </div>
    </div>

    <div class="footer-bottom">
        <p>&copy; <?= esc((string)date('Y')) ?> CoopArjana – Morocco. <?= esc($texts[$lang]['rights'] ?? 'All rights reserved.') ?></p>
    </div>
</footer>
<?php
if (isset($page_scripts) && is_array($page_scripts) && isset($page) && is_string($page)) {
    $safePage = preg_replace('/[^a-zA-Z0-9_-]/', '', $page);
    if (isset($page_scripts[$safePage]) && is_array($page_scripts[$safePage])) {
        foreach ($page_scripts[$safePage] as $script) {
            if (!is_string($script)) continue;
            $script = trim($script);
            if (strpos($script, '..') !== false) continue;
            if (!preg_match('#^[a-z0-9\/._-]+\.js$#i', $script)) continue;
            $safeScript = esc($script);
            echo "<script src=\"{$safeScript}\" defer></script>\n";
        }
    }
}
?>
</body>
</html>
