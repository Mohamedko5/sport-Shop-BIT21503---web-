<?php
$pageTitle = 'Home | Football Store';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$stmt = $pdo->query(
    'SELECT products.*, categories.name AS category, brands.name AS brand
     FROM products
     JOIN categories ON products.category_id = categories.id
     JOIN brands ON products.brand_id = brands.id
     WHERE products.is_featured = 1
     ORDER BY products.created_at DESC
     LIMIT 40'
);
$featuredProducts = array_slice(array_values(array_filter($stmt->fetchAll(), function ($product) {
    return productHasPublicImage($product['image']);
})), 0, 6);

$newArrivals = $pdo->query(
    'SELECT products.*, categories.name AS category, brands.name AS brand
     FROM products
     JOIN categories ON products.category_id = categories.id
     JOIN brands ON products.brand_id = brands.id
     WHERE products.is_new_arrival = 1
     ORDER BY products.created_at DESC
     LIMIT 40'
)->fetchAll();
$newArrivals = array_slice(array_values(array_filter($newArrivals, function ($product) {
    return productHasPublicImage($product['image']);
})), 0, 3);

$bestSellers = $pdo->query(
    'SELECT products.*, categories.name AS category, brands.name AS brand
     FROM products
     JOIN categories ON products.category_id = categories.id
     JOIN brands ON products.brand_id = brands.id
     WHERE products.is_best_seller = 1
     ORDER BY products.created_at DESC
     LIMIT 40'
)->fetchAll();
$bestSellers = array_slice(array_values(array_filter($bestSellers, function ($product) {
    return productHasPublicImage($product['image']);
})), 0, 3);
?>

<section class="hero">
    <div class="hero-content">
        <span class="eyebrow">Premium football equipment in stock</span>
        <h1>Match-ready football gear for players, keepers, coaches, and supporters.</h1>
        <p>Shop boots, jerseys, balls, goalkeeper gloves, shin guards, training equipment, bags, socks, and club merchandise from trusted football brands.</p>
        <div class="hero-actions">
            <a class="btn" href="products.php">Shop Football Gear</a>
            <a class="btn btn-outline" href="products.php?brand_id=1">Browse Nike Gear</a>
        </div>
        <div class="hero-trust">
            <span>150+ Products</span>
            <span>7 Premium Brands</span>
            <span>Live Stock Tracking</span>
        </div>
    </div>
</section>

<main class="container" data-products-page="true" data-user-role="<?php echo cleanInput($_SESSION['role'] ?? 'guest'); ?>">
    <section class="section-heading">
        <h2>Featured Football Products</h2>
        <p>Top football products selected from our local inventory.</p>
    </section>

    <div class="product-grid">
        <?php foreach ($featuredProducts as $product): ?>
            <article class="product-card">
                <img src="<?php echo cleanInput(productImage($product['image'])); ?>" alt="<?php echo cleanInput($product['name']); ?>" onerror="this.onerror=null;this.closest('.product-card').remove();">
                <div class="product-body">
                    <span class="tag"><?php echo cleanInput($product['category']); ?></span>
                    <span class="type-badge local"><?php echo cleanInput($product['brand']); ?></span>
                    <h3><?php echo cleanInput($product['name']); ?></h3>
                    <p class="sku-line">SKU: <?php echo cleanInput($product['sku']); ?> | Stock: <?php echo (int) $product['stock']; ?></p>
                    <p><?php echo cleanInput(substr($product['description'], 0, 80)); ?>...</p>
                    <div class="product-meta">
                        <strong>RM <?php echo number_format($product['price'], 2); ?></strong>
                    </div>
                    <?php echo productCardActions($product['id']); ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <section class="section-heading">
        <h2>New Arrivals</h2>
        <p>Fresh football inventory ready for the next match.</p>
    </section>
    <div class="info-grid">
        <?php foreach ($newArrivals as $product): ?>
            <div>
                <h3><?php echo cleanInput($product['name']); ?></h3>
                <p><?php echo cleanInput($product['brand']); ?> | RM <?php echo number_format($product['price'], 2); ?></p>
                <a href="product-details.php?id=<?php echo (int) $product['id']; ?>">View product</a>
            </div>
        <?php endforeach; ?>
    </div>

    <section class="section-heading">
        <h2>Best Sellers</h2>
        <p>Football gear customers keep coming back for.</p>
    </section>
    <div class="info-grid">
        <?php foreach ($bestSellers as $product): ?>
            <div>
                <h3><?php echo cleanInput($product['name']); ?></h3>
                <p><?php echo cleanInput($product['brand']); ?> | RM <?php echo number_format($product['price'], 2); ?></p>
                <a href="product-details.php?id=<?php echo (int) $product['id']; ?>">View product</a>
            </div>
        <?php endforeach; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
