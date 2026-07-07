<?php
$pageTitle = 'Checkout | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireLogin();
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$message = '';
$items = [];
$total = 0;

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll();
    $validIds = array_column($items, 'id');

    foreach ($ids as $cartProductId) {
        if (!in_array((int) $cartProductId, array_map('intval', $validIds), true)) {
            unset($_SESSION['cart'][$cartProductId]);
        }
    }

    foreach ($items as $item) {
        $total += $item['price'] * $_SESSION['cart'][$item['id']];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($items)) {
    $pdo->beginTransaction();
    try {
        $order = $pdo->prepare('INSERT INTO orders (user_id, total_price, status) VALUES (?, ?, ?)');
        $order->execute([$_SESSION['user_id'], $total, 'Pending']);
        $orderId = $pdo->lastInsertId();

        $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
        $stockUpdate = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

        foreach ($items as $item) {
            $quantity = $_SESSION['cart'][$item['id']];
            $itemInsert->execute([$orderId, $item['id'], $quantity, $item['price']]);
            $stockUpdate->execute([$quantity, $item['id'], $quantity]);
            if ($stockUpdate->rowCount() === 0) {
                throw new Exception('Insufficient stock.');
            }
        }

        $pdo->commit();
        $_SESSION['cart'] = [];
        $message = 'Order placed successfully. Your order ID is #' . $orderId . '.';
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = 'Checkout failed. Please try again.';
    }
}
?>

<main class="container page narrow">
    <section class="content-panel">
        <h1>Checkout</h1>

        <?php if ($message): ?>
            <div class="alert"><?php echo cleanInput($message); ?></div>
            <a class="btn" href="dashboard.php">View Dashboard</a>
        <?php elseif (empty($items)): ?>
            <p>Your cart is empty.</p>
            <a class="btn" href="products.php">Shop Products</a>
        <?php else: ?>
            <p><strong>Customer:</strong> <?php echo cleanInput($_SESSION['name']); ?></p>
            <p><strong>Total Payment:</strong> RM <?php echo number_format($total, 2); ?></p>
            <p>Review your local football order and place it for processing.</p>
            <form method="POST">
                <button class="btn" type="submit">Place Order</button>
            </form>
        <?php endif; ?>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
