<?php
$pageTitle = 'Manage Categories | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAdmin();
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');

    if ($name === '') {
        $errors[] = 'Category name is required.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('INSERT INTO categories (name) VALUES (?)');
        $stmt->execute([$name]);
        header('Location: manage-categories.php');
        exit;
    }
}

if (isset($_GET['delete'])) {
    $id = filter_input(INPUT_GET, 'delete', FILTER_VALIDATE_INT);
    if ($id) {
        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
        $check->execute([$id]);
        if ((int) $check->fetchColumn() === 0) {
            $delete = $pdo->prepare('DELETE FROM categories WHERE id = ?');
            $delete->execute([$id]);
        }
    }
    header('Location: manage-categories.php');
    exit;
}

$categories = $pdo->query(
    'SELECT categories.*, COUNT(products.id) AS product_count
     FROM categories
     LEFT JOIN products ON products.category_id = categories.id
     GROUP BY categories.id
     ORDER BY categories.id'
)->fetchAll();
?>

<main class="container page">
    <section class="section-heading">
        <h1>Manage Categories</h1>
        <p>Organize the Football Store catalogue by product category.</p>
    </section>

    <form class="form-card wide" method="POST">
        <h2>Add Category</h2>
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?php echo cleanInput($error); ?></div>
        <?php endforeach; ?>
        <label>Name</label>
        <input type="text" name="name" required>
        <button class="btn" type="submit">Add Category</button>
    </form>

    <section class="table-section">
        <h2>Categories</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo cleanInput($category['name']); ?></td>
                            <td><?php echo (int) $category['product_count']; ?></td>
                            <td>
                                <?php if ((int) $category['product_count'] === 0): ?>
                                    <a class="danger" href="manage-categories.php?delete=<?php echo (int) $category['id']; ?>" onclick="return confirm('Delete this category?')">Delete</a>
                                <?php else: ?>
                                    In use
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
