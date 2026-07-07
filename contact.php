<?php
$pageTitle = 'Contact | Football Store';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

$sent = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = cleanInput($_POST['name'] ?? '');
    $email = cleanInput($_POST['email'] ?? '');
    $message = cleanInput($_POST['message'] ?? '');
    $sent = $name !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) && $message !== '';
}
?>

<main class="container page narrow">
    <form class="form-card wide" method="POST">
        <h1>Contact Us</h1>
        <?php if ($sent): ?>
            <div class="alert">Thank you. Your message has been received. Our team will get back to you soon.</div>
        <?php endif; ?>

        <label>Name</label>
        <input type="text" name="name" required>

        <label>Email</label>
        <input type="email" name="email" required>

        <label>Message</label>
        <textarea name="message" rows="5" required></textarea>

        <button class="btn" type="submit">Send Message</button>
    </form>
</main>

<?php require_once 'includes/footer.php'; ?>
