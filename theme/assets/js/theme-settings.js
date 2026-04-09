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
      var inputs = clone.querySelectorAll('input');
      if (inputs[0]) inputs[0].name = prefix + '_label[]';
      if (inputs[1]) inputs[1].name = prefix + '_label_en[]';
      if (inputs[2]) inputs[2].name = prefix + '_url[]';
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

  /* Page dropdown → URL field */
  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('rockaden-page-select')) return;
    var container = e.target.closest('.rockaden-nav-row') || e.target.closest('td');
    if (!container) return;
    var urlInput = container.querySelector('.rockaden-url-input');
    if (!urlInput) return;

    var value = e.target.value;
    if (value && value !== '__custom__') {
      urlInput.value = value;
    }
  });

  /* ================================================================
     Sidebar card repeater
     ================================================================ */

  var cardsContainer = document.getElementById('rc-sidebar-cards');
  var addCardBtn = document.getElementById('rc-add-sidebar-card');
  var cardTemplate = document.getElementById('rc-sidebar-card-template');
  var cardCounter = document.querySelectorAll('.rc-card-panel').length;

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
      var editorId = 'sidebar_card_content_new_' + cardCounter;
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

      cardsContainer.appendChild(clone);

      // Initialize TinyMCE on the new textarea
      if (window.wp && window.wp.editor) {
        window.wp.editor.initialize(editorId, {
          tinymce: {
            wpautop: true,
            toolbar1: 'bold,italic,link,bullist,numlist',
            toolbar2: '',
            toolbar3: '',
            toolbar4: '',
          },
          quicktags: false,
          mediaButtons: false,
        });
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
      // Clean up TinyMCE editor
      var editorArea = panel.querySelector('.rc-card-text-fields textarea, .rc-card-text-fields .wp-editor-area');
      if (editorArea && editorArea.id && window.wp && window.wp.editor) {
        window.wp.editor.remove(editorArea.id);
      }
      panel.remove();
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
    var editorArea = panel.querySelector('.wp-editor-area');
    if (!editorArea || !editorArea.id) return;
    var editor = window.tinyMCE && window.tinyMCE.get(editorArea.id);
    if (editor) {
      editor.save();
    }
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
