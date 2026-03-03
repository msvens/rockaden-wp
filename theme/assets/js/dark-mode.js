/**
 * Dark mode toggle.
 * Reads preference from localStorage, applies class to <html>.
 */
(function () {
  var STORAGE_KEY = 'rockaden-dark-mode';
  var html = document.documentElement;

  function isDark() {
    var stored = localStorage.getItem(STORAGE_KEY);
    if (stored !== null) return stored === 'true';
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
  }

  function apply(dark) {
    html.classList.toggle('dark-mode', dark);
    localStorage.setItem(STORAGE_KEY, String(dark));
  }

  // Apply on load
  apply(isDark());

  // Expose toggle for buttons
  window.rockadenToggleDark = function () {
    apply(!html.classList.contains('dark-mode'));
  };
})();
