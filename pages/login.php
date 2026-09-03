<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

if ($me = currentUser()) {
    header('Location: ' . ($me['role'] === 'admin' ? '/admin' : '/dashboard'));
    exit;
}

$page_title = t('auth.login_title');
$csrf = generateCSRFToken();
$turnstileSiteKey = config('TURNSTILE_SITE_KEY', '');
include __DIR__ . '/../includes/auth-header.php';
?>

    <div class="auth-card">
      <h1 class="auth-title"><?php echo t('auth.login_title'); ?></h1>

      <div id="login-alert"></div>

      <form id="login-form" novalidate>
        <div class="field">
          <label for="email"><?php echo t('auth.label_email'); ?></label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="field" style="margin-top:16px;">
          <label for="password"><?php echo t('auth.label_password'); ?></label>
          <input type="password" id="password" name="password" required>
        </div>
        <label class="consent-check" style="margin-top:16px;">
          <input type="checkbox" id="remember_me" name="remember_me">
          <span><?php echo t('auth.remember_me'); ?></span>
        </label>

        <div class="auth-turnstile-wrap">
          <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>" data-theme="dark"></div>
        </div>

        <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px;">
          <?php echo t('auth.submit_login'); ?>
        </button>
      </form>

      <p class="auth-links"><a href="/reset-password"><?php echo t('auth.forgot_password'); ?></a></p>
    </div>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
document.getElementById('login-form').addEventListener('submit', function (e) {
  e.preventDefault();
  var alertBox = document.getElementById('login-alert');
  alertBox.innerHTML = '';

  var turnstileField = document.querySelector('[name="cf-turnstile-response"]');

  fetch('/api/auth-login.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-Token': '<?php echo $csrf; ?>',
    },
    body: JSON.stringify({
      email: document.getElementById('email').value,
      password: document.getElementById('password').value,
      remember_me: document.getElementById('remember_me').checked,
      'cf-turnstile-response': turnstileField ? turnstileField.value : '',
    }),
  })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (data.success) {
        window.location.href = data.redirect;
      } else {
        alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + data.error + '</span></div>';
      }
    })
    .catch(function () {
      alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + <?php echo json_encode(t('auth.err_server')); ?> + '</span></div>';
    });
});
</script>

<?php include __DIR__ . '/../includes/auth-footer.php'; ?>
