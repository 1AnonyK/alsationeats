<?php
$pageTitle = 'Orders — Alsation Eats Seller';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isSeller()) { header('Location: /seller/setup.php'); exit; }

$vendorId = currentVendorId();
$db = db();
$success = '';

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $allowed   = ['confirmed','preparing','ready','collected','cancelled'];
    if (in_array($newStatus, $allowed)) {
        // Verify order belongs to this vendor
        $check = $db->prepare("SELECT id FROM orders WHERE id = ? AND vendor_id = ?");
        $check->execute([$orderId, $vendorId]);
        if ($check->fetch()) {
            $db->prepare("UPDATE orders SET status = ? WHERE id = ?")->execute([$newStatus, $orderId]);
            $success = "Order #" . str_pad($orderId,5,'0',STR_PAD_LEFT) . " updated to " . ucwords(str_replace('_',' ',$newStatus)) . ".";
        }
    }
}

// Filter
$filter = $_GET['filter'] ?? 'active';
$where  = match($filter) {
    'pending' => "o.status = 'payment_uploaded'",
    'active'  => "o.status IN ('payment_uploaded','confirmed','preparing','ready')",
    'done'    => "o.status IN ('collected','cancelled')",
    default   => "1=1"
};

$stmt = $db->prepare("
    SELECT o.*, u.name AS buyer_name, u.phone AS buyer_phone,
           p.proof_file, p.reference AS pay_ref, p.status AS pay_status
    FROM orders o
    JOIN users u ON u.id = o.buyer_id
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.vendor_id = ? AND $where
    ORDER BY o.created_at DESC
");
$stmt->execute([$vendorId]);
$orders = $stmt->fetchAll();

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Orders</h1>
<p class="page-subtitle">Review and manage your incoming orders</p>

<div class="seller-tab-bar">
  <a href="/seller/dashboard.php" class="seller-tab">Dashboard</a>
  <a href="/seller/menu.php"      class="seller-tab">Menu Items</a>
  <a href="/seller/orders.php"    class="seller-tab active">Orders</a>
  <a href="/seller/settings.php"  class="seller-tab">Shop Settings</a>
</div>

<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

<!-- Filter tabs -->
<div class="d-flex gap-2 mb-4 flex-wrap">
  <?php foreach (['active'=>'Active','pending'=>'Needs Verification','done'=>'Completed','all'=>'All'] as $key=>$label): ?>
  <a href="?filter=<?= $key ?>"
     class="seller-tab <?= $filter===$key?'active':'' ?>"
     style="font-size:.82rem;padding:6px 16px">
    <?= $label ?>
  </a>
  <?php endforeach; ?>
</div>

<?php if (empty($orders)): ?>
<div class="empty-state">
  <i class="fa-solid fa-inbox"></i>
  <h4>No orders here</h4>
  <p>Orders matching this filter will show here</p>
</div>
<?php else: ?>

<?php foreach ($orders as $o):
  $iStmt = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
  $iStmt->execute([$o['id']]);
  $items = $iStmt->fetchAll();
?>
<div class="order-card status-<?= $o['status'] ?> mb-3">
  <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
    <div>
      <h5 style="font-family:'Poppins',sans-serif;font-weight:700;margin:0">
        Order #<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?>
        <span class="status-badge status-<?= $o['status'] ?> ms-2">
          <?= ucwords(str_replace('_',' ',$o['status'])) ?>
        </span>
      </h5>
      <small style="color:var(--text-light)">
        <?= date('d M Y, H:i', strtotime($o['created_at'])) ?> ·
        Customer: <strong><?= h($o['buyer_name']) ?></strong>
        <?= $o['buyer_phone'] ? '· ' . h($o['buyer_phone']) : '' ?>
      </small>
    </div>
    <div style="font-family:'Poppins',sans-serif;font-weight:800;font-size:1.1rem;color:var(--teal-dark)">
      R<?= number_format($o['total'],2) ?>
    </div>
  </div>

  <!-- Items -->
  <div class="mb-3" style="background:#F8FDFF;border-radius:8px;padding:12px">
    <?php foreach ($items as $it): ?>
    <div class="d-flex justify-content-between" style="font-size:.88rem;padding:3px 0">
      <span><?= $it['quantity'] ?>× <?= h($it['item_name']) ?></span>
      <strong>R<?= number_format($it['price']*$it['quantity'],2) ?></strong>
    </div>
    <?php endforeach; ?>
    <?php if ($o['notes']): ?>
    <div class="mt-2 pt-2" style="border-top:1px dashed #ccc;font-size:.82rem;color:var(--warning)">
      <i class="fa-solid fa-note-sticky"></i> <?= h($o['notes']) ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Payment proof -->
  <?php if ($o['proof_file']): ?>
  <div class="d-flex align-items-center gap-3 mb-3 p-2"
       style="background:var(--teal-light);border-radius:8px;font-size:.85rem">
    <i class="fa-solid fa-file-invoice" style="color:var(--teal)"></i>
    <div>
      <strong>Proof of Payment uploaded</strong>
      <?php if ($o['pay_ref']): ?> · Ref: <?= h($o['pay_ref']) ?><?php endif; ?>
      <span class="ms-2 status-badge status-<?= $o['status'] === 'payment_uploaded' ? 'payment_uploaded' : $o['status'] ?>">
        <?= ucfirst($o['pay_status'] ?? 'pending') ?>
      </span>
    </div>
    <a href="/uploads/proof/<?= h($o['proof_file']) ?>" target="_blank"
       class="btn-teal ms-auto" style="padding:4px 14px;font-size:.8rem">
      View
    </a>
  </div>
  <?php endif; ?>

  <!-- Status actions -->
  <form method="POST" class="d-flex gap-2 flex-wrap">
    <input type="hidden" name="csrf"     value="<?= csrfToken() ?>">
    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">

    <?php
    $transitions = match($o['status']) {
      'payment_uploaded' => ['confirmed'=>'✅ Confirm','cancelled'=>'❌ Reject'],
      'confirmed'        => ['preparing'=>'🍳 Preparing','cancelled'=>'❌ Cancel'],
      'preparing'        => ['ready'=>'🔔 Ready for Collection'],
      'ready'            => ['collected'=>'✔ Collected'],
      default            => []
    };
    foreach ($transitions as $status => $label): ?>
    <button type="submit" name="new_status" value="<?= $status ?>"
            class="<?= str_starts_with($status,'cancel')?'btn-outline-teal':'btn-teal' ?>"
            style="padding:8px 20px;font-size:.85rem">
      <?= $label ?>
    </button>
    <?php endforeach; ?>
  </form>
</div>
<?php endforeach; ?>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
