USE sports_items_shop;

DROP TEMPORARY TABLE IF EXISTS ranked_brand_products;

CREATE TEMPORARY TABLE ranked_brand_products AS
SELECT
    p.id,
    c.name AS category,
    b.name AS brand,
    ROW_NUMBER() OVER (PARTITION BY p.category_id, p.brand_id ORDER BY p.id) AS brand_category_number
FROM products p
JOIN categories c ON c.id = p.category_id
JOIN brands b ON b.id = p.brand_id;

UPDATE products p
JOIN ranked_brand_products r ON r.id = p.id
SET
    p.name = CASE r.category
        WHEN 'Football' THEN CONCAT(r.brand, ' ', CASE ((r.brand_category_number - 1) MOD 8) + 1
            WHEN 1 THEN 'UEFA Champions League Match Ball'
            WHEN 2 THEN 'Premier League Strike Football'
            WHEN 3 THEN 'FIFA Quality Pro Match Ball'
            WHEN 4 THEN 'Training Size 5 Football'
            WHEN 5 THEN 'Street Football Ball'
            WHEN 6 THEN 'Club Matchday Football'
            WHEN 7 THEN 'Thermo Bonded Football'
            ELSE 'Pro Control Football'
        END)
        WHEN 'Gym Equipment' THEN CONCAT(r.brand, ' ', CASE ((r.brand_category_number - 1) MOD 8) + 1
            WHEN 1 THEN 'Agility Ladder Set'
            WHEN 2 THEN 'Speed Training Cones'
            WHEN 3 THEN 'Adjustable Speed Hurdles'
            WHEN 4 THEN 'Resistance Band Kit'
            WHEN 5 THEN 'Marker Disc Set'
            WHEN 6 THEN 'Training Bib Pack'
            WHEN 7 THEN 'Reaction Ball Trainer'
            ELSE 'Football Training Pole Set'
        END)
        WHEN 'Accessories' THEN CONCAT(r.brand, ' ', CASE ((r.brand_category_number - 1) MOD 8) + 1
            WHEN 1 THEN 'Carbon Shin Guards'
            WHEN 2 THEN 'Captain Armband'
            WHEN 3 THEN 'Grip Football Socks'
            WHEN 4 THEN 'Boot Bag'
            WHEN 5 THEN 'Sports Water Bottle'
            WHEN 6 THEN 'Goalkeeper Towel'
            WHEN 7 THEN 'Boot Stud Key'
            ELSE 'Football Pump'
        END)
        ELSE p.name
    END
WHERE r.category IN ('Football', 'Gym Equipment', 'Accessories');

DROP TEMPORARY TABLE IF EXISTS ranked_brand_products;
