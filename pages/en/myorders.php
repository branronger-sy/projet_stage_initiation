<?php
declare(strict_types=1);

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('You must be logged in'); window.location.href='index.php?page=login';</script>";
    exit;
}
$user_id = (int) $_SESSION['user_id'];

    $stmt = $pdo->prepare("
        SELECT id, total_price, payment_status, order_status, created_at 
        FROM orders 
        WHERE user_id = :user_id 
        ORDER BY created_at DESC
    ");
    $stmt->execute(['user_id' => $user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<main>
    <section class="orders-list">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <article class="order-card status-<?php echo e(strtolower($order['order_status'])); ?>">
                    <h2>Order #<?php echo e((string)$order['id']); ?></h2>
                    <p><strong>Total Price:</strong> $<?php echo e(number_format((float)$order['total_price'], 2)); ?></p>
                    <p><strong>Payment:</strong> <?php echo e(ucfirst($order['payment_status'])); ?></p>
                    <p><strong>Date:</strong> <?php echo e(date("F j, Y", strtotime($order['created_at']))); ?></p>
                    <p class="status"><strong>Status:</strong> <?php echo e(ucfirst($order['order_status'])); ?></p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </section>
</main>
