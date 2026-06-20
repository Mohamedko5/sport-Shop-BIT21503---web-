<?php
$pageTitle = 'Shop | Football Store';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page">
    <section class="section-heading">
        <h1>Shop Football Gear</h1>
        <p>Browse boots, jerseys, balls, goalkeeper gear, training equipment, bags, socks, and club merchandise.</p>
    </section>

    <form class="filter-bar" id="productFilterForm">
        <input type="text" id="searchInput" name="search" placeholder="Search by product, SKU, or brand">
        <select id="categorySelect" name="category_id">
            <option value="">All categories</option>
        </select>
        <select id="brandSelect" name="brand_id">
            <option value="">All brands</option>
        </select>
        <button class="btn" type="submit">Filter</button>
    </form>

    <div id="apiMessage" class="api-message">Loading products...</div>
    <div class="product-grid" id="productsGrid">
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>
