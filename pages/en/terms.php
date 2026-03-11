<?php
declare(strict_types=1);

$csp  = "font-src 'self' https://cdnjs.cloudflare.com;";
$csp .= "img-src 'self' data:;";
$csp .= "connect-src 'self';";
$csp .= "frame-ancestors 'none';";
header("Content-Security-Policy: $csp");

$terms_title = "Conditions Générales de Vente";
$terms_intro = "Les présentes conditions générales de vente régissent toutes les commandes passées sur le site de la Coopérative Arjana. En confirmant un achat, vous acceptez ces conditions sans réserve.";
?>
<main class="terms-main">
  <section class="terms-section terms-hero">
    <h1><?php echo e($terms_title); ?></h1>
    <p><?php echo e($terms_intro); ?></p>
  </section>
  <section class="terms-section">
    <h2>1. Préambule</h2>
    <p><?php echo e("Les présentes conditions générales de vente régissent toutes les transactions effectuées sur le catalogue en ligne de la Coopérative Arjana. Toute commande passée sur ce site implique l’acceptation pleine et entière et sans réserve du client de ces conditions."); ?></p>
  </section>
  <section class="terms-section">
    <h2>2. Objet</h2>
    <p><?php echo e("Le présent contrat est un contrat à distance ayant pour objet de définir les droits et obligations des parties dans le cadre de la vente des produits de la Coopérative Arjana via internet, en utilisant la plateforme PayPal."); ?></p>
  </section>
  <section class="terms-section">
    <h2>3. Modalités de Paiement</h2>
    <p><?php echo e("Pour régler votre commande, vous pouvez choisir parmi les moyens de paiement proposés par la Coopérative Arjana sur la page de paiement."); ?></p>
    <p><?php echo e("La transaction est traitée et débitée de votre compte le jour suivant la confirmation de commande."); ?></p>
    <p><?php echo e("Les paiements sont sécurisés et traités par PayPal, qui offre un service totalement protégé et crypté."); ?></p>
    <p><?php echo e("Le client garantit à la Coopérative Arjana qu’il dispose des autorisations nécessaires pour utiliser le mode de paiement choisi au moment de la confirmation de la commande."); ?></p>
    <p><?php echo e("En cas de paiement par carte, les règles relatives à l’utilisation frauduleuse des moyens de paiement s’appliquent, telles que prévues dans les conventions conclues entre le consommateur et son établissement bancaire, et entre la Coopérative Arjana et son prestataire bancaire."); ?></p>
  </section>
  <section class="terms-section">
    <h2>4. Confirmation de Commande</h2>
    <p><?php echo e("Après avoir confirmé votre commande, vous recevrez un récapitulatif par email. La Coopérative Arjana se réserve le droit d’annuler toute commande suspecte ou incomplète."); ?></p>
  </section>
  <section class="terms-section">
    <h2>5. Livraison</h2>
    <p><?php echo e("Les produits sont expédiés à l’adresse indiquée par le client. La Coopérative Arjana n’est pas responsable des retards ou pertes de colis dus à des adresses incorrectes ou à des problèmes liés aux transporteurs tiers."); ?></p>
  </section>
  <section class="terms-section">
    <h2>6. Retours &amp; Remboursements</h2>
    <p><?php echo e("En raison de la nature naturelle et personnelle de nos produits, les retours ne sont acceptés que si l’article est endommagé ou défectueux à la réception. Dans ce cas, vous devez nous contacter dans un délai de 7 jours après la livraison."); ?></p>
  </section>
  <section class="terms-section">
    <h2>7. Confidentialité &amp; Protection des Données</h2>
    <p><?php echo e("Toutes les données clients sont confidentielles et utilisées uniquement pour le traitement des commandes et la communication avec les clients. Aucune donnée ne sera transmise à des tiers sans votre consentement explicite."); ?></p>
  </section>
  <section class="terms-section">
    <h2>8. Juridiction</h2>
    <p><?php echo e("Les présentes conditions sont soumises au droit marocain. Tout litige sera réglé par les tribunaux marocains."); ?></p>
  </section>
  <section class="terms-section terms-cta">
    <h2>Besoin d’Aide ?</h2>
    <p>
      <?php
          $contactUrl = '/index.php?page=contact';
          echo '<a href="' . e($contactUrl) . '">Contactez-nous</a>.';
      ?>
    </p>
  </section>
</main>