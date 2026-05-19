<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $full_name = trim($_POST['full_name'] ?? '');
    
    $errors = [];
    
    if (strlen($login) < 3) {
        $errors[] = 'Логин должен быть не менее 3 символов';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Некорректный email';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Пароль должен быть не менее 6 символов';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Пароли не совпадают';
    }
    
    $stmt = $pdo->prepare("SELECT id FROM users WHERE login = ? OR email = ?");
    $stmt->execute([$login, $email]);
    if ($stmt->fetch()) {
        $errors[] = 'Пользователь с таким логином или email уже существует';
    }
    
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("INSERT INTO users (login, email, password, full_name) VALUES (?, ?, ?, ?)");
        $stmt->execute([$login, $email, $hashed_password, $full_name]);
        
        $user_id = $pdo->lastInsertId();
        
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $full_name ?: $login;
        $_SESSION['role'] = 'user';
        
        header('Location: profile.php');
        exit;
    } else {
        $error = implode('<br>', $errors);
    }
}

include 'includes/header.php';
?>

<main>
    <div class="form-container">
        <h2 class="form-title">Регистрация</h2>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Логин *</label>
                <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Email *</label>
                <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>ФИО</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label>Пароль *</label>
                <input type="password" name="password" required>
                <small>Минимум 6 символов</small>
            </div>
            
            <div class="form-group">
                <label>Подтверждение пароля *</label>
                <input type="password" name="confirm_password" required>
            </div>
            
            <button type="submit" class="btn-submit">Зарегистрироваться</button>
            
            <p style="text-align: center; margin-top: 20px;">
                Уже есть аккаунт? <a href="login.php">Войти</a>
            </p>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>