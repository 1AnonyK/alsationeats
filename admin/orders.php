<?php
$pageTitle = 'Orders — Admin';
require_once __DIR__ . '/header.php';

$db = db();
$success = '';

// Update status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['new_status'] ?? '';
    $allowed   = ['pending_payment','payment_uploaded','confirmed','preparing','ready','collected','cancelled'];
    if (in_array($newStatus, $allowed) && $orderId) {
        $db->prepare("UPDATE orders SET status=? WHERE id=?")->execute([$newStatus,$orderId]);
        $success = "Order #".str_pad($orderId,5,'0',STR_PAD_LEFT)." updated to ".ucwords(str_replace('_',' ',$newStatus)).".";
    }
}

$filter = $_GET['filter'] ?? 'all';
$where  = match($filter) {
    'pending'  => "o.status='payment_uploaded'",
    'active'   => "o.status IN ('confirmed','preparing','ready')",
    'done'     => "o.status IN ('collected','cancelled')",
    default    => "1=1"
};

$orders = $db->query("
    SELECT o.*, v.name AS vendor_name, u.name AS buyer_name
    FROM orders o
    JOIN vendors v ON v.id = o.vendor_id
    JOIN users   u ON u.id = o.buyer_id
    WHERE $where
    ORDER BY o.created_at DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 style="font-family:'Poppins',sans-serif;font-weight:800">All Orders</h2>
</div>
<p style="color:#6B8499;margin-bottom:24px">View and manage every order on the platform</p>

<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php foreach (['all'=>'All','pending'=>'Needs Attention','active'=>'Active','done'=>'Completed'] as $k=>$l): ?>
  <a href="?filter=<?= $k ?>" class="seller-tab <?= $filter===$k?'active':'' ?>"
     style="font-size:.82rem;padding:6px 16px"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<div class="stat-card" style="border-top:none">
  <div class="table-responsive">
    <table class="ae-table">
      <thead>
        <tr><th>#</th><th>Buyer</th><th>Vendor</th><th>Total</th><th>Status</th><th>Date</th><th>Change Status</th></tr>
      </thead>
      <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td><strong>#<?= str_pad($o['id'],5,'0',STR_PAD_LEFT) ?></strong></td>
        <td><?= h($o['buyer_name']) ?></td>
        <td><?= h($o['vendor_name']) ?></td>
        <td>R<?= number_format($o['total'],2) ?></td>
        <td><span class="status-badge status-<?= $o['status'] ?>"><?= ucwords(str_replace('_',' ',$o['status'])) ?></span></td>
        <td style="font-size:.8rem;color:#6B8499"><?= date('d M Y H:i',strtotime($o['created_at'])) ?></td>
        <td>
          <form method="POST" class="d-flex gap-1">
            <input type="hidden" name="csrf"     value="<?= csrfToken() ?>">
            <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
            <select name="new_status" class="form-select form-select-sm" style="font-size:.78rem;max-width:140px">
              <?php foreach (['pending_payment','payment_uploaded','confirmed','preparing','ready','collected','cancelled'] as $s): ?>
              <option value="<?= $s ?>" <?= $o['status']===$s?'selected':'' ?>>
                <?= ucwords(str_replace('_',' ',$s)) ?>
              </option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn-teal" style="padding:4px 10px;font-size:.78rem">→</button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($orders)): ?>
      <tr><td colspan="7" style="text-align:center;color:#6B8499;padding:24px">No orders found</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
