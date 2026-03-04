/**
 * Rockaden Theme Settings – admin page JS.
 * Handles repeater add/remove rows and page dropdown → URL auto-fill.
 */
(function () {
  /* ---- Add row ---- */
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
      if (inputs[1]) inputs[1].name = prefix + '_url[]';
      container.appendChild(clone);
    });
  });

  /* ---- Remove row (delegated) ---- */
  document.addEventListener('click', function (e) {
    if (e.target.classList.contains('rockaden-remove-row')) {
      var row = e.target.closest('.rockaden-nav-row');
      if (row) row.remove();
    }
  });

  /* ---- Page dropdown → URL field ---- */
  document.addEventListener('change', function (e) {
    if (!e.target.classList.contains('rockaden-page-select')) return;
    var row = e.target.closest('.rockaden-nav-row');
    if (!row) return;
    var urlInput = row.querySelector('.rockaden-url-input');
    if (!urlInput) return;

    var value = e.target.value;
    if (value && value !== '__custom__') {
      urlInput.value = value;
    }
  });
})();
