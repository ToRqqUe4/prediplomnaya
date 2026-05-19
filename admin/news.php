<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    header('Location: ' . url('login.php'));
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_news'])) {
    $title = trim($_POST['title']);
    $short_content = trim($_POST['short_content']);
    $full_content = trim($_POST['full_content']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Заголовок новости обязателен';
    } else {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO news (title, short_content, full_content, is_published) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$title, $short_content, $full_content, $is_published]);
            $message = 'Новость успешно добавлена';
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_news'])) {
    $id = (int)$_POST['id'];
    $title = trim($_POST['title']);
    $short_content = trim($_POST['short_content']);
    $full_content = trim($_POST['full_content']);
    $is_published = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Заголовок новости обязателен';
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE news 
                SET title = ?, short_content = ?, full_content = ?, is_published = ? 
                WHERE id = ?
            ");
            $stmt->execute([$title, $short_content, $full_content, $is_published, $id]);
            $message = 'Новость успешно обновлена';
        } catch (Exception $e) {
            $error = 'Ошибка: ' . $e->getMessage();
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM news WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: ' . url('admin/news.php?msg=deleted'));
    exit;
}

$news = $pdo->query("SELECT * FROM news ORDER BY created_at DESC")->fetchAll();

$edit_news = null;
if (isset($_GET['edit'])) {
    $edit_id = (int)$_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM news WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_news = $stmt->fetch();
}

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / Новости
    </div>

    <h2>Управление новостями</h2>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div class="alert alert-success">Новость успешно удалена</div>
    <?php endif; ?>
    
    <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <h3><?= $edit_news ? 'Редактировать новость' : 'Добавить новость' ?></h3>
        
        <form method="POST">
            <?php if ($edit_news): ?>
            <input type="hidden" name="edit_news" value="1">
            <input type="hidden" name="id" value="<?= $edit_news['id'] ?>">
            <?php else: ?>
            <input type="hidden" name="add_news" value="1">
            <?php endif; ?>
            
            <div class="form-group">
                <label>Заголовок *</label>
                <input type="text" name="title" value="<?= htmlspecialchars($edit_news['title'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Краткое содержание</label>
                <textarea name="short_content" rows="3"><?= htmlspecialchars($edit_news['short_content'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>Полное содержание</label>
                <textarea name="full_content" rows="8"><?= htmlspecialchars($edit_news['full_content'] ?? '') ?></textarea>
            </div>
            
            <div class="form-group">
                <label>
                    <input type="checkbox" name="is_published" value="1" 
                           <?= ($edit_news && $edit_news['is_published']) || !$edit_news ? 'checked' : '' ?>>
                    Опубликовать
                </label>
            </div>
            
            <button type="submit" class="btn-primary">
                <?= $edit_news ? 'Сохранить изменения' : 'Добавить новость' ?>
            </button>
            
            <?php if ($edit_news): ?>
            <a href="<?= url('admin/news.php') ?>" class="btn-secondary">Отмена</a>
            <?php endif; ?>
        </form>
    </div>
    
    <h3>Список новостей</h3>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Заголовок</th>
                    <th>Дата создания</th>
                    <th>Просмотры</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($news as $item): ?>
                <tr>
                    <td><?= $item['id'] ?></td>
                    <td><?= htmlspecialchars($item['title']) ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($item['created_at'])) ?></td>
                    <td><?= $item['views'] ?? 0 ?></td>
                    <td>
                        <span style="color: <?= $item['is_published'] ? 'green' : 'red' ?>">
                            <?= $item['is_published'] ? 'Опубликована' : 'Черновик' ?>
                        </span>
                    </td>
                    <td>
                        <a href="<?= url('admin/news.php?edit=' . $item['id']) ?>" style="margin-right: 10px;">✏️</a>
                        <a href="<?= url('admin/news.php?delete=' . $item['id']) ?>" 
                           onclick="return confirm('Удалить новость?')" 
                           style="color: red;">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($news)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px;">Новости не найдены</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>