<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/notion-users.php';

if ($me = currentUser()) {
    header('Location: ' . ($me['role'] === 'admin' ? '/admin' : '/dashboard'));
    exit;
}

$token = trim($_GET['token'] ?? '');
$email = strtolower(trim($_GET['email'] ?? ''));
$mode  = ($token && $email) ? 'reset' : 'request';

if ($mode === 'reset' && !validateResetToken($email, $token)) {
    header('Location: /404');
    exit;
}

$page_title = t('auth.reset_title');
$csrf = generateCSRFToken();
include __DIR__ . '/../includes/auth-header.php';
?>

    <div class="auth-card">
      <h1 class="auth-title"><?php echo t('auth.reset_title'); ?></h1>
      <div id="reset-alert"></div>

    <?php if ($mode === 'request'): ?>
      <form id="reset-request-form" novalidate>
        <div class="field">
          <label for="email"><?php echo t('auth.label_email'); ?></label>
          <input type="email" id="email" name="email" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px;">
          <?php echo t('auth.submit_reset_request'); ?>
        </button>
      </form>
      <script>
      document.getElementById('reset-request-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var alertBox = document.getElementById('reset-alert');
        fetch('/api/auth-reset-request.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?php echo $csrf; ?>' },
          body: JSON.stringify({ email: document.getElementById('email').value }),
        })
          .then(function (r) { return r.json(); })
          .then(function () {
            alertBox.innerHTML = '<div class="alert alert--success"><span>✓</span><span>' + <?php echo json_encode(t('auth.reset_request_sent')); ?> + '</span></div>';
          });
      });
      </script>
    <?php else: ?>
      <form id="reset-exec-form" novalidate>
        <div class="field">
          <label for="password"><?php echo t('auth.label_new_password'); ?></label>
          <input type="password" id="password" name="password" minlength="8" required>
        </div>
        <button type="submit" class="btn btn--primary btn--block" style="margin-top:20px;">
          <?php echo t('auth.submit_reset_exec'); ?>
        </button>
      </form>
      <script>
      document.getElementById('reset-exec-form').addEventListener('submit', function (e) {
        e.preventDefault();
        var alertBox = document.getElementById('reset-alert');
        fetch('/api/auth-reset-exec.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?php echo $csrf; ?>' },
          body: JSON.stringify({
            email: <?php echo json_encode($email); ?>,
            token: <?php echo json_encode($token); ?>,
            password: document.getElementById('password').value,
          }),
        })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (data.success) {
              window.location.href = data.redirect;
            } else {
              alertBox.innerHTML = '<div class="alert alert--error"><span>!</span><span>' + data.error + '</span></div>';
            }
          });
      });
      </script>
    <?php endif; ?>
    </div>

<?php include __DIR__ . '/../includes/auth-footer.php'; ?>
