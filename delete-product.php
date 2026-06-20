<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id) {
    try {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        $_SESSION['delete_error'] = 'Product cannot be deleted because it has order history.';
    }
}

header('Location: dashboard.php');
exit;
?>
