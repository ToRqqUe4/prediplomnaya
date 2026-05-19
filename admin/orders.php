<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$message = '';
$error = '';

$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$status, $order_id]);
        $message = 'Статус заказа обновлен';
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

$sql = "
    SELECT o.*, u.full_name, u.email, u.phone,
           (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as items_count
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($status_filter) {
    $sql .= " AND o.status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $sql .= " AND (o.order_number LIKE ? OR u.full_name LIKE ? OR u.email LIKE ? OR o.contact_phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY o.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'new' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'new'")->fetchColumn(),
    'processing' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'processing'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'completed'")->fetchColumn(),
    'total_sum' => $pdo->query("SELECT SUM(total) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
];

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / Заказы
    </div>

    <h2>Управление заказами</h2>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: repeat(5, 1fr); gap: 15px; margin-bottom: 30px;">
        <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>Всего заказов</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['total'] ?></p>
        </div>
        <div style="background: #cce5ff; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>Новые</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['new'] ?></p>
        </div>
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>В обработке</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['processing'] ?></p>
        </div>
        <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>Выполнено</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['completed'] ?></p>
        </div>
        <div style="background: var(--black); color: white; padding: 15px; border-radius: 8px; text-align: center;">
            <h4 style="color: var(--yellow);">Общая сумма</h4>
            <p style="font-size: 20px; font-weight: bold;"><?= formatPrice($stats['total_sum'] ?? 0) ?></p>
        </div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <label>Статус:</label>
                <select name="status" style="width: 100%; padding: 10px;">
                    <option value="">Все заказы</option>
                    <option value="new" <?= $status_filter == 'new' ? 'selected' : '' ?>>Новые</option>
                    <option value="processing" <?= $status_filter == 'processing' ? 'selected' : '' ?>>В обработке</option>
                    <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Выполненные</option>
                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Отмененные</option>
                </select>
            </div>
            <div style="flex: 2;">
                <label>Поиск:</label>
                <input type="text" name="search" placeholder="Номер заказа, имя, email или телефон" 
                       value="<?= htmlspecialchars($search) ?>" style="width: 100%; padding: 10px;">
            </div>
            <div>
                <button type="submit" class="btn-primary">Применить</button>
                <a href="<?= url('admin/orders.php') ?>" class="btn-secondary">Сбросить</a>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Номер заказа</th>
                    <th>Клиент</th>
                    <th>Контакты</th>
                    <th>Товаров</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                <tr>
                    <td><?= $order['id'] ?></td>
                    <td><strong><?= $order['order_number'] ?></strong></td>
                    <td><?= htmlspecialchars($order['full_name'] ?? 'Гость') ?></td>
                    <td>
                        <?= htmlspecialchars($order['contact_phone']) ?><br>
                        <small><?= htmlspecialchars($order['contact_email']) ?></small>
                    </td>
                    <td><?= $order['items_count'] ?></td>
                    <td><strong><?= formatPrice($order['total']) ?></strong></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                            <select name="status" onchange="this.form.submit()" 
                                    style="padding: 5px; border-radius: 4px; background: <?= 
                                        $order['status'] == 'completed' ? '#d4edda' : 
                                        ($order['status'] == 'processing' ? '#fff3cd' : 
                                        ($order['status'] == 'cancelled' ? '#f8d7da' : '#cce5ff')) ?>">
                                <option value="new" <?= $order['status'] == 'new' ? 'selected' : '' ?>>Новый</option>
                                <option value="processing" <?= $order['status'] == 'processing' ? 'selected' : '' ?>>В обработке</option>
                                <option value="completed" <?= $order['status'] == 'completed' ? 'selected' : '' ?>>Выполнен</option>
                                <option value="cancelled" <?= $order['status'] == 'cancelled' ? 'selected' : '' ?>>Отменен</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <a href="<?= url('/6666/admin/order_detail.php?id=' . $order['id']) ?>">Просмотр</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($orders)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 30px;">Заказы не найдены</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>