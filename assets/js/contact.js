(function () {
  var emailInput = document.getElementById('email');
  var emailAlert = document.getElementById('email-live-alert');
  var form = document.querySelector('.contact-card form');
  if (!emailInput || !emailAlert || !form) return;

  function escHtml(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function checkEmail() {
    var invalid = emailInput.value !== '' && !emailInput.checkValidity();
    emailAlert.innerHTML = invalid
      ? '<div class="alert alert--error"><span>!</span><span>' + escHtml(emailInput.dataset.errMsg) + '</span></div>'
      : '';
    return !invalid;
  }

  emailInput.addEventListener('blur', checkEmail);
  emailInput.addEventListener('input', function () {
    if (emailAlert.innerHTML !== '') checkEmail();
  });

  form.addEventListener('submit', function (e) {
    if (!checkEmail()) {
      e.preventDefault();
      emailInput.focus();
    }
  });
})();
