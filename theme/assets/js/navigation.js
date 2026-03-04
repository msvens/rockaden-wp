/**
 * Header navigation: renders nav from settings, "Mer" dropdown + mobile drawer.
 * Reads config from window.rockadenNav (injected by wp_localize_script).
 */
(function () {
  var config = window.rockadenNav || {};
  var mainNav = config.mainNav || [];
  var moreNav = config.moreNav || [];
  var showDarkToggle = config.showDarkToggle !== false;
  var showBorder = config.showHeaderBorder !== false;

  var navContainer = document.getElementById('rockaden-main-nav');
  var actionsContainer = document.getElementById('rockaden-actions');
  var header = document.querySelector('.rockaden-header');
  if (!navContainer || !actionsContainer || !header) return;

  /* ---- Header border ---- */
  if (!showBorder) {
    header.classList.add('rockaden-header--no-border');
  }

  /* ---- Build main nav links ---- */
  mainNav.forEach(function (item) {
    var a = document.createElement('a');
    a.href = item.url;
    a.textContent = item.label;
    a.className = 'rockaden-nav-link';
    navContainer.appendChild(a);
  });

  /* ---- Build actions ---- */
  // "Mer" button (if moreNav has items or dark toggle is shown).
  var moreBtn = null;
  var moreDropdown = null;
  if (moreNav.length > 0 || showDarkToggle) {
    moreBtn = document.createElement('button');
    moreBtn.className = 'rockaden-more-btn';
    moreBtn.setAttribute('aria-expanded', 'false');
    moreBtn.setAttribute('aria-label', 'More menu');
    moreBtn.textContent = 'Mer';
    actionsContainer.appendChild(moreBtn);
  }

  // Hamburger button (mobile).
  var hamburgerBtn = document.createElement('button');
  hamburgerBtn.className = 'rockaden-hamburger-btn';
  hamburgerBtn.setAttribute('aria-expanded', 'false');
  hamburgerBtn.setAttribute('aria-label', 'Open menu');
  hamburgerBtn.innerHTML =
    '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">' +
    '<line x1="3" y1="6" x2="21" y2="6"/>' +
    '<line x1="3" y1="12" x2="21" y2="12"/>' +
    '<line x1="3" y1="18" x2="21" y2="18"/></svg>';
  actionsContainer.appendChild(hamburgerBtn);

  /* ---- Build More dropdown ---- */
  if (moreBtn) {
    moreDropdown = document.createElement('div');
    moreDropdown.className = 'rockaden-more-dropdown';
    moreDropdown.hidden = true;

    moreNav.forEach(function (item) {
      var a = document.createElement('a');
      a.href = item.url;
      a.textContent = item.label;
      moreDropdown.appendChild(a);
    });

    if (showDarkToggle) {
      if (moreNav.length > 0) {
        var divider = document.createElement('div');
        divider.className = 'rockaden-dropdown-divider';
        moreDropdown.appendChild(divider);
      }

      var toggleRow = document.createElement('div');
      toggleRow.className = 'rockaden-dropdown-toggle-row';
      toggleRow.innerHTML =
        '<span>Mörkt läge</span>' +
        '<button class="rockaden-toggle-switch" aria-label="Toggle dark mode">' +
        '<span class="rockaden-toggle-knob"></span></button>';
      toggleRow.querySelector('.rockaden-toggle-switch').addEventListener('click', function () {
        if (typeof window.rockadenToggleDark === 'function') {
          window.rockadenToggleDark();
        }
      });
      moreDropdown.appendChild(toggleRow);
    }

    header.appendChild(moreDropdown);
  }

  /* ---- More dropdown toggle ---- */
  if (moreBtn && moreDropdown) {
    moreBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      var open = moreDropdown.hidden;
      moreDropdown.hidden = !open;
      moreBtn.setAttribute('aria-expanded', String(open));
    });

    document.addEventListener('click', function (e) {
      if (!moreDropdown.hidden && !moreDropdown.contains(e.target)) {
        moreDropdown.hidden = true;
        moreBtn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ---- Mobile overlay + drawer ---- */
  var overlay = document.createElement('div');
  overlay.className = 'rockaden-mobile-overlay';
  overlay.hidden = true;
  document.body.appendChild(overlay);

  var drawer = document.createElement('div');
  drawer.className = 'rockaden-mobile-drawer';
  drawer.hidden = true;
  document.body.appendChild(drawer);

  var drawerItems = document.createElement('div');
  drawerItems.className = 'rockaden-mobile-drawer-items';
  drawer.appendChild(drawerItems);

  // Populate drawer.
  mainNav.forEach(function (item) {
    var a = document.createElement('a');
    a.href = item.url;
    a.textContent = item.label;
    a.className = 'rockaden-drawer-link';
    drawerItems.appendChild(a);
  });

  if (moreNav.length > 0) {
    var d1 = document.createElement('div');
    d1.className = 'rockaden-drawer-divider';
    drawerItems.appendChild(d1);

    var label = document.createElement('span');
    label.className = 'rockaden-drawer-label';
    label.textContent = 'Mer';
    drawerItems.appendChild(label);

    moreNav.forEach(function (item) {
      var a = document.createElement('a');
      a.href = item.url;
      a.textContent = item.label;
      a.className = 'rockaden-drawer-link';
      drawerItems.appendChild(a);
    });
  }

  if (showDarkToggle) {
    var d2 = document.createElement('div');
    d2.className = 'rockaden-drawer-divider';
    drawerItems.appendChild(d2);

    var drawerToggle = document.createElement('div');
    drawerToggle.className = 'rockaden-drawer-toggle-row';
    drawerToggle.innerHTML =
      '<span>Mörkt läge</span>' +
      '<button class="rockaden-toggle-switch" aria-label="Toggle dark mode">' +
      '<span class="rockaden-toggle-knob"></span></button>';
    drawerToggle.querySelector('.rockaden-toggle-switch').addEventListener('click', function () {
      if (typeof window.rockadenToggleDark === 'function') {
        window.rockadenToggleDark();
      }
    });
    drawerItems.appendChild(drawerToggle);
  }

  /* ---- Drawer open/close ---- */
  function openDrawer() {
    overlay.hidden = false;
    drawer.hidden = false;
    void drawer.offsetHeight; // reflow for animation
    overlay.classList.add('is-open');
    drawer.classList.add('is-open');
    hamburgerBtn.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeDrawer() {
    overlay.classList.remove('is-open');
    drawer.classList.remove('is-open');
    hamburgerBtn.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    setTimeout(function () {
      overlay.hidden = true;
      drawer.hidden = true;
    }, 200);
  }

  hamburgerBtn.addEventListener('click', function () {
    if (drawer.hidden) {
      openDrawer();
    } else {
      closeDrawer();
    }
  });

  overlay.addEventListener('click', closeDrawer);

  drawer.addEventListener('click', function (e) {
    if (e.target.tagName === 'A') {
      closeDrawer();
    }
  });

  /* ---- Escape key ---- */
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      if (moreDropdown && !moreDropdown.hidden) {
        moreDropdown.hidden = true;
        moreBtn.setAttribute('aria-expanded', 'false');
      }
      if (!drawer.hidden) {
        closeDrawer();
      }
    }
  });
})();
