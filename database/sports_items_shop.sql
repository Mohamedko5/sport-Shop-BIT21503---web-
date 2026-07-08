CREATE DATABASE IF NOT EXISTS sports_items_shop;
USE sports_items_shop;

DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS brands;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(180) NOT NULL,
    category_id INT NOT NULL,
    brand_id INT NOT NULL,
    sku VARCHAR(50) NOT NULL UNIQUE,
    price DECIMAL(10, 2) NOT NULL,
    description TEXT NOT NULL,
    image VARCHAR(700) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    is_featured TINYINT(1) NOT NULL DEFAULT 0,
    is_new_arrival TINYINT(1) NOT NULL DEFAULT 0,
    is_best_seller TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE RESTRICT
);

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    status ENUM('Pending', 'Processing', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Admin password: password
INSERT INTO users (name, email, password, role) VALUES
('Store Admin', 'admin@sportsshop.com', '$2y$10$BIVIjmwkdPWwE2xOiWULn.pWOjccHsvJgwuPqWQgpeCILuKp6XVAq', 'admin');

INSERT INTO categories (id, name) VALUES
(1, 'Football'),
(2, 'Shoes'),
(3, 'Jerseys'),
(4, 'Gym Equipment'),
(5, 'Accessories');

INSERT INTO brands (id, name) VALUES
(1, 'Nike'),
(2, 'Adidas'),
(3, 'Puma'),
(6, 'Umbro');

DELIMITER //
CREATE PROCEDURE seed_football_products()
BEGIN
    DECLARE i INT DEFAULT 1;
    DECLARE cat_id INT;
    DECLARE brand_id_value INT;
    DECLARE brand_name VARCHAR(30);
    DECLARE brand_code VARCHAR(3);
    DECLARE cat_name VARCHAR(80);
    DECLARE cat_prefix VARCHAR(3);
    DECLARE cat_folder VARCHAR(30);
    DECLARE base_name VARCHAR(80);
    DECLARE variant_name VARCHAR(80);
    DECLARE product_sku VARCHAR(50);
    DECLARE image_url VARCHAR(700);
    DECLARE image_number INT;
    DECLARE product_price DECIMAL(10,2);
    DECLARE product_stock INT;

    WHILE i <= 150 DO
        SET cat_id = ((i - 1) MOD 5) + 1;
        SET brand_id_value = CASE ((i - 1) MOD 4) + 1
            WHEN 1 THEN 1
            WHEN 2 THEN 2
            WHEN 3 THEN 3
            ELSE 6
        END;
        SET brand_name = CASE brand_id_value
            WHEN 1 THEN 'Nike'
            WHEN 2 THEN 'Adidas'
            WHEN 3 THEN 'Puma'
            ELSE 'Umbro'
        END;
        SET brand_code = CASE brand_id_value
            WHEN 1 THEN 'NIK'
            WHEN 2 THEN 'ADI'
            WHEN 3 THEN 'PUM'
            ELSE 'UMB'
        END;
        SET cat_name = CASE cat_id
            WHEN 1 THEN 'Football'
            WHEN 2 THEN 'Shoes'
            WHEN 3 THEN 'Jerseys'
            WHEN 4 THEN 'Gym Equipment'
            ELSE 'Accessories'
        END;
        SET cat_prefix = CASE cat_id
            WHEN 1 THEN 'FOO'
            WHEN 2 THEN 'SHO'
            WHEN 3 THEN 'JER'
            WHEN 4 THEN 'GYM'
            ELSE 'ACC'
        END;
        SET cat_folder = CASE cat_id
            WHEN 1 THEN 'football'
            WHEN 2 THEN 'shoes'
            WHEN 3 THEN 'jerseys'
            WHEN 4 THEN 'gym'
            ELSE 'accessories'
        END;
        SET base_name = CASE cat_id
            WHEN 1 THEN 'Size 5 Match Football'
            WHEN 2 THEN 'FG Football Shoes'
            WHEN 3 THEN 'Performance Football Jersey'
            WHEN 4 THEN 'Football Training Equipment'
            ELSE 'Football Accessory'
        END;
        SET variant_name = CASE ((i - 1) MOD 15) + 1
            WHEN 1 THEN 'Elite'
            WHEN 2 THEN 'Pro'
            WHEN 3 THEN 'Academy'
            WHEN 4 THEN 'Control'
            WHEN 5 THEN 'Speed'
            WHEN 6 THEN 'Classic'
            WHEN 7 THEN 'Strike'
            WHEN 8 THEN 'Heritage'
            WHEN 9 THEN 'Premier'
            WHEN 10 THEN 'Club'
            WHEN 11 THEN 'Training'
            WHEN 12 THEN 'Matchday'
            WHEN 13 THEN 'Vapor'
            WHEN 14 THEN 'Precision'
            ELSE 'Future'
        END;
        SET image_number = FLOOR((i - 1) / 5) + 1;
        SET product_sku = CONCAT(cat_prefix, '-', LPAD(image_number, 3, '0'), '-', brand_code);
        SET image_url = CONCAT('assets/images/products/', cat_folder, '/', product_sku, '.jpg');
        SET product_price = CASE cat_id
            WHEN 1 THEN 49 + ((i MOD 8) * 12)
            WHEN 2 THEN 179 + ((i MOD 9) * 20)
            WHEN 3 THEN 89 + ((i MOD 7) * 15)
            WHEN 4 THEN 25 + ((i MOD 10) * 12)
            ELSE 19 + ((i MOD 8) * 9)
        END;
        SET product_stock = 5 + ((i * 7) MOD 58);

        INSERT INTO products (
            name, category_id, brand_id, sku, price, description, image, stock,
            is_featured, is_new_arrival, is_best_seller
        ) VALUES (
            CONCAT(brand_name, ' ', variant_name, ' ', base_name, ' ', LPAD(i, 3, '0')),
            cat_id,
            brand_id_value,
            product_sku,
            product_price,
            CONCAT('Professional ', LOWER(cat_name), ' from ', brand_name, ' built for football players who need durable match-ready performance, reliable comfort, and a clean modern look.'),
            image_url,
            product_stock,
            IF(i MOD 6 = 0, 1, 0),
            IF(i MOD 5 = 0, 1, 0),
            IF(i MOD 7 = 0, 1, 0)
        );

        SET i = i + 1;
    END WHILE;
END//
DELIMITER ;

CALL seed_football_products();
DROP PROCEDURE seed_football_products;
