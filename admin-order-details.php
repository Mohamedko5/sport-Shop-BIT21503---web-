<?php
$pageTitle = 'Admin Order Details | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAdmin();

$validStatuses = ['Confirmed', 'Shipped', 'Rejected', 'Failed'];
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    header('Location: admin-orders.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $postOrderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = $_POST['order_status'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($postOrderId === $orderId && in_array($status, $validStatuses, true)) {
        if ($status === 'Confirmed') {
            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET order_status = ?,
                     admin_note = ?,
                     confirmed_at = NOW(),
                     estimated_delivery_date = DATE_ADD(CURDATE(), INTERVAL 3 DAY),
                     shipped_at = NULL,
                     delivered_at = NULL,
                     status_updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([$status, $adminNote !== '' ? $adminNote : null, $orderId]);
        } elseif ($status === 'Shipped') {
            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET order_status = ?,
                     admin_note = ?,
                     shipped_at = NOW(),
                     delivered_at = NULL,
                     status_updated_at = NOW()
                 WHERE id = ? AND order_status = "Confirmed"'
            );
            $stmt->execute([$status, $adminNote !== '' ? $adminNote : null, $orderId]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE orders
                 SET order_status = ?,
                     admin_note = ?,
                     estimated_delivery_date = NULL,
                     shipped_at = NULL,
                     delivered_at = NULL,
                     status_updated_at = NOW()
                 WHERE id = ?'
            );
            $stmt->execute([$status, $adminNote !== '' ? $adminNote : null, $orderId]);
        }
        header('Location: admin-order-details.php?id=' . $orderId);
        exit;
    }
}

$stmt = $pdo->prepare(
    'SELECT orders.*, users.name AS customer_name, users.email
     FROM orders
     JOIN users ON orders.user_id = users.id
     WHERE orders.id = ?'
);
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: admin-orders.php');
    exit;
}

$itemsStmt = $pdo->prepare(
    'SELECT order_items.*, products.name, products.sku, products.image
     FROM order_items
     JOIN products ON order_items.product_id = products.id
     WHERE order_items.order_id = ?
     ORDER BY order_items.id'
);
$itemsStmt->execute([$orderId]);
$items = $itemsStmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page">
    <section class="section-heading">
        <h1>Order #<?php echo (int) $order['id']; ?></h1>
        <p><span class="status-badge status-<?php echo strtolower(cleanInput($order['order_status'])); ?>"><?php echo cleanInput($order['order_status']); ?></span></p>
    </section>

    <section class="order-detail-grid">
        <div class="content-panel">
            <h2>Customer</h2>
            <p><strong>Name:</strong> <?php echo cleanInput($order['customer_name']); ?></p>
            <p><strong>Email:</strong> <?php echo cleanInput($order['email']); ?></p>
            <p><strong>Phone:</strong> <?php echo cleanInput($order['phone']); ?></p>
        </div>
        <div class="content-panel">
            <h2>Delivery</h2>
            <p><?php echo cleanInput($order['full_name']); ?></p>
            <p><?php echo cleanInput(trim($order['house_number'] . ' ' . $order['address'])); ?></p>
            <p><?php echo cleanInput($order['city']); ?> <?php echo cleanInput($order['postcode']); ?></p>
        </div>
        <div class="content-panel">
            <h2>Payment</h2>
            <p><strong>Method:</strong> <?php echo cleanInput($order['payment_method']); ?></p>
            <?php if (!empty($order['card_last4'])): ?>
                <p><strong>Card:</strong> <?php echo cleanInput($order['cardholder_name']); ?> ending <?php echo cleanInput($order['card_last4']); ?></p>
            <?php endif; ?>
            <p><strong>Total:</strong> RM <?php echo number_format($order['total_price'], 2); ?></p>
        </div>
        <div class="content-panel delivery-card">
            <h2>Delivery Tracking</h2>
            <p><strong>Confirmed At:</strong> <?php echo cleanInput($order['confirmed_at'] ?: 'Not confirmed yet'); ?></p>
            <p><strong>Estimated Delivery:</strong> <?php echo cleanInput($order['estimated_delivery_date'] ?: 'Not scheduled'); ?></p>
            <p><strong>Shipped At:</strong> <?php echo cleanInput($order['shipped_at'] ?: 'Not shipped yet'); ?></p>
            <p><strong>Delivered At:</strong> <?php echo cleanInput($order['delivered_at'] ?: 'Not delivered yet'); ?></p>
        </div>
    </section>

    <section class="content-panel">
        <h2>Update Order Status</h2>
        <p><strong>Current Status:</strong> <span class="status-badge status-<?php echo strtolower(cleanInput($order['order_status'])); ?>"><?php echo cleanInput($order['order_status']); ?></span></p>
        <?php if (!empty($order['status_updated_at'])): ?>
            <p><strong>Last Updated:</strong> <?php echo cleanInput($order['status_updated_at']); ?></p>
        <?php endif; ?>
        <form method="POST" class="admin-status-form">
            <?php echo csrfField(); ?>
            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
            <label>Admin Note
                <textarea name="admin_note" rows="3" placeholder="Optional note for this status update"><?php echo cleanInput($order['admin_note'] ?? ''); ?></textarea>
            </label>
            <div class="dashboard-actions">
                <?php
                $actions = [];
                if ($order['order_status'] === 'Pending') {
                    $actions = ['Confirmed' => 'Confirm', 'Rejected' => 'Reject', 'Failed' => 'Mark Failed'];
                } elseif ($order['order_status'] === 'Confirmed') {
                    $actions = ['Shipped' => 'Mark Shipped'];
                }
                ?>
                <?php foreach ($actions as $status => $label): ?>
                    <button class="btn status-action status-<?php echo strtolower($status); ?>" type="submit" name="order_status" value="<?php echo cleanInput($status); ?>"><?php echo cleanInput($label); ?></button>
                <?php endforeach; ?>
                <?php if ($order['order_status'] === 'Shipped'): ?>
                    <span class="muted-text">Waiting for user confirmation</span>
                <?php elseif ($order['order_status'] === 'Delivered'): ?>
                    <span class="delivery-mini delivered">Delivery successful</span>
                <?php elseif (!$actions): ?>
                    <span class="muted-text">No further admin action</span>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="table-section">
        <h2>Order Items</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>SKU</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <div class="product-cell">
                                    <img class="table-thumb" src="<?php echo cleanInput(productImage($item['image'])); ?>" alt="<?php echo cleanInput($item['name']); ?>" onerror="this.onerror=null;this.style.display='none';">
                                    <span><?php echo cleanInput($item['name']); ?></span>
                                </div>
                            </td>
                            <td><?php echo cleanInput($item['sku']); ?></td>
                            <td><?php echo (int) $item['quantity']; ?></td>
                            <td>RM <?php echo number_format($item['price'], 2); ?></td>
                            <td>RM <?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="cart-summary">
            <h2>Grand Total: RM <?php echo number_format($order['total_price'], 2); ?></h2>
            <a class="btn btn-outline" href="admin-orders.php">Back to Orders</a>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
