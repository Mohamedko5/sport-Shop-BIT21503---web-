<?php
$pageTitle = 'Order Details | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_received') {
    verifyCsrfToken();
    $postOrderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    if ($postOrderId) {
        $stmt = $pdo->prepare(
            'UPDATE orders
             SET order_status = "Delivered",
                 delivered_at = NOW(),
                 status_updated_at = NOW()
             WHERE id = ? AND user_id = ? AND order_status = "Shipped"'
        );
        $stmt->execute([$postOrderId, $_SESSION['user_id']]);
    }
    header('Location: order-details.php?id=' . (int) $postOrderId);
    exit;
}

$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$orderId) {
    header('Location: my-orders.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT *
     FROM orders
     WHERE id = ? AND user_id = ?'
);
$stmt->execute([$orderId, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: my-orders.php');
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
        <div class="content-panel tracking-card">
            <h2>Order Tracking</h2>
            <div class="tracking-steps">
                <span class="tracking-step active">Pending</span>
                <span class="tracking-line <?php echo in_array($order['order_status'], ['Confirmed', 'Shipped', 'Delivered'], true) ? 'active' : ''; ?>"></span>
                <span class="tracking-step <?php echo in_array($order['order_status'], ['Confirmed', 'Shipped', 'Delivered'], true) ? 'active' : ''; ?>">Confirmed</span>
                <span class="tracking-line <?php echo in_array($order['order_status'], ['Shipped', 'Delivered'], true) ? 'active' : ''; ?>"></span>
                <span class="tracking-step <?php echo in_array($order['order_status'], ['Shipped', 'Delivered'], true) ? 'active delivery' : ''; ?>">Shipped</span>
                <span class="tracking-line <?php echo $order['order_status'] === 'Delivered' ? 'active' : ''; ?>"></span>
                <span class="tracking-step <?php echo $order['order_status'] === 'Delivered' ? 'active delivery' : ''; ?>">Delivered</span>
            </div>

            <?php if ($order['order_status'] === 'Pending'): ?>
                <p class="delivery-message pending">Your order is waiting for admin confirmation.</p>
            <?php elseif ($order['order_status'] === 'Confirmed'): ?>
                <div class="delivery-date-card">
                    <span>Your order has been confirmed.</span>
                    <strong>Delivery by: <?php echo cleanInput($order['estimated_delivery_date']); ?></strong>
                    <small>Estimated delivery time: 3 days.</small>
                </div>
            <?php elseif ($order['order_status'] === 'Shipped'): ?>
                <div class="delivery-date-card">
                    <span>Your order is on the way.</span>
                    <strong>Shipped at: <?php echo cleanInput($order['shipped_at']); ?></strong>
                    <small>Please confirm after receiving your order.</small>
                </div>
                <form method="POST" class="confirm-received-form">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="action" value="confirm_received">
                    <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                    <button class="btn status-action status-delivered" type="submit">Confirm Received</button>
                </form>
            <?php elseif ($order['order_status'] === 'Delivered'): ?>
                <div class="delivery-date-card delivered">
                    <span>Delivery successful.</span>
                    <strong>Delivered at: <?php echo cleanInput($order['delivered_at']); ?></strong>
                </div>
            <?php elseif ($order['order_status'] === 'Rejected'): ?>
                <p class="delivery-message rejected">Your order has been rejected by admin.</p>
            <?php else: ?>
                <p class="delivery-message failed">Order failed.</p>
            <?php endif; ?>
        </div>

        <div class="content-panel">
            <h2>Delivery Information</h2>
            <p><strong>Name:</strong> <?php echo cleanInput($order['full_name']); ?></p>
            <p><strong>Phone:</strong> <?php echo cleanInput($order['phone']); ?></p>
            <p><strong>Address:</strong> <?php echo cleanInput(trim($order['house_number'] . ' ' . $order['address'])); ?></p>
            <p><strong>City:</strong> <?php echo cleanInput($order['city']); ?></p>
            <p><strong>Postcode:</strong> <?php echo cleanInput($order['postcode']); ?></p>
        </div>
        <div class="content-panel">
            <h2>Payment</h2>
            <p><strong>Method:</strong> <?php echo cleanInput($order['payment_method']); ?></p>
            <?php if (!empty($order['card_last4'])): ?>
                <p><strong>Card:</strong> <?php echo cleanInput($order['cardholder_name']); ?> ending <?php echo cleanInput($order['card_last4']); ?></p>
            <?php endif; ?>
            <p><strong>Date:</strong> <?php echo cleanInput($order['created_at']); ?></p>
            <?php if (!empty($order['admin_note'])): ?>
                <p><strong>Admin Note:</strong> <?php echo cleanInput($order['admin_note']); ?></p>
            <?php endif; ?>
        </div>
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
            <a class="btn btn-outline" href="my-orders.php">Back to My Orders</a>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
