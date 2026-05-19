<?php
require_once 'includes/config.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $address = $_POST['address'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    
    try {
        if ($new_password) {
            if ($new_password !== $_POST['confirm_password']) {
                throw new Exception('Пароли не совпадают');
            }
            
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, password = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $address, $hashed_password, $user_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $address, $user_id]);
        }
        
        $_SESSION['user_name'] = $full_name;
        $message = 'Профиль успешно обновлен!';
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$user = getCurrentUser();

include 'includes/header.php';
?>

<main>
    <div class="form-container">
        <h2 class="form-title">Редактирование профиля</h2>
        
        <?php if ($message): ?>
        <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" value="<?= htmlspecialchars($user['login']) ?>" disabled>
            </div>
            
            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>
            
            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Адрес доставки</label>
                <textarea name="address" rows="3"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
            </div>
            
            <h3>Сменить пароль</h3>
            <small>Оставьте поля пустыми, если не хотите менять пароль</small>
            
            <div class="form-group">
                <label>Новый пароль</label>
                <input type="password" name="new_password">
            </div>
            
            <div class="form-group">
                <label>Подтвердите пароль</label>
                <input type="password" name="confirm_password">
            </div>
            
            <button type="submit" class="btn-submit">Сохранить изменения</button>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>