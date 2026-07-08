USE sports_items_shop;

ALTER TABLE orders
    ADD COLUMN IF NOT EXISTS cardholder_name VARCHAR(100) NULL AFTER payment_method,
    ADD COLUMN IF NOT EXISTS card_last4 VARCHAR(4) NULL AFTER cardholder_name;

-- Demo payment rule:
-- Do not store full card numbers.
-- Do not store CVV/security code.
