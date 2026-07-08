<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please login before placing an order.']);
    exit;
}

if (!isCustomer()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Admins cannot place customer orders.']);
    exit;
}

$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);

if (!is_array($payload) || empty($payload['items']) || !is_array($payload['items'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Order items are required.']);
    exit;
}

$orderItems = [];
$total = 0;

foreach ($payload['items'] as $item) {
    $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);

    if (!$productId || !$quantity || $quantity < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Each order item needs a valid product ID and quantity.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, price, stock FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    if (!$product || $quantity > (int) $product['stock']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'One product is unavailable or has insufficient stock.']);
        exit;
    }

    $price = (float) $product['price'];
    $orderItems[] = ['product_id' => $productId, 'quantity' => $quantity, 'price' => $price];
    $total += $price * $quantity;
}

$pdo->beginTransaction();

try {
    $order = $pdo->prepare('INSERT INTO orders (user_id, total_price, status, order_status) VALUES (?, ?, ?, ?)');
    $order->execute([$_SESSION['user_id'], $total, 'Pending', 'Pending']);
    $orderId = $pdo->lastInsertId();

    $itemInsert = $pdo->prepare('INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
    $stockUpdate = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');

    foreach ($orderItems as $item) {
        $itemInsert->execute([$orderId, $item['product_id'], $item['quantity'], $item['price']]);
        $stockUpdate->execute([$item['quantity'], $item['product_id'], $item['quantity']]);
        if ($stockUpdate->rowCount() === 0) {
            throw new Exception('Insufficient stock.');
        }
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'message' => 'Order created successfully.', 'order_id' => (int) $orderId, 'total_price' => $total]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Order could not be created.']);
}
?>
