/**
 * Suppress GTranslate UI and prevent the page from being translated.
 * Originally a WPCode snippet conditioned on the quiz page URL.
 */
document.addEventListener('DOMContentLoaded', function () {
  var selectors = '#goog-gt-tt, .goog-te-banner-frame, .goog-te-menu-frame, .goog-te-combo, .gtranslate_wrapper';
  document.querySelectorAll(selectors).forEach(function (el) {
    el.style.display = 'none';
  });
  document.body.classList.add('notranslate');
  document.body.setAttribute('translate', 'no');
});
