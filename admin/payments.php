<?php
$pageTitle = 'Payments — Admin';
require_once __DIR__ . '/header.php';

$db = db();
$success = $error = '';

// Handle verify/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action  = $_POST['action'] ?? '';
    $notes   = trim($_POST['notes'] ?? '');

    if ($action === 'verify') {
        $db->prepare("UPDATE payments SET status='verified', verified_at=NOW(), verified_by=?, notes=? WHERE order_id=?")
           ->execute([currentUserId(), $notes, $orderId]);
        $db->prepare("UPDATE orders SET status='confirmed' WHERE id=?")->execute([$orderId]);
        $success = "Payment for order #".str_pad($orderId,5,'0',STR_PAD_LEFT)." verified and order confirmed.";
    } elseif ($action === 'reject') {
        $db->prepare("UPDATE payments SET status='rejected', verified_at=NOW(), verified_by=?, notes=? WHERE order_id=?")
           ->execute([currentUserId(), $notes ?: 'Payment rejected by admin.', $orderId]);
        $db->prepare("UPDATE orders SET status='cancelled' WHERE id=?")->execute([$orderId]);
        $success = "Payment rejected and order cancelled.";
    }
}

// Single order view
$viewId = (int)($_GET['id'] ?? 0);
$single = null;
if ($viewId) {
    $s = $db->prepare("
        SELECT p.*, o.*, o.id AS order_id, u.name AS buyer_name, u.phone AS buyer_phone, u.email AS buyer_email,
               v.name AS vendor_name
        FROM payments p
        JOIN orders  o ON o.id = p.order_id
        JOIN users   u ON u.id = o.buyer_id
        JOIN vendors v ON v.id = o.vendor_id
        WHERE p.order_id = ?
    ");
    $s->execute([$viewId]);
    $single = $s->fetch();
}

// List
$filter = $_GET['filter'] ?? 'pending';
$where  = match($filter) {
    'pending'  => "p.status='pending'",
    'verified' => "p.status='verified'",
    'rejected' => "p.status='rejected'",
    default    => "1=1"
};
$payments = $db->query("
    SELECT p.*, o.total, o.id AS order_id, u.name AS buyer_name, v.name AS vendor_name
    FROM payments p
    JOIN orders  o ON o.id = p.order_id
    JOIN users   u ON u.id = o.buyer_id
    JOIN vendors v ON v.id = o.vendor_id
    WHERE $where
    ORDER BY p.uploaded_at DESC
")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h2 style="font-family:'Poppins',sans-serif;font-weight:800">Payments</h2>
</div>
<p style="color:#6B8499;margin-bottom:24px">Review EFT proof of payments submitted by customers</p>

<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="ae-alert ae-alert-danger"><?= h($error) ?></div><?php endif; ?>

<?php if ($single): ?>
<!-- Single payment detail view -->
<div class="row g-4 mb-4">
  <div class="col-lg-6">
    <div class="stat-card" style="border-top:none">
      <h5 style="font-family:'Poppins',sans-serif;font-weight:700;margin-bottom:16px">
        Order #<?= str_pad($single['order_id'],5,'0',STR_PAD_LEFT) ?> Payment
      </h5>
      <div class="ae-table-wrapper">
        <?php foreach ([
          'Buyer'       => $single['buyer_name'].' ('.$single['buyer_email'].')',
          'Phone'       => $single['buyer_phone'] ?: '—',
          'Vendor'      => $single['vendor_name'],
          'Amount'      => 'R'.number_format($single['total'],2),
          'Reference'   => $single['reference'] ?: '—',
          'Uploaded'    => date('d M Y H:i', strtotime($single['uploaded_at'])),
          'Pay Status'  => ucfirst($single['status']),
        ] as $k=>$v): ?>
        <div class="bank-row"><span><?= $k ?></span><strong><?= h($v) ?></strong></div>
        <?php endforeach; ?>
      </div>

      <?php if ($single['status'] === 'pending'): ?>
      <form method="POST" class="mt-4 ae-form">
        <input type="hidden" name="csrf"     value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= $single['order_id'] ?>">
        <div class="mb-3">
          <label class="form-label">Notes (optional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="Add a note for rejection reason etc."></textarea>
        </div>
        <div class="d-flex gap-2">
          <button type="submit" name="action" value="verify"
                  class="btn-teal flex-grow-1">✅ Verify Payment</button>
          <button type="submit" name="action" value="reject"
                  class="btn-outline-teal flex-grow-1"
                  style="border-color:var(--danger);color:var(--danger)"
                  onclick="return confirm('Reject this payment?')">
            ❌ Reject
          </button>
        </div>
      </form>
      <?php else: ?>
      <div class="ae-alert ae-alert-<?= $single['status']==='verified'?'success':'danger' ?> mt-3">
        Payment <?= $single['status'] ?>
        <?php if ($single['notes']): ?> — <?= h($single['notes']) ?><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
  <div class="col-lg-6">
    <div class="stat-card" style="border-top:none;text-align:center">
      <h5 style="font-family:'Poppins',sans-serif;font-weight:700;margin-bottom:16px">Proof of Payment</h5>
      <?php $ext = strtolower(pathinfo($single['proof_file'],PATHINFO_EXTENSION)); ?>
      <?php if (in_array($ext,['jpg','jpeg','png','gif','webp'])): ?>
      <img src="/uploads/proof/<?= h($single['proof_file']) ?>"
           style="max-width:100%;max-height:400px;border-radius:10px;box-shadow:0 4px 20px rgba(0,0,0,.1)">
      <?php else: ?>
      <a href="/uploads/proof/<?= h($single['proof_file']) ?>" target="_blank" class="btn-teal">
        <i class="fa-solid fa-file-pdf"></i> Open PDF
      </a>
      <?php endif; ?>
    </div>
  </div>
</div>
<a href="/admin/payments.php" style="color:var(--teal);font-weight:700">← Back to all payments</a>
<hr style="margin:24px 0">
<?php endif; ?>

<!-- Filter tabs -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <?php foreach (['pending'=>'Pending','verified'=>'Verified','rejected'=>'Rejected','all'=>'All'] as $k=>$l): ?>
  <a href="?filter=<?= $k ?>" class="seller-tab <?= $filter===$k?'active':'' ?>"
     style="font-size:.82rem;padding:6px 16px"><?= $l ?></a>
  <?php endforeach; ?>
</div>

<div class="stat-card" style="border-top:none">
  <div class="table-responsive">
    <table class="ae-table">
      <thead>
        <tr><th>#Order</th><th>Buyer</th><th>Vendor</th><th>Amount</th><th>Ref</th><th>Status</th><th>Uploaded</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($payments as $p): ?>
      <tr>
        <td><strong>#<?= str_pad($p['order_id'],5,'0',STR_PAD_LEFT) ?></strong></td>
        <td><?= h($p['buyer_name']) ?></td>
        <td><?= h($p['vendor_name']) ?></td>
        <td>R<?= number_format($p['total'],2) ?></td>
        <td style="font-size:.82rem"><?= h($p['reference'] ?: '—') ?></td>
        <td><span class="status-badge status-<?= $p['status']==='verified'?'confirmed':($p['status']==='rejected'?'cancelled':'payment_uploaded') ?>"><?= ucfirst($p['status']) ?></span></td>
        <td style="font-size:.82rem;color:#6B8499"><?= date('d M H:i',strtotime($p['uploaded_at'])) ?></td>
        <td><a href="?id=<?= $p['order_id'] ?>&filter=<?= $filter ?>" class="btn-teal" style="padding:4px 12px;font-size:.78rem">Review</a></td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($payments)): ?>
      <tr><td colspan="8" style="text-align:center;color:#6B8499;padding:24px">No payments found</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
