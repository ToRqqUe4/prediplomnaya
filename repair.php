<?php
require_once 'includes/config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $equipment_type = $_POST['equipment_type'] ?? '';
    $equipment_model = $_POST['equipment_model'] ?? '';
    $problem = $_POST['problem'] ?? '';
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';
    $address = $_POST['address'] ?? '';
    
    if (empty($name) || empty($phone)) {
        $error = 'Имя и телефон обязательны для заполнения';
    } else {
        $user_id = isLoggedIn() ? $_SESSION['user_id'] : null;
        
        $stmt = $pdo->prepare("
            INSERT INTO repair_requests (user_id, client_name, phone, email, equipment_type, equipment_model, problem_description, preferred_date, preferred_time, address) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        if ($stmt->execute([$user_id, $name, $phone, $email, $equipment_type, $equipment_model, $problem, $date, $time, $address])) {
            $success = true;
        } else {
            $error = 'Ошибка при отправке заявки';
        }
    }
}

$current_user = getCurrentUser();

include 'includes/header.php';
?>

<main>
    <div class="breadcrumbs">
        <a href="/">Главная</a> / Запись на ремонт
    </div>

    <div class="form-container" style="max-width: 800px;">
        <h2 class="form-title">Запись на ремонт строительной техники</h2>
        
        <?php if ($success): ?>
        <div class="alert alert-success">
            <h3>Спасибо за заявку!</h3>
            <p>Наш специалист свяжется с вами в ближайшее время.</p>
            <a href="/6666/" class="btn-primary" style="display: inline-block; margin-top: 20px;">На главную</a>
        </div>
        <?php else: ?>
        
        <?php if ($error): ?>
        <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-row">
                <div class="form-group">
                    <label>Ваше имя *</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($current_user['full_name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label>Телефон *</label>
                    <input type="tel" name="phone" value="<?= htmlspecialchars($current_user['phone'] ?? '') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" value="<?= htmlspecialchars($current_user['email'] ?? '') ?>">
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Тип техники</label>
                    <select name="equipment_type">
                        <option value="">Выберите тип</option>
                        <option value="excavator">Экскаватор</option>
                        <option value="bulldozer">Бульдозер</option>
                        <option value="loader">Погрузчик</option>
                        <option value="crane">Кран</option>
                        <option value="other">Другое</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Модель техники</label>
                    <input type="text" name="equipment_model" placeholder="Например: Hitachi ZX200">
                </div>
            </div>
            
            <div class="form-group">
                <label>Описание проблемы *</label>
                <textarea name="problem" rows="5" required></textarea>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label>Желаемая дата</label>
                    <input type="date" name="date" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Желаемое время</label>
                    <select name="time">
                        <option value="">Любое время</option>
                        <option value="9-12">9:00 - 12:00</option>
                        <option value="12-15">12:00 - 15:00</option>
                        <option value="15-18">15:00 - 18:00</option>
                    </select>
                </div>
            </div>
            
            <div class="form-group">
                <label>Адрес проведения работ</label>
                <textarea name="address" rows="3"><?= htmlspecialchars($current_user['address'] ?? '') ?></textarea>
            </div>
            
            <button type="submit" class="btn-submit">Отправить заявку</button>
        </form>
        <?php endif; ?>
    </div>
</main>

<?php include 'includes/footer.php'; ?>