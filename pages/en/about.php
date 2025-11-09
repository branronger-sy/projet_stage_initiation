<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Permissions-Policy: fullscreen=(), camera=(), microphone=()');
    if ((empty($_SERVER['HTTPS']) === false && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    header("Content-Security-Policy: img-src 'self' data:; frame-ancestors 'none';");
}

?>
<main class="about-main" role="main" aria-labelledby="about-heading">
  <section class="about-section about-hero">
    <h1 id="about-heading"><?php echo e('About CoopArjana'); ?></h1>
    <p><?php echo e('Empowering women and promoting natural beauty with 100% pure Moroccan argan oil.'); ?></p>
  </section>
  <section class="about-section">
    <h2><?php echo e('Who We Are'); ?></h2>
    <p><?php echo e('CoopArjana is a women-led cooperative located in Essaouira, Morocco. We are dedicated to producing cold-pressed, organic argan oil using traditional methods passed down through generations. Our mission is to share the magic of argan oil while supporting local communities.'); ?></p>
  </section>

  <section class="about-section">
    <h2><?php echo e('Our Mission & Vision'); ?></h2>
    <p><strong><?php echo e('Mission:'); ?></strong> <?php echo e('To provide the world with authentic, natural skincare and haircare through high-quality argan oil, while empowering Moroccan women artisans.'); ?></p>
    <p><strong><?php echo e('Vision:'); ?></strong> <?php echo e('To become a globally trusted source of sustainable beauty and wellness rooted in tradition and ethics.'); ?></p>
  </section>

  <section class="about-section">
    <h2><?php echo e('Meet the Women Behind the Oil'); ?></h2>
    <p><?php echo e('Our cooperative consists of passionate Berber women who take pride in every step of the process – from hand-picking the argan fruit to cold-pressing the oil. Each bottle carries a story of dedication, tradition, and empowerment.'); ?></p>
  </section>

  <section class="about-section">
    <h2><?php echo e('Sustainability & Ethics'); ?></h2>
    <p><?php echo e('At CoopArjana, we are committed to eco-friendly practices, fair trade, and recyclable packaging. We believe in beauty that cares for people and the planet.'); ?></p>
  </section>

  <section class="about-section about-certifications" aria-label="<?php echo e('Certifications & Quality'); ?>">
    <h2><?php echo e('Certifications & Quality'); ?></h2>
    <ul>
      <li><?php echo e('100% Organic Argan Certified'); ?></li>
      <li><?php echo e('EcoCert Approved'); ?></li>
      <li><?php echo e('Fair Trade Compliant'); ?></li>
    </ul>
  </section>

  <section class="about-cta">
    <h2><?php echo e('Ready to Experience the Difference?'); ?></h2>
    <a class="cta-button" href="index.php?page=ourstore" role="button" rel="noopener noreferrer"><?php echo e('Shop Pure Argan Oil'); ?></a>
  </section>
</main>
