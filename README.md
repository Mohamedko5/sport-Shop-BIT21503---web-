# Football Store

Football Store is a professional football e-commerce website built with plain PHP, MySQL, HTML, CSS, and JavaScript. All products are local inventory stored in MySQL.

## Catalogue

The database seed generates 150 football products across:

- Football
- Shoes
- Jerseys
- Gym Equipment
- Accessories

Each product includes:

- Product name
- Category
- Price
- Description
- Image URL
- Stock quantity
- SKU code
- Brand

Brands:

- Nike
- Adidas
- Puma
- New Balance
- Mizuno
- Umbro
- Under Armour

Main database tables:

- `categories`
- `brands`
- `products`
- `users`
- `orders`
- `order_items`

## Features

- Customer registration, login, logout
- Product search and filtering by category or brand
- Product details with related products
- Shopping cart with update/remove actions
- Checkout with stock reduction
- Customer order history
- Admin dashboard with sales statistics
- Product CRUD
- Category management
- Inventory and low-stock tracking
- Featured products, new arrivals, and best sellers

## Database Setup

1. Start Apache and MySQL in XAMPP.
2. Open `http://localhost/phpmyadmin`.
3. Import `database/sports_items_shop.sql`.
4. Open `http://localhost/sports_shop`.

Admin login:

Email: `admin@sportsshop.com`

Password: `password`

## Testing

1. Open `products.php`.
2. Search by product name, SKU, or brand.
3. Filter by category and brand.
4. Open a product details page.
5. Add a product to cart.
6. Update or remove cart items.
7. Checkout and confirm stock reduces.
8. Login as admin.
9. Add, edit, and delete products.
10. Manage categories from the dashboard.
