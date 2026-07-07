<?php
$pageTitle = 'Product Details | Football Store';
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page" data-product-details-page="true">
    <div id="apiMessage" class="api-message">Loading product details...</div>
    <section class="details-layout" id="productDetails"></section>
    <section class="section-heading">
        <h2>Related Products</h2>
        <p>More football gear from the same category.</p>
    </section>
    <div class="product-grid" id="relatedProductsGrid"></div>
</main>

<?php require_once 'includes/footer.php'; ?>
