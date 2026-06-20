<?php
$pageTitle = 'Login | Football Shop';
require_once 'config/database.php';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Please enter a valid email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit;
        }

        $error = 'Invalid login details.';
    }
}
?>

<main class="container auth-page">
    <form class="form-card" method="POST">
        <h1>Login</h1>
        <?php if (isset($_GET['registered'])): ?>
            <div class="alert">Registration successful. You can now login.</div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?php echo cleanInput($error); ?></div>
        <?php endif; ?>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Password</label>
        <input type="password" name="password" required>

        <button class="btn" type="submit">Login</button>
        <p>No account yet? <a href="register.php">Register here</a></p>
    </form>
</main>

<?php require_once 'includes/footer.php'; ?>
