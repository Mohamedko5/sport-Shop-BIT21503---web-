<?php
$pendingOrderCount = 0;
if (isAdmin()) {
    require_once __DIR__ . '/../config/database.php';
    $pendingOrderCount = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'Pending'")->fetchColumn();
}
?>
<nav class="navbar">
    <a class="brand" href="index.php">Football Store</a>
    <button class="nav-toggle" type="button" aria-label="Toggle navigation">Menu</button>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="products.php">Shop</a>
        <a href="contact.php">Contact</a>
        <?php if (isAdmin()): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="admin-orders.php">Orders<?php echo $pendingOrderCount > 0 ? ' (' . $pendingOrderCount . ')' : ''; ?></a>
            <a href="logout.php">Logout</a>
        <?php elseif (isLoggedIn()): ?>
            <a href="cart.php">Cart (<?php echo cartCount(); ?>)</a>
            <a href="my-orders.php">My Orders</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a class="btn-small" href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
