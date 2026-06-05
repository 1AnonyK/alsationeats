<?php
$pageTitle = 'Users — Admin';
require_once __DIR__ . '/header.php';

$db = db();
$success = '';

// Toggle admin / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $uid    = (int)($_POST['user_id'] ?? 0);

    if ($action === 'make_admin' && $uid && $uid !== currentUserId()) {
        $db->prepare("UPDATE users SET role='admin' WHERE id=?")->execute([$uid]);
        $success = 'User promoted to admin.';
    } elseif ($action === 'remove_admin' && $uid && $uid !== currentUserId()) {
        $db->prepare("UPDATE users SET role='customer' WHERE id=?")->execute([$uid]);
        $success = 'Admin privileges removed.';
    } elseif ($action === 'delete' && $uid && $uid !== currentUserId()) {
        $db->prepare("DELETE FROM users WHERE id=?")->execute([$uid]);
        $success = 'User deleted.';
    }
}

$search  = trim($_GET['q'] ?? '');
$sql     = "SELECT u.*, (SELECT COUNT(*) FROM orders WHERE buyer_id=u.id) AS order_count,
                        (SELECT id FROM vendors WHERE user_id=u.id AND is_active=1 LIMIT 1) AS vendor_id,
                        (SELECT name FROM vendors WHERE user_id=u.id AND is_active=1 LIMIT 1) AS vendor_name
            FROM users u";
$params  = [];
if ($search) { $sql .= " WHERE u.name LIKE ? OR u.email LIKE ?"; $params=["%$search%","%$search%"]; }
$sql .= " ORDER BY u.created_at DESC";
$stmt = $db->prepare($sql); $stmt->execute($params);
$users = $stmt->fetchAll();
?>

<h2 style="font-family:'Poppins',sans-serif;font-weight:800;margin-bottom:4px">Users</h2>
<p style="color:#6B8499;margin-bottom:24px">All registered accounts</p>

<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

<form class="d-flex gap-2 mb-4" method="GET" style="max-width:400px">
  <input type="text" name="q" class="form-control form-control-sm" placeholder="Search name or email..." value="<?= h($search) ?>">
  <button type="submit" class="btn-teal" style="padding:6px 18px;font-size:.85rem">Search</button>
  <?php if ($search): ?><a href="/admin/users.php" class="btn-outline-teal" style="padding:6px 14px;font-size:.85rem">Clear</a><?php endif; ?>
</form>

<div class="stat-card" style="border-top:none">
  <div class="table-responsive">
    <table class="ae-table">
      <thead>
        <tr><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Vendor</th><th>Orders</th><th>Joined</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><strong><?= h($u['name']) ?></strong></td>
        <td style="font-size:.85rem"><?= h($u['email']) ?></td>
        <td style="font-size:.82rem"><?= h($u['phone'] ?: '—') ?></td>
        <td>
          <span class="status-badge <?= $u['role']==='admin'?'status-preparing':'status-confirmed' ?>">
            <?= ucfirst($u['role']) ?>
          </span>
        </td>
        <td style="font-size:.82rem">
          <?php if ($u['vendor_id']): ?>
          <a href="/vendor.php?id=<?= $u['vendor_id'] ?>" style="color:var(--teal)"><?= h($u['vendor_name']) ?></a>
          <?php else: ?><span style="color:#ccc">—</span><?php endif; ?>
        </td>
        <td style="font-weight:700"><?= $u['order_count'] ?></td>
        <td style="font-size:.8rem;color:#6B8499"><?= date('d M Y',strtotime($u['created_at'])) ?></td>
        <td>
          <div class="d-flex gap-1">
            <?php if ($u['id'] !== currentUserId()): ?>
            <form method="POST" style="margin:0">
              <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button name="action" value="<?= $u['role']==='admin'?'remove_admin':'make_admin' ?>"
                      type="submit" class="btn-outline-teal" style="padding:3px 10px;font-size:.75rem">
                <?= $u['role']==='admin'?'Demote':'Make Admin' ?>
              </button>
            </form>
            <form method="POST" style="margin:0" onsubmit="return confirm('Delete this user?')">
              <input type="hidden" name="csrf"    value="<?= csrfToken() ?>">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button name="action" value="delete" type="submit"
                      style="background:none;border:none;color:var(--danger);cursor:pointer">
                <i class="fa-solid fa-trash"></i>
              </button>
            </form>
            <?php else: ?>
            <span style="font-size:.78rem;color:#6B8499">You</span>
            <?php endif; ?>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (empty($users)): ?>
      <tr><td colspan="8" style="text-align:center;color:#6B8499;padding:24px">No users found</td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
