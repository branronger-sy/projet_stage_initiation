<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') {
    header_remove('X-Powered-By');
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: no-referrer-when-downgrade');
    header('Permissions-Policy: fullscreen=(), camera=(), microphone=()');
    if (
        (empty($_SERVER['HTTPS']) === false && $_SERVER['HTTPS'] !== 'off') ||
        (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
    ) {
        header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
    }
    header("Content-Security-Policy: img-src 'self' data:; frame-ancestors 'none';");
}
?>

<main class="about-main" role="main" aria-labelledby="about-heading">
  <section class="about-section about-hero">
    <h1 id="about-heading"><?php echo e('À propos de CoopArjana'); ?></h1>
    <p><?php echo e('Valoriser les femmes et promouvoir la beauté naturelle avec de l’huile d’argan marocaine 100% pure.'); ?></p>
  </section>

  <section class="about-section">
    <h2><?php echo e('Qui nous sommes'); ?></h2>
    <p><?php echo e('CoopArjana est une coopérative dirigée par des femmes située à Essaouira, au Maroc. Nous sommes dédiées à la production d’huile d’argan biologique, pressée à froid, en utilisant des méthodes traditionnelles transmises de génération en génération. Notre mission est de partager la magie de l’huile d’argan tout en soutenant les communautés locales.'); ?></p>
  </section>

  <section class="about-section">
    <h2><?php echo e('Notre mission & vision'); ?></h2>
    <p><strong><?php echo e('Mission :'); ?></strong> <?php echo e('Offrir au monde des soins naturels pour la peau et les cheveux grâce à une huile d’argan de haute qualité, tout en autonomisant les artisanes marocaines.'); ?></p>
    <p><strong><?php echo e('Vision :'); ?></strong> <?php echo e('Devenir une source mondiale de confiance en matière de beauté et de bien-être durables, enracinés dans la tradition et l’éthique.'); ?></p>
  </section>

  <section class="about-section">
    <h2><?php echo e('Rencontrez les femmes derrière l’huile'); ?></h2>
    <p><?php echo e('Notre coopérative est composée de femmes berbères passionnées qui sont fières de chaque étape du processus – de la cueillette manuelle du fruit de l’argan au pressage à froid de l’huile. Chaque bouteille porte une histoire de dévouement, de tradition et d’émancipation.'); ?></p>
  </section>

  <section class="about-section">
    <h2><?php echo e('Durabilité & éthique'); ?></h2>
    <p><?php echo e('Chez CoopArjana, nous nous engageons pour des pratiques respectueuses de l’environnement, le commerce équitable et des emballages recyclables. Nous croyons en une beauté qui prend soin des personnes et de la planète.'); ?></p>
  </section>

  <section class="about-section about-certifications" aria-label="<?php echo e('Certifications & Qualité'); ?>">
    <h2><?php echo e('Certifications & Qualité'); ?></h2>
    <ul>
      <li><?php echo e('Huile d’argan 100% biologique certifiée'); ?></li>
      <li><?php echo e('Approuvé par EcoCert'); ?></li>
      <li><?php echo e('Conforme au commerce équitable'); ?></li>
    </ul>
  </section>

  <section class="about-cta">
    <h2><?php echo e('Prêt(e) à découvrir la différence ?'); ?></h2>
    <a class="cta-button" href="index.php?page=ourstore" role="button" rel="noopener noreferrer"><?php echo e('Acheter de l’huile d’argan pure'); ?></a>
  </section>
</main>
