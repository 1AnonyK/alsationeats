<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$adminPage = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title><?= h($pageTitle ?? 'Admin — ' . SITE_NAME) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link href="/assets/css/style.css" rel="stylesheet">
</head>
<body>
<div class="admin-wrapper">
  <!-- Admin Sidebar -->
  <aside class="admin-sidebar">
    <div class="mb-4">
      <span class="logo-text" style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.1rem">
        Alsation Eats
      </span>
      <div style="color:rgba(255,255,255,.4);font-size:.75rem;margin-top:2px">Admin Panel</div>
    </div>

    <nav>
      <a href="/admin/index.php"    class="admin-nav-link <?= $adminPage==='index'?'active':'' ?>">
        <i class="fa-solid fa-gauge-high fa-fw"></i> Dashboard
      </a>
      <a href="/admin/orders.php"   class="admin-nav-link <?= $adminPage==='orders'?'active':'' ?>">
        <i class="fa-solid fa-receipt fa-fw"></i> Orders
      </a>
      <a href="/admin/payments.php" class="admin-nav-link <?= $adminPage==='payments'?'active':'' ?>">
        <i class="fa-solid fa-money-bill-wave fa-fw"></i> Payments
      </a>
      <a href="/admin/vendors.php"  class="admin-nav-link <?= $adminPage==='vendors'?'active':'' ?>">
        <i class="fa-solid fa-store fa-fw"></i> Vendors
      </a>
      <a href="/admin/users.php"    class="admin-nav-link <?= $adminPage==='users'?'active':'' ?>">
        <i class="fa-solid fa-users fa-fw"></i> Users
      </a>
      <a href="/admin/menu.php"     class="admin-nav-link <?= $adminPage==='menu'?'active':'' ?>">
        <i class="fa-solid fa-utensils fa-fw"></i> All Menu Items
      </a>
    </nav>

    <div style="margin-top:auto;padding-top:40px">
      <div style="color:rgba(255,255,255,.5);font-size:.8rem;margin-bottom:8px">
        Logged in as<br><strong style="color:#fff"><?= h(currentUserName()) ?></strong>
      </div>
      <a href="/index.php"   class="admin-nav-link"><i class="fa-solid fa-eye fa-fw"></i> View Site</a>
      <a href="/logout.php"  class="admin-nav-link" style="color:#E05C5C">
        <i class="fa-solid fa-right-from-bracket fa-fw"></i> Log Out
      </a>
    </div>
  </aside>

  <!-- Admin Content -->
  <div class="admin-content">
