<?php
require_once 'includes/config.php';

$cart_items = getCartItems();
$cart_total = getCartTotal();

if (empty($cart_items)) {
    header('Location: ' . url('cart.php'));
    exit;
}

$current_user = getCurrentUser();
$error = '';

// Проверяем наличие всех товаров перед оформлением
foreach ($cart_items as $item) {
    $stmt = $pdo->prepare("SELECT id, name, stock, in_stock FROM products WHERE id = ?");
    $stmt->execute([$item['product_id']]);
    $product = $stmt->fetch();
    
    if (!$product || !$product['in_stock']) {
        $error = 'Товар "' . $item['name'] . '" больше недоступен для заказа';
        break;
    }
    
    if ($item['quantity'] > $product['stock']) {
        $error = 'Товара "' . $item['name'] . '" недостаточно на складе. Доступно: ' . $product['stock'] . ' шт.';
        break;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $order_number = generateOrderNumber();
    $payment_method = $_POST['payment_method'] ?? 'card';
    $delivery_address = $_POST['address'] ?? '';
    $contact_phone = $_POST['phone'] ?? '';
    $contact_email = $_POST['email'] ?? '';
    $comment = $_POST['comment'] ?? '';
    
    if (empty($delivery_address) || empty($contact_phone) || empty($contact_email)) {
        $error = 'Все поля обязательны для заполнения';
    } else {
        // Повторная проверка наличия перед созданием заказа
        foreach ($cart_items as $item) {
            $stmt = $pdo->prepare("SELECT stock, in_stock FROM products WHERE id = ? FOR UPDATE");
            $stmt->execute([$item['product_id']]);
            $product = $stmt->fetch();
            
            if (!$product || !$product['in_stock'] || $item['quantity'] > $product['stock']) {
                $error = 'Некоторые товары стали недоступны. Пожалуйста, обновите корзину.';
                break;
            }
        }
        
        if (!$error) {
            $pdo->beginTransaction();
            
            try {
                // Создаем заказ
                $stmt = $pdo->prepare("
                    INSERT INTO orders (user_id, order_number, total, payment_method, delivery_address, contact_phone, contact_email, comment) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $_SESSION['user_id'] ?? null,
                    $order_number,
                    $cart_total,
                    $payment_method,
                    $delivery_address,
                    $contact_phone,
                    $contact_email,
                    $comment
                ]);
                
                $order_id = $pdo->lastInsertId();
                
                // Добавляем товары в заказ и уменьшаем остатки
                foreach ($cart_items as $item) {
                    // Добавляем в order_items
                    $stmt = $pdo->prepare("
                        INSERT INTO order_items (order_id, product_id, product_name, article, quantity, price) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $order_id,
                        $item['product_id'],
                        $item['name'],
                        $item['article'],
                        $item['quantity'],
                        $item['price']
                    ]);
                    
                    // Уменьшаем количество на складе
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET stock = stock - ?, 
                            in_stock = CASE WHEN (stock - ?) <= 0 THEN 0 ELSE 1 END 
                        WHERE id = ?
                    ");
                    $stmt->execute([$item['quantity'], $item['quantity'], $item['product_id']]);
                }
                
                // Очищаем корзину
                if (isLoggedIn()) {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ?");
                    $stmt->execute([session_id()]);
                }
                
                $pdo->commit();
                
                header('Location: ' . url('order_success.php?order_id=' . $order_id));
                exit;
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Ошибка при создании заказа: " . $e->getMessage();
            }
        }
    }
}

include 'includes/header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('index.php') ?>">Главная</a> / <a href="<?= url('cart.php') ?>">Корзина</a> / Оформление заказа
    </div>

    <h2>Оформление заказа</h2>

    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
        <div>
            <form method="POST" action="<?= url('checkout.php') ?>" class="form-container" style="max-width: 100%;">
                <h3>Контактная информация</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>ФИО</label>
                        <input type="text" name="full_name" value="<?= htmlspecialchars($current_user['full_name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label>Телефон *</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Email *</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>" required>
                </div>
                
                <h3>Доставка</h3>
                
                <div class="form-group">
                    <label>Адрес доставки *</label>
                    <textarea name="address" rows="3" required><?= htmlspecialchars($current_user['address'] ?? '') ?></textarea>
                </div>
                
                <h3>Способ оплаты</h3>
                
                <div class="form-group">
                    <label>
                        <input type="radio" name="payment_method" value="card" checked> Банковской картой онлайн
                    </label><br>
                    <label>
                        <input type="radio" name="payment_method" value="cash"> Наличными при получении
                    </label><br>
                    <label>
                        <input type="radio" name="payment_method" value="invoice"> Безналичный расчет
                    </label>
                </div>
                
                <div class="form-group">
                    <label>Комментарий к заказу</label>
                    <textarea name="comment" rows="3"></textarea>
                </div>
                
                <?php if (!$error): ?>
                <button type="submit" class="btn-submit">Подтвердить заказ</button>
                <?php else: ?>
                <a href="<?= url('cart.php') ?>" class="btn-primary" style="display: inline-block;">Вернуться в корзину</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div>
            <div style="background: white; padding: 25px; border-radius: 8px;">
                <h3>Ваш заказ</h3>
                
                <div style="margin-bottom: 20px;">
                    <?php foreach ($cart_items as $item): 
                        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                        $stmt->execute([$item['product_id']]);
                        $product = $stmt->fetch();
                        $stock = $product ? $product['stock'] : 0;
                    ?>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>
                            <?= htmlspecialchars($item['name']) ?> × <?= $item['quantity'] ?>
                            <?php if ($item['quantity'] > $stock): ?>
                            <br><small style="color: red;">⚠️ Недостаточно на складе!</small>
                            <?php endif; ?>
                        </span>
                        <span><?= formatPrice($item['price'] * $item['quantity']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <hr style="margin: 20px 0;">
                
                <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: bold;">
                    <span>Итого:</span>
                    <span><?= formatPrice($cart_total) ?></span>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>