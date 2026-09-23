/**
 * Language switcher: the SV/EN controls are links to ?rc_setlang=, and the
 * server sets the `rc_locale` cookie and redirects — to the paired page when
 * the menu item for the current page has an English target configured, and
 * back to the same URL when it does not. `data-lang` on <html> is set
 * server-side. No cookie is written here and no mapping logic lives here.
 */
(function () {
  var PARAM = 'rc_setlang';

  function getLang() {
    // Prefer what the server actually rendered. The cookie is absent on a
    // first visit, when the page is Swedish but nothing has been stored yet.
    var attr = document.documentElement.getAttribute('data-lang');
    if (attr === 'en' || attr === 'sv') {
      return attr;
    }
    var m = document.cookie.match(/(?:^|;\s*)rc_locale=([^;]+)/);
    var loc = m ? decodeURIComponent(m[1]) : 'sv_SE';
    return loc.indexOf('en') === 0 ? 'en' : 'sv';
  }

  /* Build the switch URL for whatever page we are on. */
  function switchUrl(lang) {
    var code = lang === 'en' ? 'en' : 'sv';
    var href = window.location.href;
    var hash = '';

    var h = href.indexOf('#');
    if (h > -1) {
      hash = href.slice(h);
      href = href.slice(0, h);
    }

    // Drop any parameter already present so repeated switches don't stack up.
    href = href.replace(/([?&])rc_setlang=[^&]*(&|$)/, '$1').replace(/[?&]$/, '');

    return href + (href.indexOf('?') > -1 ? '&' : '?') + PARAM + '=' + code + hash;
  }

  function setLang(lang) {
    window.location.assign(switchUrl(lang));
  }

  window.rockadenGetLanguage = getLang;
  window.rockadenSetLanguage = setLang;
  window.rockadenLanguageUrl = switchUrl;
})();
