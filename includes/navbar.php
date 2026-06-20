<nav class="navbar">
    <a class="brand" href="index.php">Football Store</a>
    <button class="nav-toggle" type="button" aria-label="Toggle navigation">Menu</button>
    <div class="nav-links">
        <a href="index.php">Home</a>
        <a href="about.php">About</a>
        <a href="products.php">Shop</a>
        <a href="contact.php">Contact</a>
        <a href="cart.php">Cart (<?php echo cartCount(); ?>)</a>
        <?php if (isLoggedIn()): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a class="btn-small" href="register.php">Register</a>
        <?php endif; ?>
    </div>
</nav>
