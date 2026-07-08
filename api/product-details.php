<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Only GET requests are allowed.']);
    exit;
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'Valid product ID is required.']);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT products.id, products.name, categories.name AS category, products.category_id,
            brands.name AS brand, products.brand_id, products.sku, products.price, products.description, products.image,
            products.stock, products.is_featured, products.is_new_arrival, products.is_best_seller
     FROM products
     JOIN categories ON products.category_id = categories.id
     JOIN brands ON products.brand_id = brands.id
     WHERE products.id = ?'
);
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    http_response_code(404);
    echo json_encode(['error' => 'Product not found.']);
    exit;
}

if (!productHasPublicImage($product['image'])) {
    http_response_code(409);
    echo json_encode(['error' => 'This product is currently unavailable because the image is missing.']);
    exit;
}

$product['image'] = productImage($product['image']);

echo json_encode($product);
?>
