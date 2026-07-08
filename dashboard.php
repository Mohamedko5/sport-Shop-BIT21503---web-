<?php
$pageTitle = 'Dashboard | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['order_status'])) {
    verifyCsrfToken();
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = $_POST['order_status'];
    if ($orderId && in_array($status, ['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Rejected', 'Failed'], true)) {
        if ($status === 'Confirmed') {
            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET order_status = ?,
                     confirmed_at = NOW(),
                     estimated_delivery_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY),
                     shipped_at = NULL,
                     delivered_at = NULL,
                     status_updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([$status, $orderId]);
        } elseif ($status === 'Shipped') {
            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET order_status = ?,
                     shipped_at = NOW(),
                     delivered_at = NULL,
                     status_updated_at = NOW()
                 WHERE id = ? AND order_status = "Confirmed"'
            );
            $stmt->execute([$status, $orderId]);
        } elseif ($status === 'Delivered') {
            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET order_status = ?,
                     delivered_at = NOW(),
                     status_updated_at = NOW()
                 WHERE id = ? AND order_status = "Shipped"'
            );
            $stmt->execute([$status, $orderId]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET order_status = ?,
                     estimated_delivery_date = NULL,
                     shipped_at = NULL,
                     delivered_at = NULL,
                     status_updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([$status, $orderId]);
        }
        header('Location: dashboard.php');
        exit;
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';

$stats = [
    'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    'orders' => (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'sales' => (float) $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE order_status = 'Confirmed'")->fetchColumn(),
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
?>

<main class="container page">
    <section class="section-heading">
        <h1>Dashboard</h1>
        <p>Welcome, <?php echo cleanInput($_SESSION['name']); ?>.</p>
    </section>

    <?php if (!empty($_SESSION['delete_error'])): ?>
        <div class="alert error"><?php echo cleanInput($_SESSION['delete_error']); unset($_SESSION['delete_error']); ?></div>
    <?php endif; ?>

    <div class="stats-grid">
            <div class="stat-card"><span>Total Products</span><strong><?php echo $stats['products']; ?></strong></div>
            <div class="stat-card"><span>Total Orders</span><strong><?php echo $stats['orders']; ?></strong></div>
            <div class="stat-card"><span>Sales</span><strong>RM <?php echo number_format($stats['sales'], 2); ?></strong></div>
            <div class="stat-card"><span>Low Stock</span><strong><?php echo $stats['low_stock']; ?></strong></div>
        </div>

        <div class="dashboard-actions">
            <a class="btn" href="add-product.php">Add Product</a>
            <a class="btn btn-outline" href="manage-categories.php">Manage Categories</a>
            <a class="btn btn-outline" href="admin-orders.php">Manage Orders</a>
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
                            <th>Product</th>
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
                                <td>
                                    <div class="product-cell">
                                        <img class="table-thumb" src="<?php echo cleanInput(productImage($product['image'])); ?>" alt="<?php echo cleanInput($product['name']); ?>" onerror="this.onerror=null;this.src='assets/images/products/default-product.jpg';">
                                        <span>
                                            <?php echo cleanInput($product['name']); ?>
                                            <?php if (!productHasPublicImage($product['image'])): ?>
                                                <em class="missing-image-badge">Missing Image</em>
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </td>
                                <td><?php echo cleanInput($product['sku']); ?></td>
                                <td><?php echo cleanInput($product['brand']); ?></td>
                                <td><?php echo cleanInput($product['category']); ?></td>
                                <td>RM <?php echo number_format($product['price'], 2); ?></td>
                                <td><?php echo (int) $product['stock']; ?></td>
                                <td>
                                    <a href="edit-product.php?id=<?php echo $product['id']; ?>">Edit</a>
                                    <form method="POST" action="delete-product.php" class="inline-form" onsubmit="return confirm('Delete this product?')">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="id" value="<?php echo (int) $product['id']; ?>">
                                        <button class="link-danger" type="submit">Delete</button>
                                    </form>
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
                                <td><span class="status-badge status-<?php echo strtolower(cleanInput($order['order_status'])); ?>"><?php echo cleanInput($order['order_status']); ?></span></td>
                                <td><?php echo cleanInput($order['created_at']); ?></td>
                                <td>
                                    <form method="POST" class="inline-form">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                        <select name="order_status">
                                            <?php foreach (['Pending', 'Confirmed', 'Shipped', 'Delivered', 'Rejected', 'Failed'] as $status): ?>
                                                <option value="<?php echo cleanInput($status); ?>" <?php echo $order['order_status'] === $status ? 'selected' : ''; ?>><?php echo cleanInput($status); ?></option>
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
</main>

<?php require_once 'includes/footer.php'; ?>
