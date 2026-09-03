<?php
require_once 'includes/config.php';
require_once 'includes/i18n.php';
require_once 'includes/auth.php';
require_once 'includes/notion-contact.php';

$page_title = t('contact.meta_title');
$page_description = t('contact.meta_description');

$errors = [];
$sent = isset($_GET['sent']);
$csrf = generateCSRFToken();
$turnstileSiteKey = config('TURNSTILE_SITE_KEY', '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $company   = trim($_POST['company'] ?? '');
    $subject   = trim($_POST['subject'] ?? '');
    $message   = trim($_POST['message'] ?? '');
    $consent   = isset($_POST['consent']);
    $csrfToken = $_POST['csrf_token'] ?? '';
    $turnstile = $_POST['cf-turnstile-response'] ?? '';
    $ip        = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (!verifyCSRFToken($csrfToken)) {
        $errors[] = t('contact.err_csrf');
    } elseif (!rateLimitCheck('contact_ip_' . $ip, 5, 900)) {
        $errors[] = t('contact.err_rate_limit');
    } else {
        if ($firstname === '' || $lastname === '') $errors[] = t('contact.err_name');
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = t('contact.err_email');
        if ($message === '' || mb_strlen($message) > 5000) $errors[] = t('contact.err_message');
        if (!$consent) $errors[] = t('contact.err_consent');
        if ($subject === '' || resolveContactSubjectLabel($subject) === null) $errors[] = t('contact.err_subject');

        if ($turnstile === '') {
            $errors[] = t('contact.err_captcha');
        } else {
            $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => http_build_query([
                    'secret'   => config('TURNSTILE_SECRET_KEY'),
                    'response' => $turnstile,
                    'remoteip' => $ip,
                ]),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);
            $verify = json_decode(curl_exec($ch), true);
            if (empty($verify['success'])) {
                $errors[] = t('contact.err_captcha_failed');
            }
        }
    }

    if (empty($errors)) {
        if (!submitContactMessage($firstname, $lastname, $email, $company, $subject, $message)) {
            $errors[] = t('contact.err_server');
        } else {
            // No webhook call here: n8n's "Formulaire site WEB" workflow
            // triggers directly off new pages in the Contact database
            // (pageAddedToDatabase), which submitContactMessage() just
            // created. Posting to N8N_AUTH_WEBHOOK_URL would incorrectly
            // hit the password-reset webhook with contact-form data.
            header('Location: contact.php?sent=1');
            exit;
        }
    }
}

include 'includes/header.php';
?>

<section class="page-hero">
  <div class="page-hero-canvas" aria-hidden="true"><span></span><span></span><span></span><span></span><span></span><span></span></div>
  <div class="container">
    <span class="eyebrow"><?php echo t('contact.eyebrow'); ?></span>
    <h1 class="page-hero__title"><?php echo t('contact.title_pre'); ?><mark><?php echo t('contact.title_mark'); ?></mark><?php echo t('contact.title_post'); ?></h1>
    <p class="page-hero__lede"><?php echo t('contact.lede'); ?></p>
  </div>
</section>

<section class="section" style="padding-top:0">
  <div class="container contact-layout">

    <div class="contact-card reveal">
      <?php if ($sent): ?>
        <div class="alert alert--success">
          <span>✓</span>
          <span><?php echo t('contact.success'); ?></span>
        </div>
      <?php endif; ?>

      <?php if (!empty($errors)): ?>
        <div class="alert alert--error">
          <span>!</span>
          <span><?php echo implode(' ', array_map('htmlspecialchars', $errors)); ?></span>
        </div>
      <?php endif; ?>

      <form method="post" action="contact.php" novalidate>
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
        <div class="form-grid">
          <div class="field">
            <label for="firstname"><?php echo t('contact.label_firstname'); ?></label>
            <input type="text" id="firstname" name="firstname" required value="<?php echo htmlspecialchars($_POST['firstname'] ?? ''); ?>">
          </div>
          <div class="field">
            <label for="lastname"><?php echo t('contact.label_lastname'); ?></label>
            <input type="text" id="lastname" name="lastname" required value="<?php echo htmlspecialchars($_POST['lastname'] ?? ''); ?>">
          </div>
          <div class="field">
            <label for="email"><?php echo t('contact.label_email'); ?></label>
            <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" data-err-msg="<?php echo htmlspecialchars(t('contact.err_email')); ?>">
            <div id="email-live-alert"></div>
          </div>
          <div class="field">
            <label for="company"><?php echo t('contact.label_company'); ?></label>
            <input type="text" id="company" name="company" value="<?php echo htmlspecialchars($_POST['company'] ?? ''); ?>">
          </div>
          <div class="field">
            <label for="subject"><?php echo t('contact.label_subject'); ?></label>
            <?php $selectedSubject = $_POST['subject'] ?? ''; ?>
            <select id="subject" name="subject" required>
              <option value="" <?php echo $selectedSubject === '' ? 'selected' : ''; ?> disabled><?php echo t('contact.subject_placeholder'); ?></option>
              <?php foreach (CONTACT_SUBJECT_OPTIONS as $slug => $label): ?>
              <option value="<?php echo htmlspecialchars($slug); ?>" <?php echo $selectedSubject === $slug ? 'selected' : ''; ?>><?php echo t('contact.' . $slug); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field field--full">
            <label for="message"><?php echo t('contact.label_message'); ?></label>
            <textarea id="message" name="message" rows="6" required placeholder="<?php echo t('contact.message_placeholder'); ?>"><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
          </div>
        </div>
        <label class="consent-check">
          <input type="checkbox" name="consent" required <?php echo isset($_POST['consent']) ? 'checked' : ''; ?>>
          <span><?php echo t('contact.consent_text'); ?> <a href="confidentialite.php"><?php echo t('contact.consent_link'); ?></a>.</span>
        </label>
        <div class="contact-turnstile-wrap">
          <div class="cf-turnstile" data-sitekey="<?php echo htmlspecialchars($turnstileSiteKey); ?>"></div>
        </div>
        <button type="submit" class="btn btn--signal"><?php echo t('contact.submit'); ?> <span class="btn__arrow">→</span></button>
      </form>

<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script src="<?php echo assetUrl('assets/js/contact.js'); ?>"></script>
    </div>

    <div class="contact-info reveal">
      <div class="contact-info__item">
        <span class="contact-info__icon">@</span>
        <div>
          <h4><?php echo t('contact.info_email_label'); ?></h4>
          <a href="mailto:contact@slapia.com">contact@slapia.com</a>
        </div>
      </div>
      <div class="contact-info__item">
        <span class="contact-info__icon">◎</span>
        <div>
          <h4><?php echo t('contact.info_zone_label'); ?></h4>
          <p><?php echo t('contact.info_zone_text'); ?></p>
        </div>
      </div>
      <div class="contact-info__item">
        <span class="contact-info__icon">✓</span>
        <div>
          <h4><?php echo t('contact.info_delay_label'); ?></h4>
          <p><?php echo t('contact.info_delay_text'); ?></p>
        </div>
      </div>
    </div>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
