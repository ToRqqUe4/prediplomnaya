<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT o.*, 
           (SELECT SUM(quantity) FROM order_items WHERE order_id = o.id) as items_count
    FROM orders o 
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->execute([$order_id, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: profile.php');
    exit;
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order_id]);

include 'includes/header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="/">Главная</a> / <a href="profile.php">Личный кабинет</a> / Заказ №<?= $order['order_number'] ?>
    </div>

    <h2>Заказ №<?= $order['order_number'] ?></h2>
    
    <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
            <div>
                <h4>Информация о заказе</h4>
                <p><strong>Дата:</strong> <?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></p>
                <p><strong>Статус:</strong> 
                    <span style="padding: 5px 10px; border-radius: 4px; background: <?= 
                        $order['status'] == 'completed' ? '#d4edda' : 
                        ($order['status'] == 'processing' ? '#fff3cd' : '#cce5ff') ?>">
                        <?= 
                            $order['status'] == 'new' ? 'Новый' : 
                            ($order['status'] == 'processing' ? 'В обработке' : 
                            ($order['status'] == 'completed' ? 'Выполнен' : 'Отменен')) 
                        ?>
                    </span>
                </p>
                <p><strong>Способ оплаты:</strong> 
                    <?= 
                        $order['payment_method'] == 'card' ? 'Банковской картой' : 
                        ($order['payment_method'] == 'cash' ? 'Наличными' : 'Безналичный расчет') 
                    ?>
                </p>
            </div>
            <div>
                <h4>Контактная информация</h4>
                <p><strong>Телефон:</strong> <?= htmlspecialchars($order['contact_phone']) ?></p>
                <p><strong>Email:</strong> <?= htmlspecialchars($order['contact_email']) ?></p>
                <p><strong>Адрес доставки:</strong><br><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
            </div>
        </div>
        
        <?php if ($order['comment']): ?>
        <div style="margin-top: 20px;">
            <h4>Комментарий к заказу</h4>
            <p><?= nl2br(htmlspecialchars($order['comment'])) ?></p>
        </div>
        <?php endif; ?>
    </div>

    <h3>Состав заказа</h3>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Артикул</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($item = $items->fetch()): ?>
                <tr>
                    <td>
                        <a href="product.php?id=<?= $item['product_id'] ?>">
                            <?= htmlspecialchars($item['product_name']) ?>
                        </a>
                    </td>
                    <td><?= htmlspecialchars($item['article'] ?? '-') ?></td>
                    <td><?= formatPrice($item['price']) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" style="text-align: right;"><strong>Итого:</strong></td>
                    <td><strong><?= formatPrice($order['total']) ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    
    <div style="margin-top: 30px;">
        <a href="profile.php" class="btn-secondary">← Назад к списку заказов</a>
    </div>
</main>

<?php include 'includes/footer.php'; ?>