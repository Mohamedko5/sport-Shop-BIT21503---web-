<?php
$pageTitle = 'Manage Categories | Football Store';
require_once 'config/database.php';
require_once 'includes/auth.php';
requireAdmin();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken();
    $action = $_POST['action'] ?? 'add';
    $name = trim($_POST['name'] ?? '');

    if ($action === 'add') {
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

    if ($action === 'edit') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id || $name === '') {
            $errors[] = 'Valid category and name are required.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?');
            $stmt->execute([$name, $id]);
            header('Location: manage-categories.php');
            exit;
        }
    }

    if ($action === 'delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id) {
            $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE category_id = ?');
            $check->execute([$id]);
            if ((int) $check->fetchColumn() === 0) {
                $delete = $pdo->prepare('DELETE FROM categories WHERE id = ?');
                $delete->execute([$id]);
            } else {
                $_SESSION['category_error'] = 'Category cannot be deleted while products are assigned to it.';
            }
        }
        header('Location: manage-categories.php');
        exit;
    }
}

$editId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT);
$editCategory = null;
if ($editId) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$editId]);
    $editCategory = $stmt->fetch();
}

$categories = $pdo->query(
    'SELECT categories.*, COUNT(products.id) AS product_count
     FROM categories
     LEFT JOIN products ON products.category_id = categories.id
     GROUP BY categories.id
     ORDER BY categories.id'
)->fetchAll();

require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<main class="container page">
    <section class="section-heading">
        <h1>Manage Categories</h1>
        <p>Organize the catalogue by product category.</p>
    </section>

    <?php if (!empty($_SESSION['category_error'])): ?>
        <div class="alert error"><?php echo cleanInput($_SESSION['category_error']); unset($_SESSION['category_error']); ?></div>
    <?php endif; ?>

    <form class="form-card wide" method="POST">
        <?php echo csrfField(); ?>
        <input type="hidden" name="action" value="<?php echo $editCategory ? 'edit' : 'add'; ?>">
        <?php if ($editCategory): ?>
            <input type="hidden" name="id" value="<?php echo (int) $editCategory['id']; ?>">
        <?php endif; ?>

        <h2><?php echo $editCategory ? 'Edit Category' : 'Add Category'; ?></h2>
        <?php foreach ($errors as $error): ?>
            <div class="alert error"><?php echo cleanInput($error); ?></div>
        <?php endforeach; ?>
        <label>Name</label>
        <input type="text" name="name" value="<?php echo $editCategory ? cleanInput($editCategory['name']) : ''; ?>" required>
        <button class="btn" type="submit"><?php echo $editCategory ? 'Update Category' : 'Add Category'; ?></button>
        <?php if ($editCategory): ?>
            <a class="btn btn-outline" href="manage-categories.php">Cancel</a>
        <?php endif; ?>
    </form>

    <section class="table-section">
        <h2>Categories</h2>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Products</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $category): ?>
                        <tr>
                            <td><?php echo cleanInput($category['name']); ?></td>
                            <td><?php echo (int) $category['product_count']; ?></td>
                            <td>
                                <div class="inline-form">
                                    <a href="manage-categories.php?edit=<?php echo (int) $category['id']; ?>">Edit</a>
                                    <?php if ((int) $category['product_count'] === 0): ?>
                                        <form method="POST" onsubmit="return confirm('Delete this category?')">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo (int) $category['id']; ?>">
                                            <button class="link-danger" type="submit">Delete</button>
                                        </form>
                                    <?php else: ?>
                                        <span>In use</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once 'includes/footer.php'; ?>
