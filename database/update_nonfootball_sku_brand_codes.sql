USE sports_items_shop;

DROP TEMPORARY TABLE IF EXISTS ranked_nonfootball_products;

CREATE TEMPORARY TABLE ranked_nonfootball_products AS
SELECT
    p.id,
    c.name AS category,
    b.name AS brand,
    ROW_NUMBER() OVER (PARTITION BY p.category_id ORDER BY p.id) AS category_number
FROM products p
JOIN categories c ON c.id = p.category_id
JOIN brands b ON b.id = p.brand_id
WHERE c.name <> 'Football';

UPDATE products p
JOIN ranked_nonfootball_products r ON r.id = p.id
SET p.sku = CONCAT(CASE r.category
    WHEN 'Shoes' THEN 'SHO'
    WHEN 'Jerseys' THEN 'JER'
    WHEN 'Gym Equipment' THEN 'GYM'
    ELSE 'ACC'
END, '-', LPAD(r.category_number, 3, '0'), '-', CASE r.brand
    WHEN 'Nike' THEN 'NIK'
    WHEN 'Adidas' THEN 'ADI'
    WHEN 'Puma' THEN 'PUM'
    ELSE 'UMB'
END);

DROP TEMPORARY TABLE IF EXISTS ranked_nonfootball_products;
