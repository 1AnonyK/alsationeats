<?php
$pageTitle = 'Admin Dashboard — Alsation Eats';
require_once __DIR__ . '/header.php';

$db = db();

// Stats
$stats = [
    'Total Users'       => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'Total Vendors'     => $db->query("SELECT COUNT(*) FROM vendors WHERE is_active=1")->fetchColumn(),
    'Total Orders'      => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'Pending Payment'   => $db->query("SELECT COUNT(*) FROM orders WHERE status='payment_uploaded'")->fetchColumn(),
    'Platform Revenue'  => 'R'.number_format($db->query("SELECT COALESCE(SUM(platform_fee),0) FROM orders WHERE status NOT IN ('cancelled','pending_payment')")->fetchColumn(),2),
    'Total Revenue'     => 'R'.number_format($db->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status NOT IN ('cancelled','pending_payment')")->fetchColumn(),2),
];

// Recent orders
$recentOrders = $db->query("
    SELECT o.*, v.name AS vendor_name, u.name AS buyer_name
    FROM orders o
    JOIN vendors v ON v.id = o.vendor_id
    JOIN users   u ON u.id = o.buyer_id
    ORDER BY o.created_at DESC
    LIMIT 15
")->fetchAll();

// Recent payments to verify
$pendingPay = $db->query("
    SELECT p.*, o.total, o.buyer_id, u.name AS buyer_name, v.name AS vendor_name
    FROM payments p
    JOIN orders  o ON o.id = p.order_id
    JOIN users   u ON u.id = o.buyer_id
    JOIN vendors v ON v.id = o.vendor_id
    WHERE p.status = 'pending'
    ORDER BY p.uploaded_at DESC
    LIMIT 5
")->fetchAll();
?>

<h2 style="font-family:'Poppins',sans-serif;font-weight:800;margin-bottom:4px">Dashboard</h2>
<p style="color:#6B8499;margin-bottom:28px">Welcome back, <?= h(currentUserName()) ?> 👋</p>

<!-- Stats -->
<div class="row g-3 mb-4">
  <?php $colors=['var(--teal)','#3DAA78','#7B5EA7','var(--warning)','#E05C5C','#4191A8'];
  $i=0; foreach ($stats as $label=>$val): ?>
  <div class="col-6 col-lg-2">
    <div class="stat-card" style="border-top-color:<?= $colors[$i++] ?>">
      <div class="stat-num"><?= $val ?></div>
      <div class="stat-label"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-4">
  <!-- Pending Payments -->
  <div class="col-lg-5">
    <div class="stat-card" style="border-top:none">
      <h5 style="font-family:'Poppins',sans-serif;font-weight:700;margin-bottom:16px">
        <i class="fa-solid fa-clock" style="color:var(--warning)"></i>
        Payments to Verify (<?= count($pendingPay) ?>)
      </h5>
      <?php if (empty($pendingPay)): ?>
      <p style="color:#6B8499;font-size:.9rem">All payments verified ✅</p>
      <?php else: foreach ($pendingPay as $p): ?>
      <div class="d-flex justify-content-between align-items-center py-2" style="border-bottom:1px solid #EEF5F8;font-size:.88rem">
        <div>
          <strong><?= h($p['buyer_name']) ?></strong> →  <?= h($p['vendor_name']) ?><br>
          <span style="color:#6B8499">R<?= number_format($p['total'],2) ?></span>
        </div>
        <a href="/admin/payments.php?id=<?= $p['order_id'] ?>" class="btn-teal" style="padding:4px 12px;font-size:.78rem">Review</a>
      </div>
      <?php endforeach; endif; ?>
      <a href="/admin/payments.php" style="font-size:.85rem;color:var(--teal);font-weight:700;display:block;margin-top:12px">View all payments →</a>
    </div>
  </div>

  <!-- Recent Orders -->
  <div class="col-lg-7">
    <div class="stat-card" style="border-top:none">
      <h5 style="font-family:'Poppins',sans-serif;font-weight:700;margin-bottom:16px">
        <i class="fa-solid fa-receipt" style="color:var(--teal)"></i> Recent Orders
      </h5>
      <div class="table-responsive">
        <table class="ae-table">
          <thead>
            <tr><th>#</th><th>Buyer</th><th>Vendor</th><th>Total</th><th>Status</th></tr>
          </thead>
          <tbody>
          <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><a href="/admin/orders.php?id=<?= $o['id'] ?>" style="font-weight:700">#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></a></td>
            <td><?= h($o['buyer_name']) ?></td>
            <td><?= h($o['vendor_name']) ?></td>
            <td>R<?= number_format($o['total'],2) ?></td>
            <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucwords(str_replace('_',' ',$o['status'])) ?></span></td>
          </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
