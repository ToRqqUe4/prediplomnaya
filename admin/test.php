<?php
require_once '../includes/config.php';

echo "URL для request_detail.php: " . url('admin/request_detail.php?id=1') . "<br>";
echo "Файл существует: " . (file_exists(__DIR__ . '/request_detail.php') ? 'Да' : 'Нет') . "<br>";

// Проверяем заявки в базе
$requests = $pdo->query("SELECT id, client_name FROM repair_requests LIMIT 5")->fetchAll();
echo "<h3>Заявки в базе:</h3>";
foreach ($requests as $r) {
    echo "ID: " . $r['id'] . " - " . $r['client_name'] . "<br>";
    echo "<a href='" . url('admin/request_detail.php?id=' . $r['id']) . "'>Посмотреть заявку #" . $r['id'] . "</a><br><br>";
}
?>