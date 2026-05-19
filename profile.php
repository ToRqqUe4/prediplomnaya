<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$orders = $pdo->prepare("
    SELECT * FROM orders 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$orders->execute([$user_id]);

include 'includes/header.php';
?>

<main>
    <h2>Личный кабинет</h2>
    
    <div style="display: grid; grid-template-columns: 250px 1fr; gap: 30px;">
        <div>
            <div style="background: white; padding: 20px; border-radius: 8px;">
                <h4>Меню</h4>
                <ul style="list-style: none; padding: 0;">
                    <li style="margin-bottom: 10px;"><a href="profile.php" style="color: black; text-decoration: none; font-weight: bold;">📋 Мои заказы</a></li>
                    <li style="margin-bottom: 10px;"><a href="edit_profile.php" style="color: black; text-decoration: none;">👤 Профиль</a></li>
                    <li><a href="logout.php" style="color: red; text-decoration: none;">🚪 Выход</a></li>
                </ul>
            </div>
        </div>
        
        <div>
            <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
                <h3>Добро пожаловать, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Пользователь') ?>!</h3>
            </div>
            
            <h3>История заказов</h3>
            
            <?php if ($orders->rowCount() == 0): ?>
                <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
                    <p>У вас пока нет заказов</p>
                    <a href="catalog.php" class="btn-primary" style="display: inline-block; margin-top: 15px;">Перейти в каталог</a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>№ заказа</th>
                                <th>Дата</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($order = $orders->fetch()): ?>
                            <tr>
                                <td><?= $order['order_number'] ?></td>
                                <td><?= date('d.m.Y H:i', strtotime($order['created_at'])) ?></td>
                                <td><?= formatPrice($order['total']) ?></td>
                                <td>
                                    <span style="padding: 5px 10px; border-radius: 4px; background: <?= 
                                        $order['status'] == 'completed' ? '#d4edda' : 
                                        ($order['status'] == 'processing' ? '#fff3cd' : '#cce5ff') ?>">
                                        <?= 
                                            $order['status'] == 'new' ? 'Новый' : 
                                            ($order['status'] == 'processing' ? 'В обработке' : 
                                            ($order['status'] == 'completed' ? 'Выполнен' : 'Отменен')) 
                                        ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="order_details.php?id=<?= $order['id'] ?>">Подробнее</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>