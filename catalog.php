<?php
require_once 'includes/config.php';

$categories = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as has_children
    FROM categories c 
    WHERE parent_id IS NULL 
    ORDER BY sort_order
")->fetchAll();

$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$min_price = isset($_GET['min_price']) ? (float)$_GET['min_price'] : null;
$max_price = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$in_stock = isset($_GET['in_stock']);
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';

$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE 1=1";
$params = [];

if ($category_id) {
    $cat_ids = [$category_id];
    $subcats = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
    $subcats->execute([$category_id]);
    while ($sub = $subcats->fetch()) {
        $cat_ids[] = $sub['id'];
    }
    $placeholders = implode(',', array_fill(0, count($cat_ids), '?'));
    $sql .= " AND p.category_id IN ($placeholders)";
    $params = array_merge($params, $cat_ids);
}

if ($search) {
    $sql .= " AND (p.name LIKE ? OR p.article LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
}

if ($min_price !== null) {
    $sql .= " AND p.price >= ?";
    $params[] = $min_price;
}

if ($max_price !== null) {
    $sql .= " AND p.price <= ?";
    $params[] = $max_price;
}

if ($in_stock) {
    $sql .= " AND p.in_stock = 1 AND p.stock > 0";
}

switch ($sort) {
    case 'price_asc': $sql .= " ORDER BY p.price ASC"; break;
    case 'price_desc': $sql .= " ORDER BY p.price DESC"; break;
    case 'name_desc': $sql .= " ORDER BY p.name DESC"; break;
    case 'newest': $sql .= " ORDER BY p.created_at DESC"; break;
    default: $sql .= " ORDER BY p.name ASC";
}

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

$count_stmt = $pdo->prepare(str_replace('SELECT p.*, c.name as category_name', 'SELECT COUNT(*)', $sql));
$count_stmt->execute($params);
$total_products = $count_stmt->fetchColumn();
$total_pages = ceil($total_products / $per_page);

$sql .= " LIMIT $per_page OFFSET $offset";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

include 'includes/header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('index.php') ?>">Главная</a> / Каталог запчастей
    </div>

    <h2>Каталог запчастей</h2>
    
    <div class="filter-panel">
        <h3 class="filter-title">Фильтр товаров</h3>
        <form method="GET" action="<?= url('catalog.php') ?>" class="filter-form">
            <div class="filter-group">
                <label>Категория:</label>
                <select name="category">
                    <option value="">Все категории</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category_id == $cat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php if ($cat['has_children']): 
                        $subcats = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ? ORDER BY sort_order");
                        $subcats->execute([$cat['id']]);
                        while ($sub = $subcats->fetch()):
                    ?>
                    <option value="<?= $sub['id'] ?>" <?= $category_id == $sub['id'] ? 'selected' : '' ?>>
                        — <?= htmlspecialchars($sub['name']) ?>
                    </option>
                    <?php endwhile; endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Цена от:</label>
                <input type="number" name="min_price" placeholder="0" value="<?= $min_price ?>">
            </div>
            
            <div class="filter-group">
                <label>Цена до:</label>
                <input type="number" name="max_price" placeholder="100000" value="<?= $max_price ?>">
            </div>
            
            <div class="filter-group">
                <label>Сортировка:</label>
                <select name="sort">
                    <option value="name_asc" <?= $sort == 'name_asc' ? 'selected' : '' ?>>По названию (А-Я)</option>
                    <option value="name_desc" <?= $sort == 'name_desc' ? 'selected' : '' ?>>По названию (Я-А)</option>
                    <option value="price_asc" <?= $sort == 'price_asc' ? 'selected' : '' ?>>Сначала дешевле</option>
                    <option value="price_desc" <?= $sort == 'price_desc' ? 'selected' : '' ?>>Сначала дороже</option>
                    <option value="newest" <?= $sort == 'newest' ? 'selected' : '' ?>>Новинки</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Поиск:</label>
                <input type="text" name="search" placeholder="Название или артикул" value="<?= htmlspecialchars($search) ?>">
            </div>
            
            <div class="filter-group">
                <label>
                    <input type="checkbox" name="in_stock" value="1" <?= $in_stock ? 'checked' : '' ?>>
                    Только в наличии
                </label>
            </div>
            
            <div class="filter-actions">
                <button type="submit">Применить</button>
                <a href="<?= url('catalog.php') ?>" class="btn-reset">Сбросить</a>
            </div>
        </form>
    </div>

    <p>Найдено товаров: <?= $total_products ?></p>
    
    <div class="products-grid">
        <?php foreach ($products as $product): ?>
        <div class="product-card">
            <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
            <div class="product-badge">Скидка</div>
            <?php endif; ?>
            
            <img src="<?= url('assets/uploads/' . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
            
            <div class="product-info">
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <div class="product-article">Арт. <?= htmlspecialchars($product['article'] ?? 'N/A') ?></div>
                
                <div class="product-price">
                    <span class="current-price"><?= formatPrice($product['price']) ?></span>
                    <?php if ($product['old_price'] && $product['old_price'] > $product['price']): ?>
                    <span class="old-price"><?= formatPrice($product['old_price']) ?></span>
                    <?php endif; ?>
                </div>
                
                <div style="font-size: 14px; color: <?= $product['in_stock'] ? 'green' : 'red' ?>; margin-bottom: 10px;">
                    <?= $product['in_stock'] ? 'В наличии' : 'Под заказ' ?>
                </div>
                
                <div class="product-actions">
                    <a href="<?= url('product.php?id=' . $product['id']) ?>" class="btn-details">Подробнее</a>
                    <?php if ($product['in_stock']): ?>
                    <form method="POST" action="<?= url('cart.php') ?>" style="flex:1;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                        <input type="hidden" name="quantity" value="1">
                        <button type="submit" class="btn-buy" style="width:100%;">В корзину</button>
                    </form>
                    <?php else: ?>
                    <a href="<?= url('repair.php') ?>" class="btn-secondary">Под заказ</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">← Назад</a>
        <?php endif; ?>
        
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
            <span class="active"><?= $i ?></span>
            <?php else: ?>
            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        
        <?php if ($page < $total_pages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">Вперед →</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>