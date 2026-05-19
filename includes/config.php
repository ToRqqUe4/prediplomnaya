<?php
session_start();

// Автоматическое определение базового пути
$script_name = dirname($_SERVER['SCRIPT_NAME']);
$base_url = $script_name;
if ($base_url == '/' || $base_url == '\\') {
    $base_url = '';
}
define('BASE_URL', $base_url);
define('ROOT_PATH', __DIR__ . '/..');

$host = 'localhost';
$db   = 'dorstroitech';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    die('Ошибка подключения: ' . $e->getMessage());
}

function url($path = '') {
    if (strpos($path, BASE_URL) === 0) {
        return $path;
    }
    return BASE_URL . '/' . ltrim($path, '/');
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function getCurrentUser() {
    global $pdo;
    if (!isLoggedIn()) return null;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

function getCartCount() {
    global $pdo;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("SELECT SUM(quantity) as total FROM cart WHERE session_id = ?");
        $stmt->execute([$session_id]);
    }
    $result = $stmt->fetch();
    return $result['total'] ?? 0;
}

function getCartItems() {
    global $pdo;
    if (isLoggedIn()) {
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.article, p.price, p.image 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.user_id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $session_id = session_id();
        $stmt = $pdo->prepare("
            SELECT c.*, p.name, p.article, p.price, p.image 
            FROM cart c 
            JOIN products p ON c.product_id = p.id 
            WHERE c.session_id = ?
        ");
        $stmt->execute([$session_id]);
    }
    return $stmt->fetchAll();
}

function getCartTotal() {
    $items = getCartItems();
    $total = 0;
    foreach ($items as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}
function redirect($path) {
    header('Location: ' . url($path));
    exit;
}

function generateOrderNumber() {
    return 'DST-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
}

function formatPrice($price) {
    return number_format($price, 0, ',', ' ') . ' ₽';
}
?>
