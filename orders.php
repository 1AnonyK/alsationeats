<?php
$pageTitle = 'My Orders — Alsation Eats';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = currentUserId();

$stmt = db()->prepare("
    SELECT o.*, v.name AS vendor_name,
           p.status AS payment_status
    FROM orders o
    JOIN vendors v ON v.id = o.vendor_id
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">My Orders</h1>
<p class="page-subtitle">Track all your orders here</p>

<?php if (empty($orders)): ?>
<div class="empty-state">
  <i class="fa-solid fa-receipt"></i>
  <h4>No orders yet</h4>
  <p>Your order history will appear here</p>
  <a href="/index.php" class="btn-teal mt-3">Start Ordering</a>
</div>
<?php else: ?>

<?php foreach ($orders as $order):
  // Get order items
  $iStmt = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
  $iStmt->execute([$order['id']]);
  $items = $iStmt->fetchAll();
?>
<div class="order-card status-<?= $order['status'] ?>">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h5 style="font-family:'Poppins',sans-serif;font-weight:700;margin:0">
        <?= h($order['vendor_name']) ?>
      </h5>
      <small style="color:var(--text-light)">
        Order #<?= str_pad($order['id'], 5, '0', STR_PAD_LEFT) ?> ·
        <?= date('d M Y, H:i', strtotime($order['created_at'])) ?>
      </small>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <span class="status-badge status-<?= $order['status'] ?>">
        <?= ucwords(str_replace('_', ' ', $order['status'])) ?>
      </span>
      <?php if ($order['payment_status']): ?>
      <span class="status-badge"
            style="background:#F0F0F0;color:#555">
        Payment: <?= ucfirst($order['payment_status']) ?>
      </span>
      <?php endif; ?>
    </div>
  </div>

  <div class="mb-3" style="font-size:.88rem;color:var(--text-mid)">
    <?php foreach ($items as $i => $oi): ?>
    <?= $oi['quantity'] ?>× <?= h($oi['item_name']) ?><?= $i < count($items) - 1 ? ', ' : '' ?>
    <?php endforeach; ?>
  </div>

  <div class="d-flex justify-content-between align-items-center">
    <strong style="font-family:'Poppins',sans-serif;color:var(--teal-dark);font-size:1rem">
      Total: R<?= number_format($order['total'], 2) ?>
    </strong>
    <?php if ($order['status'] === 'pending_payment'): ?>
    <a href="/checkout.php?order_id=<?= $order['id'] ?>" class="btn-teal" style="font-size:.85rem;padding:8px 20px">
      Pay Now
    </a>
    <?php elseif ($order['status'] === 'payment_uploaded'): ?>
    <a href="/checkout.php?order_id=<?= $order['id'] ?>" class="btn-outline-teal" style="font-size:.85rem;padding:6px 18px">
      View Upload
    </a>
    <?php endif; ?>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
