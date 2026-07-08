USE sports_items_shop;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS full_name VARCHAR(100) NOT NULL DEFAULT '' AFTER user_id,
    ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NOT NULL DEFAULT '' AFTER full_name,
    ADD COLUMN IF NOT EXISTS house_number VARCHAR(50) NOT NULL DEFAULT '' AFTER phone,
    ADD COLUMN IF NOT EXISTS address TEXT NULL AFTER house_number,
    ADD COLUMN IF NOT EXISTS city VARCHAR(100) NOT NULL DEFAULT '' AFTER address,
    ADD COLUMN IF NOT EXISTS postcode VARCHAR(20) NOT NULL DEFAULT '' AFTER city,
    ADD COLUMN IF NOT EXISTS payment_method VARCHAR(50) NOT NULL DEFAULT 'Cash on Delivery' AFTER postcode;

-- The project already uses orders.status for:
-- Pending, Processing, Completed, Cancelled
