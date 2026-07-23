// public/assets/js/admin.js
// Responsive Navigation Drawer for Admin Dashboard
document.addEventListener('DOMContentLoaded', function () {
  const toggleBtn = document.querySelector('.admin-menu-toggle');
  const drawer = document.getElementById('admin-drawer');
  const backdrop = document.querySelector('.admin-drawer-backdrop');
  const closeBtn = document.querySelector('.admin-drawer-close');
  const drawerItems = document.querySelectorAll('.admin-drawer-item');

  if (!toggleBtn || !drawer || !backdrop) return;

  function openDrawer() {
    drawer.classList.add('is-open');
    backdrop.classList.add('is-open');
    document.body.classList.add('menu-open');
    toggleBtn.setAttribute('aria-expanded', 'true');
    drawer.setAttribute('aria-hidden', 'false');
    backdrop.setAttribute('aria-hidden', 'false');

    // Move focus into drawer
    setTimeout(() => {
      if (closeBtn) {
        closeBtn.focus();
      } else if (drawerItems.length > 0) {
        drawerItems[0].focus();
      }
    }, 50);
  }

  function closeDrawer() {
    if (!drawer.classList.contains('is-open')) return;
    drawer.classList.remove('is-open');
    backdrop.classList.remove('is-open');
    document.body.classList.remove('menu-open');
    toggleBtn.setAttribute('aria-expanded', 'false');
    drawer.setAttribute('aria-hidden', 'true');
    backdrop.setAttribute('aria-hidden', 'true');

    // Return focus to toggle button
    toggleBtn.focus();
  }

  // Toggle button click
  toggleBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    const isOpen = drawer.classList.contains('is-open');
    if (isOpen) {
      closeDrawer();
    } else {
      openDrawer();
    }
  });

  // Close button click
  if (closeBtn) {
    closeBtn.addEventListener('click', closeDrawer);
  }

  // Backdrop click
  backdrop.addEventListener('click', closeDrawer);

  // Close on nav link click
  drawerItems.forEach(item => {
    item.addEventListener('click', closeDrawer);
  });

  // Keyboard navigation & Focus Trap
  document.addEventListener('keydown', function (e) {
    if (!drawer.classList.contains('is-open')) return;

    if (e.key === 'Escape') {
      closeDrawer();
      return;
    }

    if (e.key === 'Tab') {
      const focusables = Array.from(
        drawer.querySelectorAll('button, a[href], input, select, textarea, [tabindex]:not([tabindex="-1"])')
      ).filter(el => !el.hasAttribute('disabled') && el.offsetParent !== null);

      if (focusables.length === 0) return;

      const firstEl = focusables[0];
      const lastEl = focusables[focusables.length - 1];

      if (e.shiftKey) {
        if (document.activeElement === firstEl) {
          e.preventDefault();
          lastEl.focus();
        }
      } else {
        if (document.activeElement === lastEl) {
          e.preventDefault();
          firstEl.focus();
        }
      }
    }
  });

  // Auto-close when resizing to desktop breakpoint (>= 960px)
  window.addEventListener('resize', function () {
    if (window.innerWidth >= 960 && drawer.classList.contains('is-open')) {
      closeDrawer();
    }
  });
});
