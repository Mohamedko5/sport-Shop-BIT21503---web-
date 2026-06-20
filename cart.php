<?php
$pageTitle = 'Cart | Football Shop';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

if (isset($_GET['remove'])) {
    $removeId = filter_input(INPUT_GET, 'remove', FILTER_VALIDATE_INT);
    if ($removeId && isset($_SESSION['cart'][$removeId])) {
        unset($_SESSION['cart'][$removeId]);
    }
    header('Location: cart.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['quantities'])) {
    foreach ($_POST['quantities'] as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        if ($quantity > 0) {
            $stockCheck = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
            $stockCheck->execute([$productId]);
            $productStock = $stockCheck->fetch();

            if ($productStock) {
                $_SESSION['cart'][$productId] = min($quantity, (int) $productStock['stock']);
            }
        } else {
            unset($_SESSION['cart'][$productId]);
        }
    }
    header('Location: cart.php');
    exit;
}

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
?>

<main class="container page">
    <section class="section-heading">
        <h1>Shopping Cart</h1>
        <p>Review your local football products before checkout.</p>
    </section>

    <?php if (empty($items)): ?>
        <div class="content-panel">
            <p>Your cart is empty.</p>
            <a class="btn" href="products.php">Shop Products</a>
        </div>
    <?php else: ?>
        <form method="POST">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Quantity</th>
                            <th>Subtotal</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <?php $quantity = $_SESSION['cart'][$item['id']]; ?>
                            <tr>
                                <td><?php echo cleanInput($item['name']); ?></td>
                                <td>RM <?php echo number_format($item['price'], 2); ?></td>
                                <td><input class="qty-input" type="number" name="quantities[<?php echo $item['id']; ?>]" min="1" max="<?php echo (int) $item['stock']; ?>" value="<?php echo (int) $quantity; ?>"></td>
                                <td>RM <?php echo number_format($item['price'] * $quantity, 2); ?></td>
                                <td><a class="danger" href="cart.php?remove=<?php echo $item['id']; ?>">Remove</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="cart-summary">
                <h2>Total: RM <?php echo number_format($total, 2); ?></h2>
                <button class="btn btn-outline" type="submit">Update Cart</button>
                <a class="btn" href="checkout.php">Checkout</a>
            </div>
        </form>
    <?php endif; ?>
</main>

<?php require_once 'includes/footer.php'; ?>
