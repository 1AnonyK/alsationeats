<?php
$pageTitle = 'Open My Shop — Alsation Eats';
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

// Already a seller?
if (isSeller()) { header('Location: /seller/dashboard.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name = trim($_POST['shop_name'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if (strlen($name) < 2) {
        $error = 'Please enter a valid shop name.';
    } else {
        $banner = handleUpload('banner', UPLOAD_VENDOR, ['jpg','jpeg','png','webp']);

        db()->prepare("INSERT INTO vendors (user_id, name, description, banner_img) VALUES (?, ?, ?, ?)")
            ->execute([currentUserId(), $name, $desc, $banner ?: '']);

        $vendorId = db()->lastInsertId();
        $_SESSION['vendor_id'] = $vendorId;
        header('Location: /seller/menu.php?new=1'); exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<h1 class="page-title">Open Your Shop 🏪</h1>
<p class="page-subtitle">Start selling on Alsation Eats — it's free to join</p>

<div class="row">
  <div class="col-lg-7">
    <div class="ae-card ae-form">
      <?php if ($error): ?><div class="ae-alert ae-alert-danger"><?= h($error) ?></div><?php endif; ?>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

        <div class="mb-3">
          <label class="form-label">Shop Name</label>
          <input type="text" name="shop_name" class="form-control" required
                 placeholder="e.g. Woza Woza, Ma Bee's Kitchen"
                 value="<?= h($_POST['shop_name'] ?? '') ?>">
        </div>

        <div class="mb-3">
          <label class="form-label">Description</label>
          <textarea name="description" class="form-control" rows="3"
                    placeholder="What do you sell? What makes your food special?"><?= h($_POST['description'] ?? '') ?></textarea>
        </div>

        <div class="mb-4">
          <label class="form-label">Shop Banner Image (optional)</label>
          <div class="upload-zone" onclick="document.getElementById('bannerFile').click()">
            <i class="fa-solid fa-image d-block"></i>
            <p class="mb-0">Click to upload banner image</p>
            <small style="color:var(--text-light)">JPG, PNG, WEBP — recommended 800×400px</small>
          </div>
          <input type="file" id="bannerFile" name="banner" accept=".jpg,.jpeg,.png,.webp" style="display:none">
          <div id="uploadPreview"></div>
        </div>

        <button type="submit" class="btn-teal">Open My Shop →</button>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="ae-card">
      <div class="ae-card-title">How it works</div>
      <?php $steps = [
        ['fa-store', 'Create your shop', 'Set up your shop name, description and banner image.'],
        ['fa-utensils', 'Add your menu', 'Upload your food items with prices and photos.'],
        ['fa-bell', 'Receive orders', 'Customers browse, add to cart and pay via EFT.'],
        ['fa-check-circle', 'Confirm & prepare', 'Verify payment and update order status.'],
      ]; foreach ($steps as $i => [$icon, $title, $desc]): ?>
      <div class="d-flex gap-3 mb-3">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--teal);color:#fff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="fa-solid <?= $icon ?>"></i>
        </div>
        <div>
          <div style="font-weight:700;font-size:.9rem"><?= $title ?></div>
          <div style="font-size:.82rem;color:var(--text-mid)"><?= $desc ?></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
