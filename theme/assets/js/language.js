/**
 * Language switcher: stores the chosen WordPress locale in the `rc_locale`
 * cookie and reloads, so the server renders the whole page (gettext + nav +
 * dates) in that language. `data-lang` on <html> is set server-side.
 */
(function () {
  var COOKIE = 'rc_locale';
  var LOCALE = { sv: 'sv_SE', en: 'en_US' };

  function getLang() {
    var m = document.cookie.match(/(?:^|;\s*)rc_locale=([^;]+)/);
    var loc = m ? decodeURIComponent(m[1]) : 'sv_SE';
    return loc.indexOf('en') === 0 ? 'en' : 'sv';
  }

  function setLang(lang) {
    var loc = LOCALE[lang] || 'sv_SE';
    document.cookie =
      COOKIE + '=' + loc + ';path=/;max-age=31536000;samesite=lax';
    window.location.reload();
  }

  window.rockadenGetLanguage = getLang;
  window.rockadenSetLanguage = setLang;
})();
