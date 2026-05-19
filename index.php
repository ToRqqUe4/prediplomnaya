<?php 
require_once 'includes/config.php';

$news = $pdo->query("SELECT * FROM news WHERE is_published = 1 ORDER BY created_at DESC LIMIT 3")->fetchAll();
$popular_products = $pdo->query("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.in_stock = 1 
    ORDER BY p.id DESC 
    LIMIT 6
")->fetchAll();

include 'includes/header.php'; 
?>

<main>
    <section class="hero" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)); background-size: cover; background-position: center; color: white; padding: 80px 20px; border-radius: 8px; margin-bottom: 40px;">
        <div style="max-width: 800px;">
            <h2 style="color: var(--yellow); font-size: 42px; margin-bottom: 20px;">ДорСтройТехх</h2>
            <p style="font-size: 24px; margin-bottom: 20px;">Продажа запчастей и ремонт строительной техники</p>
            <p style="font-size: 18px; margin-bottom: 30px;">Более 10 лет на рынке. Оригинальные запчасти и качественный сервис.</p>
            <div style="display: flex; gap: 15px;">
                <a href="<?= url('catalog.php') ?>" class="btn-primary" style="padding: 15px 30px; font-size: 18px;">Перейти в каталог</a>
                <a href="<?= url('repair.php') ?>" class="btn-secondary" style="padding: 15px 30px; font-size: 18px;">Записаться на ремонт</a>
            </div>
        </div>
    </section>

    <section style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px;">
        <div style="background: var(--white); padding: 30px; border-radius: 8px;">
            <h3 style="color: var(--black); border-left: 5px solid var(--yellow); padding-left: 15px;">О компании</h3>
            <p>Компания «ДорСтройТехх» — ваш надежный партнер в мире строительной техники. Мы специализируемся на поставке высококачественных запчастей для экскаваторов, бульдозеров, погрузчиков и другой спецтехники.</p>
            <p>Наш сервисный центр оснащен современным оборудованием для диагностики и ремонта любой сложности.</p>
            <h4>Наши преимущества:</h4>
            <ul>
                <li>Собственный склад запчастей</li>
                <li>Выезд специалиста на объект</li>
                <li>Гарантия до 12 месяцев</li>
                <li>Бесплатная диагностика</li>
            </ul>
        </div>
        
        <div style="background: var(--yellow); padding: 30px; border-radius: 8px; color: var(--black);">
            <h3 style="border-left: 5px solid var(--black); padding-left: 15px;">Срочный ремонт</h3>
            <p style="font-size: 18px; margin-bottom: 20px;">Оставьте заявку и наш специалист свяжется с вами в течение 15 минут!</p>
            <form method="POST" action="<?= url('repair.php') ?>">
                <input type="text" name="name" placeholder="Ваше имя" required style="width: 100%; padding: 12px; margin-bottom: 10px; border: none; border-radius: 4px;">
                <input type="tel" name="phone" placeholder="Телефон" required style="width: 100%; padding: 12px; margin-bottom: 20px; border: none; border-radius: 4px;">
                <button type="submit" class="btn-secondary" style="width: 100%;">Отправить заявку</button>
            </form>
        </div>
    </section>

    <section style="margin-bottom: 40px;">
        <h3 style="color: var(--black); border-left: 5px solid var(--yellow); padding-left: 15px;">Популярные запчасти</h3>
        <div class="products-grid">
            <?php foreach ($popular_products as $product): ?>
            <div class="product-card">
                <img src="<?= url('assets/uploads/' . $product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                <div class="product-info">
                    <h3><?= htmlspecialchars($product['name']) ?></h3>
                    <div class="product-article">Арт. <?= htmlspecialchars($product['article'] ?? 'N/A') ?></div>
                    <div class="product-price">
                        <span class="current-price"><?= formatPrice($product['price']) ?></span>
                    </div>
                    <div class="product-actions">
                        <a href="<?= url('product.php?id=' . $product['id']) ?>" class="btn-details">Подробнее</a>
                        <form method="POST" action="<?= url('cart.php') ?>" style="flex:1;">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-buy" style="width:100%;">В корзину</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section style="margin-bottom: 40px;">
        <h3 style="color: var(--black); border-left: 5px solid var(--yellow); padding-left: 15px;">Новости компании</h3>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
            <?php foreach ($news as $item): ?>
            <div style="background: var(--white); border-radius: 8px; overflow: hidden;">
                <div style="padding: 20px;">
                    <h4 style="margin: 0 0 10px 0;"><?= htmlspecialchars($item['title']) ?></h4>
                    <p style="color: #666; font-size: 14px;"><?= date('d.m.Y', strtotime($item['created_at'])) ?></p>
                    <p><?= htmlspecialchars($item['short_content']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section style="background: var(--black); color: var(--white); padding: 30px; border-radius: 8px;">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 30px;">
            <div>
                <h4 style="color: var(--yellow);">Контакты</h4>
                <p>📞 +7 (495) 123-45-67</p>
                <p>📞 +7 (800) 123-45-67</p>
                <p>✉️ info@dorstroitech.ru</p>
            </div>
            <div>
                <h4 style="color: var(--yellow);">Адрес</h4>
                <p>г. Москва, ул. Строителей, д. 15</p>
                <p>Пн-Пт: 9:00 - 20:00</p>
                <p>Сб-Вс: 10:00 - 18:00</p>
            </div>
            <div>
                <h4 style="color: var(--yellow);">Реквизиты</h4>
                <p>ООО "ДорСтройТехх"</p>
                <p>ИНН 1234567890</p>
                <p>ОГРН 1234567890123</p>
            </div>
        </div>
    </section>
</main>

<?php include 'includes/footer.php'; ?>