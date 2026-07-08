USE sports_items_shop;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS order_status ENUM('Pending', 'Confirmed', 'Rejected', 'Failed') NOT NULL DEFAULT 'Pending' AFTER status,
    ADD COLUMN IF NOT EXISTS admin_note TEXT NULL AFTER order_status,
    ADD COLUMN IF NOT EXISTS status_updated_at TIMESTAMP NULL AFTER admin_note;

UPDATE orders
SET order_status = CASE status
    WHEN 'Completed' THEN 'Confirmed'
    WHEN 'Cancelled' THEN 'Rejected'
    ELSE 'Pending'
END
WHERE order_status = 'Pending';
