<?php
$pageTitle = 'Edit Product | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    header('Location: dashboard.php');
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY id')->fetchAll();
$brands = $pdo->query('SELECT id, name FROM brands ORDER BY id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $name = trim($_POST['name'] ?? '');
    $categoryId = filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $sku = strtoupper(trim($_POST['sku'] ?? ''));
    $brandId = filter_input(INPUT_POST, 'brand_id', FILTER_VALIDATE_INT);
    $price = filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    $description = trim($_POST['description'] ?? '');
    $image = trim($_POST['image'] ?? '');
    $stock = filter_input(INPUT_POST, 'stock', FILTER_VALIDATE_INT);
    $isFeatured = isset($_POST['is_featured']) ? 1 : 0;
    $isNewArrival = isset($_POST['is_new_arrival']) ? 1 : 0;
    $isBestSeller = isset($_POST['is_best_seller']) ? 1 : 0;

    if ($name === '' || $sku === '' || $description === '' || $image === '') {
        $errors[] = 'All text fields are required.';
    }
    if (!$categoryId) {
        $errors[] = 'Please select a category.';
    }
    if ($price === false || $price <= 0) {
        $errors[] = 'Price must be greater than zero.';
    }
    if (!$brandId) {
        $errors[] = 'Please select a brand.';
    }
    if (!isValidImageReference($image)) {
        $errors[] = 'Image must be a valid URL or an assets/images/products/ path.';
    }
    if ($stock === false || $stock < 0) {
        $errors[] = 'Stock must be zero or more.';
    }

    if (empty($errors)) {
        $update = $pdo->prepare(
            'UPDATE products
             SET name = ?, category_id = ?, brand_id = ?, sku = ?, price = ?, description = ?, image = ?, stock = ?, is_featured = ?, is_new_arrival = ?, is_best_seller = ?
             WHERE id = ?'
        );
        $update->execute([$name, $categoryId, $brandId, $sku, $price, $description, $image, $stock, $isFeatured, $isNewArrival, $isBestSeller, $id]);
        header('Location: dashboard.php');
        exit;
    }
}

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page narrow">
    <form class="form-card wide" method="POST">
        <?php echo csrfField(); ?>
        <h1>Edit Product</h1>
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?php echo cleanInput($error); ?></div>
        <?php endforeach; ?>

        <label>Name</label>
        <input type="text" name="name" value="<?php echo cleanInput($product['name']); ?>" required>

        <label>SKU Code</label>
        <input type="text" name="sku" value="<?php echo cleanInput($product['sku']); ?>" required>

        <label>Brand</label>
        <select name="brand_id" required>
            <?php foreach ($brands as $brand): ?>
                <option value="<?php echo (int) $brand['id']; ?>" <?php echo (int) $product['brand_id'] === (int) $brand['id'] ? 'selected' : ''; ?>>
                    <?php echo cleanInput($brand['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Category</label>
        <select name="category_id" required>
            <option value="">Select category</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?php echo (int) $category['id']; ?>" <?php echo (int) $product['category_id'] === (int) $category['id'] ? 'selected' : ''; ?>>
                    <?php echo cleanInput($category['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Price</label>
        <input type="number" name="price" min="0.01" step="0.01" value="<?php echo cleanInput($product['price']); ?>" required>

        <label>Description</label>
        <textarea name="description" rows="5" required><?php echo cleanInput($product['description']); ?></textarea>

        <label>Image URL or Local Path</label>
        <input type="text" name="image" value="<?php echo cleanInput($product['image']); ?>" required>

        <label>Stock</label>
        <input type="number" name="stock" min="0" value="<?php echo (int) $product['stock']; ?>" required>

        <label class="checkbox-label"><input type="checkbox" name="is_featured" <?php echo $product['is_featured'] ? 'checked' : ''; ?>> Featured Product</label>
        <label class="checkbox-label"><input type="checkbox" name="is_new_arrival" <?php echo $product['is_new_arrival'] ? 'checked' : ''; ?>> New Arrival</label>
        <label class="checkbox-label"><input type="checkbox" name="is_best_seller" <?php echo $product['is_best_seller'] ? 'checked' : ''; ?>> Best Seller</label>

        <button class="btn" type="submit">Update Product</button>
    </form>
</main>

<?php require_once 'includes/footer.php'; ?>
