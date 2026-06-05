/**
 * Section menu reorder (admin, page editor).
 *
 * The "Section menu" meta box renders the section sidebar menu as an ordered
 * list. This script lets editors reorder the child items with ↑/↓ buttons and
 * persists the new order by writing each page's menu_order via the core REST
 * API (POST /wp/v2/pages/{id}). The REST nonce + root URL are injected
 * automatically by wp-api-fetch in wp-admin, so no manual auth is needed.
 *
 * No build step: plain vanilla JS using the global wp.apiFetch, matching the
 * other theme scripts in assets/js.
 */
(function () {
  var wp = window.wp;
  if (!wp || !wp.apiFetch) {
    return;
  }

  var list = document.querySelector('.rc-section-menu__list');
  if (!list) {
    return;
  }

  var status = document.querySelector('.rc-section-menu__status');
  var strings = window.rockadenSectionMenu || {};
  var sent = {}; // pageId -> last successfully saved menu_order
  var saving = false;

  // Reorderable items = children only; the root (data-root) is pinned first.
  function childItems() {
    return Array.prototype.slice.call(
      list.querySelectorAll('.rc-section-menu__item[data-page-id]:not([data-root])')
    );
  }

  function setStatus(text) {
    if (status) {
      status.textContent = text;
    }
  }

  function setSaving(on) {
    saving = on;
    var buttons = list.querySelectorAll('.rc-section-menu__move');
    Array.prototype.forEach.call(buttons, function (btn) {
      btn.disabled = on;
    });
  }

  // Write each child's position as its menu_order. Only pages whose order
  // actually changed since the last save are written; on the first reorder
  // every child is written so all menu_order values become explicit (initial
  // values may be identical, which would otherwise break partial updates).
  function persist() {
    var items = childItems();
    var writes = [];

    items.forEach(function (li, index) {
      var id = parseInt(li.getAttribute('data-page-id'), 10);
      if (sent[id] === index) {
        return;
      }
      writes.push(
        wp
          .apiFetch({
            path: '/wp/v2/pages/' + id,
            method: 'POST',
            data: { menu_order: index },
          })
          .then(function () {
            sent[id] = index;
          })
      );
    });

    if (!writes.length) {
      return;
    }

    setSaving(true);
    setStatus(strings.saving || 'Saving…');

    Promise.all(writes)
      .then(function () {
        setSaving(false);
        setStatus(strings.saved || 'Saved');
        window.setTimeout(function () {
          setStatus('');
        }, 2000);
      })
      .catch(function () {
        setSaving(false);
        setStatus(strings.error || 'Could not save — reload and try again.');
      });
  }

  // Write per-page section-menu meta (label / hidden) for any item via core REST.
  function saveMeta(id, meta) {
    setSaving(true);
    setStatus(strings.saving || 'Saving…');
    wp
      .apiFetch({
        path: '/wp/v2/pages/' + id,
        method: 'POST',
        data: { meta: meta },
      })
      .then(function () {
        setSaving(false);
        setStatus(strings.saved || 'Saved');
        window.setTimeout(function () {
          setStatus('');
        }, 2000);
      })
      .catch(function () {
        setSaving(false);
        setStatus(strings.error || 'Could not save — reload and try again.');
      });
  }

  function itemId(el) {
    var li = el.closest('.rc-section-menu__item');
    return li ? parseInt(li.getAttribute('data-page-id'), 10) : 0;
  }

  // Inline label edits + show/hide toggles (fire on blur / checkbox change).
  list.addEventListener('change', function (e) {
    var target = e.target;
    if (target.classList.contains('rc-section-menu__label-input')) {
      saveMeta(itemId(target), { rc_section_menu_label: target.value });
    } else if (target.classList.contains('rc-section-menu__hide-toggle')) {
      var li = target.closest('.rc-section-menu__item');
      var hidden = !target.checked; // checkbox label is "Show in menu"
      if (li) {
        li.classList.toggle('rc-item-hidden', hidden);
      }
      saveMeta(itemId(target), { rc_section_menu_hidden: hidden ? '1' : '' });
    }
  });

  function move(li, direction) {
    if (saving || !li) {
      return;
    }
    if (direction < 0) {
      var prev = li.previousElementSibling;
      // Never move a child above the pinned section root.
      if (!prev || prev.hasAttribute('data-root')) {
        return;
      }
      list.insertBefore(li, prev);
    } else {
      var next = li.nextElementSibling;
      if (!next) {
        return;
      }
      list.insertBefore(next, li);
    }
    persist();
  }

  list.addEventListener('click', function (e) {
    var btn = e.target.closest('.rc-section-menu__move');
    if (!btn) {
      return;
    }
    var li = btn.closest('.rc-section-menu__item');
    move(li, btn.classList.contains('rc-section-menu__up') ? -1 : 1);
  });
})();
