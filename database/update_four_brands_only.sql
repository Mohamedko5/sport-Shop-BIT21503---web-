USE sports_items_shop;

-- Keep only these four brands in the store: Nike, Adidas, Puma, Umbro.
INSERT INTO brands (id, name) VALUES
(1, 'Nike'),
(2, 'Adidas'),
(3, 'Puma'),
(6, 'Umbro')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- Reassign products from removed brands before deleting those brand rows.
UPDATE products SET brand_id = 1 WHERE brand_id = 4; -- New Balance -> Nike
UPDATE products SET brand_id = 2 WHERE brand_id = 5; -- Mizuno -> Adidas
UPDATE products SET brand_id = 3 WHERE brand_id = 7; -- Under Armour -> Puma

DELETE FROM brands WHERE id NOT IN (1, 2, 3, 6);
