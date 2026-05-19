<?php
require_once 'includes/config.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
}

include 'includes/header.php';
?>

<main>
    <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px;">
        <h2 style="color: var(--success); margin-bottom: 20px;">✓ Заказ успешно оформлен!</h2>
        
        <?php if (isset($order)): ?>
        <p style="font-size: 18px; margin-bottom: 30px;">
            Номер вашего заказа: <strong><?= $order['order_number'] ?></strong>
        </p>
        <p>Мы свяжемся с вами в ближайшее время для подтверждения заказа.</p>
        <?php endif; ?>
        
        <div style="margin-top: 40px;">
            <a href="catalog.php" class="btn-primary" style="display: inline-block; margin-right: 15px;">Продолжить покупки</a>
            <?php if (isLoggedIn()): ?>
            <a href="profile.php" class="btn-secondary" style="display: inline-block;">Мои заказы</a>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>