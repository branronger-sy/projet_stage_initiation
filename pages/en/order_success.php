<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
unset($_SESSION['cart'], $_SESSION['checkout_progress'], $_SESSION['payment_success']);
?>
<section class="success">
    <div class="container_success">
        <h1><?php echo e("Thank you for your purchase!"); ?></h1>
        <p><?php echo e("Your order has been confirmed successfully."); ?></p>
        <a href="index.php?page=home"><?php echo e("Back to Home"); ?></a>
    </div>
</section>
