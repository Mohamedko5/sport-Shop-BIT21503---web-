USE sports_items_shop;

CREATE TABLE IF NOT EXISTS brands (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO brands (id, name) VALUES
(1, 'Nike'),
(2, 'Adidas'),
(3, 'Puma'),
(6, 'Umbro')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO categories (id, name) VALUES
(1, 'Football'),
(2, 'Shoes'),
(3, 'Jerseys'),
(4, 'Gym Equipment'),
(5, 'Accessories')
ON DUPLICATE KEY UPDATE name = VALUES(name);

ALTER TABLE products
ADD COLUMN brand_id INT NULL AFTER category_id;

UPDATE products
SET category_id = CASE
    WHEN name LIKE '%Boot%' OR name LIKE '%Shoe%' THEN 2
    WHEN name LIKE '%Jersey%' THEN 3
    WHEN name LIKE '%Training%' OR name LIKE '%Cone%' OR name LIKE '%Ladder%' THEN 4
    WHEN name LIKE '%Bag%' OR name LIKE '%Sock%' OR name LIKE '%Guard%' OR name LIKE '%Glove%' OR name LIKE '%Accessory%' THEN 5
    ELSE 1
END;

DELETE FROM categories WHERE id > 5;

UPDATE products
SET brand_id = CASE
    WHEN brand = 'Nike' THEN 1
    WHEN brand = 'Adidas' THEN 2
    WHEN brand = 'Puma' THEN 3
    WHEN brand = 'Umbro' THEN 6
    WHEN brand = 'New Balance' THEN 1
    WHEN brand = 'Mizuno' THEN 2
    WHEN brand = 'Under Armour' THEN 3
    ELSE 1
END
WHERE brand_id IS NULL;

ALTER TABLE products
MODIFY brand_id INT NOT NULL;

UPDATE products SET brand_id = 1 WHERE brand_id = 4;
UPDATE products SET brand_id = 2 WHERE brand_id = 5;
UPDATE products SET brand_id = 3 WHERE brand_id = 7;

DELETE FROM brands WHERE id NOT IN (1, 2, 3, 6);
