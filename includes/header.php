<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/config.php';
}

$current_page = basename($_SERVER['PHP_SELF']);
$cart_count = getCartCount();
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ДорСтройТехх - запчасти и ремонт строительной техники</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <a href="<?= url('index.php') ?>">
                <h1>ДорСтройТехх</h1>
                <span>Запчасти и ремонт спецтехники</span>
            </a>
        </div>
        
        <nav>
            <ul>
                <li><a href="<?= url('index.php') ?>" <?= $current_page == 'index.php' ? 'class="active"' : '' ?>>Главная</a></li>
                <li><a href="<?= url('catalog.php') ?>" <?= $current_page == 'catalog.php' ? 'class="active"' : '' ?>>Каталог</a></li>
                <li><a href="<?= url('repair.php') ?>" <?= $current_page == 'repair.php' ? 'class="active"' : '' ?>>Ремонт</a></li>
                <?php if (isAdmin()): ?>
                <li><a href="<?= url('admin/index.php') ?>">Админка</a></li>
                <?php endif; ?>
            </ul>
        </nav>
        
        <div class="user-menu">
            <a href="<?= url('cart.php') ?>" class="cart-link">
                🛒
                <?php if ($cart_count > 0): ?>
                <span class="cart-count"><?= $cart_count ?></span>
                <?php endif; ?>
            </a>
            
            <?php if (isLoggedIn()): ?>
                <a href="<?= url('profile.php') ?>" class="btn-profile">
                    <?= htmlspecialchars($current_user['full_name'] ?? $current_user['login']) ?>
                </a>
                <a href="<?= url('logout.php') ?>" class="btn-login">Выход</a>
            <?php else: ?>
                <a href="<?= url('login.php') ?>" class="btn-login">Вход</a>
                <a href="<?= url('register.php') ?>" class="btn-register">Регистрация</a>
            <?php endif; ?>
        </div>
    </div>
</header>