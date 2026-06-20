<?php
header('Content-Type: application/json');
require_once '../includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

$action = $_POST['action'] ?? '';
$productId = filter_input(INPUT_POST, 'product_id', FILTER_VALIDATE_INT);
$quantity = filter_input(INPUT_POST, 'quantity', FILTER_VALIDATE_INT);

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cartApiSummary($pdo)
{
    $items = [];
    $total = 0;

    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price, stock FROM products WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $products = $stmt->fetchAll();

        foreach ($products as $product) {
            $qty = (int) $_SESSION['cart'][$product['id']];
            $subtotal = $qty * (float) $product['price'];
            $items[] = [
                'id' => (int) $product['id'],
                'name' => $product['name'],
                'price' => (float) $product['price'],
                'quantity' => $qty,
                'subtotal' => $subtotal,
            ];
            $total += $subtotal;
        }
    }

    return ['items' => $items, 'total' => $total, 'count' => array_sum($_SESSION['cart'])];
}

if ($action === 'add') {
    if (!$productId || !$quantity || $quantity < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid product and quantity are required.']);
        exit;
    }

    $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();

    $newQuantity = ($_SESSION['cart'][$productId] ?? 0) + $quantity;
    if (!$product || $newQuantity > (int) $product['stock']) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product is unavailable or quantity is too high.']);
        exit;
    }

    $_SESSION['cart'][$productId] = $newQuantity;
    echo json_encode(['success' => true, 'message' => 'Product added to cart.', 'cart' => cartApiSummary($pdo)]);
    exit;
}

if ($action === 'update') {
    if (!$productId || $quantity === false || $quantity < 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid product and quantity are required.']);
        exit;
    }

    if ($quantity === 0) {
        unset($_SESSION['cart'][$productId]);
    } else {
        $stmt = $pdo->prepare('SELECT stock FROM products WHERE id = ?');
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product || $quantity > (int) $product['stock']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Product is unavailable or quantity is too high.']);
            exit;
        }

        $_SESSION['cart'][$productId] = $quantity;
    }

    echo json_encode(['success' => true, 'message' => 'Cart updated.', 'cart' => cartApiSummary($pdo)]);
    exit;
}

if ($action === 'remove') {
    if (!$productId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Valid product ID is required.']);
        exit;
    }

    unset($_SESSION['cart'][$productId]);
    echo json_encode(['success' => true, 'message' => 'Product removed from cart.', 'cart' => cartApiSummary($pdo)]);
    exit;
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    echo json_encode(['success' => true, 'message' => 'Cart cleared.', 'cart' => cartApiSummary($pdo)]);
    exit;
}

http_response_code(400);
echo json_encode(['success' => false, 'message' => 'Unknown cart action.']);
?>
