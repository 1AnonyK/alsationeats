<?php
$pageTitle = 'Your Cart — Alsation Eats';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$userId = currentUserId();

// Fetch cart items (all must be from same vendor — enforced on add)
$stmt = db()->prepare("
    SELECT ci.id AS cart_id, ci.quantity, ci.menu_item_id,
           mi.name, mi.price, mi.image, mi.vendor_id,
           v.name AS vendor_name, v.id AS vendor_id
    FROM cart_items ci
    JOIN menu_items mi ON mi.id = ci.menu_item_id
    JOIN vendors    v  ON v.id  = mi.vendor_id
    WHERE ci.user_id = ?
    ORDER BY ci.added_at ASC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$subtotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));
$total    = $subtotal + (empty($cartItems) ? 0 : PLATFORM_FEE);
$vendor   = !empty($cartItems) ? ['id' => $cartItems[0]['vendor_id'], 'name' => $cartItems[0]['vendor_name']] : null;

// Place order
$orderError = $orderSuccess = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    verifyCsrf();
    if (empty($cartItems)) {
        $orderError = 'Your cart is empty.';
    } else {
        $db = db();
        $db->beginTransaction();
        try {
            $notes = trim($_POST['notes'] ?? '');
            $db->prepare("INSERT INTO orders (buyer_id, vendor_id, status, subtotal, platform_fee, total, notes)
                          VALUES (?, ?, 'pending_payment', ?, ?, ?, ?)")
               ->execute([$userId, $vendor['id'], $subtotal, PLATFORM_FEE, $total, $notes]);
            $orderId = $db->lastInsertId();

            $iStmt = $db->prepare("INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, price)
                                   VALUES (?, ?, ?, ?, ?)");
            foreach ($cartItems as $ci) {
                $iStmt->execute([$orderId, $ci['menu_item_id'], $ci['name'], $ci['quantity'], $ci['price']]);
            }

            // Clear cart
            $db->prepare("DELETE FROM cart_items WHERE user_id = ?")->execute([$userId]);
            $db->commit();

            header("Location: /checkout.php?order_id=$orderId");
            exit;
        } catch (Exception $e) {
            $db->rollBack();
            $orderError = 'Something went wrong. Please try again.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Your Cart 🛒</h1>
<p class="page-subtitle">Review your items before placing your order</p>

<?php if ($orderError): ?>
<div class="ae-alert ae-alert-danger"><?= h($orderError) ?></div>
<?php endif; ?>

<?php if (empty($cartItems)): ?>
<div class="empty-state">
  <i class="fa-solid fa-cart-shopping"></i>
  <h4>Your cart is empty</h4>
  <p>Head back to find something delicious</p>
  <a href="/index.php" class="btn-teal mt-3">Browse Vendors</a>
</div>
<?php else: ?>

<div class="row g-4">
  <!-- Cart items -->
  <div class="col-lg-7">
    <div class="ae-card">
      <div class="ae-card-title">
        <i class="fa-solid fa-store" style="color:var(--teal)"></i>
        Ordering from <?= h($vendor['name']) ?>
      </div>

      <?php foreach ($cartItems as $item): ?>
      <div class="cart-item" id="cart-row-<?= $item['cart_id'] ?>">
        <?php if ($item['image']): ?>
        <img src="/uploads/menu/<?= h($item['image']) ?>" class="cart-item-img" alt="">
        <?php else: ?>
        <div class="cart-item-img-placeholder"><i class="fa-solid fa-utensils"></i></div>
        <?php endif; ?>

        <div class="cart-item-info">
          <div class="cart-item-name"><?= h($item['name']) ?></div>
          <div class="cart-item-price">R<?= number_format($item['price'], 2) ?> each</div>
        </div>

        <div class="qty-control">
          <button class="qty-btn" data-item-id="<?= $item['menu_item_id'] ?>" data-action="dec">−</button>
          <span class="qty-num"><?= $item['quantity'] ?></span>
          <button class="qty-btn" data-item-id="<?= $item['menu_item_id'] ?>" data-action="inc">+</button>
        </div>

        <div style="min-width:64px;text-align:right;font-weight:700;font-family:'Poppins',sans-serif;color:var(--teal-dark)">
          <span class="cart-line-total">R<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
        </div>

        <button class="qty-btn" data-item-id="<?= $item['menu_item_id'] ?>" data-action="remove"
                style="color:var(--danger);border-color:var(--danger)" title="Remove">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Order summary -->
  <div class="col-lg-5">
    <div class="ae-card">
      <div class="ae-card-title">Order Summary</div>

      <div class="d-flex justify-content-between mb-2">
        <span style="color:var(--text-mid)">Subtotal</span>
        <span id="cartSubtotal">R<?= number_format($subtotal, 2) ?></span>
      </div>
      <div class="d-flex justify-content-between mb-2">
        <span style="color:var(--text-mid)">Platform fee</span>
        <span>R<?= number_format(PLATFORM_FEE, 2) ?></span>
      </div>
      <hr style="border-color:#EEF5F8">
      <div class="d-flex justify-content-between mb-0" style="font-weight:800;font-size:1.1rem;font-family:'Poppins',sans-serif">
        <span>Total</span>
        <span id="cartTotal" style="color:var(--teal-dark)">R<?= number_format($total, 2) ?></span>
      </div>

      <div class="ae-alert ae-alert-info mt-3" style="font-size:.82rem">
        <i class="fa-solid fa-circle-info"></i>
        R1 platform fee helps keep Alsation Eats running.
      </div>

      <form method="POST" class="ae-form mt-4">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <div class="mb-3">
          <label class="form-label">Order notes (optional)</label>
          <textarea name="notes" class="form-control" rows="2" placeholder="e.g. No onions, extra sauce..."></textarea>
        </div>
        <button type="submit" name="place_order" class="btn-teal w-100">
          Place Order & Pay via EFT
        </button>
      </form>

      <a href="/index.php" class="btn-outline-teal w-100 text-center mt-3" style="display:block">
        ← Continue Shopping
      </a>
    </div>
  </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
