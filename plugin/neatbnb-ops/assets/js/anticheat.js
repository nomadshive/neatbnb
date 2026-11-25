/**
 * Quiz anti-cheat: blocks right-click, copy/cut/paste, selection,
 * dragging, devtools shortcuts. Originally a WPCode snippet injected
 * as raw <script>/<style>; now enqueued only on the quiz page.
 *
 * NOTE: this is a deterrent, not security. Content is still delivered
 * to the browser — see docs/security-and-privacy.md.
 */
document.addEventListener('DOMContentLoaded', function () {
  // Disable right-click
  document.addEventListener('contextmenu', function (event) { event.preventDefault(); });

  // Disable copy, cut, paste
  ['copy', 'cut', 'paste'].forEach(function (evt) {
    document.addEventListener(evt, function (e) { e.preventDefault(); });
  });

  // Disable text selection
  document.addEventListener('selectstart', function (e) { e.preventDefault(); });

  // Disable dragging
  document.addEventListener('dragstart', function (e) { e.preventDefault(); });

  // Block common cheat keyboard shortcuts
  document.addEventListener('keydown', function (e) {
    var blockedKeys = ['c', 'v', 'x', 'a', 's', 'u', 'p', 'j', 'i'];
    if ((e.ctrlKey || e.metaKey) && blockedKeys.indexOf(e.key.toLowerCase()) !== -1) {
      e.preventDefault();
    }
    if (e.key === 'F12') {
      e.preventDefault();
    }
    if (e.ctrlKey && e.shiftKey && ['i', 'j', 'c'].indexOf(e.key.toLowerCase()) !== -1) {
      e.preventDefault();
    }
  });
});
