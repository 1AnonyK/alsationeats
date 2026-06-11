<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/auth.php';

$cartCount = cartCount();
$currentPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= h($pageTitle ?? SITE_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="app-wrapper">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <a href="<?= SITE_URL ?>/index.php" class="text-decoration-none">
                <span class="logo-text">Alsation Eats</span>
            </a>
        </div>
        <nav class="sidebar-nav">
            <a href="<?= SITE_URL ?>/index.php" class="nav-pill <?= $currentPage === 'index' ? 'active' : '' ?>">
                <i class="fa-solid fa-house"></i> Home
            </a>
            <a href="<?= SITE_URL ?>/orders.php" class="nav-pill <?= $currentPage === 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-receipt"></i> Orders
            </a>
            <a href="<?= SITE_URL ?>/favourites.php" class="nav-pill <?= $currentPage === 'favourites' ? 'active' : '' ?>">
                <i class="fa-solid fa-heart"></i> Favourites
            </a>
            <?php if (isSeller()): ?>
            <a href="<?= SITE_URL ?>/seller/dashboard.php" class="nav-pill <?= str_starts_with($currentPage, 'seller') ? 'active' : '' ?>">
                <i class="fa-solid fa-store"></i> My Shop
            </a>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/seller/setup.php" class="nav-pill">
                <i class="fa-solid fa-store"></i> Sell Here
            </a>
            <?php endif; ?>
            <?php if (isAdmin()): ?>
            <a href="<?= SITE_URL ?>/admin/index.php" class="nav-pill">
                <i class="fa-solid fa-shield-halved"></i> Admin
            </a>
            <?php endif; ?>
            <a href="<?= SITE_URL ?>/help.php" class="nav-pill <?= $currentPage === 'help' ? 'active' : '' ?>">
                <i class="fa-solid fa-circle-question"></i> Help
            </a>
        </nav>
        <div class="sidebar-footer">
            <?php if (isLoggedIn()): ?>
            <div class="user-chip">
                <i class="fa-solid fa-circle-user"></i>
                <span><?= h(currentUserName()) ?></span>
            </div>
            <a href="<?= SITE_URL ?>/logout.php" class="nav-pill logout-pill">
                <i class="fa-solid fa-right-from-bracket"></i> Log Out
            </a>
            <?php else: ?>
            <a href="<?= SITE_URL ?>/login.php" class="nav-pill login-pill">
                <i class="fa-solid fa-right-to-bracket"></i> Log In
            </a>
            <?php endif; ?>
        </div>
    </aside>

    <div class="main-area">
        <header class="top-bar">
            <button class="sidebar-toggle d-md-none" id="sidebarToggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <form class="search-form" action="<?= SITE_URL ?>/index.php" method="GET">
                <div class="search-wrap">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" name="q" class="search-input" placeholder="Search vendors or food..."
                           value="<?= h($_GET['q'] ?? '') ?>">
                </div>
            </form>
            <div class="top-actions">
                <a href="<?= SITE_URL ?>/cart.php" class="action-btn cart-btn" title="Cart">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <?php if ($cartCount > 0): ?>
                    <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
                <?php if (isLoggedIn()): ?>
                <a href="<?= SITE_URL ?>/profile.php" class="action-btn user-btn" title="Profile">
                    <i class="fa-solid fa-user"></i>
                </a>
                <?php else: ?>
                <a href="<?= SITE_URL ?>/login.php" class="action-btn user-btn" title="Login">
                    <i class="fa-solid fa-user"></i>
                </a>
                <?php endif; ?>
            </div>
        </header>

        <main class="page-content">
