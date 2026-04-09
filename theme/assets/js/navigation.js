/**
 * Header navigation: renders nav from settings, "Mer" dropdown + mobile drawer.
 * Reads config from window.rockadenNav (injected by wp_localize_script).
 */
(function () {
  var config = window.rockadenNav || {};
  var mainNav = config.mainNav || [];
  var moreNav = config.moreNav || [];
  var showDarkToggle = config.showDarkToggle !== false && config.showDarkToggle !== '';
  var showLangToggle = !!config.showLanguageToggle && config.showLanguageToggle !== '0';
  var showBorder = config.showHeaderBorder !== false && config.showHeaderBorder !== '';

  /* ---- Language helpers ---- */
  function currentLang() {
    return typeof window.rockadenGetLanguage === 'function'
      ? window.rockadenGetLanguage() : 'sv';
  }

  function navLabel(item) {
    var lang = currentLang();
    return (lang === 'en' && item.labelEn) ? item.labelEn : item.label;
  }

  function setTranslatedText(el, item) {
    el.setAttribute('data-label-sv', item.label);
    el.setAttribute('data-label-en', item.labelEn || item.label);
    el.textContent = navLabel(item);
  }

  function updateNavLabels() {
    var lang = currentLang();
    document.querySelectorAll('[data-label-sv]').forEach(function (el) {
      el.textContent = lang === 'en'
        ? (el.getAttribute('data-label-en') || el.getAttribute('data-label-sv'))
        : el.getAttribute('data-label-sv');
    });
  }

  /* ---- Language switcher builder ---- */
  function buildLangSwitcher() {
    var activeLang = currentLang();

    var row = document.createElement('div');
    row.className = 'rockaden-dropdown-toggle-row';

    var langLabel = document.createElement('span');
    langLabel.setAttribute('data-label-sv', 'Språk');
    langLabel.setAttribute('data-label-en', 'Language');
    langLabel.textContent = activeLang === 'en' ? 'Language' : 'Språk';
    row.appendChild(langLabel);

    var switcher = document.createElement('div');
    switcher.className = 'rockaden-lang-switcher';

    ['sv', 'en'].forEach(function (lang) {
      var btn = document.createElement('button');
      btn.className = 'rockaden-lang-btn' + (lang === activeLang ? ' is-active' : '');
      btn.textContent = lang.toUpperCase();
      btn.setAttribute('aria-label', 'Switch to ' + lang.toUpperCase());
      btn.addEventListener('click', function () {
        if (typeof window.rockadenSetLanguage === 'function') {
          window.rockadenSetLanguage(lang);
        }
        // Update active state on all switcher buttons in the page.
        document.querySelectorAll('.rockaden-lang-btn').forEach(function (b) {
          b.classList.toggle('is-active', b.textContent === lang.toUpperCase());
        });
        updateNavLabels();
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
    setTranslatedText(a, item);
    navContainer.appendChild(a);
  });

  /* ---- Build actions ---- */
  // CTA button (always visible, even on mobile).
  if (ctaButton && ctaButton.url) {
    var ctaLink = document.createElement('a');
    ctaLink.href = ctaButton.url;
    ctaLink.className = 'rockaden-cta-btn';
    setTranslatedText(ctaLink, ctaButton);
    actionsContainer.appendChild(ctaLink);
  }

  // "Mer" button (if moreNav has items or dark toggle is shown).
  var moreBtn = null;
  var moreDropdown = null;
  if (moreNav.length > 0 || showDarkToggle || showLangToggle) {
    moreBtn = document.createElement('button');
    moreBtn.className = 'rockaden-more-btn';
    moreBtn.setAttribute('aria-expanded', 'false');
    moreBtn.setAttribute('aria-label', 'More menu');
    moreBtn.setAttribute('data-label-sv', 'Mer');
    moreBtn.setAttribute('data-label-en', 'More');
    moreBtn.textContent = currentLang() === 'en' ? 'More' : 'Mer';
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
      setTranslatedText(a, item);
      moreDropdown.appendChild(a);
    });

    if (showDarkToggle || showLangToggle) {
      if (moreNav.length > 0) {
        var divider = document.createElement('div');
        divider.className = 'rockaden-dropdown-divider';
        moreDropdown.appendChild(divider);
      }
    }

    if (showDarkToggle) {
      var toggleRow = document.createElement('div');
      toggleRow.className = 'rockaden-dropdown-toggle-row';
      var darkLabel = document.createElement('span');
      darkLabel.setAttribute('data-label-sv', 'Mörkt läge');
      darkLabel.setAttribute('data-label-en', 'Dark mode');
      darkLabel.textContent = currentLang() === 'en' ? 'Dark mode' : 'Mörkt läge';
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
    setTranslatedText(a, item);
    drawerItems.appendChild(a);
  });

  if (moreNav.length > 0) {
    var d1 = document.createElement('div');
    d1.className = 'rockaden-drawer-divider';
    drawerItems.appendChild(d1);

    var moreLabel = document.createElement('span');
    moreLabel.className = 'rockaden-drawer-label';
    moreLabel.setAttribute('data-label-sv', 'Mer');
    moreLabel.setAttribute('data-label-en', 'More');
    moreLabel.textContent = currentLang() === 'en' ? 'More' : 'Mer';
    drawerItems.appendChild(moreLabel);

    moreNav.forEach(function (item) {
      var a = document.createElement('a');
      a.href = item.url;
      a.className = 'rockaden-drawer-link';
      setTranslatedText(a, item);
      drawerItems.appendChild(a);
    });
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
    drawerDarkLabel.setAttribute('data-label-sv', 'Mörkt läge');
    drawerDarkLabel.setAttribute('data-label-en', 'Dark mode');
    drawerDarkLabel.textContent = currentLang() === 'en' ? 'Dark mode' : 'Mörkt läge';
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
