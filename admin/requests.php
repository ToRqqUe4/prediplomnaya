<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$message = '';
$error = '';

$status_filter = $_GET['status'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $request_id = (int)$_POST['request_id'];
    $status = $_POST['status'];
    
    try {
        $stmt = $pdo->prepare("UPDATE repair_requests SET status = ? WHERE id = ?");
        $stmt->execute([$status, $request_id]);
        $message = 'Статус заявки обновлен';
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM repair_requests WHERE id = ?");
    $stmt->execute([$id]);
    redirect('admin/requests.php?msg=deleted');
}

$sql = "
    SELECT r.*, u.full_name as user_name, u.email as user_email
    FROM repair_requests r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE 1=1
";
$params = [];

if ($status_filter) {
    $sql .= " AND r.status = ?";
    $params[] = $status_filter;
}

$sql .= " ORDER BY 
    CASE r.status 
        WHEN 'new' THEN 1 
        WHEN 'in_progress' THEN 2 
        ELSE 3 
    END, 
    r.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$requests = $stmt->fetchAll();

$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM repair_requests")->fetchColumn(),
    'new' => $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'new'")->fetchColumn(),
    'in_progress' => $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'in_progress'")->fetchColumn(),
    'completed' => $pdo->query("SELECT COUNT(*) FROM repair_requests WHERE status = 'completed'")->fetchColumn(),
];

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / Заявки на ремонт
    </div>

    <h2>Управление заявками на ремонт</h2>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'deleted'): ?>
    <div class="alert alert-success">Заявка успешно удалена</div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
        <div style="background: white; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>Всего заявок</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['total'] ?></p>
        </div>
        <div style="background: #cce5ff; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>Новые</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['new'] ?></p>
        </div>
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>В работе</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['in_progress'] ?></p>
        </div>
        <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
            <h4>Выполнено</h4>
            <p style="font-size: 24px; font-weight: bold;"><?= $stats['completed'] ?></p>
        </div>
    </div>
    
    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <form method="GET" style="display: flex; gap: 15px; align-items: flex-end;">
            <div style="flex: 1;">
                <label>Статус:</label>
                <select name="status" style="width: 100%; padding: 10px;">
                    <option value="">Все заявки</option>
                    <option value="new" <?= $status_filter == 'new' ? 'selected' : '' ?>>Новые</option>
                    <option value="in_progress" <?= $status_filter == 'in_progress' ? 'selected' : '' ?>>В работе</option>
                    <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Выполненные</option>
                    <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Отмененные</option>
                </select>
            </div>
            <div>
                <button type="submit" class="btn-primary">Применить</button>
                <a href="<?= url('admin/requests.php') ?>" class="btn-secondary">Сбросить</a>
            </div>
        </form>
    </div>
    
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Клиент</th>
                    <th>Контакты</th>
                    <th>Техника</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?= $request['id'] ?></td>
                    <td><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></td>
                    <td><?= htmlspecialchars($request['client_name']) ?></td>
                    <td>
                        <strong><?= htmlspecialchars($request['phone']) ?></strong><br>
                        <small><?= htmlspecialchars($request['email'] ?? '') ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($request['equipment_type'] ?? '—') ?>
                        <?php if ($request['equipment_model']): ?>
                        <br><small><?= htmlspecialchars($request['equipment_model']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="request_id" value="<?= $request['id'] ?>">
                            <select name="status" onchange="this.form.submit()" 
                                    style="padding: 5px; border-radius: 4px; background: <?= 
                                        $request['status'] == 'completed' ? '#d4edda' : 
                                        ($request['status'] == 'in_progress' ? '#fff3cd' : 
                                        ($request['status'] == 'cancelled' ? '#f8d7da' : '#cce5ff')) ?>">
                                <option value="new" <?= $request['status'] == 'new' ? 'selected' : '' ?>>Новая</option>
                                <option value="in_progress" <?= $request['status'] == 'in_progress' ? 'selected' : '' ?>>В работе</option>
                                <option value="completed" <?= $request['status'] == 'completed' ? 'selected' : '' ?>>Выполнена</option>
                                <option value="cancelled" <?= $request['status'] == 'cancelled' ? 'selected' : '' ?>>Отменена</option>
                            </select>
                            <input type="hidden" name="update_status" value="1">
                        </form>
                    </td>
                    <td>
                        <a href="/6666/admin/request_detail.php?id=<?= $request['id'] ?>" class="btn-secondary" style="padding: 5px 10px; font-size: 12px;">
    Подробнее
</a>
                        <a href="<?= url('admin/requests.php?delete=' . $request['id']) ?>" 
                           onclick="return confirm('Удалить заявку?')" 
                           style="color: red; margin-left: 10px;">🗑️</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($requests)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px;">Заявки не найдены</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</main>

<?php include '../includes/footer.php'; ?>