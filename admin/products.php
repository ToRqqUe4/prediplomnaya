<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$message = '';
$error = '';

// Добавление товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $category_id = (int)$_POST['category_id'];
    $article = $_POST['article'];
    $description = $_POST['description'];
    $price = (float)$_POST['price'];
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $stock = (int)$_POST['stock'];
    $in_stock = isset($_POST['in_stock']) ? 1 : 0;
    
    $image = 'no-image.jpg';
    if (!empty($_FILES['image']['name'])) {
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/uploads/' . $image);
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO products (category_id, article, name, description, price, old_price, stock, in_stock, image) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$category_id, $article, $name, $description, $price, $old_price, $stock, $in_stock, $image]);
        $message = 'Товар успешно добавлен';
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

// Удаление товара
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ' . url('admin/products.php?msg=deleted'));
    exit;
}

// Получение товаров
$products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.id DESC
")->fetchAll();

$categories = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as has_children
    FROM categories c 
    WHERE parent_id IS NULL 
    ORDER BY sort_order
")->fetchAll();

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / Товары
    </div>

    <h2>Управление товарами</h2>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div class="alert alert-success">Товар успешно удален</div>
    <?php endif; ?>
    
    <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <h3>Добавить новый товар</h3>
        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>Название *</label>
                <input type="text" name="name" required>
            </div>
            
            <div class="form-group">
                <label>Категория *</label>
                <select name="category_id" required>
                    <option value="">Выберите категорию</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php if ($cat['has_children']): 
                        $subcats = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ?");
                        $subcats->execute([$cat['id']]);
                        while ($sub = $subcats->fetch()):
                    ?>
                    <option value="<?= $sub['id'] ?>">— <?= htmlspecialchars($sub['name']) ?></option>
                    <?php endwhile; endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Артикул</label>
                <input type="text" name="article">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Цена *</label>
                    <input type="number" name="price" step="0.01" required>
                </div>
                <div class="form-group">
                    <label>Старая цена</label>
                    <input type="number" name="old_price" step="0.01">
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Количество на складе</label>
                    <input type="number" name="stock" value="0">
                </div>
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="in_stock" checked> В наличии
                    </label>
                </div>
            </div>
            
            <div class="form-group">
                <label>Описание</label>
                <textarea name="description" rows="3"></textarea>
            </div>
            
            <div class="form-group">
                <label>Изображение</label>
                <input type="file" name="image" accept="image/*">
            </div>
            
            <button type="submit" name="add_product" class="btn-primary">Добавить товар</button>
        </form>
    </div>
    
    <h3>Список товаров</h3>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Фото</th>
                    <th>Название</th>
                    <th>Артикул</th>
                    <th>Цена</th>
                    <th>Наличие</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                <tr>
                    <td><?= $product['id'] ?></td>
                    <td>
                        <img src="<?= url('assets/uploads/' . $product['image']) ?>" 
                             style="width: 50px; height: 50px; object-fit: contain;">
                    </td>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td><?= htmlspecialchars($product['article'] ?? '-') ?></td>
                    <td><?= formatPrice($product['price']) ?></td>
                    <td>
                        <span style="color: <?= $product['in_stock'] ? 'green' : 'red' ?>">
                            <?= $product['in_stock'] ? 'В наличии' : 'Нет' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= url('/6666/admin/edit_product.php?id=' . $product['id']) ?>">✏️</a>
                        <a href="<?= url('/6666/admin/products.php?delete=' . $product['id']) ?>" 
                           onclick="return confirm('Удалить товар?')">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>