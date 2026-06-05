<?php
$pageTitle = 'Favourites — Alsation Eats';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = currentUserId();
$stmt = db()->prepare("
    SELECT v.*, u.name AS owner_name,
           (SELECT COUNT(*) FROM menu_items WHERE vendor_id = v.id AND is_available = 1) AS item_count
    FROM favourites f
    JOIN vendors v ON v.id = f.vendor_id
    JOIN users   u ON u.id = v.user_id
    WHERE f.user_id = ? AND v.is_active = 1
    ORDER BY f.created_at DESC
");
$stmt->execute([$userId]);
$vendors = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Your Favourites ❤️</h1>
<p class="page-subtitle">Vendors you've saved for quick access</p>

<?php if (empty($vendors)): ?>
<div class="empty-state">
  <i class="fa-regular fa-heart"></i>
  <h4>No favourites yet</h4>
  <p>Tap the ❤️ on any vendor to save them here</p>
  <a href="/index.php" class="btn-teal mt-3">Browse Vendors</a>
</div>
<?php else: ?>
<div class="vendor-grid">
  <?php foreach ($vendors as $i => $v): ?>
  <a href="/vendor.php?id=<?= $v['id'] ?>" class="vendor-card" style="animation-delay:<?= $i * 0.06 ?>s">
    <?php if ($v['banner_img']): ?>
    <img src="/uploads/vendor/<?= h($v['banner_img']) ?>" class="vendor-card-img" alt="">
    <?php else: ?>
    <div class="vendor-card-img d-flex align-items-center justify-content-center" style="background:var(--teal-light);font-size:3.5rem">🍽️</div>
    <?php endif; ?>
    <div class="vendor-card-label">
      <div>
        <div><?= h($v['name']) ?></div>
        <div style="font-size:.75rem;opacity:.85;font-family:'Nunito',sans-serif;font-weight:600"><?= $v['item_count'] ?> items · <?= h($v['owner_name']) ?></div>
      </div>
      <button class="fav-btn active" data-vendor-id="<?= $v['id'] ?>">
        <i class="fa-solid fa-heart" style="color:#ff8fa3"></i>
      </button>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
