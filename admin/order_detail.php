<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

$stmt = $pdo->prepare("
    SELECT o.*, u.full_name, u.email as user_email, u.phone as user_phone
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->execute([$order_id]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: ' . url('admin/orders.php'));
    exit;
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order_id]);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        $message = 'Статус заказа обновлен';
        
        $stmt = $pdo->prepare("
            SELECT o.*, u.full_name, u.email as user_email, u.phone as user_phone
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / 
        <a href="<?= url('admin/orders.php') ?>">Заказы</a> / 
        Заказ №<?= $order['order_number'] ?>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Заказ №<?= $order['order_number'] ?></h2>
            <li><a href=" /6666/admin/orders.php " class="btn-secondary">← Назад к списку</a></li>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <div style="background: white; padding: 25px; border-radius: 8px;">
            <h3>Информация о заказе</h3>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0;"><strong>Номер заказа:</strong></td>
                    <td><?= $order['order_number'] ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Дата создания:</strong></td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Статус:</strong></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="update_status" value="1">
                            <select name="status" onchange="this.form.submit()" 
                                    style="padding: 8px; border-radius: 4px; background: <?= 
                                        $order['status'] == 'completed' ? '#d4edda' : 
                                        ($order['status'] == 'processing' ? '#fff3cd' : 
                                        ($order['status'] == 'cancelled' ? '#f8d7da' : '#cce5ff')) ?>">
                                <option value="new" <?= $order['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Выполнен</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                            </select>
                        </form>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Способ оплаты:</strong></td>
                    <td>
                        <?= 
                            $order['payment_method'] == 'card' ? 'Банковской картой онлайн' : 
                            ($order['payment_method'] == 'cash' ? 'Наличными при получении' : 'Безналичный расчет') 
                        ?>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Сумма заказа:</strong></td>
                    <td><strong style="font-size: 20px;"><?= formatPrice($order['total']) ?></strong></td>
                </tr>
            </table>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 8px;">
            <h3>Информация о клиенте</h3>
            
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0;"><strong>Имя:</strong></td>
                    <td><?= htmlspecialchars($order['full_name'] ?? 'Не указано') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Телефон:</strong></td>
                    <td><?= htmlspecialchars($order['contact_phone']) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Email:</strong></td>
                    <td><?= htmlspecialchars($order['contact_email']) ?></td>
                </tr>
            </table>
        </div>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <h3>Адрес доставки</h3>
        <p><?= nl2br(htmlspecialchars($order['delivery_address'])) ?></p>
        
        <?php if ($order['comment']): ?>
        <h3 style="margin-top: 20px;">Комментарий к заказу</h3>
        <p><?= nl2br(htmlspecialchars($order['comment'])) ?></p>
        <?php endif; ?>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px;">
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
                    <?php 
                    $total_items = 0;
                    while ($item = $items->fetch()): 
                        $total_items += $item['quantity'];
                    ?>
                    <tr>
                        <td>
                            <?php if ($item['product_id']): ?>
                            <a href="<?= url('product.php?id=' . $item['product_id']) ?>" target="_blank">
                                <?= htmlspecialchars($item['product_name']) ?>
                            </a>
                            <?php else: ?>
                            <?= htmlspecialchars($item['product_name']) ?>
                            <?php endif; ?>
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
                        <td colspan="3"></td>
                        <td><strong>Всего товаров: <?= $total_items ?></strong></td>
                        <td><strong><?= formatPrice($order['total']) ?></strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</main>

<?php include '../includes/footer.php'; ?>