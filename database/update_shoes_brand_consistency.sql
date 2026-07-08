USE sports_items_shop;

DROP TEMPORARY TABLE IF EXISTS ranked_shoes;

CREATE TEMPORARY TABLE ranked_shoes AS
SELECT
    p.id,
    b.name AS brand,
    ROW_NUMBER() OVER (ORDER BY p.id) AS global_number,
    ROW_NUMBER() OVER (PARTITION BY p.brand_id ORDER BY p.id) AS brand_number
FROM products p
JOIN categories c ON c.id = p.category_id
JOIN brands b ON b.id = p.brand_id
WHERE c.name = 'Shoes';

UPDATE products p
JOIN ranked_shoes r ON r.id = p.id
SET
    p.name = CASE r.brand
        WHEN 'Nike' THEN CONCAT('Nike ', CASE ((r.brand_number - 1) MOD 6) + 1
            WHEN 1 THEN 'Mercurial Vapor FG Football Boots'
            WHEN 2 THEN 'Phantom GX Elite Football Boots'
            WHEN 3 THEN 'Tiempo Legend Elite Football Boots'
            WHEN 4 THEN 'Zoom Superfly Academy Boots'
            WHEN 5 THEN 'Premier III FG Football Boots'
            ELSE 'Phantom Luna FG Football Boots'
        END)
        WHEN 'Adidas' THEN CONCAT('Adidas ', CASE ((r.brand_number - 1) MOD 6) + 1
            WHEN 1 THEN 'Predator Accuracy FG Football Boots'
            WHEN 2 THEN 'X Crazyfast FG Football Boots'
            WHEN 3 THEN 'Copa Pure Leather Football Boots'
            WHEN 4 THEN 'F50 Elite FG Football Boots'
            WHEN 5 THEN 'Nemeziz Messi FG Football Boots'
            ELSE 'Ace Control FG Football Boots'
        END)
        WHEN 'Puma' THEN CONCAT('Puma ', CASE ((r.brand_number - 1) MOD 6) + 1
            WHEN 1 THEN 'Future Ultimate FG Football Boots'
            WHEN 2 THEN 'Ultra Ultimate FG Football Boots'
            WHEN 3 THEN 'King Ultimate FG Football Boots'
            WHEN 4 THEN 'Future Match Football Boots'
            WHEN 5 THEN 'Ultra Match FG Football Boots'
            ELSE 'King Pro FG Football Boots'
        END)
        ELSE CONCAT('Umbro ', CASE ((r.brand_number - 1) MOD 6) + 1
            WHEN 1 THEN 'Velocita Elixir FG Football Boots'
            WHEN 2 THEN 'Tocco Pro FG Football Boots'
            WHEN 3 THEN 'Speciali Pro Football Boots'
            WHEN 4 THEN 'UX Accuro Premier Boots'
            WHEN 5 THEN 'Medusae Elite FG Football Boots'
            ELSE 'Classico XI FG Football Boots'
        END)
    END,
    p.sku = CONCAT('SHO-', LPAD(r.global_number, 3, '0'), '-', CASE r.brand
        WHEN 'Nike' THEN 'NIK'
        WHEN 'Adidas' THEN 'ADI'
        WHEN 'Puma' THEN 'PUM'
        ELSE 'UMB'
    END),
    p.image = CONCAT('assets/images/products/shoes/shoes-', LPAD(r.global_number, 3, '0'), '.jpg'),
    p.description = CONCAT(r.brand, ' football boots with a clean product image, firm-ground traction, comfortable fit, and match-ready control for football players.');

DROP TEMPORARY TABLE IF EXISTS ranked_shoes;
