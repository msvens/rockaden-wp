/**
 * Client-side i18n for server-rendered post blocks.
 * Reformats post dates and translates the "Read more" link per the visitor's
 * chosen locale (data-lang). (Comments are disabled site-wide, so there is no
 * comment UI to translate.)
 */
(function () {
  var strings = {
    sv: {
      readMore: 'Läs mer',
    },
    en: {
      readMore: 'Read more',
    },
  };

  var dateFormatters = {};
  function getFormatter(lang) {
    if (!dateFormatters[lang]) {
      var locale = lang === 'sv' ? 'sv-SE' : 'en-US';
      dateFormatters[lang] = new Intl.DateTimeFormat(locale, {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
      });
    }
    return dateFormatters[lang];
  }

  function update() {
    var lang = document.documentElement.getAttribute('data-lang') || 'sv';
    var s = strings[lang] || strings.sv;
    var fmt = getFormatter(lang);

    // Reformat post dates.
    document.querySelectorAll(
      '.wp-block-post-date time[datetime]'
    ).forEach(function (el) {
      var date = new Date(el.getAttribute('datetime'));
      if (!isNaN(date.getTime())) {
        el.textContent = fmt.format(date);
      }
    });

    // Swap "Läs mer" / "Read more"
    document.querySelectorAll('.wp-block-post-excerpt__more-link').forEach(function (el) {
      el.textContent = s.readMore;
    });
  }

  // Run once on load. The locale is fixed per request (server-set data-lang);
  // switching language reloads the page, so there's no live update event.
  update();
})();
