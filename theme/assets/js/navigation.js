/**
 * Header navigation: renders nav from settings, "Mer" dropdown + mobile drawer.
 * Reads config from window.rockadenNav (injected by wp_localize_script).
 *
 * Labels are already resolved to the active locale server-side (config.mainNav
 * etc. + config.i18n), so there is no client-side language swapping. The
 * language switcher sets a cookie and reloads (see language.js).
 */
(function () {
  var config = window.rockadenNav || {};
  var mainNav = config.mainNav || [];
  var moreNav = config.moreNav || [];
  var i18n = config.i18n || {};
  var showDarkToggle = config.showDarkToggle !== false && config.showDarkToggle !== '';
  var showLangToggle = !!config.showLanguageToggle && config.showLanguageToggle !== '0';
  var showBorder = config.showHeaderBorder !== false && config.showHeaderBorder !== '';
  var docsUrl = config.docsUrl || '';

  function currentLang() {
    return typeof window.rockadenGetLanguage === 'function'
      ? window.rockadenGetLanguage() : 'sv';
  }

  /* ---- Language switcher builder ---- */
  function buildLangSwitcher() {
    var activeLang = currentLang();

    var row = document.createElement('div');
    row.className = 'rockaden-dropdown-toggle-row';

    var langLabel = document.createElement('span');
    langLabel.textContent = i18n.language || 'Språk';
    row.appendChild(langLabel);

    var switcher = document.createElement('div');
    switcher.className = 'rockaden-lang-switcher';

    ['sv', 'en'].forEach(function (lang) {
      var btn = document.createElement('button');
      btn.className = 'rockaden-lang-btn' + (lang === activeLang ? ' is-active' : '');
      btn.textContent = lang.toUpperCase();
      btn.setAttribute('aria-label', 'Switch to ' + lang.toUpperCase());
      btn.addEventListener('click', function () {
        // Sets the rc_locale cookie and reloads; the server then renders
        // the whole page in the chosen language.
        if (typeof window.rockadenSetLanguage === 'function') {
          window.rockadenSetLanguage(lang);
        }
      });
      switcher.appendChild(btn);
    });

    row.appendChild(switcher);
    return row;
  }

  var ctaButton = config.ctaButton || null;

  var headerStyle = config.headerStyle || 'contrast';
  var headerDensity = config.headerDensity || 'normal';
  var headerBorderWidth = config.headerBorderWidth || 'thin';

  var navContainer = document.getElementById('rockaden-main-nav');
  var actionsContainer = document.getElementById('rockaden-actions');
  var header = document.querySelector('.rockaden-header');
  if (!navContainer || !actionsContainer || !header) return;

  /* ---- Header data attributes (density + style on <html> for global access) ---- */
  document.documentElement.setAttribute('data-header-density', headerDensity);
  document.documentElement.setAttribute('data-header-style', headerStyle);
  if (headerBorderWidth === 'medium') {
    document.documentElement.style.setProperty('--rc-header-border-width', '2px');
  }

  /* ---- Header border ---- */
  if (!showBorder) {
    header.classList.add('rockaden-header--no-border');
  }

  /* ---- Build main nav links ---- */
  mainNav.forEach(function (item) {
    var a = document.createElement('a');
    a.href = item.url;
    a.className = 'rockaden-nav-link';
    a.textContent = item.label;
    navContainer.appendChild(a);
  });

  /* ---- Build actions ---- */
  // CTA button (always visible, even on mobile).
  if (ctaButton && ctaButton.url) {
    var ctaLink = document.createElement('a');
    ctaLink.href = ctaButton.url;
    ctaLink.className = 'rockaden-cta-btn';
    ctaLink.textContent = ctaButton.label;
    actionsContainer.appendChild(ctaLink);
  }

  // "Mer" button (if moreNav has items or dark toggle is shown).
  var moreBtn = null;
  var moreDropdown = null;
  if (moreNav.length > 0 || docsUrl || showDarkToggle || showLangToggle) {
    moreBtn = document.createElement('button');
    moreBtn.className = 'rockaden-more-btn';
    moreBtn.setAttribute('aria-expanded', 'false');
    moreBtn.setAttribute('aria-label', 'More menu');
    moreBtn.textContent = i18n.more || 'Mer';
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

    if (docsUrl) {
      var docsLink = document.createElement('a');
      docsLink.href = docsUrl;
      docsLink.textContent = i18n.docs || 'Dokumentation';
      moreDropdown.appendChild(docsLink);
    }

    if (showDarkToggle || showLangToggle) {
      if (moreNav.length > 0 || docsUrl) {
        var divider = document.createElement('div');
        divider.className = 'rockaden-dropdown-divider';
        moreDropdown.appendChild(divider);
      }
    }

    if (showDarkToggle) {
      var toggleRow = document.createElement('div');
      toggleRow.className = 'rockaden-dropdown-toggle-row';
      var darkLabel = document.createElement('span');
      darkLabel.textContent = i18n.darkMode || 'Mörkt läge';
      toggleRow.appendChild(darkLabel);
      var darkBtn = document.createElement('button');
      darkBtn.className = 'rockaden-toggle-switch';
      darkBtn.setAttribute('aria-label', 'Toggle dark mode');
      darkBtn.innerHTML = '<span class="rockaden-toggle-knob"></span>';
      darkBtn.addEventListener('click', function () {
        if (typeof window.rockadenToggleDark === 'function') {
          window.rockadenToggleDark();
        }
      });
      toggleRow.appendChild(darkBtn);
      moreDropdown.appendChild(toggleRow);
    }

    if (showLangToggle) {
      moreDropdown.appendChild(buildLangSwitcher());
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
    a.className = 'rockaden-drawer-link';
    a.textContent = item.label;
    drawerItems.appendChild(a);
  });

  if (moreNav.length > 0) {
    var d1 = document.createElement('div');
    d1.className = 'rockaden-drawer-divider';
    drawerItems.appendChild(d1);

    var moreLabel = document.createElement('span');
    moreLabel.className = 'rockaden-drawer-label';
    moreLabel.textContent = i18n.more || 'Mer';
    drawerItems.appendChild(moreLabel);

    moreNav.forEach(function (item) {
      var a = document.createElement('a');
      a.href = item.url;
      a.className = 'rockaden-drawer-link';
      a.textContent = item.label;
      drawerItems.appendChild(a);
    });
  }

  if (docsUrl) {
    if (!moreNav.length) {
      var d1b = document.createElement('div');
      d1b.className = 'rockaden-drawer-divider';
      drawerItems.appendChild(d1b);
    }
    var drawerDocsLink = document.createElement('a');
    drawerDocsLink.href = docsUrl;
    drawerDocsLink.className = 'rockaden-drawer-link';
    drawerDocsLink.textContent = i18n.docs || 'Dokumentation';
    drawerItems.appendChild(drawerDocsLink);
  }

  if (showDarkToggle || showLangToggle) {
    var d2 = document.createElement('div');
    d2.className = 'rockaden-drawer-divider';
    drawerItems.appendChild(d2);
  }

  if (showDarkToggle) {
    var drawerToggle = document.createElement('div');
    drawerToggle.className = 'rockaden-drawer-toggle-row';
    var drawerDarkLabel = document.createElement('span');
    drawerDarkLabel.textContent = i18n.darkMode || 'Mörkt läge';
    drawerToggle.appendChild(drawerDarkLabel);
    var drawerDarkBtn = document.createElement('button');
    drawerDarkBtn.className = 'rockaden-toggle-switch';
    drawerDarkBtn.setAttribute('aria-label', 'Toggle dark mode');
    drawerDarkBtn.innerHTML = '<span class="rockaden-toggle-knob"></span>';
    drawerDarkBtn.addEventListener('click', function () {
      if (typeof window.rockadenToggleDark === 'function') {
        window.rockadenToggleDark();
      }
    });
    drawerToggle.appendChild(drawerDarkBtn);
    drawerItems.appendChild(drawerToggle);
  }

  if (showLangToggle) {
    drawerItems.appendChild(buildLangSwitcher());
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
