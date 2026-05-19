<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$message = '';
$error = '';

// Добавление категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $sort_order = (int)$_POST['sort_order'];
    
    if (empty($name)) {
        $error = 'Название категории обязательно';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id, sort_order) VALUES (?, ?, ?)");
            $stmt->execute([$name, $parent_id, $sort_order]);
            $message = 'Категория успешно добавлена';
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

// Редактирование категории
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_category'])) {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $parent_id = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
    $sort_order = (int)$_POST['sort_order'];
    
    if (empty($name)) {
        $error = 'Название категории обязательно';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, parent_id = ?, sort_order = ? WHERE id = ?");
            $stmt->execute([$name, $parent_id, $sort_order, $id]);
            $message = 'Категория успешно обновлена';
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

// Удаление категории
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->execute([$id]);
    $products_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $stmt->execute([$id]);
    $children_count = $stmt->fetchColumn();
    
    if ($products_count > 0) {
        $error = 'Нельзя удалить категорию, в которой есть товары';
    } elseif ($children_count > 0) {
        $error = 'Нельзя удалить категорию, у которой есть подкатегории';
    } else {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: ' . url('admin/categories.php?msg=deleted'));
        exit;
    }
}

// Получение всех категорий
$categories = $pdo->query("
    SELECT c.*, 
           p.name as parent_name,
           (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as children_count,
           (SELECT COUNT(*) FROM products WHERE category_id = c.id) as products_count
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY c.parent_id IS NULL DESC, c.parent_id, c.sort_order, c.name
")->fetchAll();

$parent_categories = $pdo->query("
    SELECT * FROM categories 
    WHERE parent_id IS NULL 
    ORDER BY sort_order, name
")->fetchAll();

$edit_category = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_category = $stmt->fetch();
}

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / Категории
    </div>

    <h2>Управление категориями</h2>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div class="alert alert-success">Категория успешно удалена</div>
    <?php endif; ?>
    
    <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <h3><?= $edit_category ? 'Редактировать категорию' : 'Добавить новую категорию' ?></h3>
        
        <form method="POST">
            <?php if ($edit_category): ?>
            <input type="hidden" name="edit_category" value="1">
            <input type="hidden" name="id" value="<?= $edit_category['id'] ?>">
            <?php else: ?>
            <input type="hidden" name="add_category" value="1">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Название категории *</label>
                <input type="text" name="name" value="<?= htmlspecialchars($edit_category['name'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Родительская категория</label>
                <select name="parent_id">
                    <option value="">Нет (корневая категория)</option>
                    <?php foreach ($parent_categories as $cat): ?>
                        <?php if (!$edit_category || $cat['id'] != $edit_category['id']): ?>
                        <option value="<?= $cat['id'] ?>" <?= ($edit_category && $edit_category['parent_id'] == $cat['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($cat['name']) ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Порядок сортировки</label>
                <input type="number" name="sort_order" value="<?= $edit_category['sort_order'] ?? 0 ?>">
            </div>
            
            <button type="submit" class="btn-primary">
                <?= $edit_category ? 'Сохранить изменения' : 'Добавить категорию' ?>
            </button>
            
            <?php if ($edit_category): ?>
            <a href="<?= url('admin/categories.php') ?>" class="btn-secondary">Отмена</a>
            <?php endif; ?>
        </form>
    </div>
    
    <h3>Список категорий</h3>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Название</th>
                    <th>Родительская категория</th>
                    <th>Подкатегорий</th>
                    <th>Товаров</th>
                    <th>Сортировка</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($categories as $category): ?>
                <tr>
                    <td><?= $category['id'] ?></td>
                    <td><strong><?= htmlspecialchars($category['name']) ?></strong></td>
                    <td><?= htmlspecialchars($category['parent_name'] ?? '—') ?></td>
                    <td><?= $category['children_count'] ?></td>
                    <td><?= $category['products_count'] ?></td>
                    <td><?= $category['sort_order'] ?></td>
                    <td>
                        <a href="<?= url('/6666/admin/categories.php?edit=' . $category['id']) ?>" style="margin-right: 10px;">✏️</a>
                        <?php if ($category['products_count'] == 0 && $category['children_count'] == 0): ?>
                        <a href="<?= url('/6666/admin/categories.php?delete=' . $category['id']) ?>" 
                           onclick="return confirm('Удалить категорию?')" 
                           style="color: red;">🗑️</a>
                        <?php else: ?>
                        <span style="color: #999;" title="Нельзя удалить категорию с товарами или подкатегориями">🗑️</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>