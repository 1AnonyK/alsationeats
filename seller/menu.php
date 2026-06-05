<?php
$pageTitle = 'Menu Items — Alsation Eats';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isSeller()) { header('Location: /seller/setup.php'); exit; }

$vendorId = currentVendorId();
$db = db();
$error = $success = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'edit') {
        $name     = trim($_POST['name'] ?? '');
        $desc     = trim($_POST['description'] ?? '');
        $price    = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? 'General');
        $avail    = isset($_POST['is_available']) ? 1 : 0;
        $itemId   = (int)($_POST['item_id'] ?? 0);

        if (!$name || $price <= 0) {
            $error = 'Name and a valid price are required.';
        } else {
            $imgFile = handleUpload('image', UPLOAD_MENU, ['jpg','jpeg','png','gif','webp']);

            if ($action === 'add') {
                $db->prepare("INSERT INTO menu_items (vendor_id, name, description, price, image, category, is_available) VALUES (?,?,?,?,?,?,?)")
                   ->execute([$vendorId, $name, $desc, $price, $imgFile ?: '', $category, $avail]);
                $success = "\"$name\" added to your menu!";
            } else {
                // Edit — only update image if new one uploaded
                if ($imgFile) {
                    $db->prepare("UPDATE menu_items SET name=?,description=?,price=?,image=?,category=?,is_available=? WHERE id=? AND vendor_id=?")
                       ->execute([$name, $desc, $price, $imgFile, $category, $avail, $itemId, $vendorId]);
                } else {
                    $db->prepare("UPDATE menu_items SET name=?,description=?,price=?,category=?,is_available=? WHERE id=? AND vendor_id=?")
                       ->execute([$name, $desc, $price, $category, $avail, $itemId, $vendorId]);
                }
                $success = "\"$name\" updated!";
            }
        }
    } elseif ($action === 'delete') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $db->prepare("DELETE FROM menu_items WHERE id = ? AND vendor_id = ?")->execute([$itemId, $vendorId]);
        $success = 'Item removed from menu.';
    } elseif ($action === 'toggle') {
        $itemId = (int)($_POST['item_id'] ?? 0);
        $db->prepare("UPDATE menu_items SET is_available = 1 - is_available WHERE id = ? AND vendor_id = ?")->execute([$itemId, $vendorId]);
    }
}

// Load items
$items = $db->prepare("SELECT * FROM menu_items WHERE vendor_id = ? ORDER BY category, name");
$items->execute([$vendorId]);
$menuItems = $items->fetchAll();

// Edit item
$editItem = null;
if (isset($_GET['edit'])) {
    $eStmt = $db->prepare("SELECT * FROM menu_items WHERE id = ? AND vendor_id = ?");
    $eStmt->execute([(int)$_GET['edit'], $vendorId]);
    $editItem = $eStmt->fetch();
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Menu Items</h1>
<p class="page-subtitle">Manage your food offerings</p>

<div class="seller-tab-bar">
  <a href="/seller/dashboard.php" class="seller-tab">Dashboard</a>
  <a href="/seller/menu.php"      class="seller-tab active">Menu Items</a>
  <a href="/seller/orders.php"    class="seller-tab">Orders</a>
  <a href="/seller/settings.php"  class="seller-tab">Shop Settings</a>
</div>

<?php if ($_GET['new'] ?? false): ?>
<div class="ae-alert ae-alert-info">Welcome! Add your first menu items below to start receiving orders.</div>
<?php endif; ?>
<?php if ($error):   ?><div class="ae-alert ae-alert-danger"><?= h($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

<div class="row g-4">
  <!-- Add / Edit form -->
  <div class="col-lg-4">
    <div class="ae-card ae-form">
      <div class="ae-card-title">
        <i class="fa-solid <?= $editItem ? 'fa-pen-to-square' : 'fa-plus-circle' ?>" style="color:var(--teal)"></i>
        <?= $editItem ? 'Edit Item' : 'Add New Item' ?>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
        <input type="hidden" name="action"  value="<?= $editItem ? 'edit' : 'add' ?>">
        <?php if ($editItem): ?>
        <input type="hidden" name="item_id" value="<?= $editItem['id'] ?>">
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">Item Name *</label>
          <input type="text" name="name" class="form-control" required
                 value="<?= h($editItem['name'] ?? $_POST['name'] ?? '') ?>"
                 placeholder="e.g. Full Kota, Braai Plate">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="2"
                    placeholder="What's in it?"><?= h($editItem['description'] ?? $_POST['description'] ?? '') ?></textarea>
        </div>
        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label">Price (R) *</label>
            <input type="number" name="price" class="form-control" required step="0.50" min="1"
                   value="<?= $editItem['price'] ?? $_POST['price'] ?? '' ?>" placeholder="0.00">
          </div>
          <div class="col-6">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control"
                   value="<?= h($editItem['category'] ?? $_POST['category'] ?? 'General') ?>"
                   placeholder="e.g. Kotas, Mains">
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label">Photo</label>
          <?php if ($editItem && $editItem['image']): ?>
          <div class="mb-2">
            <img src="/uploads/menu/<?= h($editItem['image']) ?>"
                 style="width:100%;height:120px;object-fit:cover;border-radius:8px">
            <small style="color:var(--text-light)">Upload a new image to replace</small>
          </div>
          <?php endif; ?>
          <input type="file" name="image" class="form-control" accept=".jpg,.jpeg,.png,.gif,.webp">
        </div>
        <div class="mb-4 form-check">
          <input type="checkbox" name="is_available" class="form-check-input" id="availCheck"
                 <?= (!$editItem || $editItem['is_available']) ? 'checked' : '' ?>>
          <label class="form-check-label" for="availCheck">Available for ordering</label>
        </div>
        <button type="submit" class="btn-teal w-100">
          <?= $editItem ? 'Save Changes' : 'Add to Menu' ?>
        </button>
        <?php if ($editItem): ?>
        <a href="/seller/menu.php" class="btn-outline-teal w-100 text-center mt-2" style="display:block">Cancel</a>
        <?php endif; ?>
      </form>
    </div>
  </div>

  <!-- Menu list -->
  <div class="col-lg-8">
    <?php if (empty($menuItems)): ?>
    <div class="ae-card">
      <div class="empty-state py-5">
        <i class="fa-solid fa-utensils"></i>
        <h4>No menu items yet</h4>
        <p>Add your first item using the form</p>
      </div>
    </div>
    <?php else: ?>
    <?php
    $grouped = [];
    foreach ($menuItems as $m) $grouped[$m['category']][] = $m;
    foreach ($grouped as $cat => $items): ?>
    <div class="ae-card mb-3">
      <h6 style="color:var(--teal-dark);font-weight:700;margin-bottom:16px;border-bottom:2px solid var(--teal-light);padding-bottom:8px">
        <?= h($cat) ?>
      </h6>
      <?php foreach ($items as $item): ?>
      <div class="d-flex align-items-center gap-3 py-2" style="border-bottom:1px solid #EEF5F8">
        <?php if ($item['image']): ?>
        <img src="/uploads/menu/<?= h($item['image']) ?>"
             style="width:52px;height:52px;border-radius:8px;object-fit:cover;flex-shrink:0" alt="">
        <?php else: ?>
        <div style="width:52px;height:52px;border-radius:8px;background:var(--teal-light);display:flex;align-items:center;justify-content:center;flex-shrink:0;color:var(--teal)">
          <i class="fa-solid fa-utensils"></i>
        </div>
        <?php endif; ?>

        <div class="flex-grow-1">
          <div style="font-weight:700;font-size:.9rem"><?= h($item['name']) ?></div>
          <div style="font-size:.78rem;color:var(--text-mid)"><?= h(substr($item['description'],0,60)) ?><?= strlen($item['description'])>60?'…':'' ?></div>
        </div>

        <div style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--teal-dark);white-space:nowrap">
          R<?= number_format($item['price'],2) ?>
        </div>

        <!-- Toggle available -->
        <form method="POST" style="margin:0">
          <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
          <input type="hidden" name="action"  value="toggle">
          <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
          <button type="submit" title="<?= $item['is_available']?'Mark unavailable':'Mark available' ?>"
                  style="background:none;border:none;cursor:pointer;font-size:1.1rem;color:<?= $item['is_available']?'var(--success)':'#ccc' ?>">
            <i class="fa-solid fa-circle-check"></i>
          </button>
        </form>

        <a href="/seller/menu.php?edit=<?= $item['id'] ?>" style="color:var(--teal);" title="Edit">
          <i class="fa-solid fa-pen"></i>
        </a>

        <form method="POST" style="margin:0" onsubmit="return confirm('Remove this item?')">
          <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
          <input type="hidden" name="action"  value="delete">
          <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
          <button type="submit" style="background:none;border:none;cursor:pointer;color:var(--danger)" title="Delete">
            <i class="fa-solid fa-trash"></i>
          </button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
