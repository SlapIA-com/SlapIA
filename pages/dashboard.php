<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

requireLogin();

$me = currentUser();
if ($me['role'] === 'admin') {
    header('Location: /admin');
    exit;
}

$page_title = t('dashboard.title');
$csrf = generateCSRFToken();
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?php echo assetUrl('assets/css/dashboard.css'); ?>">

<section class="section">
  <div class="container">
    <h1 class="page-hero__title"><?php echo t('dashboard.title'); ?></h1>

    <div id="dashboard-alert"></div>

    <div id="dashboard-summary" class="dash-summary"></div>

    <div class="dash-grid">
      <div id="dashboard-profile" class="dash-card"></div>
      <div id="dashboard-billing" class="dash-card"></div>
    </div>

    <div id="dashboard-invoices" class="dash-card"></div>

    <div id="dashboard-avis" class="dash-card"></div>

    <div id="dashboard-password" class="dash-card"></div>
  </div>
</section>

<script>
window.DASHBOARD_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;
window.DASHBOARD_I18N = <?php echo json_encode([
    'greeting' => t('dashboard.greeting'),
    'no_service' => t('dashboard.no_service'),
    'label_profile' => t('dashboard.label_profile'),
    'label_name' => t('dashboard.label_name'),
    'label_email' => t('dashboard.label_email'),
    'label_company' => t('dashboard.label_company'),
    'label_billing' => t('dashboard.label_billing'),
    'label_status' => t('dashboard.label_status'),
    'label_last_login' => t('dashboard.label_last_login'),
    'label_invoices' => t('dashboard.label_invoices'),
    'empty_invoices' => t('dashboard.empty_invoices'),
    'download' => t('dashboard.download'),
    'view' => t('dashboard.view'),
    'change_password_title' => t('dashboard.change_password_title'),
    'label_current_password' => t('dashboard.label_current_password'),
    'label_new_password' => t('dashboard.label_new_password'),
    'submit_update' => t('dashboard.submit_update'),
    'password_updated' => t('dashboard.password_updated'),
    'err_generic' => t('dashboard.err_generic'),
    'label_photo' => t('dashboard.label_photo'),
    'change_photo' => t('dashboard.change_photo'),
    'photo_updated' => t('dashboard.photo_updated'),
    'err_photo_type' => t('dashboard.err_photo_type'),
    'err_photo_size' => t('dashboard.err_photo_size'),
    'err_photo_failed' => t('dashboard.err_photo_failed'),
    'label_linkedin' => t('dashboard.label_linkedin'),
    'placeholder_linkedin' => t('dashboard.placeholder_linkedin'),
    'save' => t('dashboard.save'),
    'linkedin_updated' => t('dashboard.linkedin_updated'),
    'err_linkedin_invalid' => t('dashboard.err_linkedin_invalid'),
    'label_avis_title' => t('dashboard.label_avis_title'),
    'label_avis_text' => t('dashboard.label_avis_text'),
    'placeholder_avis' => t('dashboard.placeholder_avis'),
    'label_satisfaction' => t('dashboard.label_satisfaction'),
    'publish' => t('dashboard.publish'),
    'avis_updated' => t('dashboard.avis_updated'),
    'err_avis_empty' => t('dashboard.err_avis_empty'),
    'preview_label' => t('dashboard.preview_label'),
    'preview_empty' => t('dashboard.preview_empty'),
    'label_phone' => t('dashboard.label_phone'),
    'placeholder_phone' => t('dashboard.placeholder_phone'),
    'err_phone_invalid' => t('dashboard.err_phone_invalid'),
    'phone_updated' => t('dashboard.phone_updated'),
    'label_location' => t('dashboard.label_location'),
    'placeholder_location' => t('dashboard.placeholder_location'),
    'location_updated' => t('dashboard.location_updated'),
    'label_orders' => t('dashboard.label_orders'),
    'empty_orders' => t('dashboard.empty_orders'),
], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="<?php echo assetUrl('assets/js/dashboard.js'); ?>"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
