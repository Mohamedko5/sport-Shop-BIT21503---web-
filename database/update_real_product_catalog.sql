USE sports_items_shop;

DROP TEMPORARY TABLE IF EXISTS ranked_products;

CREATE TEMPORARY TABLE ranked_products AS
SELECT
    p.id,
    c.name AS category,
    b.name AS brand,
    ROW_NUMBER() OVER (PARTITION BY p.category_id ORDER BY p.id) AS product_number
FROM products p
JOIN categories c ON c.id = p.category_id
JOIN brands b ON b.id = p.brand_id;

UPDATE products p
JOIN ranked_products r ON r.id = p.id
SET
    p.name = CASE r.category
        WHEN 'Football' THEN CONCAT(r.brand, ' ', CASE ((r.product_number - 1) MOD 10) + 1
            WHEN 1 THEN 'UEFA Champions League Match Ball'
            WHEN 2 THEN 'Premier League Strike Football'
            WHEN 3 THEN 'FIFA Quality Pro Match Ball'
            WHEN 4 THEN 'Training Size 5 Football'
            WHEN 5 THEN 'Street Football Ball'
            WHEN 6 THEN 'Club Matchday Football'
            WHEN 7 THEN 'Thermo Bonded Football'
            WHEN 8 THEN 'Academy Training Ball'
            WHEN 9 THEN 'Indoor Futsal Ball'
            ELSE 'Pro Control Football'
        END)
        WHEN 'Shoes' THEN CONCAT(r.brand, ' ', CASE ((r.product_number - 1) MOD 10) + 1
            WHEN 1 THEN 'Mercurial Speed FG Football Boots'
            WHEN 2 THEN 'Predator Control FG Football Boots'
            WHEN 3 THEN 'Future Ultimate Football Boots'
            WHEN 4 THEN 'Morelia Neo FG Football Boots'
            WHEN 5 THEN 'Furon V7 Pro Football Boots'
            WHEN 6 THEN 'Ultra Match FG Football Boots'
            WHEN 7 THEN 'Copa Pure Leather Football Boots'
            WHEN 8 THEN 'Phantom GX Football Boots'
            WHEN 9 THEN 'Speciali Pro Football Boots'
            ELSE 'Magnetico Elite Football Boots'
        END)
        WHEN 'Jerseys' THEN CONCAT(CASE ((r.product_number - 1) MOD 15) + 1
            WHEN 1 THEN 'Arsenal'
            WHEN 2 THEN 'Barcelona'
            WHEN 3 THEN 'Real Madrid'
            WHEN 4 THEN 'Liverpool'
            WHEN 5 THEN 'Manchester City'
            WHEN 6 THEN 'Manchester United'
            WHEN 7 THEN 'Chelsea'
            WHEN 8 THEN 'PSG'
            WHEN 9 THEN 'Bayern Munich'
            WHEN 10 THEN 'Juventus'
            WHEN 11 THEN 'Inter Milan'
            WHEN 12 THEN 'AC Milan'
            WHEN 13 THEN 'Borussia Dortmund'
            WHEN 14 THEN 'Atletico Madrid'
            ELSE 'Tottenham'
        END, ' ', CASE ((r.product_number - 1) MOD 4) + 1
            WHEN 1 THEN 'Home Jersey'
            WHEN 2 THEN 'Away Jersey'
            WHEN 3 THEN 'Third Jersey'
            ELSE 'Training Jersey'
        END)
        WHEN 'Gym Equipment' THEN CONCAT(r.brand, ' ', CASE ((r.product_number - 1) MOD 10) + 1
            WHEN 1 THEN 'Agility Ladder Set'
            WHEN 2 THEN 'Speed Training Cones'
            WHEN 3 THEN 'Adjustable Speed Hurdles'
            WHEN 4 THEN 'Resistance Band Kit'
            WHEN 5 THEN 'Marker Disc Set'
            WHEN 6 THEN 'Training Bib Pack'
            WHEN 7 THEN 'Reaction Ball Trainer'
            WHEN 8 THEN 'Balance Board Trainer'
            WHEN 9 THEN 'Core Slider Set'
            ELSE 'Football Training Pole Set'
        END)
        ELSE CONCAT(r.brand, ' ', CASE ((r.product_number - 1) MOD 10) + 1
            WHEN 1 THEN 'Carbon Shin Guards'
            WHEN 2 THEN 'Captain Armband'
            WHEN 3 THEN 'Grip Football Socks'
            WHEN 4 THEN 'Boot Bag'
            WHEN 5 THEN 'Sports Water Bottle'
            WHEN 6 THEN 'Goalkeeper Towel'
            WHEN 7 THEN 'Boot Stud Key'
            WHEN 8 THEN 'Football Pump'
            WHEN 9 THEN 'Sock Tape Roll'
            ELSE 'Matchday Kit Bag'
        END)
    END,
    p.description = CASE r.category
        WHEN 'Jerseys' THEN 'Product-only football jersey with club-inspired colors, designed for supporters who want a clean matchday shirt for training, casual wear, and collection displays.'
        WHEN 'Shoes' THEN 'Product-only football boot image matched to this item, built for grip, speed, comfort, and confident movement on firm-ground football pitches.'
        WHEN 'Football' THEN 'Product-only football image matched to this item, suitable for training sessions, match practice, passing drills, and everyday football play.'
        WHEN 'Gym Equipment' THEN 'Product-only training equipment image matched to this item, ideal for football fitness, speed work, agility practice, and team training sessions.'
        ELSE 'Product-only football accessory image matched to this item, suitable for matchday preparation, training support, and everyday football use.'
    END,
    p.image = CASE r.category
        WHEN 'Football' THEN CONCAT('assets/images/products/football/FOO-', LPAD(r.product_number, 3, '0'), '-', UPPER(LEFT(REPLACE(r.brand, ' ', ''), 3)), '.jpg')
        WHEN 'Shoes' THEN CONCAT('assets/images/products/shoes/SHO-', LPAD(r.product_number, 3, '0'), '-', UPPER(LEFT(REPLACE(r.brand, ' ', ''), 3)), '.jpg')
        WHEN 'Jerseys' THEN CONCAT('assets/images/products/jerseys/JER-', LPAD(r.product_number, 3, '0'), '-', UPPER(LEFT(REPLACE(r.brand, ' ', ''), 3)), '.jpg')
        WHEN 'Gym Equipment' THEN CONCAT('assets/images/products/gym/GYM-', LPAD(r.product_number, 3, '0'), '-', UPPER(LEFT(REPLACE(r.brand, ' ', ''), 3)), '.jpg')
        ELSE CONCAT('assets/images/products/accessories/ACC-', LPAD(r.product_number, 3, '0'), '-', UPPER(LEFT(REPLACE(r.brand, ' ', ''), 3)), '.jpg')
    END,
    p.sku = CONCAT(CASE r.category
        WHEN 'Football' THEN 'FOO'
        WHEN 'Shoes' THEN 'SHO'
        WHEN 'Jerseys' THEN 'JER'
        WHEN 'Gym Equipment' THEN 'GYM'
        ELSE 'ACC'
    END, '-', LPAD(r.product_number, 3, '0'), '-', UPPER(LEFT(REPLACE(r.brand, ' ', ''), 3))),
    p.price = CASE r.category
        WHEN 'Football' THEN 39.00 + (r.product_number * 3)
        WHEN 'Shoes' THEN 169.00 + (r.product_number * 8)
        WHEN 'Jerseys' THEN 89.00 + (r.product_number * 4)
        WHEN 'Gym Equipment' THEN 29.00 + (r.product_number * 5)
        ELSE 19.00 + (r.product_number * 3)
    END;

DROP TEMPORARY TABLE IF EXISTS ranked_products;
