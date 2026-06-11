/* Alsation Eats JS */

document.addEventListener('DOMContentLoaded', () => {

  // ── Sidebar mobile toggle (Main Website) ───────────────────
  const sidebar  = document.getElementById('sidebar');
  const overlay  = document.getElementById('sidebarOverlay');
  const toggle   = document.getElementById('sidebarToggle');

  function openSidebar() {
    sidebar?.classList.add('open');
    overlay?.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeSidebar() {
    sidebar?.classList.remove('open');
    overlay?.classList.remove('show');
    document.body.style.overflow = '';
  }

  toggle?.addEventListener('click', openSidebar);
  overlay?.addEventListener('click', closeSidebar);

  // ── Sidebar mobile toggle (Admin Panel Namespace) ──────────
  const adminSidebar = document.getElementById('adminSidebar');
  const adminOverlay = document.getElementById('adminSidebarOverlay');
  const adminToggle  = document.getElementById('adminSidebarToggle');

  function openAdminSidebar() {
    adminSidebar?.classList.add('open');
    adminOverlay?.classList.add('show');
    document.body.style.overflow = 'hidden';
  }
  function closeAdminSidebar() {
    adminSidebar?.classList.remove('open');
    adminOverlay?.classList.remove('show');
    document.body.style.overflow = '';
  }

  adminToggle?.addEventListener('click', openAdminSidebar);
  adminOverlay?.addEventListener('click', closeAdminSidebar);

  // ── Add to cart (AJAX) ─────────────────────────────────────
  document.querySelectorAll('.btn-add-cart').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      const itemId   = btn.dataset.itemId;
      const vendorId = btn.dataset.vendorId;
      btn.disabled = true;
      btn.textContent = '...';

      try {
        const res  = await fetch('/api/cart.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ action: 'add', item_id: itemId, vendor_id: vendorId })
        });
        const data = await res.json();

        if (data.success) {
          btn.textContent = 'Added ✓';
          btn.style.background = '#3DAA78';
          updateCartBadge(data.cart_count);
          setTimeout(() => {
            btn.textContent = 'Add';
            btn.style.background = '';
            btn.disabled = false;
          }, 1500);
        } else {
          showToast(data.message || 'Could not add item.', 'warning');
          btn.textContent = 'Add';
          btn.disabled = false;
        }
      } catch {
        showToast('Network error.', 'danger');
        btn.textContent = 'Add';
        btn.disabled = false;
      }
    });
  });

  // ── Cart quantity controls ─────────────────────────────────
  document.querySelectorAll('.qty-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
      const itemId = btn.dataset.itemId;
      const action = btn.dataset.action; // 'inc' | 'dec' | 'remove'
      const row    = btn.closest('.cart-item');

      const res  = await fetch('/api/cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ action, item_id: itemId })
      });
      const data = await res.json();

      if (data.success) {
        if (action === 'remove' || data.qty === 0) {
          row?.remove();
        } else {
          const qtyEl = row?.querySelector('.qty-num');
          if (qtyEl) qtyEl.textContent = data.qty;
          const lineEl = row?.querySelector('.cart-line-total');
          if (lineEl) lineEl.textContent = 'R' + data.line_total.toFixed(2);
        }
        updateCartBadge(data.cart_count);
        updateCartTotal(data.subtotal, data.total);
      }
    });
  });

  // ── Favourites toggle ──────────────────────────────────────
  document.querySelectorAll('.fav-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
      e.preventDefault();
      e.stopPropagation();
      const vendorId = btn.dataset.vendorId;

      const res  = await fetch('/api/favourites.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ vendor_id: vendorId })
      });
      const data = await res.json();

      if (data.success) {
        btn.classList.toggle('active', data.favourited);
        btn.querySelector('i').className = data.favourited ? 'fa-solid fa-heart' : 'fa-regular fa-heart';
      } else if (data.redirect) {
        window.location.href = data.redirect;
      }
    });
  });

  // ── File upload preview ────────────────────────────────────
  const fileInput   = document.getElementById('proofFile');
  const previewArea = document.getElementById('uploadPreview');

  fileInput?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (!file || !previewArea) return;
    if (file.type.startsWith('image/')) {
      const url = URL.createObjectURL(file);
      previewArea.innerHTML = `<img src="${url}" style="max-width:100%;max-height:220px;border-radius:8px;margin-top:12px;">`;
    } else {
      previewArea.innerHTML = `<div style="margin-top:12px;color:#4A6070"><i class="fa-solid fa-file-pdf" style="font-size:2rem;color:#5BAAC2"></i><br>${file.name}</div>`;
    }
  });

  // ── Helpers ────────────────────────────────────────────────
  function updateCartBadge(count) {
    let badge = document.querySelector('.cart-badge');
    const cartBtn = document.querySelector('.cart-btn');
    if (!cartBtn) return;
    if (count > 0) {
      if (!badge) {
        badge = document.createElement('span');
        badge.className = 'cart-badge';
        cartBtn.appendChild(badge);
      }
      badge.textContent = count;
    } else {
      badge?.remove();
    }
  }

  function updateCartTotal(subtotal, total) {
    const subEl = document.getElementById('cartSubtotal');
    const totEl = document.getElementById('cartTotal');
    if (subEl) subEl.textContent = 'R' + subtotal.toFixed(2);
    if (totEl) totEl.textContent = 'R' + total.toFixed(2);
  }

  function showToast(msg, type = 'info') {
    const toast = document.createElement('div');
    toast.className = `ae-alert ae-alert-${type}`;
    toast.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;min-width:240px;animation:fadeInUp .3s ease';
    toast.textContent = msg;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  }
});
