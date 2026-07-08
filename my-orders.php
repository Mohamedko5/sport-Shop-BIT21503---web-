<?php
$pageTitle = 'My Orders | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'confirm_received') {
    verifyCsrfToken();
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    if ($orderId) {
        $stmt = $pdo->prepare(
            'UPDATE orders
             SET order_status = "Delivered",
                 delivered_at = NOW(),
                 status_updated_at = NOW()
             WHERE id = ? AND user_id = ? AND order_status = "Shipped"'
        );
        $stmt->execute([$orderId, $_SESSION['user_id']]);
    }
    header('Location: my-orders.php');
    exit;
}

$stmt = $pdo->prepare(
    'SELECT id, total_price, payment_method, order_status, estimated_delivery_date, shipped_at, delivered_at, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page">
    <section class="section-heading">
        <h1>My Orders</h1>
        <p>Track your order history and current order status.</p>
    </section>

    <?php if (empty($orders)): ?>
        <div class="content-panel">
            <p>You have not placed any orders yet.</p>
            <a class="btn" href="products.php">Shop Products</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Date</th>
                        <th>Total Amount</th>
                        <th>Payment Method</th>
                        <th>Status</th>
                        <th>Delivery Info</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo (int) $order['id']; ?></td>
                            <td><?php echo cleanInput($order['created_at']); ?></td>
                            <td>RM <?php echo number_format($order['total_price'], 2); ?></td>
                            <td><?php echo cleanInput($order['payment_method']); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower(cleanInput($order['order_status'])); ?>"><?php echo cleanInput($order['order_status']); ?></span></td>
                            <td>
                                <?php if ($order['order_status'] === 'Pending'): ?>
                                    <span class="delivery-message pending">Waiting for admin confirmation</span>
                                <?php elseif ($order['order_status'] === 'Confirmed'): ?>
                                    <span class="delivery-message confirmed">
                                        Order confirmed.<br>
                                        Delivery by: <?php echo cleanInput($order['estimated_delivery_date']); ?><br>
                                        Estimated delivery: 3 days
                                    </span>
                                <?php elseif ($order['order_status'] === 'Shipped'): ?>
                                    <span class="delivery-message shipped">Your order is on the way.</span>
                                    <form method="POST" class="inline-form confirm-received-form">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="action" value="confirm_received">
                                        <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                        <button class="btn-small status-action status-delivered" type="submit">Confirm Received</button>
                                    </form>
                                <?php elseif ($order['order_status'] === 'Delivered'): ?>
                                    <span class="delivery-message delivered">Delivery successful.</span>
                                <?php elseif ($order['order_status'] === 'Rejected'): ?>
                                    <span class="delivery-message rejected">Order rejected</span>
                                <?php else: ?>
                                    <span class="delivery-message failed">Order failed</span>
                                <?php endif; ?>
                            </td>
                            <td><a class="btn-small" href="order-details.php?id=<?php echo (int) $order['id']; ?>">View Details</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php'; ?>
