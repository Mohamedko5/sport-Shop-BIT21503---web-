USE sports_items_shop;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS confirmed_at DATETIME NULL AFTER status_updated_at,
    ADD COLUMN IF NOT EXISTS estimated_delivery_date DATE NULL AFTER confirmed_at,
    ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL AFTER estimated_delivery_date;
