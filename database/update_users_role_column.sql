USE sports_items_shop;

-- Your current database already has this column.
-- Run this only if another copy of the project is missing users.role.
ALTER TABLE users
    ADD COLUMN role ENUM('admin', 'user') NOT NULL DEFAULT 'user';
