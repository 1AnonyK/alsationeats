<?php
$pageTitle = 'My Shop Dashboard — Alsation Eats';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isSeller()) { header('Location: /seller/setup.php'); exit; }

$vendorId = currentVendorId();

// Stats
$db = db();

$totalOrders = $db->prepare("SELECT COUNT(*) FROM orders WHERE vendor_id = ?");
$totalOrders->execute([$vendorId]);

$pendingOrders = $db->prepare("SELECT COUNT(*) FROM orders WHERE vendor_id = ? AND status IN ('payment_uploaded','confirmed','preparing')");
$pendingOrders->execute([$vendorId]);

$revenue = $db->prepare("SELECT COALESCE(SUM(subtotal),0) FROM orders WHERE vendor_id = ? AND status NOT IN ('cancelled','pending_payment')");
$revenue->execute([$vendorId]);

$menuItems = $db->prepare("SELECT COUNT(*) FROM menu_items WHERE vendor_id = ?");
$menuItems->execute([$vendorId]);

// Vendor details
$vStmt = $db->prepare("SELECT * FROM vendors WHERE id = ?");
$vStmt->execute([$vendorId]);
$vendor = $vStmt->fetch();

// Recent orders (last 10)
$recentStmt = $db->prepare("
    SELECT o.*, u.name AS buyer_name, u.phone AS buyer_phone
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    WHERE o.vendor_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
");
$recentStmt->execute([$vendorId]);
$recentOrders = $recentStmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">My Shop: <?= h($vendor['name']) ?></h1>
<p class="page-subtitle">Manage your menu, orders and shop settings</p>

<!-- Seller tabs -->
<div class="seller-tab-bar">
  <a href="/seller/dashboard.php" class="seller-tab active">Dashboard</a>
  <a href="/seller/menu.php"      class="seller-tab">Menu Items</a>
  <a href="/seller/orders.php"    class="seller-tab">Orders</a>
  <a href="/seller/settings.php"  class="seller-tab">Shop Settings</a>
</div>

<?php if ($_GET['new'] ?? false): ?>
<div class="ae-alert ae-alert-success">
  🎉 Shop created! Now add some menu items to get started.
  <a href="/seller/menu.php" class="fw-bold ms-2">Add items →</a>
</div>
<?php endif; ?>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php $stats = [
    ['fa-receipt',     'Total Orders',    $totalOrders->fetchColumn(),  'var(--teal)'],
    ['fa-clock',       'Active Orders',   $pendingOrders->fetchColumn(),'var(--warning)'],
    ['fa-coins',       'Revenue (EFT)',   'R'.number_format($revenue->fetchColumn(),2), 'var(--success)'],
    ['fa-utensils',    'Menu Items',      $menuItems->fetchColumn(),    '#7B5EA7'],
  ]; foreach ($stats as [$icon, $label, $value, $color]): ?>
  <div class="col-6 col-lg-3">
    <div class="ae-card text-center py-4" style="border-top:4px solid <?= $color ?>">
      <i class="fa-solid <?= $icon ?>" style="font-size:1.6rem;color:<?= $color ?>;margin-bottom:8px"></i>
      <div style="font-size:1.6rem;font-weight:800;font-family:'Poppins',sans-serif;color:var(--text-dark)"><?= $value ?></div>
      <div style="font-size:.8rem;color:var(--text-light)"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Recent orders -->
<div class="ae-card">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="ae-card-title mb-0">Recent Orders</div>
    <a href="/seller/orders.php" style="font-size:.88rem;color:var(--teal);font-weight:700">View all →</a>
  </div>

  <?php if (empty($recentOrders)): ?>
  <div class="empty-state py-4">
    <i class="fa-solid fa-inbox" style="font-size:2.5rem"></i>
    <h4 class="mt-3">No orders yet</h4>
    <p>Orders will appear here once customers start buying</p>
  </div>
  <?php else: ?>
  <div class="table-responsive">
    <table class="ae-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Customer</th>
          <th>Items</th>
          <th>Total</th>
          <th>Status</th>
          <th>Date</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($recentOrders as $o):
        $iStmt = $db->prepare("SELECT GROUP_CONCAT(quantity,'× ',item_name SEPARATOR ', ') AS summary FROM order_items WHERE order_id = ?");
        $iStmt->execute([$o['id']]);
        $summary = $iStmt->fetchColumn();
      ?>
      <tr>
        <td><strong>#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></strong></td>
        <td><?= h($o['buyer_name']) ?></td>
        <td style="max-width:200px;font-size:.82rem;color:var(--text-mid)"><?= h($summary) ?></td>
        <td><strong>R<?= number_format($o['total'],2) ?></strong></td>
        <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucwords(str_replace('_',' ',$o['status'])) ?></span></td>
        <td style="font-size:.82rem;color:var(--text-light)"><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
        <td>
          <a href="/seller/orders.php?id=<?= $o['id'] ?>" class="btn-outline-teal" style="padding:4px 14px;font-size:.8rem">Manage</a>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
