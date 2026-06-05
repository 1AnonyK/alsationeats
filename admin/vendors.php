<?php
$pageTitle = 'Vendors — Admin';
require_once __DIR__ . '/header.php';

$db = db();
$success = $error = '';

// Toggle active / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action   = $_POST['action'] ?? '';
    $vendorId = (int)($_POST['vendor_id'] ?? 0);

    if ($action === 'toggle' && $vendorId) {
        $db->prepare("UPDATE vendors SET is_active = 1-is_active WHERE id=?")->execute([$vendorId]);
        $success = 'Vendor status updated.';
    } elseif ($action === 'delete' && $vendorId) {
        $db->prepare("DELETE FROM vendors WHERE id=?")->execute([$vendorId]);
        $success = 'Vendor removed.';
    }
}

$vendors = $db->query("
    SELECT v.*, u.name AS owner_name, u.email AS owner_email,
           (SELECT COUNT(*) FROM menu_items  WHERE vendor_id=v.id) AS item_count,
           (SELECT COUNT(*) FROM orders      WHERE vendor_id=v.id) AS order_count,
           (SELECT COALESCE(SUM(subtotal),0) FROM orders WHERE vendor_id=v.id AND status NOT IN ('cancelled','pending_payment')) AS revenue
    FROM vendors v
    JOIN users u ON u.id = v.user_id
    ORDER BY v.created_at DESC
")->fetchAll();
?>

<h2 style="font-family:'Poppins',sans-serif;font-weight:800;margin-bottom:4px">Vendors</h2>
<p style="color:#6B8499;margin-bottom:24px">Manage all vendor shops on the platform</p>

<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>
<?php if ($error):   ?><div class="ae-alert ae-alert-danger"><?= h($error) ?></div><?php endif; ?>

<div class="row g-3 mb-4">
  <?php foreach ($vendors as $v): ?>
  <div class="col-md-6 col-lg-4">
    <div class="stat-card" style="border-top-color:<?= $v['is_active']?'var(--success)':'var(--danger)' ?>">
      <?php if ($v['banner_img']): ?>
      <img src="/uploads/vendor/<?= h($v['banner_img']) ?>"
           style="width:100%;height:100px;object-fit:cover;border-radius:8px;margin-bottom:12px">
      <?php endif; ?>
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div style="font-weight:800;font-family:'Poppins',sans-serif"><?= h($v['name']) ?></div>
          <div style="font-size:.8rem;color:#6B8499">Owner: <?= h($v['owner_name']) ?></div>
          <div style="font-size:.78rem;color:#6B8499"><?= h($v['owner_email']) ?></div>
        </div>
        <span class="status-badge <?= $v['is_active']?'status-ready':'status-cancelled' ?>">
          <?= $v['is_active']?'Active':'Inactive' ?>
        </span>
      </div>

      <div class="d-flex gap-3 mt-3" style="font-size:.82rem;color:#6B8499">
        <span><strong style="color:var(--teal-dark)"><?= $v['item_count'] ?></strong> items</span>
        <span><strong style="color:var(--teal-dark)"><?= $v['order_count'] ?></strong> orders</span>
        <span><strong style="color:var(--success)">R<?= number_format($v['revenue'],2) ?></strong></span>
      </div>

      <div class="d-flex gap-2 mt-3">
        <a href="/vendor.php?id=<?= $v['id'] ?>" target="_blank"
           class="btn-outline-teal" style="padding:5px 12px;font-size:.8rem">View Shop</a>

        <form method="POST" style="margin:0">
          <input type="hidden" name="csrf"      value="<?= csrfToken() ?>">
          <input type="hidden" name="action"    value="toggle">
          <input type="hidden" name="vendor_id" value="<?= $v['id'] ?>">
          <button type="submit" class="btn-teal" style="padding:5px 12px;font-size:.8rem;background:<?= $v['is_active']?'var(--warning)':'var(--success)' ?>">
            <?= $v['is_active']?'Deactivate':'Activate' ?>
          </button>
        </form>

        <form method="POST" style="margin:0" onsubmit="return confirm('Delete this vendor and all their data?')">
          <input type="hidden" name="csrf"      value="<?= csrfToken() ?>">
          <input type="hidden" name="action"    value="delete">
          <input type="hidden" name="vendor_id" value="<?= $v['id'] ?>">
          <button type="submit" style="background:none;border:none;color:var(--danger);cursor:pointer;font-size:1rem">
            <i class="fa-solid fa-trash"></i>
          </button>
        </form>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php if (empty($vendors)): ?>
  <div class="col-12"><div class="ae-alert ae-alert-info">No vendors yet.</div></div>
  <?php endif; ?>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
