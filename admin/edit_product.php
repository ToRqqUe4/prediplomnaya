<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$message = '';
$error = '';

$stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: ' . url('admin/products.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category_id = (int)$_POST['category_id'];
    $article = $_POST['article'];
    $description = $_POST['description'];
    $full_description = $_POST['full_description'];
    $price = (float)$_POST['price'];
    $old_price = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
    $stock = (int)$_POST['stock'];
    $in_stock = isset($_POST['in_stock']) ? 1 : 0;
    
    $image = $product['image'];
    if (!empty($_FILES['image']['name'])) {
        if ($image != 'no-image.jpg' && file_exists(__DIR__ . '/../assets/uploads/' . $image)) {
            unlink(__DIR__ . '/../assets/uploads/' . $image);
        }
        
        $ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $image = time() . '_' . uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['image']['tmp_name'], __DIR__ . '/../assets/uploads/' . $image);
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE products 
            SET category_id = ?, article = ?, name = ?, description = ?, full_description = ?, 
                price = ?, old_price = ?, stock = ?, in_stock = ?, image = ? 
            WHERE id = ?
        ");
        $stmt->execute([
            $category_id, $article, $name, $description, $full_description, 
            $price, $old_price, $stock, $in_stock, $image, $id
        ]);
        
        $message = 'Товар успешно обновлен';
        
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

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
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / 
        <a href="<?= url('admin/products.php') ?>">Товары</a> / 
        Редактирование товара
    </div>

    <h2>Редактирование товара #<?= $product['id'] ?></h2>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div style="background: white; padding: 30px; border-radius: 8px;">
        <form method="POST" enctype="multipart/form-data">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                <div>
                    <div class="form-group">
                        <label>Название *</label>
                        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Категория *</label>
                        <select name="category_id" required>
                            <option value="">Выберите категорию</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $product['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                            <?php if ($cat['has_children']): 
                                $subcats = $pdo->prepare("SELECT * FROM categories WHERE parent_id = ?");
                                $subcats->execute([$cat['id']]);
                                while ($sub = $subcats->fetch()):
                            ?>
                            <option value="<?= $sub['id'] ?>" <?= $product['category_id'] == $sub['id'] ? 'selected' : '' ?>>
                                — <?= htmlspecialchars($sub['name']) ?>
                            </option>
                            <?php endwhile; endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Артикул</label>
                        <input type="text" name="article" value="<?= htmlspecialchars($product['article'] ?? '') ?>">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Цена *</label>
                            <input type="number" name="price" step="0.01" value="<?= $product['price'] ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Старая цена</label>
                            <input type="number" name="old_price" step="0.01" value="<?= $product['old_price'] ?>">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Количество на складе</label>
                            <input type="number" name="stock" value="<?= $product['stock'] ?>">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="in_stock" value="1" <?= $product['in_stock'] ? 'checked' : '' ?>>
                                В наличии
                            </label>
                        </div>
                    </div>
                </div>
                
                <div>
                    <div class="form-group">
                        <label>Текущее изображение</label>
                        <div style="margin-bottom: 15px;">
                            <img src="<?= url('assets/uploads/' . $product['image']) ?>" 
                                 style="max-width: 200px; max-height: 200px; border: 1px solid #ddd; padding: 10px; border-radius: 4px;">
                        </div>
                        <label>Заменить изображение</label>
                        <input type="file" name="image" accept="image/*">
                    </div>
                </div>
            </div>
            
            <div class="form-group">
                <label>Краткое описание</label>
                <textarea name="description" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Полное описание</label>
                <textarea name="full_description" rows="8"><?= htmlspecialchars($product['full_description'] ?? '') ?></textarea>
            </div>
            
            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button type="submit" class="btn-primary">Сохранить изменения</button>
                <a href="<?= url('/6666/admin/products.php') ?>" class="btn-secondary">Отмена</a>
            </div>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>