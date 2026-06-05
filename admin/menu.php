<?php
$pageTitle = 'All Menu Items — Admin';
require_once __DIR__ . '/header.php';

$db = db();
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $itemId = (int)($_POST['item_id'] ?? 0);
    if ($action === 'toggle' && $itemId) {
        $db->prepare("UPDATE menu_items SET is_available=1-is_available WHERE id=?")->execute([$itemId]);
        $success = 'Item availability updated.';
    } elseif ($action === 'delete' && $itemId) {
        $db->prepare("DELETE FROM menu_items WHERE id=?")->execute([$itemId]);
        $success = 'Menu item deleted.';
    }
}

$vendorFilter = (int)($_GET['vendor'] ?? 0);
$sql = "SELECT mi.*, v.name AS vendor_name FROM menu_items mi JOIN vendors v ON v.id=mi.vendor_id";
$params = [];
if ($vendorFilter) { $sql .= " WHERE mi.vendor_id=?"; $params[]=$vendorFilter; }
$sql .= " ORDER BY v.name, mi.category, mi.name";
$stmt = $db->prepare($sql); $stmt->execute($params);
$items = $stmt->fetchAll();

$vendors = $db->query("SELECT id, name FROM vendors ORDER BY name")->fetchAll();
?>

<h2 style="font-family:'Poppins',sans-serif;font-weight:800;margin-bottom:4px">All Menu Items</h2>
<p style="color:#6B8499;margin-bottom:24px">Cross-vendor view of all food items</p>

<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

<form class="d-flex gap-2 mb-4" method="GET" style="max-width:320px">
  <select name="vendor" class="form-select form-select-sm">
    <option value="">All vendors</option>
    <?php foreach ($vendors as $v): ?>
    <option value="<?= $v['id'] ?>" <?= $vendorFilter===$v['id']?'selected':'' ?>><?= h($v['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn-teal" style="padding:6px 16px;font-size:.85rem">Filter</button>
</form>

<div class="stat-card" style="border-top:none">
  <div class="table-responsive">
    <table class="ae-table">
      <thead>
        <tr><th>Image</th><th>Name</th><th>Vendor</th><th>Category</th><th>Price</th><th>Available</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($items as $item): ?>
      <tr>
        <td>
          <?php if ($item['image']): ?>
          <img src="/uploads/menu/<?= h($item['image']) ?>"
               style="width:44px;height:44px;border-radius:6px;object-fit:cover">
          <?php else: ?>
          <div style="width:44px;height:44px;border-radius:6px;background:var(--teal-light);display:flex;align-items:center;justify-content:center;color:var(--teal)">
            <i class="fa-solid fa-utensils" style="font-size:.8rem"></i>
          </div>
          <?php endif; ?>
        </td>
        <td><strong><?= h($item['name']) ?></strong><br>
            <span style="font-size:.78rem;color:#6B8499"><?= h(substr($item['description'],0,50)) ?>...</span></td>
        <td><?= h($item['vendor_name']) ?></td>
        <td><?= h($item['category']) ?></td>
        <td><strong>R<?= number_format($item['price'],2) ?></strong></td>
        <td>
          <form method="POST" style="margin:0">
            <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
            <input type="hidden" name="action"  value="toggle">
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <button type="submit" style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:<?= $item['is_available']?'var(--success)':'#ccc' ?>">
              <i class="fa-solid fa-circle-check"></i>
            </button>
          </form>
        </td>
        <td>
          <form method="POST" style="margin:0" onsubmit="return confirm('Delete this item?')">
            <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
            <input type="hidden" name="action"  value="delete">
            <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
            <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--danger)">
              <i class="fa-solid fa-trash"></i>
            </button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($items)): ?>
      <tr><td colspan="7" style="text-align:center;color:#6B8499;padding:24px">No menu items found</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
