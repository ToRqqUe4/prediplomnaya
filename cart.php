<?php
require_once 'includes/config.php';

// Обработка действий
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $product_id = (int)($_POST['product_id'] ?? 0);
    
    if ($action === 'add') {
        $quantity = (int)($_POST['quantity'] ?? 1);
        
        // Проверяем наличие товара на складе
        $stmt = $pdo->prepare("SELECT id, name, stock, in_stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if (!$product || !$product['in_stock']) {
            $_SESSION['cart_error'] = 'Товар недоступен для заказа';
            header('Location: ' . url('catalog.php'));
            exit;
        }
        
        // Проверяем текущее количество в корзине
        $current_cart_qty = 0;
        if (isLoggedIn()) {
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
            $cart_item = $stmt->fetch();
            if ($cart_item) {
                $current_cart_qty = $cart_item['quantity'];
            }
        } else {
            $session_id = session_id();
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE session_id = ? AND product_id = ?");
            $stmt->execute([$session_id, $product_id]);
            $cart_item = $stmt->fetch();
            if ($cart_item) {
                $current_cart_qty = $cart_item['quantity'];
            }
        }
        
        $total_qty = $current_cart_qty + $quantity;
        
        if ($total_qty > $product['stock']) {
            $_SESSION['cart_error'] = 'Недостаточно товара на складе. Доступно: ' . $product['stock'] . ' шт.';
            header('Location: ' . url('product.php?id=' . $product_id));
            exit;
        }
        
        if (isLoggedIn()) {
            if ($cart_item) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$total_qty, $_SESSION['user_id'], $product_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $product_id, $quantity]);
            }
        } else {
            if ($cart_item) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?");
                $stmt->execute([$total_qty, $session_id, $product_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO cart (session_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$session_id, $product_id, $quantity]);
            }
        }
        
        $_SESSION['cart_success'] = 'Товар добавлен в корзину';
        header('Location: ' . url('cart.php'));
        exit;
    }
    elseif ($action === 'update') {
        $quantity = max(1, (int)$_POST['quantity']);
        
        // Проверяем наличие товара на складе
        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch();
        
        if ($product && $quantity > $product['stock']) {
            $_SESSION['cart_error'] = 'Недостаточно товара на складе. Максимальное количество: ' . $product['stock'] . ' шт.';
        } else {
            if (isLoggedIn()) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $_SESSION['user_id'], $product_id]);
            } else {
                $session_id = session_id();
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE session_id = ? AND product_id = ?");
                $stmt->execute([$quantity, $session_id, $product_id]);
            }
            $_SESSION['cart_success'] = 'Корзина обновлена';
        }
        
        header('Location: ' . url('cart.php'));
        exit;
    }
    elseif ($action === 'remove') {
        if (isLoggedIn()) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$_SESSION['user_id'], $product_id]);
        } else {
            $session_id = session_id();
            $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ? AND product_id = ?");
            $stmt->execute([$session_id, $product_id]);
        }
        
        $_SESSION['cart_success'] = 'Товар удален из корзины';
        header('Location: ' . url('cart.php'));
        exit;
    }
    elseif ($action === 'clear') {
        if (isLoggedIn()) {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
        } else {
            $session_id = session_id();
            $stmt = $pdo->prepare("DELETE FROM cart WHERE session_id = ?");
            $stmt->execute([$session_id]);
        }
        
        header('Location: ' . url('cart.php'));
        exit;
    }
}

$cart_items = getCartItems();
$cart_total = getCartTotal();

// Получаем сообщения из сессии
$cart_success = $_SESSION['cart_success'] ?? '';
$cart_error = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_success'], $_SESSION['cart_error']);

include 'includes/header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('index.php') ?>">Главная</a> / Корзина
    </div>

    <h2>Корзина</h2>
    
    <?php if ($cart_success): ?>
    <div class="alert alert-success"><?= htmlspecialchars($cart_success) ?></div>
    <?php endif; ?>
    
    <?php if ($cart_error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($cart_error) ?></div>
    <?php endif; ?>

    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 60px 20px; background: white; border-radius: 8px;">
            <p style="font-size: 18px; margin-bottom: 20px;">Ваша корзина пуста</p>
            <a href="<?= url('catalog.php') ?>" class="btn-primary" style="display: inline-block;">Перейти в каталог</a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Товар</th>
                        <th>Цена</th>
                        <th>Количество</th>
                        <th>Сумма</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart_items as $item): 
                        // Получаем актуальную информацию о товаре
                        $stmt = $pdo->prepare("SELECT stock FROM products WHERE id = ?");
                        $stmt->execute([$item['product_id']]);
                        $product = $stmt->fetch();
                        $max_stock = $product ? $product['stock'] : 0;
                    ?>
                    <tr>
                        <td>
                            <div class="cart-product">
                                <img src="<?= url('assets/uploads/' . $item['image']) ?>" 
                                     alt="<?= htmlspecialchars($item['name']) ?>">
                                <div>
                                    <a href="<?= url('product.php?id=' . $item['product_id']) ?>" style="color: black; text-decoration: none;">
                                        <strong><?= htmlspecialchars($item['name']) ?></strong>
                                    </a>
                                    <p style="color: #666; font-size: 14px; margin-top: 5px;">
                                        Артикул: <?= htmlspecialchars($item['article'] ?? 'N/A') ?>
                                    </p>
                                    <p style="color: #666; font-size: 12px;">
                                        В наличии: <?= $max_stock ?> шт.
                                    </p>
                                </div>
                            </div>
                        </td>
                        <td><?= formatPrice($item['price']) ?></td>
                        <td>
                            <form method="POST" action="<?= url('cart.php') ?>" class="cart-quantity-form">
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <input type="number" name="quantity" value="<?= $item['quantity'] ?>" min="1" max="<?= $max_stock ?>">
                                <button type="submit">Обновить</button>
                            </form>
                        </td>
                        <td><strong><?= formatPrice($item['price'] * $item['quantity']) ?></strong></td>
                        <td>
                            <form method="POST" action="<?= url('cart.php') ?>">
                                <input type="hidden" name="action" value="remove">
                                <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                <button type="submit" style="background: none; border: none; color: red; cursor: pointer; font-size: 20px;" title="Удалить">
                                    ✕
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="cart-summary">
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <form method="POST" action="<?= url('cart.php') ?>" style="display: inline;">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn-secondary" style="padding: 10px 20px;">Очистить корзину</button>
                    </form>
                </div>
                <div>
                    <p style="font-size: 20px; margin-bottom: 10px;">
                        Итого: <strong><?= formatPrice($cart_total) ?></strong>
                    </p>
                    <a href="<?= url('checkout.php') ?>" class="btn-primary" style="padding: 15px 40px; font-size: 18px; display: inline-block;">
                        Оформить заказ
                    </a>
                </div>
            </div>
        </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>