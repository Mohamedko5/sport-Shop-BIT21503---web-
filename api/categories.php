<?php
header('Content-Type: application/json');
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Only GET requests are allowed.']);
    exit;
}

$stmt = $pdo->query('SELECT id, name FROM categories ORDER BY id');
echo json_encode($stmt->fetchAll());
?>
