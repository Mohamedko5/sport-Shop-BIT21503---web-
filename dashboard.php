<?php
$pageTitle = 'Dashboard | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();

if (isAdmin() && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = $_POST['status'];
    if ($orderId && in_array($status, ['Pending', 'Processing', 'Completed', 'Cancelled'], true)) {
        $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
        $stmt->execute([$status, $orderId]);
        header('Location: dashboard.php');
        exit;
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';

if (isAdmin()) {
    $stats = [
        'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
        'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'sales' => (float) $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE status <> 'Cancelled'")->fetchColumn(),
        'low_stock' => (int) $pdo->query('SELECT COUNT(*) FROM products WHERE stock <= 8')->fetchColumn(),
    ];
    $products = $pdo->query(
        'SELECT products.*, categories.name AS category, brands.name AS brand
         FROM products
         JOIN categories ON products.category_id = categories.id
         JOIN brands ON products.brand_id = brands.id
         ORDER BY products.created_at DESC'
    )->fetchAll();
    $lowStockProducts = $pdo->query(
        'SELECT products.name, products.sku, products.stock, categories.name AS category
         FROM products
         JOIN categories ON products.category_id = categories.id
         WHERE products.stock <= 8
         ORDER BY products.stock ASC, products.name ASC
         LIMIT 10'
    )->fetchAll();
    $orders = $pdo->query('SELECT orders.*, users.name FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.created_at DESC')->fetchAll();
} else {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$_SESSION['user_id']]);
    $orders = $stmt->fetchAll();
}
?>

<main class="container page">
    <section class="section-heading">
        <h1>Dashboard</h1>
        <p>Welcome, <?php echo cleanInput($_SESSION['name']); ?>.</p>
    </section>

    <?php if (!empty($_SESSION['delete_error'])): ?>
        <div class="alert error"><?php echo cleanInput($_SESSION['delete_error']); unset($_SESSION['delete_error']); ?></div>
    <?php endif; ?>

    <?php if (isAdmin()): ?>
        <div class="stats-grid">
            <div class="stat-card"><span>Total Products</span><strong><?php echo $stats['products']; ?></strong></div>
            <div class="stat-card"><span>Total Orders</span><strong><?php echo $stats['orders']; ?></strong></div>
            <div class="stat-card"><span>Sales</span><strong>RM <?php echo number_format($stats['sales'], 2); ?></strong></div>
            <div class="stat-card"><span>Low Stock</span><strong><?php echo $stats['low_stock']; ?></strong></div>
        </div>

        <div class="dashboard-actions">
            <a class="btn" href="add-product.php">Add Product</a>
            <a class="btn btn-outline" href="manage-categories.php">Manage Categories</a>
        </div>

        <?php if (!empty($lowStockProducts)): ?>
            <section class="table-section">
                <h2>Low Stock Alerts</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th>Category</th>
                                <th>Stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lowStockProducts as $product): ?>
                                <tr>
                                    <td><?php echo cleanInput($product['name']); ?></td>
                                    <td><?php echo cleanInput($product['sku']); ?></td>
                                    <td><?php echo cleanInput($product['category']); ?></td>
                                    <td><strong class="danger"><?php echo (int) $product['stock']; ?></strong></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        <?php endif; ?>

        <section class="table-section">
            <h2>Product Management</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>SKU</th>
                            <th>Brand</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Stock</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?php echo cleanInput($product['name']); ?></td>
                                <td><?php echo cleanInput($product['sku']); ?></td>
                                <td><?php echo cleanInput($product['brand']); ?></td>
                                <td><?php echo cleanInput($product['category']); ?></td>
                                <td>RM <?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo (int) $product['stock']; ?></td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>">Edit</a>
                                    <a class="danger" href="delete-product.php?id=<?php echo $product['id']; ?>" onclick="return confirm('Delete this product?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="table-section">
            <h2>Recent Orders</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo (int) $order['id']; ?></td>
                                <td><?php echo cleanInput($order['name']); ?></td>
                                <td>RM <?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo cleanInput($order['status']); ?></td>
                                <td><?php echo cleanInput($order['created_at']); ?></td>
                                <td>
                                    <form method="POST" class="inline-form">
                                        <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                        <select name="status">
                                            <?php foreach (['Pending', 'Processing', 'Completed', 'Cancelled'] as $status): ?>
                                                <option value="<?php echo cleanInput($status); ?>" <?php echo $order['status'] === $status ? 'selected' : ''; ?>><?php echo cleanInput($status); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button class="btn-small" type="submit">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php else: ?>
        <section class="table-section">
            <h2>My Orders</h2>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo (int) $order['id']; ?></td>
                                <td>RM <?php echo number_format($order['total_price'], 2); ?></td>
                                <td><?php echo cleanInput($order['status']); ?></td>
                                <td><?php echo cleanInput($order['created_at']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php'; ?>
