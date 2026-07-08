USE sports_items_shop;

ALTER TABLE orders
    MODIFY order_status ENUM('Pending', 'Confirmed', 'Shipped', 'Delivered', 'Rejected', 'Failed') NOT NULL DEFAULT 'Pending',
    ADD COLUMN IF NOT EXISTS shipped_at DATETIME NULL AFTER estimated_delivery_date,
    ADD COLUMN IF NOT EXISTS delivered_at DATETIME NULL AFTER shipped_at;
