USE sports_items_shop;

-- Updates existing products so each category uses different internet-hosted images.
-- For matching product names such as Arsenal Jersey, Barcelona Jersey, boots, balls,
-- and training gear, run database/update_real_product_catalog.sql instead.
UPDATE products p
JOIN (
    SELECT
        id,
        category_id,
        ROW_NUMBER() OVER (PARTITION BY category_id ORDER BY id) AS image_number
    FROM products
) ranked ON ranked.id = p.id
JOIN categories c ON c.id = p.category_id
SET p.image = CASE c.name
    WHEN 'Football' THEN CONCAT('assets/images/products/football/', p.sku, '.jpg')
    WHEN 'Shoes' THEN CONCAT('assets/images/products/shoes/', p.sku, '.jpg')
    WHEN 'Jerseys' THEN CONCAT('assets/images/products/jerseys/', p.sku, '.jpg')
    WHEN 'Gym Equipment' THEN CONCAT('assets/images/products/gym/', p.sku, '.jpg')
    WHEN 'Accessories' THEN CONCAT('assets/images/products/accessories/', p.sku, '.jpg')
    ELSE 'assets/images/products/default-product.jpg'
END;
