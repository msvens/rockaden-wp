/**
 * Dark mode toggle.
 * CSS class: "dark" on <html>
 * Storage key: "theme" with values "dark" / "light"
 * Default: follows system preference (prefers-color-scheme)
 */
(function () {
  var STORAGE_KEY = 'theme';
  var html = document.documentElement;

  function getPreference() {
    var stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'dark' || stored === 'light') return stored;
    return window.matchMedia('(prefers-color-scheme: dark)').matches
      ? 'dark'
      : 'light';
  }

  function apply(mode) {
    html.classList.toggle('dark', mode === 'dark');
    localStorage.setItem(STORAGE_KEY, mode);
  }

  // Apply on load (backup — inline <head> script should have already set the class)
  apply(getPreference());

  // Expose toggle for the header button
  window.rockadenToggleDark = function () {
    var next = html.classList.contains('dark') ? 'light' : 'dark';
    apply(next);
  };
})();
