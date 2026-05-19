<?php
require_once '../includes/config.php';

if (!isAdmin()) {
    redirect('login.php');
}

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("
    SELECT r.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone
    FROM repair_requests r 
    LEFT JOIN users u ON r.user_id = u.id 
    WHERE r.id = ?
");
$stmt->execute([$id]);
$request = $stmt->fetch();

if (!$request) {
    redirect('admin/requests.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $status = $_POST['status'];
    $admin_comment = $_POST['admin_comment'] ?? '';
    
    try {
        $stmt = $pdo->prepare("UPDATE repair_requests SET status = ?, admin_comment = ? WHERE id = ?");
        $stmt->execute([$status, $admin_comment, $id]);
        $message = 'Статус заявки обновлен';
        
        // Обновляем данные
        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name as user_name, u.email as user_email, u.phone as user_phone
            FROM repair_requests r 
            LEFT JOIN users u ON r.user_id = u.id 
            WHERE r.id = ?
        ");
        $stmt->execute([$id]);
        $request = $stmt->fetch();
        
    } catch (Exception $e) {
        $error = 'Ошибка: ' . $e->getMessage();
    }
}

include 'header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="<?= url('admin/index.php') ?>">Админ-панель</a> / 
        <a href="<?= url('admin/requests.php') ?>">Заявки на ремонт</a> / 
        Заявка #<?= $request['id'] ?>
    </div>

    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Заявка на ремонт #<?= $request['id'] ?></h2>
        <div>
            <a href="<?= url('/6666/admin/requests.php') ?>" class="btn-secondary">← Назад к списку</a>
        </div>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <div style="background: white; padding: 25px; border-radius: 8px;">
            <h3>Информация о клиенте</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0;"><strong>Имя:</strong></td>
                    <td><?= htmlspecialchars($request['client_name']) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Телефон:</strong></td>
                    <td><?= htmlspecialchars($request['phone']) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Email:</strong></td>
                    <td><?= htmlspecialchars($request['email'] ?? '—') ?></td>
                </tr>
                <?php if ($request['user_name']): ?>
                <tr>
                    <td style="padding: 8px 0;"><strong>Пользователь:</strong></td>
                    <td><?= htmlspecialchars($request['user_name']) ?></td>
                </tr>
                <?php endif; ?>
            </table>
        </div>
        
        <div style="background: white; padding: 25px; border-radius: 8px;">
            <h3>Информация о заявке</h3>
            <table style="width: 100%;">
                <tr>
                    <td style="padding: 8px 0;"><strong>Дата создания:</strong></td>
                    <td><?= date('d.m.Y H:i', strtotime($request['created_at'])) ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Тип техники:</strong></td>
                    <td><?= htmlspecialchars($request['equipment_type'] ?? '—') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Модель:</strong></td>
                    <td><?= htmlspecialchars($request['equipment_model'] ?? '—') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Желаемая дата:</strong></td>
                    <td><?= $request['preferred_date'] ? date('d.m.Y', strtotime($request['preferred_date'])) : '—' ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Желаемое время:</strong></td>
                    <td><?= htmlspecialchars($request['preferred_time'] ?? '—') ?></td>
                </tr>
                <tr>
                    <td style="padding: 8px 0;"><strong>Статус:</strong></td>
                    <td>
                        <form method="POST" style="display: inline;">
                            <select name="status" onchange="this.form.submit()" 
                                    style="padding: 8px; border-radius: 4px; background: <?= 
                                        $request['status'] == 'completed' ? '#d4edda' : 
                                        ($request['status'] == 'in_progress' ? '#fff3cd' : 
                                        ($request['status'] == 'cancelled' ? '#f8d7da' : '#cce5ff')) ?>">
                                <option value="new" <?= $request['status'] == 'new' ? 'selected' : '' ?>>Новая</option>
                                <option value="in_progress" <?= $request['status'] == 'in_progress' ? 'selected' : '' ?>>В работе</option>
                                <option value="completed" <?= $request['status'] == 'completed' ? 'selected' : '' ?>>Выполнена</option>
                                <option value="cancelled" <?= $request['status'] == 'cancelled' ? 'selected' : '' ?>>Отменена</option>
                            </select>
                        </form>
                    </td>
                </tr>
            </table>
        </div>
    </div>
    
    <?php if ($request['address']): ?>
    <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <h3>Адрес проведения работ</h3>
        <p><?= nl2br(htmlspecialchars($request['address'])) ?></p>
    </div>
    <?php endif; ?>
    
    <div style="background: white; padding: 25px; border-radius: 8px; margin-bottom: 30px;">
        <h3>Описание проблемы</h3>
        <p><?= nl2br(htmlspecialchars($request['problem_description'])) ?></p>
    </div>
    
    <div style="background: white; padding: 25px; border-radius: 8px;">
        <h3>Комментарий администратора</h3>
        <form method="POST">
            <input type="hidden" name="status" value="<?= $request['status'] ?>">
            <textarea name="admin_comment" rows="4" style="width: 100%; padding: 10px;"><?= htmlspecialchars($request['admin_comment'] ?? '') ?></textarea>
            <button type="submit" class="btn-primary" style="margin-top: 15px;">Сохранить комментарий</button>
        </form>
    </div>
</main>

<?php include '../includes/footer.php'; ?>