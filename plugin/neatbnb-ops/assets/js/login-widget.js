/**
 * Inline login widget: hide the form for logged-in visitors, AJAX
 * submit to wp-login.php, then redirect to the page's target.
 * Consolidates the copy-pasted Elementor HTML widgets.
 */
document.addEventListener('DOMContentLoaded', function () {
  var container = document.getElementById('loginContainer');
  if (!container) return;

  // WordPress marks logged-in visitors with a body class.
  if (document.body.classList.contains('logged-in')) {
    container.style.display = 'none';
    return;
  }

  var form = container.querySelector('.login-form');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    e.preventDefault();

    var redirect = container.getAttribute('data-nb-redirect') || '';

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      credentials: 'same-origin'
    })
      .then(function () {
        container.style.display = 'none';
        if (redirect) {
          window.location.href = redirect;
        } else {
          window.location.reload();
        }
      })
      .catch(function (error) {
        console.error('Error:', error);
        form.submit(); // Fallback to a normal POST.
      });
  });
});
