/**
 * Rockaden Theme Settings – admin page JS.
 * Handles nav repeater rows, sidebar card repeater (add/remove/collapse/
 * reorder/type-toggle/media-picker/editor-init), and form submit sync.
 */
(function () {
  /* ================================================================
     Nav repeater (existing logic)
     ================================================================ */

  /* Add row */
  document.querySelectorAll('.rockaden-add-row').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetId = btn.getAttribute('data-target');
      var prefix = btn.getAttribute('data-prefix');
      var container = document.getElementById(targetId);
      var template = document.getElementById('rockaden-nav-row-template');
      if (!container || !template) return;

      var clone = template.content.cloneNode(true);
      // Names come from each input's data-field, not its position, so
      // render_nav_row can rearrange its markup without breaking this.
      clone.querySelectorAll('input[data-field]').forEach(function (input) {
        input.name = prefix + '_' + input.getAttribute('data-field') + '[]';
      });
      container.appendChild(clone);
    });
  });

  /* Remove row (delegated) */
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('rockaden-remove-row')) {
      var row = e.target.closest('.rockaden-nav-row');
      if (row) row.remove();
    }
  });

  /* Reorder rows up/down (delegated). Row order = submit order, so swapping
     rows in the DOM reorders the nav on save. */
  document.addEventListener('click', function (e) {
    var up = e.target.classList.contains('rockaden-nav-move-up');
    var down = e.target.classList.contains('rockaden-nav-move-down');
    if (!up && !down) return;
    var row = e.target.closest('.rockaden-nav-row');
    if (!row) return;
    if (up) {
      var prev = row.previousElementSibling;
      if (prev && prev.classList.contains('rockaden-nav-row')) {
        row.parentNode.insertBefore(row, prev);
      }
    } else {
      var next = row.nextElementSibling;
      if (next && next.classList.contains('rockaden-nav-row')) {
        row.parentNode.insertBefore(next, row);
      }
    }
  });

  /* Page dropdown → URL field
     - Picking a Page or theme route writes that URL into the (hidden)
       url input and keeps it hidden.
     - Picking "Custom URL" reveals the url input for free typing. */
  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('rockaden-page-select')) return;
    syncRowFromSelect(e.target);
  });

  function syncRowFromSelect(select) {
    // .rockaden-url-cell first: a nav row holds two select+input pairs (SV and
    // EN), so scoping to the row would always find the Swedish input.
    var container = select.closest('.rockaden-url-cell') || select.closest('.rockaden-nav-row') || select.closest('td');
    if (!container) return;
    var urlInput = container.querySelector('.rockaden-url-input');
    if (!urlInput) return;

    var value = select.value;
    if (value === '__custom__') {
      urlInput.classList.add('is-visible');
      // Only clear/focus on user interaction, not initial sync.
      if (select.dataset.userTouched === '1') {
        urlInput.value = '';
        urlInput.focus();
      }
    } else if (value === '') {
      urlInput.classList.remove('is-visible');
      urlInput.value = '';
    } else {
      urlInput.value = value;
      urlInput.classList.remove('is-visible');
    }
  }

  /* Mark selects as user-touched on real interaction (so init sync
     doesn't trigger the focus/clear behavior). */
  document.addEventListener('mousedown', function (e) {
    if (e.target.classList.contains('rockaden-page-select')) {
      e.target.dataset.userTouched = '1';
    }
  });
  document.addEventListener('keydown', function (e) {
    if (e.target.classList.contains('rockaden-page-select')) {
      e.target.dataset.userTouched = '1';
    }
  });

  /* Init: for each existing row, sync URL input visibility to match
     the dropdown's selected option. PHP has already selected
     "Custom URL" if the saved URL doesn't match a known option. */
  document.querySelectorAll('.rockaden-page-select').forEach(syncRowFromSelect);

  /* ================================================================
     Sidebar card repeater
     ================================================================ */

  var cardsContainer = document.getElementById('rc-sidebar-cards');
  var addCardBtn = document.getElementById('rc-add-sidebar-card');
  var cardTemplate = document.getElementById('rc-sidebar-card-template');
  var cardCounter = document.querySelectorAll('.rc-card-panel').length;

  /* Shared by the add-card handler and initEnEditor. Two copies of this would
     let the Swedish and English toolbars drift apart. */
  var EDITOR_OPTS = {
    tinymce: {
      wpautop: true,
      toolbar1: 'bold,italic,link,bullist,numlist',
      toolbar2: '',
      toolbar3: '',
      toolbar4: '',
    },
    quicktags: false,
    mediaButtons: false,
  };

  if (!cardsContainer) return;

  /* --- Sync TinyMCE editors before form submit --- */
  var form = cardsContainer.closest('form');
  if (form) {
    form.addEventListener('submit', function () {
      if (window.tinyMCE) {
        window.tinyMCE.triggerSave();
      }
    });
  }

  /* --- Add card --- */
  if (addCardBtn && cardTemplate) {
    addCardBtn.addEventListener('click', function () {
      var clone = cardTemplate.content.cloneNode(true);
      var panel = clone.querySelector('.rc-card-panel');
      var suffix = cardCounter;
      var editorId = 'sidebar_card_content_new_' + suffix;
      cardCounter++;

      // New cards start expanded so the user can fill them in.
      panel.classList.add('is-expanded');
      var collapseBtn = panel.querySelector('.rc-card-collapse');
      if (collapseBtn) collapseBtn.innerHTML = '&#9662;';

      // Replace textarea with one that has a unique ID for wp.editor
      var textarea = panel.querySelector('.rc-card-content-textarea');
      if (textarea) {
        textarea.id = editorId;
      }
      // The English body stays a plain textarea until its section is opened,
      // but it needs its id now so initEnEditor can find it later.
      var enTextarea = panel.querySelector('.rc-card-content-en-textarea');
      if (enTextarea) {
        enTextarea.id = 'sidebar_card_content_en_new_' + suffix;
      }

      cardsContainer.appendChild(clone);

      // Initialize TinyMCE on the new textarea
      if (window.wp && window.wp.editor) {
        window.wp.editor.initialize(editorId, EDITOR_OPTS);
      }
    });
  }

  /* --- Delegated click handler for card actions --- */
  cardsContainer.addEventListener('click', function (e) {
    var btn = e.target.closest('.button-link, .button');
    if (!btn) return;
    var panel = btn.closest('.rc-card-panel');
    if (!panel) return;

    /* Collapse/expand */
    if (btn.classList.contains('rc-card-collapse')) {
      panel.classList.toggle('is-expanded');
      btn.innerHTML = panel.classList.contains('is-expanded') ? '&#9662;' : '&#9656;';
      e.stopPropagation();
      return;
    }

    /* Remove */
    if (btn.classList.contains('rc-card-remove')) {
      // Clean up TinyMCE. A card can carry two editors (Swedish and English),
      // and the English one may never have been initialised, so ask tinyMCE
      // which ids it actually knows about rather than assuming.
      if (window.wp && window.wp.editor) {
        panel.querySelectorAll('textarea[id]').forEach(function (area) {
          if (window.tinyMCE && window.tinyMCE.get(area.id)) {
            window.wp.editor.remove(area.id);
          }
        });
      }
      panel.remove();
      e.stopPropagation();
      return;
    }

    /* English section toggle */
    if (btn.classList.contains('rc-card-en-toggle')) {
      var enFields = panel.querySelector('.rc-card-en-fields');
      if (enFields) {
        var opening = enFields.hidden;
        enFields.hidden = !opening;
        btn.setAttribute('aria-expanded', opening ? 'true' : 'false');
        var state = btn.querySelector('.rc-card-en-state');
        if (state) state.innerHTML = opening ? '&#9662;' : '&#9656;';
        // Unhide before initialising: TinyMCE measures its container, and a
        // hidden parent gives a zero-height iframe.
        if (opening) initEnEditor(panel);
      }
      e.stopPropagation();
      return;
    }

    /* Move up */
    if (btn.classList.contains('rc-card-move-up')) {
      var prev = panel.previousElementSibling;
      if (prev && prev.classList.contains('rc-card-panel')) {
        syncEditors(panel);
        syncEditors(prev);
        cardsContainer.insertBefore(panel, prev);
      }
      e.stopPropagation();
      return;
    }

    /* Move down */
    if (btn.classList.contains('rc-card-move-down')) {
      var next = panel.nextElementSibling;
      if (next && next.classList.contains('rc-card-panel')) {
        syncEditors(panel);
        syncEditors(next);
        cardsContainer.insertBefore(next, panel);
      }
      e.stopPropagation();
      return;
    }

    /* Select image */
    if (btn.classList.contains('rc-card-select-image')) {
      openMediaPicker(panel);
      e.stopPropagation();
      return;
    }

    /* Remove image */
    if (btn.classList.contains('rc-card-remove-image')) {
      var urlInput = panel.querySelector('.rc-card-image-url');
      var preview = panel.querySelector('.rc-card-image-preview');
      if (urlInput) urlInput.value = '';
      if (preview) preview.innerHTML = '';
      btn.style.display = 'none';
      e.stopPropagation();
      return;
    }
  });

  /* --- Click on header to toggle collapse --- */
  cardsContainer.addEventListener('click', function (e) {
    if (!e.target.classList.contains('rc-card-header') &&
        !e.target.classList.contains('rc-card-title-preview') &&
        !e.target.classList.contains('rc-card-type-badge')) {
      return;
    }
    var panel = e.target.closest('.rc-card-panel');
    if (!panel) return;
    var collapseBtn = panel.querySelector('.rc-card-collapse');
    panel.classList.toggle('is-expanded');
    if (collapseBtn) {
      collapseBtn.innerHTML = panel.classList.contains('is-expanded') ? '&#9662;' : '&#9656;';
    }
  });

  /* --- Type toggle --- */
  cardsContainer.addEventListener('change', function (e) {
    if (!e.target.classList.contains('rc-card-type-select')) return;
    var panel = e.target.closest('.rc-card-panel');
    if (!panel) return;
    var type = e.target.value;
    var textFields = panel.querySelector('.rc-card-text-fields');
    var imageFields = panel.querySelector('.rc-card-image-fields');
    var linkLabelField = panel.querySelector('.rc-card-link-label-field');
    var badge = panel.querySelector('.rc-card-type-badge');

    if (textFields) textFields.style.display = type === 'text' ? '' : 'none';
    if (imageFields) imageFields.style.display = type === 'image' ? '' : 'none';
    if (linkLabelField) linkLabelField.style.display = type === 'image' ? 'none' : '';
    if (badge) badge.textContent = type === 'text' ? 'Text' : 'Image';

    // Same rules for the English section. Title (EN) and Link URL (EN) stay
    // visible for image cards: the title becomes the image alt text, and an
    // image card can still be linked.
    var enTextFields = panel.querySelector('.rc-card-en-text-fields');
    var enLinkLabelField = panel.querySelector('.rc-card-en-link-label-field');
    if (enTextFields) enTextFields.style.display = type === 'text' ? '' : 'none';
    if (enLinkLabelField) enLinkLabelField.style.display = type === 'image' ? 'none' : '';

    // Smart defaults when switching type.
    var showTitleSelect = panel.querySelector('.rc-card-show-title-select');
    var fullBleedSelect = panel.querySelector('.rc-card-full-bleed-select');
    if (type === 'image') {
      if (showTitleSelect) showTitleSelect.value = '0';
      if (fullBleedSelect) fullBleedSelect.value = '1';
    } else {
      if (showTitleSelect) showTitleSelect.value = '1';
      if (fullBleedSelect) fullBleedSelect.value = '0';
    }
  });

  /* --- Title input → header preview sync --- */
  cardsContainer.addEventListener('input', function (e) {
    if (!e.target.classList.contains('rc-card-title-input')) return;
    var panel = e.target.closest('.rc-card-panel');
    if (!panel) return;
    var preview = panel.querySelector('.rc-card-title-preview');
    if (preview) {
      preview.textContent = e.target.value || 'New card';
    }
  });

  /* --- Helper: sync TinyMCE content to textarea before DOM reorder --- */
  function syncEditors(panel) {
    if (!window.tinyMCE) return;
    // Every textarea with an id, not just the first: a card holds a Swedish and
    // an English body, and reordering without saving both loses the unsaved one.
    panel.querySelectorAll('textarea[id]').forEach(function (area) {
      var editor = window.tinyMCE.get(area.id);
      if (editor) {
        editor.save();
      }
    });
  }

  /* --- Helper: upgrade the English textarea to TinyMCE on first open --- */
  function initEnEditor(panel) {
    if (!window.wp || !window.wp.editor) return;
    var area = panel.querySelector('.rc-card-content-en-textarea');
    if (!area || !area.id) return;
    // Already running — re-initialising would stack a second editor.
    if (window.tinyMCE && window.tinyMCE.get(area.id)) return;
    window.wp.editor.initialize(area.id, EDITOR_OPTS);
  }

  /* --- Helper: WP media picker --- */
  function openMediaPicker(panel) {
    if (!window.wp || !window.wp.media) return;

    var frame = window.wp.media({
      title: 'Select Image',
      library: { type: 'image' },
      multiple: false,
    });

    frame.on('select', function () {
      var attachment = frame.state().get('selection').first().toJSON();
      var urlInput = panel.querySelector('.rc-card-image-url');
      var preview = panel.querySelector('.rc-card-image-preview');
      var removeBtn = panel.querySelector('.rc-card-remove-image');

      if (urlInput) urlInput.value = attachment.url;
      if (preview) preview.innerHTML = '<img src="' + attachment.url + '" alt="" />';
      if (removeBtn) removeBtn.style.display = '';
    });

    frame.open();
  }
})();
