<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn()
{
    return isset($_SESSION['user_id']);
}

function isAdmin()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isCustomer()
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'user';
}

function isUser()
{
    return isCustomer();
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit;
    }
}

function requireAdmin()
{
    requireLogin();
    if (!isAdmin()) {
        header('Location: index.php');
        exit;
    }
}

function requireCustomer()
{
    requireLogin();
    if (!isCustomer()) {
        header('Location: dashboard.php');
        exit;
    }
}

function requireUser()
{
    requireCustomer();
}

function cleanInput($value)
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function productImage($image)
{
    $image = trim((string) $image);
    if ($image === '') {
        return 'assets/images/products/default-product.jpg';
    }

    if (!filter_var($image, FILTER_VALIDATE_URL)) {
        $path = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $image);
        $fullPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $path;
        if (!is_file($fullPath)) {
            return 'assets/images/products/default-product.jpg';
        }
    }

    return $image;
}

function productImagePath($image)
{
    $image = trim((string) $image);
    if ($image === '' || filter_var($image, FILTER_VALIDATE_URL)) {
        return null;
    }

    if (strpos($image, '..') !== false) {
        return null;
    }

    $normalized = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $image);
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . $normalized;
}

function generatedPlaceholderImages()
{
    static $images = null;
    if ($images !== null) {
        return $images;
    }

    $images = [];
    $reportPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'database' . DIRECTORY_SEPARATOR . 'image_created_files.txt';
    if (!is_file($reportPath)) {
        return $images;
    }

    foreach (file($reportPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $images['assets/images/products/' . str_replace('\\', '/', trim($line))] = true;
    }

    return $images;
}

function productHasPublicImage($image)
{
    $image = trim((string) $image);
    if ($image === '' || $image === 'assets/images/products/default-product.jpg') {
        return false;
    }

    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return false;
    }

    if (isset(generatedPlaceholderImages()[str_replace('\\', '/', $image)])) {
        return false;
    }

    $fullPath = productImagePath($image);
    return $fullPath !== null && is_file($fullPath);
}

function isValidImageReference($image)
{
    $image = trim((string) $image);
    if (filter_var($image, FILTER_VALIDATE_URL)) {
        return true;
    }

    return (bool) preg_match('/^assets\/images\/products\/[A-Za-z0-9_\-\/.]+$/', $image)
        && strpos($image, '..') === false;
}

function csrfToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . cleanInput(csrfToken()) . '">';
}

function verifyCsrfToken()
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        die('Invalid security token. Please go back and try again.');
    }
}

function cartCount()
{
    if (empty($_SESSION['cart'])) {
        return 0;
    }

    return array_sum($_SESSION['cart']);
}

function productCardActions($productId)
{
    $productId = (int) $productId;

    if (isAdmin()) {
        return '<div class="product-actions single-action">'
            . '<a class="btn btn-outline" href="product-details.php?id=' . $productId . '">Details</a>'
            . '</div>';
    }

    if (isCustomer()) {
        return '<div class="product-actions">'
            . '<a class="btn btn-outline" href="product-details.php?id=' . $productId . '">Add Cart</a>'
            . '<button class="btn product-pay-btn" type="button" data-pay-product="' . $productId . '">Pay</button>'
            . '</div>';
    }

    return '<div class="product-actions">'
        . '<a class="btn btn-outline" href="product-details.php?id=' . $productId . '">Details</a>'
        . '<a class="btn product-pay-btn" href="login.php?redirect=product-details.php?id=' . $productId . '">Login to Buy</a>'
        . '</div>';
}
?>
