<?php
require_once __DIR__ . '/includes/auth.php';

$vendorId = (int)($_GET['id'] ?? 0);
if (!$vendorId) { header('Location: /index.php'); exit; }

$stmt = db()->prepare("SELECT v.*, u.name AS owner_name FROM vendors v JOIN users u ON u.id = v.user_id WHERE v.id = ? AND v.is_active = 1");
$stmt->execute([$vendorId]);
$vendor = $stmt->fetch();
if (!$vendor) { header('Location: /index.php'); exit; }

// Menu items grouped by category
$mStmt = db()->prepare("SELECT * FROM menu_items WHERE vendor_id = ? AND is_available = 1 ORDER BY category, name");
$mStmt->execute([$vendorId]);
$allItems = $mStmt->fetchAll();

$categories = [];
foreach ($allItems as $item) {
    $categories[$item['category']][] = $item;
}

// Is this vendor favourited?
$isFav = false;
if (isLoggedIn()) {
    $fStmt = db()->prepare("SELECT 1 FROM favourites WHERE user_id = ? AND vendor_id = ?");
    $fStmt->execute([currentUserId(), $vendorId]);
    $isFav = (bool)$fStmt->fetchColumn();
}

$pageTitle = h($vendor['name']) . ' — Alsation Eats';
require_once __DIR__ . '/includes/header.php';
?>

<!-- Vendor Hero -->
<div class="vendor-hero">
  <?php if ($vendor['banner_img']): ?>
  <img src="/uploads/vendor/<?= h($vendor['banner_img']) ?>" class="vendor-hero-img" alt="<?= h($vendor['name']) ?>">
  <?php else: ?>
  <div class="vendor-hero-img" style="background:linear-gradient(135deg,var(--teal),var(--teal-dark));"></div>
  <?php endif; ?>
  <div class="vendor-hero-overlay">
    <div>
      <h1 class="vendor-hero-title"><?= h($vendor['name']) ?></h1>
      <div class="vendor-hero-owner">by <?= h($vendor['owner_name']) ?></div>
    </div>
    <?php if (isLoggedIn()): ?>
    <button class="fav-btn <?= $isFav ? 'active' : '' ?> ms-auto"
            data-vendor-id="<?= $vendorId ?>"
            style="font-size:1.4rem;background:none;border:none;cursor:pointer;color:<?= $isFav ? '#ff8fa3' : 'rgba(255,255,255,.7)' ?>">
      <i class="<?= $isFav ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
    </button>
    <?php endif; ?>
  </div>
</div>

<p style="color:var(--text-mid);margin-bottom:28px;"><?= h($vendor['description']) ?></p>

<?php if (empty($allItems)): ?>
<div class="empty-state">
  <i class="fa-solid fa-plate-wheat"></i>
  <h4>No items available right now</h4>
  <p>Check back soon!</p>
</div>
<?php else: ?>

<?php if (!isLoggedIn()): ?>
<div class="ae-alert ae-alert-info mb-4">
  <i class="fa-solid fa-circle-info"></i>
  <a href="/login.php">Log in</a> or <a href="/register.php">sign up</a> to order.
</div>
<?php endif; ?>

<?php foreach ($categories as $cat => $items): ?>
<h3 style="font-size:1.1rem;color:var(--teal-dark);margin:0 0 16px;padding-bottom:6px;border-bottom:2px solid var(--teal-light);">
  <?= h($cat) ?>
</h3>
<div class="menu-grid mb-4">
  <?php foreach ($items as $i => $item): ?>
  <div class="menu-item-card" style="animation-delay:<?= $i * 0.05 ?>s">
    <?php if ($item['image']): ?>
    <img src="/uploads/menu/<?= h($item['image']) ?>" class="menu-item-img" alt="<?= h($item['name']) ?>">
    <?php else: ?>
    <div class="menu-item-img-placeholder"><i class="fa-solid fa-utensils"></i></div>
    <?php endif; ?>
    <div class="menu-item-body">
      <div class="menu-item-name"><?= h($item['name']) ?></div>
      <?php if ($item['description']): ?>
      <div class="menu-item-desc"><?= h($item['description']) ?></div>
      <?php endif; ?>
      <div class="menu-item-footer">
        <span class="menu-item-price">R<?= number_format($item['price'], 2) ?></span>
        <?php if (isLoggedIn()): ?>
        <button class="btn-add-cart"
                data-item-id="<?= $item['id'] ?>"
                data-vendor-id="<?= $vendorId ?>">
          Add
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endforeach; ?>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
