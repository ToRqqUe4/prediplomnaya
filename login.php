<?php
require_once 'includes/config.php';

if (isLoggedIn()) {
    header('Location: profile.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE login = ? OR email = ?");
    $stmt->execute([$login, $login]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'] ?: $user['login'];
        $_SESSION['role'] = $user['role'];
        
        $session_id = session_id();
        $stmt = $pdo->prepare("UPDATE cart SET user_id = ?, session_id = NULL WHERE session_id = ?");
        $stmt->execute([$user['id'], $session_id]);
        
        header('Location: profile.php');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}

include 'includes/header.php';
?>

<main>
    <div class="form-container">
        <h2 class="form-title">Вход в личный кабинет</h2>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Логин или Email</label>
                <input type="text" name="login" value="<?= htmlspecialchars($_POST['login'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="password" required>
            </div>
            
            <button type="submit" class="btn-submit">Войти</button>
            
            <p style="text-align: center; margin-top: 20px;">
                Нет аккаунта? <a href="register.php">Зарегистрироваться</a>
            </p>
        </form>
    </div>
</main>

<?php include 'includes/footer.php'; ?>