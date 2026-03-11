<?php
$user_id = $_SESSION['user_id'] ?? 0;
$orders = [];
$result = $pdo->query("SELECT id, total_price, payment_status, order_status, created_at 
                       FROM orders 
                       WHERE user_id = $user_id 
                       ORDER BY created_at DESC");
if ($result) {
    $orders = $result->fetchAll(PDO::FETCH_ASSOC);
}
?>

<main>
    <section class="orders-list">
        <?php if (!empty($orders)): ?>
            <?php foreach ($orders as $order): ?>
                <article class="order-card status-<?php echo strtolower($order['order_status']); ?>">
                    <h2>Order #<?php echo $order['id']; ?></h2>
                    <p><strong>Total Price:</strong> $<?php echo number_format($order['total_price'], 2); ?></p>
                    <p><strong>Payment:</strong> <?php echo $order['payment_status']; ?></p>
                    <p><strong>Date:</strong> <?php echo date("F j, Y", strtotime($order['created_at'])); ?></p>
                    <p class="status"><strong>Status:</strong> <?php echo $order['order_status']; ?></p>
                </article>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </section>
</main>
