<?php
$pageTitle = 'My Profile — Alsation Eats';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = currentUserId();
$stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// Vendor info if seller
$vendor = null;
if (isSeller()) {
    $vStmt = db()->prepare("SELECT * FROM vendors WHERE id = ?");
    $vStmt->execute([currentVendorId()]);
    $vendor = $vStmt->fetch();
}

$error = $success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pass  = $_POST['new_password'] ?? '';

    if (strlen($name) < 2) { $error = 'Name is too short.'; }
    else {
        db()->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ?")
            ->execute([$name, $phone, $userId]);
        if ($pass) {
            if (strlen($pass) < 8) { $error = 'New password must be at least 8 characters.'; }
            else {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                db()->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $userId]);
            }
        }
        if (!$error) {
            $_SESSION['name'] = $name;
            $success = 'Profile updated successfully!';
            $user['name'] = $name;
            $user['phone'] = $phone;
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">My Profile</h1>
<p class="page-subtitle">Manage your account details</p>

<div class="row g-4">
  <div class="col-lg-7">
    <div class="ae-card ae-form">
      <div class="ae-card-title"><i class="fa-solid fa-user-pen" style="color:var(--teal)"></i> Account Details</div>

      <?php if ($error): ?><div class="ae-alert ae-alert-danger"><?= h($error) ?></div><?php endif; ?>
      <?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

      <form method="POST">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <div class="mb-3">
          <label class="form-label">Full Name</label>
          <input type="text" name="name" class="form-control" value="<?= h($user['name']) ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Email address</label>
          <input type="email" class="form-control" value="<?= h($user['email']) ?>" disabled>
          <small class="text-muted">Email cannot be changed.</small>
        </div>
        <div class="mb-3">
          <label class="form-label">Phone</label>
          <input type="tel" name="phone" class="form-control" value="<?= h($user['phone'] ?? '') ?>" placeholder="083 123 4567">
        </div>
        <div class="mb-4">
          <label class="form-label">New Password (leave blank to keep current)</label>
          <input type="password" name="new_password" class="form-control" placeholder="Min 8 characters">
        </div>
        <button type="submit" class="btn-teal">Save Changes</button>
      </form>
    </div>
  </div>

  <div class="col-lg-5">
    <!-- Account role -->
    <div class="ae-card">
      <div class="ae-card-title">Account Type</div>
      <div style="font-size:.9rem;color:var(--text-mid);margin-bottom:16px">
        Your account lets you <strong>buy</strong> and <strong>sell</strong> on one profile.
      </div>

      <div class="d-flex gap-2 flex-wrap">
        <span class="status-badge" style="background:var(--teal-light);color:var(--teal-dark);font-size:.9rem;padding:6px 16px">
          <i class="fa-solid fa-cart-shopping"></i> Buyer
        </span>
        <?php if (isSeller()): ?>
        <span class="status-badge" style="background:#D4EDDA;color:#155724;font-size:.9rem;padding:6px 16px">
          <i class="fa-solid fa-store"></i> Seller – <?= h($vendor['name']) ?>
        </span>
        <?php elseif (isAdmin()): ?>
        <span class="status-badge" style="background:#E8D8F8;color:#7B5EA7;font-size:.9rem;padding:6px 16px">
          <i class="fa-solid fa-shield-halved"></i> Admin
        </span>
        <?php endif; ?>
      </div>

      <?php if (!isSeller() && !isAdmin()): ?>
      <div class="ae-alert ae-alert-info mt-3" style="font-size:.82rem">
        Want to sell on Alsation Eats?
        <a href="/seller/setup.php" class="fw-bold">Open your shop →</a>
      </div>
      <?php elseif (isSeller()): ?>
      <a href="/seller/dashboard.php" class="btn-outline-teal mt-3" style="display:inline-block">
        Go to My Shop →
      </a>
      <?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="ae-card">
      <div class="ae-card-title">My Activity</div>
      <?php
      $oCount = db()->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ?");
      $oCount->execute([$userId]);
      $fCount = db()->prepare("SELECT COUNT(*) FROM favourites WHERE user_id = ?");
      $fCount->execute([$userId]);
      ?>
      <div class="d-flex gap-4">
        <div class="text-center">
          <div style="font-size:1.8rem;font-weight:800;color:var(--teal-dark);font-family:'Poppins',sans-serif">
            <?= $oCount->fetchColumn() ?>
          </div>
          <div style="font-size:.8rem;color:var(--text-light)">Orders</div>
        </div>
        <div class="text-center">
          <div style="font-size:1.8rem;font-weight:800;color:var(--teal-dark);font-family:'Poppins',sans-serif">
            <?= $fCount->fetchColumn() ?>
          </div>
          <div style="font-size:.8rem;color:var(--text-light)">Favourites</div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
