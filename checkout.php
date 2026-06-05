<?php
$pageTitle = 'Pay via EFT — Alsation Eats';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$orderId = (int)($_GET['order_id'] ?? 0);
$userId  = currentUserId();

$stmt = db()->prepare("
    SELECT o.*, v.name AS vendor_name, u.name AS buyer_name
    FROM orders o
    JOIN vendors v ON v.id = o.vendor_id
    JOIN users   u ON u.id = o.buyer_id
    WHERE o.id = ? AND o.buyer_id = ?
");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();
if (!$order) { header('Location: /orders.php'); exit; }

// Order items
$iStmt = db()->prepare("SELECT * FROM order_items WHERE order_id = ?");
$iStmt->execute([$orderId]);
$orderItems = $iStmt->fetchAll();

// Payment record
$pStmt = db()->prepare("SELECT * FROM payments WHERE order_id = ?");
$pStmt->execute([$orderId]);
$payment = $pStmt->fetch();

$error = $success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$payment) {
    verifyCsrf();
    $ref  = trim($_POST['reference'] ?? '');
    $file = handleUpload('proof', UPLOAD_PROOF, ['jpg','jpeg','png','gif','webp','pdf']);

    if (!$file) {
        $error = 'Please upload a valid proof of payment (JPG, PNG, or PDF, max ' . MAX_UPLOAD_MB . 'MB).';
    } else {
        db()->prepare("INSERT INTO payments (order_id, proof_file, reference) VALUES (?, ?, ?)")
            ->execute([$orderId, $file, $ref]);
        db()->prepare("UPDATE orders SET status = 'payment_uploaded' WHERE id = ?")
            ->execute([$orderId]);
        $success = 'Payment proof uploaded! Your order is being reviewed.';
        $order['status'] = 'payment_uploaded';
        $payment = ['proof_file' => $file, 'reference' => $ref, 'status' => 'pending'];
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Complete Your Payment</h1>
<p class="page-subtitle">Order #<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?> · <?= h($order['vendor_name']) ?></p>

<?php if ($error): ?><div class="ae-alert ae-alert-danger"><?= h($error) ?></div><?php endif; ?>
<?php if ($success): ?><div class="ae-alert ae-alert-success"><?= h($success) ?></div><?php endif; ?>

<div class="row g-4">
  <!-- EFT details -->
  <div class="col-lg-7">
    <?php if ($order['status'] === 'pending_payment'): ?>
    <div class="bank-box">
      <h5><i class="fa-solid fa-building-columns"></i> EFT Bank Details</h5>
      <div class="bank-row"><span>Bank</span><strong><?= BANK_NAME ?></strong></div>
      <div class="bank-row"><span>Account Holder</span><strong><?= BANK_HOLDER ?></strong></div>
      <div class="bank-row"><span>Account Number</span><strong><?= BANK_ACCOUNT ?></strong></div>
      <div class="bank-row"><span>Branch Code</span><strong><?= BANK_BRANCH ?></strong></div>
      <div class="bank-row"><span>Account Type</span><strong><?= BANK_TYPE ?></strong></div>
      <div class="bank-row">
        <span>Reference</span>
        <strong>AE-<?= str_pad($orderId, 5, '0', STR_PAD_LEFT) ?>-<?= $userId ?></strong>
      </div>
      <div class="bank-amount mt-3">
        Total: R<?= number_format($order['total'], 2) ?>
      </div>
    </div>

    <div class="ae-card ae-form">
      <div class="ae-card-title"><i class="fa-solid fa-file-arrow-up" style="color:var(--teal)"></i> Upload Proof of Payment</div>

      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

        <div class="mb-3">
          <label class="form-label">Payment Reference (optional)</label>
          <input type="text" name="reference" class="form-control"
                 placeholder="e.g. AE-00001-12">
        </div>

        <div class="mb-3">
          <label class="form-label">Proof of Payment</label>
          <div class="upload-zone" onclick="document.getElementById('proofFile').click()">
            <i class="fa-solid fa-cloud-arrow-up d-block"></i>
            <p class="mb-0">Click to upload screenshot or PDF</p>
            <small style="color:var(--text-light)">JPG, PNG, PDF — max <?= MAX_UPLOAD_MB ?>MB</small>
          </div>
          <input type="file" id="proofFile" name="proof" accept=".jpg,.jpeg,.png,.gif,.webp,.pdf" style="display:none">
          <div id="uploadPreview"></div>
        </div>

        <button type="submit" class="btn-teal w-100">Submit Payment Proof</button>
      </form>
    </div>

    <?php elseif ($payment): ?>
    <div class="ae-card">
      <div class="ae-card-title">Payment Status</div>
      <div class="text-center py-4">
        <?php if ($payment['status'] === 'verified'): ?>
          <i class="fa-solid fa-circle-check" style="font-size:3rem;color:var(--success)"></i>
          <h4 class="mt-3">Payment Verified!</h4>
          <p style="color:var(--text-mid)">Your order is being prepared.</p>
        <?php elseif ($payment['status'] === 'rejected'): ?>
          <i class="fa-solid fa-circle-xmark" style="font-size:3rem;color:var(--danger)"></i>
          <h4 class="mt-3">Payment Rejected</h4>
          <p style="color:var(--text-mid)"><?= h($payment['notes'] ?? 'Please contact support.') ?></p>
        <?php else: ?>
          <i class="fa-solid fa-clock" style="font-size:3rem;color:var(--warning)"></i>
          <h4 class="mt-3">Awaiting Verification</h4>
          <p style="color:var(--text-mid)">We'll update your order once payment is confirmed.</p>
        <?php endif; ?>
        <a href="/orders.php" class="btn-teal mt-3">View My Orders</a>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Order summary -->
  <div class="col-lg-5">
    <div class="ae-card">
      <div class="ae-card-title">Order Summary</div>
      <?php foreach ($orderItems as $oi): ?>
      <div class="d-flex justify-content-between py-2" style="border-bottom:1px solid #EEF5F8;font-size:.9rem">
        <span><?= $oi['quantity'] ?>× <?= h($oi['item_name']) ?></span>
        <span style="font-weight:700">R<?= number_format($oi['price'] * $oi['quantity'], 2) ?></span>
      </div>
      <?php endforeach; ?>
      <div class="d-flex justify-content-between pt-3 pb-1" style="color:var(--text-mid)">
        <span>Subtotal</span><span>R<?= number_format($order['subtotal'], 2) ?></span>
      </div>
      <div class="d-flex justify-content-between pb-1" style="color:var(--text-mid)">
        <span>Platform fee</span><span>R<?= number_format($order['platform_fee'], 2) ?></span>
      </div>
      <div class="d-flex justify-content-between pt-2" style="font-weight:800;font-size:1.1rem;font-family:'Poppins',sans-serif;">
        <span>Total</span>
        <span style="color:var(--teal-dark)">R<?= number_format($order['total'], 2) ?></span>
      </div>

      <?php if ($order['notes']): ?>
      <div class="ae-alert ae-alert-info mt-3" style="font-size:.82rem">
        <strong>Notes:</strong> <?= h($order['notes']) ?>
      </div>
      <?php endif; ?>

      <div class="mt-3">
        <span class="status-badge status-<?= $order['status'] ?>">
          <?= ucwords(str_replace('_', ' ', $order['status'])) ?>
        </span>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
