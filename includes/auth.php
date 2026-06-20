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
        header('Location: dashboard.php');
        exit;
    }
}

function cleanInput($value)
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

function cartCount()
{
    if (empty($_SESSION['cart'])) {
        return 0;
    }

    return array_sum($_SESSION['cart']);
}
?>
