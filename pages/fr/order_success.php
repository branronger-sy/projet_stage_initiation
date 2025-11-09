<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

unset($_SESSION['cart'], $_SESSION['checkout_progress'], $_SESSION['payment_success']);

?>

<section class="success">
    <div class="container_success">
        <h1><?php echo e("Merci pour votre achat !"); ?></h1>
        <p><?php echo e("Votre commande a été confirmée avec succès."); ?></p>
        <a href="index.php?page=home"><?php echo e("Retour à l'accueil"); ?></a>
    </div>
</section>
