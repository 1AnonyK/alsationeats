<?php
$pageTitle = 'Shop Settings — Alsation Eats';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();
if (!isSeller()) { header('Location: /seller/setup.php'); exit; }

$vendorId = currentVendorId();
$db = db();

$vStmt = $db->prepare("SELECT * FROM vendors WHERE id = ?");
$vStmt->execute([$vendorId]);
$vendor = $vStmt->fetch();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name   = trim($_POST['shop_name'] ?? '');
    $desc   = trim($_POST['description'] ?? '');
    $active = isset($_POST['is_active']) ? 1 : 0;

    if (strlen($name) < 2) {
        $error = 'Shop name is too short.';
    } else {
        $banner = handleUpload('banner', UPLOAD_VENDOR, ['jpg','jpeg','png','webp']);
        if ($banner) {
            $db->prepare("UPDATE vendors SET name=?,description=?,is_active=?,banner_img=? WHERE id=?")
               ->execute([$name, $desc, $active, $banner, $vendorId]);
        } else {
            $db->prepare("UPDATE vendors SET name=?,description=?,is_active=? WHERE id=?")
               ->execute([$name, $desc, $active, $vendorId]);
        }
        // Re-fetch
        $vStmt->execute([$vendorId]);
        $vendor  = $vStmt->fetch();
        $success = 'Shop settings updated!';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Shop Settings</h1>
<p class="page-subtitle">Update your shop profile</p>

<div class="seller-tab-bar">
  <a href="/seller/dashboard.php" class="seller-tab">Dashboard</a>
  <a href="/seller/menu.php"      class="seller-tab">Menu Items</a>
  <a href="/seller/orders.php"    class="seller-tab">Orders</a>
  <a href="/seller/settings.php"  class="seller-tab active">Shop Settings</a>
</div>

<?php if ($error):   ?><div class="ae-alert ae-alert-danger"><?= h($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

<div class="row">
  <div class="col-lg-7">
    <div class="ae-card ae-form">
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <div class="mb-3">
          <label class="form-label">Shop Name</label>
          <input type="text" name="shop_name" class="form-control" required value="<?= h($vendor['name']) ?>">
        </div>
        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"><?= h($vendor['description']) ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label">Banner Image</label>
          <?php if ($vendor['banner_img']): ?>
          <div class="mb-2">
            <img src="/uploads/vendor/<?= h($vendor['banner_img']) ?>"
                 style="width:100%;height:140px;object-fit:cover;border-radius:10px" alt="">
            <small style="color:var(--text-light)">Upload new image to replace</small>
          </div>
          <?php endif; ?>
          <input type="file" name="banner" class="form-control" accept=".jpg,.jpeg,.png,.webp">
        </div>
        <div class="mb-4 form-check">
          <input type="checkbox" name="is_active" class="form-check-input" id="activeCheck"
                 <?= $vendor['is_active'] ? 'checked' : '' ?>>
          <label class="form-check-label" for="activeCheck">Shop is active (visible to customers)</label>
        </div>
        <button type="submit" class="btn-teal">Save Settings</button>
      </form>
    </div>
  </div>
  <div class="col-lg-5">
    <div class="ae-card">
      <div class="ae-card-title">Your Shop URL</div>
      <p style="font-size:.85rem;color:var(--text-mid)">Share this link with customers:</p>
      <code style="background:#F0F8FB;padding:10px 14px;border-radius:8px;display:block;font-size:.85rem;word-break:break-all">
        <?= SITE_URL ?>/vendor.php?id=<?= $vendorId ?>
      </code>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
