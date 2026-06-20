<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Only GET requests are allowed.']);
    exit;
}

$search = trim($_GET['search'] ?? '');
$categoryId = filter_input(INPUT_GET, 'category_id', FILTER_VALIDATE_INT);
$brandId = filter_input(INPUT_GET, 'brand_id', FILTER_VALIDATE_INT);

$sql = 'SELECT products.id, products.name, categories.name AS category, products.category_id,
               brands.name AS brand, products.brand_id, products.sku, products.price, products.description, products.image,
               products.stock, products.is_featured, products.is_new_arrival, products.is_best_seller
        FROM products
        JOIN categories ON products.category_id = categories.id
        JOIN brands ON products.brand_id = brands.id
        WHERE 1';
$params = [];

if ($search !== '') {
    $sql .= ' AND (products.name LIKE ? OR products.sku LIKE ? OR brands.name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($categoryId) {
    $sql .= ' AND products.category_id = ?';
    $params[] = $categoryId;
}

if ($brandId) {
    $sql .= ' AND products.brand_id = ?';
    $params[] = $brandId;
}

$sql .= ' ORDER BY products.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

echo json_encode($stmt->fetchAll());
?>
