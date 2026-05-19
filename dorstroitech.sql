CREATE DATABASE IF NOT EXISTS dorstroitech;
USE dorstroitech;

-- Пользователи
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Администратор (пароль: admin123)
INSERT INTO users (login, password, email, full_name, role) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@dorstroitech.ru', 'Администратор', 'admin');

-- Категории
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    parent_id INT DEFAULT NULL,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE CASCADE
);

INSERT INTO categories (name, parent_id, sort_order) VALUES 
('Двигатель и система охлаждения', NULL, 1),
('Поршневая группа', 1, 1),
('Турбокомпрессоры', 1, 2),
('Гидравлическая система', NULL, 2),
('Гидронасосы', 4, 1),
('Гидроцилиндры', 4, 2),
('Ходовая часть', NULL, 3),
('Гусеницы', 7, 1),
('Катки', 7, 2),
('Электрика', NULL, 4);

-- Товары
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    article VARCHAR(50),
    name VARCHAR(255) NOT NULL,
    description TEXT,
    full_description TEXT,
    price DECIMAL(10,2),
    old_price DECIMAL(10,2),
    image VARCHAR(255) DEFAULT 'no-image.jpg',
    stock INT DEFAULT 0,
    in_stock BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Корзина
CREATE TABLE cart (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    session_id VARCHAR(100),
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Заказы
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_number VARCHAR(20) UNIQUE,
    total DECIMAL(10,2),
    status ENUM('new', 'processing', 'completed', 'cancelled') DEFAULT 'new',
    payment_method VARCHAR(50),
    delivery_address TEXT,
    contact_phone VARCHAR(20),
    contact_email VARCHAR(100),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Состав заказа
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT,
    product_id INT,
    product_name VARCHAR(255),
    article VARCHAR(50),
    quantity INT,
    price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
);

-- Заявки на ремонт
CREATE TABLE repair_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    client_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    equipment_type VARCHAR(255),
    equipment_model VARCHAR(100),
    problem_description TEXT,
    preferred_date DATE,
    preferred_time VARCHAR(20),
    address TEXT,
    status ENUM('new', 'in_progress', 'completed', 'cancelled') DEFAULT 'new',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

-- Новости
CREATE TABLE news (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255),
    short_content TEXT,
    full_content TEXT,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO news (title, short_content, full_content) VALUES 
('Расширение ассортимента запчастей', 'В наш каталог добавлено более 500 новых позиций', 'Мы рады сообщить о расширении складской программы.'),
('Скидки на ремонт гидравлики', 'Специальное предложение - скидка 15%', 'Предлагаем специальные условия на диагностику и ремонт.'),
('Открытие нового сервисного центра', 'Новый сервисный центр начал работу', 'Расширяем географию присутствия.');