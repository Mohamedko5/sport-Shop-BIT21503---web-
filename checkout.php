<?php
$pageTitle = 'Checkout | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireCustomer();

$message = '';
$errors = [];
$items = [];
$total = 0;
$form = [
    'full_name' => $_SESSION['name'] ?? '',
    'phone' => '',
    'house_number' => '',
    'address' => '',
    'city' => '',
    'postcode' => '',
    'payment_method' => 'Cash on Delivery',
    'cardholder_name' => '',
];
$paymentMethods = ['Cash on Delivery', 'Visa Card', 'MasterCard'];

if (!empty($_SESSION['cart'])) {
    $ids = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $items = $stmt->fetchAll();

    foreach ($items as $item) {
        if (!productHasPublicImage($item['image'])) {
            unset($_SESSION['cart'][$item['id']]);
        }
    }

    $items = array_values(array_filter($items, function ($item) {
        return productHasPublicImage($item['image']);
    }));
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
    verifyCsrfToken();
    foreach (array_keys($form) as $field) {
        $form[$field] = trim($_POST[$field] ?? $form[$field]);
    }
    $cardNumber = preg_replace('/\D+/', '', $_POST['card_number'] ?? '');
    $expiryDate = trim($_POST['expiry_date'] ?? '');
    $cvv = trim($_POST['cvv'] ?? '');
    $cardLast4 = null;

    if ($form['full_name'] === '' || strlen($form['full_name']) > 100) {
        $errors[] = 'Full name is required and must be under 100 characters.';
    }
    if (!preg_match('/^[0-9+\-\s]{7,30}$/', $form['phone'])) {
        $errors[] = 'Phone number must be 7 to 30 characters and contain only numbers, spaces, +, or -.';
    }
    if ($form['house_number'] === '' || strlen($form['house_number']) > 50) {
        $errors[] = 'House number is required and must be under 50 characters.';
    }
    if ($form['address'] === '' || strlen($form['address']) > 500) {
        $errors[] = 'Address is required and must be under 500 characters.';
    }
    if ($form['city'] === '' || strlen($form['city']) > 100) {
        $errors[] = 'City is required and must be under 100 characters.';
    }
    if (!preg_match('/^[A-Za-z0-9\-\s]{3,20}$/', $form['postcode'])) {
        $errors[] = 'Postcode must be 3 to 20 letters or numbers.';
    }
    if (!in_array($form['payment_method'], $paymentMethods, true)) {
        $errors[] = 'Please choose a valid payment method.';
    }
    if (in_array($form['payment_method'], ['Visa Card', 'MasterCard'], true)) {
        if ($form['cardholder_name'] === '' || strlen($form['cardholder_name']) > 100) {
            $errors[] = 'Cardholder name is required and must be under 100 characters.';
        }
        if (!preg_match('/^\d{13,19}$/', $cardNumber)) {
            $errors[] = 'Card number must be 13 to 19 digits.';
        }
        if (!preg_match('/^(0[1-9]|1[0-2])\/\d{2}$/', $expiryDate)) {
            $errors[] = 'Expiry date must use MM/YY format.';
        }
        if (!preg_match('/^\d{3,4}$/', $cvv)) {
            $errors[] = 'CVV/security code must be 3 or 4 digits.';
        }
        if (empty($errors)) {
            $cardLast4 = substr($cardNumber, -4);
        }
    } else {
        $form['cardholder_name'] = '';
    }

    if (empty($errors)) {
        $pdo->beginTransaction();
        try {
            $order = $pdo->prepare(
                'INSERT INTO orders
                 (user_id, full_name, phone, house_number, address, city, postcode, payment_method, cardholder_name, card_last4, total_price, status, order_status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $order->execute([
                $_SESSION['user_id'],
                $form['full_name'],
                $form['phone'],
                $form['house_number'],
                $form['address'],
                $form['city'],
                $form['postcode'],
                $form['payment_method'],
                $form['cardholder_name'] !== '' ? $form['cardholder_name'] : null,
                $cardLast4,
                $total,
                'Pending',
                'Pending',
            ]);
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
            $items = [];
            $message = 'Order placed successfully. Your order ID is #' . $orderId . '.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $message = 'Checkout failed. Please check stock availability and try again.';
        }
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page narrow">
    <section class="content-panel">
        <h1>Checkout</h1>

        <?php if ($message): ?>
            <div class="alert"><?php echo cleanInput($message); ?></div>
            <a class="btn" href="products.php">Continue Shopping</a>
            <a class="btn btn-outline" href="my-orders.php">View My Orders</a>
        <?php elseif ($errors): ?>
            <?php foreach ($errors as $error): ?>
                <div class="alert error"><?php echo cleanInput($error); ?></div>
            <?php endforeach; ?>
        <?php elseif (empty($items)): ?>
            <p>Your cart is empty.</p>
            <a class="btn" href="products.php">Shop Products</a>
        <?php endif; ?>

        <?php if (!$message && !empty($items)): ?>
            <div class="checkout-layout">
                <aside class="order-summary-box">
                    <h2>Order Summary</h2>
                    <div class="checkout-list">
                        <?php foreach ($items as $item): ?>
                            <?php $quantity = $_SESSION['cart'][$item['id']]; ?>
                            <div class="checkout-item">
                                <img class="table-thumb" src="<?php echo cleanInput(productImage($item['image'])); ?>" alt="<?php echo cleanInput($item['name']); ?>" onerror="this.onerror=null;this.style.display='none';">
                                <div>
                                    <strong><?php echo cleanInput($item['name']); ?></strong>
                                    <span><?php echo (int) $quantity; ?> x RM <?php echo number_format($item['price'], 2); ?></span>
                                    <span>Subtotal: RM <?php echo number_format($item['price'] * $quantity, 2); ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="grand-total">
                        <span>Grand Total</span>
                        <strong>RM <?php echo number_format($total, 2); ?></strong>
                    </div>
                </aside>

                <form class="checkout-form" method="POST">
                    <?php echo csrfField(); ?>
                    <h2>Delivery Information</h2>
                    <div class="form-grid">
                        <label>Full Name
                            <input type="text" name="full_name" maxlength="100" value="<?php echo cleanInput($form['full_name']); ?>" required>
                        </label>
                        <label>Phone Number
                            <input type="tel" name="phone" maxlength="30" value="<?php echo cleanInput($form['phone']); ?>" required>
                        </label>
                        <label>House Number
                            <input type="text" name="house_number" maxlength="50" value="<?php echo cleanInput($form['house_number']); ?>" required>
                        </label>
                        <label>City
                            <input type="text" name="city" maxlength="100" value="<?php echo cleanInput($form['city']); ?>" required>
                        </label>
                        <label>Postcode
                            <input type="text" name="postcode" maxlength="20" value="<?php echo cleanInput($form['postcode']); ?>" required>
                        </label>
                        <label class="full-span">Address
                            <textarea name="address" rows="4" maxlength="500" required><?php echo cleanInput($form['address']); ?></textarea>
                        </label>
                    </div>

                    <h2>Payment Method</h2>
                    <div class="payment-options">
                        <?php foreach ($paymentMethods as $method): ?>
                            <label class="payment-card">
                                <input type="radio" name="payment_method" value="<?php echo cleanInput($method); ?>" <?php echo $form['payment_method'] === $method ? 'checked' : ''; ?>>
                                <span class="payment-icon"><?php echo $method === 'Cash on Delivery' ? 'COD' : ($method === 'Visa Card' ? 'VISA' : 'MC'); ?></span>
                                <span>
                                    <strong><?php echo cleanInput($method); ?></strong>
                                    <small><?php echo $method === 'Cash on Delivery' ? 'Pay when your order arrives.' : 'Demo card payment. No real payment is processed.'; ?></small>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-demo-fields" id="cardDemoFields">
                        <h3>Demo Card Details</h3>
                        <div class="form-grid">
                            <label class="full-span">Cardholder Name
                                <input type="text" name="cardholder_name" maxlength="100" value="<?php echo cleanInput($form['cardholder_name']); ?>">
                            </label>
                            <label class="full-span">Card Number
                                <input type="text" name="card_number" inputmode="numeric" autocomplete="off" placeholder="13 to 19 digits">
                            </label>
                            <label>Expiry Date
                                <input type="text" name="expiry_date" placeholder="MM/YY" maxlength="5" autocomplete="off">
                            </label>
                            <label>CVV / Security Code
                                <input type="password" name="cvv" inputmode="numeric" maxlength="4" autocomplete="off">
                            </label>
                        </div>
                    </div>
                    <p class="form-note">Demo checkout only. Full card number and CVV are validated but never stored.</p>
                    <button class="btn place-order-btn" type="submit">Place Order</button>
                </form>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
