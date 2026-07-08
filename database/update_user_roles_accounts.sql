USE sports_items_shop;

-- Run this only if your users table does not already have a role column.
-- ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user';

UPDATE users
SET role = 'user'
WHERE role IS NULL OR role NOT IN ('admin', 'user');

INSERT INTO users (name, email, password, role)
VALUES
('Store Admin', 'admin@sportsshop.com', '$2y$10$q3F7HuFKujcdl9GPDfOuuOw0NP4rH8KJOefjvmi/hscoIUXEbBK/.', 'admin'),
('Customer User', 'user@sportsshop.com', '$2y$10$q3F7HuFKujcdl9GPDfOuuOw0NP4rH8KJOefjvmi/hscoIUXEbBK/.', 'user')
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    password = VALUES(password),
    role = VALUES(role);
