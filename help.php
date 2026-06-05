<?php
$pageTitle = 'Help — Alsation Eats';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/header.php';
?>

<h1 class="page-title">Help & FAQ 💬</h1>
<p class="page-subtitle">Everything you need to know about Alsation Eats</p>

<div class="row g-4">
  <div class="col-lg-8">
    <div class="ae-card">
      <div class="ae-card-title">Frequently Asked Questions</div>

      <?php $faqs = [
        ['How do I place an order?', 'Browse to a vendor, add items to your cart, then click "Place Order". You\'ll receive EFT bank details to make payment. Once payment is verified, the vendor prepares your order.'],
        ['What is the R1 platform fee?', 'A small R1 fee is added to every order to help keep Alsation Eats running. This is charged to the buyer and not the vendor.'],
        ['How does EFT payment work?', 'After placing your order, you\'ll see our bank account details. Make the EFT from your banking app using your order number as reference, then upload a screenshot as proof of payment.'],
        ['How long does verification take?', 'Payment is usually verified within 15–30 minutes during operating hours. You\'ll see your order status update in real time on the Orders page.'],
        ['Can I be both a buyer and a seller?', 'Yes! One account lets you order food from any vendor AND run your own shop. Visit your profile to open a shop.'],
        ['How do I open my own shop?', 'Go to your Profile or click "Sell Here" in the sidebar. Fill in your shop details and start adding menu items immediately.'],
        ['What if my payment is rejected?', 'Contact the shop or admin. Common reasons include incorrect reference number or unclear screenshot. Re-upload a clearer proof.'],
        ['How do I collect my order?', 'When your order status changes to "Ready", head to the vendor\'s location. Show them your order number.'],
      ];
      foreach ($faqs as $i => [$q, $a]): ?>
      <div style="border-bottom:1px solid #EEF5F8;padding:16px 0;<?= $i === 0 ? 'padding-top:0' : '' ?>">
        <h6 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--text-dark);margin-bottom:6px">
          <?= h($q) ?>
        </h6>
        <p style="color:var(--text-mid);font-size:.9rem;margin:0"><?= h($a) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="ae-card">
      <div class="ae-card-title">Contact Support</div>
      <p style="color:var(--text-mid);font-size:.9rem">Can't find your answer? Reach out to us:</p>
      <div class="d-flex align-items-center gap-3 mb-3">
        <i class="fa-solid fa-envelope" style="color:var(--teal);font-size:1.2rem;width:24px"></i>
        <span style="font-size:.9rem">support@alsationeats.co.za</span>
      </div>
      <div class="d-flex align-items-center gap-3 mb-3">
        <i class="fa-brands fa-whatsapp" style="color:#25D366;font-size:1.2rem;width:24px"></i>
        <span style="font-size:.9rem">+27 82 123 4567</span>
      </div>
      <div class="d-flex align-items-center gap-3">
        <i class="fa-solid fa-clock" style="color:var(--teal);font-size:1.2rem;width:24px"></i>
        <span style="font-size:.9rem">Mon–Fri, 07:00–18:00</span>
      </div>
    </div>

    <div class="ae-card">
      <div class="ae-card-title">About Alsation Eats</div>
      <p style="font-size:.88rem;color:var(--text-mid);line-height:1.7">
        Alsation Eats is a C2C township food ordering platform connecting students
        with informal takeaway vendors. We're proudly South African, built to
        digitally empower the kasi economy — one order at a time.
      </p>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
