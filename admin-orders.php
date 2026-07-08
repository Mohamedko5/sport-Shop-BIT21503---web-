<?php
$pageTitle = 'Manage Orders | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAdmin();

$validStatuses = ['Confirmed', 'Shipped', 'Rejected', 'Failed'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $orderId = filter_input(INPUT_POST, 'order_id', FILTER_VALIDATE_INT);
    $status = $_POST['order_status'] ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($orderId && in_array($status, $validStatuses, true)) {
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
        header('Location: admin-orders.php');
        exit;
    }
}

$orders = $pdo->query(
    'SELECT orders.*, users.name AS customer_name, users.email
     FROM orders
     JOIN users ON orders.user_id = users.id
     ORDER BY orders.created_at DESC'
)->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page">
    <section class="section-heading">
        <h1>Manage Orders</h1>
        <p>Confirm, reject, or mark failed customer orders.</p>
    </section>

    <?php if (empty($orders)): ?>
        <div class="content-panel">
            <p>No customer orders yet.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Delivery</th>
                        <th>Confirmed At</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>#<?php echo (int) $order['id']; ?></td>
                            <td>
                                <strong><?php echo cleanInput($order['customer_name']); ?></strong><br>
                                <span><?php echo cleanInput($order['email']); ?></span>
                            </td>
                            <td><?php echo cleanInput($order['phone']); ?></td>
                            <td>RM <?php echo number_format($order['total_price'], 2); ?></td>
                            <td><?php echo cleanInput($order['payment_method']); ?></td>
                            <td><span class="status-badge status-<?php echo strtolower(cleanInput($order['order_status'])); ?>"><?php echo cleanInput($order['order_status']); ?></span></td>
                            <td>
                                <?php if ($order['order_status'] === 'Confirmed' && !empty($order['estimated_delivery_date'])): ?>
                                    <span class="delivery-mini">By <?php echo cleanInput($order['estimated_delivery_date']); ?></span>
                                <?php elseif ($order['order_status'] === 'Shipped'): ?>
                                    <span class="delivery-mini shipped">Shipped <?php echo cleanInput($order['shipped_at'] ?: ''); ?></span>
                                <?php elseif ($order['order_status'] === 'Delivered'): ?>
                                    <span class="delivery-mini delivered">Delivered <?php echo cleanInput($order['delivered_at'] ?: ''); ?></span>
                                <?php else: ?>
                                    <span class="muted-text">Not scheduled</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo cleanInput($order['confirmed_at'] ?: '-'); ?></td>
                            <td><?php echo cleanInput($order['created_at']); ?></td>
                            <td>
                                <div class="order-actions">
                                    <a class="btn-small" href="admin-order-details.php?id=<?php echo (int) $order['id']; ?>">View Details</a>
                                    <?php
                                    $actions = [];
                                    if ($order['order_status'] === 'Pending') {
                                        $actions = ['Confirmed' => 'Confirm', 'Rejected' => 'Reject', 'Failed' => 'Mark Failed'];
                                    } elseif ($order['order_status'] === 'Confirmed') {
                                        $actions = ['Shipped' => 'Mark Shipped'];
                                    }
                                    ?>
                                    <?php foreach ($actions as $status => $label): ?>
                                        <form method="POST" class="inline-form">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                                            <input type="hidden" name="order_status" value="<?php echo cleanInput($status); ?>">
                                            <button class="btn-small status-action status-<?php echo strtolower($status); ?>" type="submit"><?php echo cleanInput($label); ?></button>
                                        </form>
                                    <?php endforeach; ?>
                                    <?php if ($order['order_status'] === 'Shipped'): ?>
                                        <span class="muted-text">Waiting for user</span>
                                    <?php elseif ($order['order_status'] === 'Delivered'): ?>
                                        <span class="delivery-mini delivered">Delivery successful</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php'; ?>
