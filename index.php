<?php
$pageTitle = 'Home — Alsation Eats';
require_once __DIR__ . '/includes/auth.php';

$search = trim($_GET['q'] ?? '');
$userId = currentUserId();

// Fetch vendors (with optional search)
$sql = "SELECT v.*, u.name AS owner_name,
               (SELECT COUNT(*) FROM menu_items WHERE vendor_id = v.id AND is_available = 1) AS item_count
        FROM vendors v
        JOIN users u ON u.id = v.user_id
        WHERE v.is_active = 1";

$params = [];
if ($search !== '') {
    $sql .= " AND (v.name LIKE ? OR v.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$sql .= " ORDER BY v.name ASC";
$stmt = db()->prepare($sql);
$stmt->execute($params);
$vendors = $stmt->fetchAll();

// Get user favourites
$favIds = [];
if ($userId) {
    $fStmt = db()->prepare("SELECT vendor_id FROM favourites WHERE user_id = ?");
    $fStmt->execute([$userId]);
    $favIds = array_column($fStmt->fetchAll(), 'vendor_id');
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">What are you craving? 🍖</h1>
<p class="page-subtitle">Order from your favourite campus takeaways</p>

<?php if ($search): ?>
<p class="mb-3" style="color:var(--text-mid)">
  Showing results for <strong>"<?= h($search) ?>"</strong> —
  <a href="/index.php">Clear</a>
</p>
<?php endif; ?>

<?php if (empty($vendors)): ?>
<div class="empty-state">
  <i class="fa-solid fa-store-slash"></i>
  <h4>No vendors found</h4>
  <p>Try a different search term</p>
</div>
<?php else: ?>
<div class="vendor-grid">
  <?php foreach ($vendors as $i => $v): ?>
  <a href="/vendor.php?id=<?= $v['id'] ?>"
     class="vendor-card"
     style="animation-delay: <?= $i * 0.06 ?>s">

    <?php if ($v['banner_img']): ?>
    <img src="/uploads/vendor/<?= h($v['banner_img']) ?>"
         alt="<?= h($v['name']) ?>"
         class="vendor-card-img">
    <?php else: ?>
    <div class="vendor-card-img d-flex align-items-center justify-content-center"
         style="background:var(--teal-light); font-size:3.5rem;">
      🍽️
    </div>
    <?php endif; ?>

    <div class="vendor-card-label">
      <div>
        <div><?= h($v['name']) ?></div>
        <div style="font-size:.75rem;opacity:.85;font-family:'Nunito',sans-serif;font-weight:600">
          <?= $v['item_count'] ?> items · <?= h($v['owner_name']) ?>
        </div>
      </div>
      <?php if (isLoggedIn()): ?>
      <button class="fav-btn <?= in_array($v['id'], $favIds) ? 'active' : '' ?>"
              data-vendor-id="<?= $v['id'] ?>"
              title="<?= in_array($v['id'], $favIds) ? 'Remove favourite' : 'Add to favourites' ?>">
        <i class="<?= in_array($v['id'], $favIds) ? 'fa-solid' : 'fa-regular' ?> fa-heart"></i>
      </button>
      <?php endif; ?>
    </div>
  </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
