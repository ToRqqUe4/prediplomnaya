<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$stats = [
    'products' => $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'requests' => $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'new'")->fetchColumn(),
];

$recent_orders = $pdo->query("
    SELECT o.*, u.full_name 
    FROM orders o 
    LEFT JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('index.php') ?>">Главная</a> / Админ-панель
    </div>

    <h2>Панель администратора</h2>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin: 30px 0;">
        <div style="background: var(--black); color: var(--white); padding: 25px; border-radius: 8px;">
            <h3 style="color: var(--yellow);">Товары</h3>
            <p style="font-size: 32px; margin: 0;"><?= $stats['products'] ?></p>
        </div>
        <div style="background: var(--yellow); color: var(--black); padding: 25px; border-radius: 8px;">
            <h3>Заказы</h3>
            <p style="font-size: 32px; margin: 0;"><?= $stats['orders'] ?></p>
        </div>
        <div style="background: var(--black); color: var(--white); padding: 25px; border-radius: 8px;">
            <h3 style="color: var(--yellow);">Пользователи</h3>
            <p style="font-size: 32px; margin: 0;"><?= $stats['users'] ?></p>
        </div>
        <div style="background: var(--yellow); color: var(--black); padding: 25px; border-radius: 8px;">
            <h3>Заявки на ремонт</h3>
            <p style="font-size: 32px; margin: 0;"><?= $stats['requests'] ?></p>
        </div>
    </div>
    
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin: 30px 0;">
        <a href="<?= url('/products.php') ?>" style="text-decoration: none;">
            <div style="background: white; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3>📦 Управление товарами</h3>
                <p>Добавление, редактирование, цены, фото</p>
            </div>
        </a>
        <a href="<?= url('/categories.php') ?>" style="text-decoration: none;">
            <div style="background: white; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3>📂 Категории</h3>
                <p>Управление разделами каталога</p>
            </div>
        </a>
        <a href="<?= url('orders.php') ?>" style="text-decoration: none;">
            <div style="background: white; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3>🛒 Заказы</h3>
                <p>Просмотр и управление заказами</p>
            </div>
        </a>
        <a href="<?= url('requests.php') ?>" style="text-decoration: none;">
            <div style="background: white; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3>🔧 Заявки на ремонт</h3>
                <p>Просмотр и обработка заявок</p>
            </div>
        </a>
        <a href="<?= url('/news.php') ?>" style="text-decoration: none;">
            <div style="background: white; padding: 30px; text-align: center; border-radius: 8px; box-shadow: 0 2px 5px rgba(0,0,0,0.05);">
                <h3>📰 Новости</h3>
                <p>Управление новостями компании</p>
            </div>
        </a>
    </div>
    
    <h3>Последние заказы</h3>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>№ заказа</th>
                    <th>Клиент</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td><?= $order['order_number'] ?></td>
                    <td><?= htmlspecialchars($order['full_name'] ?? 'Гость') ?></td>
                    <td><?= formatPrice($order['total']) ?></td>
                    <td>
                        <span style="padding: 5px 10px; border-radius: 4px; background: <?= 
                            $order['status'] == 'completed' ? '#d4edda' : 
                            ($order['status'] == 'processing' ? '#fff3cd' : '#cce5ff') ?>">
                            <?= $order['status'] ?>
                        </span>
                    </td>
                    <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                    <td>
                        <a href="<?= url('/order_detail.php?id=' . $order['id']) ?>">Просмотр</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>