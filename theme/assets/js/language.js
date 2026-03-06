/**
 * Language switcher: persists language preference in localStorage,
 * sets data-lang on <html>, and dispatches events for React blocks.
 */
(function () {
  var STORAGE_KEY = 'language';
  var DEFAULT_LANG = 'sv';

  function getLang() {
    return localStorage.getItem(STORAGE_KEY) || DEFAULT_LANG;
  }

  function setLang(lang) {
    localStorage.setItem(STORAGE_KEY, lang);
    document.documentElement.setAttribute('data-lang', lang);
    window.dispatchEvent(
      new CustomEvent('rockaden-lang-change', { detail: { lang: lang } })
    );
  }

  window.rockadenGetLanguage = getLang;
  window.rockadenSetLanguage = setLang;

  // Ensure data-lang is set (inline script sets it early, this is a fallback).
  if (!document.documentElement.getAttribute('data-lang')) {
    document.documentElement.setAttribute('data-lang', getLang());
  }
})();
