<?php
declare(strict_types=1);
$store_city = 'ESSAOUIRA';
$store_heading = 'Coop Arjana - Main Store';
$store_description = 'We are happy to welcome you at our main store in ' . $store_city . '.';

$map_src = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d13276.123456789!2d-9.600000!3d30.420000!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xdb1234567890abcd%3A0xabcdef1234567890!2sAgadir%2C%20Morocco!5e0!3m2!1sen!2sma!4v1718633200000!5m2!1sen!2sma";

$map_valid = false;
$parsed = parse_url($map_src);
if (isset($parsed['scheme'], $parsed['host']) && $parsed['scheme'] === 'https' && (strpos($parsed['host'], 'google') !== false || strpos($parsed['host'], 'maps') !== false)) {
    $map_valid = true;
}

?>
  <main>
    <h1><?php echo htmlspecialchars($store_heading, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h1>

    <section class="store-location">
      <div class="container">
        <h2><?php echo htmlspecialchars('Our Store Location', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></h2>
        <p><?php echo htmlspecialchars($store_description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></p>

        <div class="map-container" aria-hidden="false" role="region" aria-label="Map showing store location">
<?php if ($map_valid): ?>
  <iframe
  src="<?php echo htmlspecialchars($map_src, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
  title="Map to our store in <?php echo htmlspecialchars($store_city, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>"
  width="100%"
  height="100%"
  style="border:0;"
  loading="lazy"
  referrerpolicy="no-referrer-when-downgrade"
  allowfullscreen>
</iframe>

<?php else: ?>
          <p>Map is currently unavailable. <a href="https://www.google.com/maps/search/?api=1&query=<?php echo urlencode($store_city); ?>" rel="noopener noreferrer">Open in Google Maps</a>.</p>
<?php endif; ?>
        </div>
      </div>
    </section>
  </main>
