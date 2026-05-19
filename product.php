<?php
require_once 'includes/config.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ?
");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . url('catalog.php'));
    exit;
}

$similar = $pdo->prepare("
    SELECT * FROM products 
    WHERE category_id = ? AND id != ? 
    LIMIT 4
");
$similar->execute([$product['category_id'], $id]);
$similar_products = $similar->fetchAll();

// Сообщения из сессии
$success = $_SESSION['cart_success'] ?? '';
$error = $_SESSION['cart_error'] ?? '';
unset($_SESSION['cart_success'], $_SESSION['cart_error']);

include 'includes/header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('index.php') ?>">Главная</a> / 
        <a href="<?= url('catalog.php') ?>">Каталог</a> / 
        <?= htmlspecialchars($product['name']) ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px;">
        <div>
            <div style="background: white; padding: 30px; border-radius: 8px; text-align: center;">
                <img src="<?= url('assets/uploads/' . $product['image']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     style="max-width: 100%; max-height: 400px;">
            </div>
        </div>

        <div>
            <h1 style="margin-bottom: 10px;"><?= htmlspecialchars($product['name']) ?></h1>
            <p style="color: #666; margin-bottom: 20px;">Артикул: <?= htmlspecialchars($product['article'] ?? 'N/A') ?></p>
            
            <?php if ($success): ?>
            <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div style="margin-bottom: 20px;">
                <span style="font-size: 32px; font-weight: bold;"><?= formatPrice($product['price']) ?></span>
                <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                <span style="font-size: 20px; color: #999; text-decoration: line-through; margin-left: 15px;">
                    <?= formatPrice($product['old_price']) ?>
                </span>
                <?php endif; ?>
            </div>

            <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border-radius: 8px;">
                <p style="font-size: 18px; color: <?= $product['in_stock'] && $product['stock'] > 0 ? 'green' : 'red' ?>;">
                    <?= ($product['in_stock'] && $product['stock'] > 0) ? '✓ В наличии' : '❌ Нет в наличии' ?>
                </p>
                <?php if ($product['in_stock'] && $product['stock'] > 0): ?>
                <p>Количество на складе: <?= $product['stock'] ?> шт.</p>
                <?php endif; ?>
            </div>

            <?php if ($product['in_stock'] && $product['stock'] > 0): ?>
            <div style="margin-bottom: 30px;">
                <form method="POST" action="<?= url('cart.php') ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    
                    <label style="display: block; margin-bottom: 10px; font-weight: bold;">Количество:</label>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 20px;">
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" 
                               style="width: 80px; padding: 12px; border: 1px solid #ddd; border-radius: 4px; text-align: center;">
                        <span style="color: #666;">Макс: <?= $product['stock'] ?> шт.</span>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="width: 100%; padding: 15px; font-size: 18px;">
                        Добавить в корзину
                    </button>
                </form>
            </div>
            <?php else: ?>
            <div style="margin-bottom: 30px;">
                <button class="btn-secondary" style="width: 100%; padding: 15px; font-size: 18px;" disabled>
                    Нет в наличии
                </button>
                <p style="margin-top: 10px; text-align: center;">
                    <a href="<?= url('repair.php') ?>">Оставить заявку на подбор аналога</a>
                </p>
            </div>
            <?php endif; ?>

            <div style="margin-bottom: 20px;">
                <h3>Описание</h3>
                <div style="line-height: 1.8;">
                    <?= nl2br(htmlspecialchars($product['full_description'] ?? $product['description'])) ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (count($similar_products) > 0): ?>
    <section style="margin-bottom: 40px;">
        <h3>Похожие товары</h3>
        <div class="products-grid">
            <?php foreach ($similar_products as $similar_product): ?>
            <div class="product-card">
                <img src="<?= url('assets/uploads/' . $similar_product['image']) ?>" 
                     alt="<?= htmlspecialchars($similar_product['name']) ?>">
                <div class="product-info">
                    <h3><?= htmlspecialchars($similar_product['name']) ?></h3>
                    <div class="product-price">
                        <span class="current-price"><?= formatPrice($similar_product['price']) ?></span>
                    </div>
                    <a href="<?= url('product.php?id=' . $similar_product['id']) ?>" class="btn-details">Подробнее</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>