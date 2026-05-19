<?php
if (!isset($pdo)) {
    require_once __DIR__ . '/../includes/config.php';
}

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$current_page = basename($_SERVER['PHP_SELF']);
$current_user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ДорСтройТехх - Админ-панель</title>
	<!-- ПОМЕНЯТЬ КОРНЕВУЮ ПАПКУ ОБЯЗАТЕЛЬНО А ТО ПО ДРУГОМУ НЕ ЗАРОБИТ -->
    <link rel="stylesheet" href="/6666/assets/css/style.css">
	<!-- ПОМЕНЯТЬ КОРНЕВУЮ ПАПКУ ОБЯЗАТЕЛЬНО А ТО ПО ДРУГОМУ НЕ ЗАРОБИТ -->
	
</head>
<body>
<header>
    <div class="header-container">
        <div class="logo">
            <a href="<?= url('index.php') ?>">
                <h1>ДорСтройТехх</h1>
                <span>Админ-панель</span>
            </a>
        </div>
        
        <nav>
            <ul>
                <li><a href="<?= url('/index.php') ?>" <?= $current_page == 'index.php' ? 'class="active"' : '' ?>>Главная</a></li>
                <li><a href="<?= url('/products.php') ?>" <?= $current_page == 'products.php' ? 'class="active"' : '' ?>>Товары</a></li>
                <li><a href="<?= url('/categories.php') ?>" <?= $current_page == 'categories.php' ? 'class="active"' : '' ?>>Категории</a></li>
                <li><a href="<?= url('/orders.php') ?>" <?= $current_page == 'orders.php' ? 'class="active"' : '' ?>>Заказы</a></li>
                <li><a href="<?= url('/requests.php') ?>" <?= $current_page == 'requests.php' ? 'class="active"' : '' ?>>Заявки</a></li>
                <li><a href="<?= url('/news.php') ?>" <?= $current_page == 'news.php' ? 'class="active"' : '' ?>>Новости</a></li>
                <li><a href="/6666/index.php">← На сайт</a></li><!-- ПОМЕНЯТЬ КОРНЕВУЮ ПАПКУ ОБЯЗАТЕЛЬНО А ТО ПО ДРУГОМУ НЕ ЗАРОБИТ -->
            </ul>
        </nav>
        
        <div class="user-menu">
            <span class="btn-profile"><?= htmlspecialchars($current_user['full_name'] ?? $current_user['login']) ?></span>
            <a href="/6666/logout.php" " class="btn-login">Выход</a><!-- ПОМЕНЯТЬ КОРНЕВУЮ ПАПКУ ОБЯЗАТЕЛЬНО А ТО ПО ДРУГОМУ НЕ ЗАРОБИТ -->
        </div>
    </div>
</header>
