<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$page_title = t('admin.title');
$csrf = generateCSRFToken();
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?php echo assetUrl('assets/css/admin.css'); ?>">

<section class="section">
  <div class="container">
    <h1 class="page-hero__title"><?php echo t('admin.title'); ?></h1>

    <nav class="admin-tabs">
      <button class="admin-tab-btn is-active" data-tab="overview"><?php echo t('admin.tab_overview'); ?></button>
      <button class="admin-tab-btn" data-tab="accounts"><?php echo t('admin.tab_accounts'); ?></button>
      <button class="admin-tab-btn" data-tab="reviews"><?php echo t('admin.tab_reviews'); ?></button>
      <button class="admin-tab-btn" data-tab="rss"><?php echo t('admin.tab_rss'); ?></button>
      <button class="admin-tab-btn" data-tab="invoices"><?php echo t('admin.tab_invoices'); ?></button>
    </nav>

    <div id="admin-tab-overview" class="admin-tab-panel is-active"></div>
    <div id="admin-tab-accounts" class="admin-tab-panel"></div>
    <div id="admin-tab-reviews" class="admin-tab-panel"></div>
    <div id="admin-tab-rss" class="admin-tab-panel"></div>
    <div id="admin-tab-invoices" class="admin-tab-panel"></div>
  </div>
</section>

<script src="<?php echo assetUrl('assets/js/vendor/chart.min.js'); ?>"></script>
<script>
window.ADMIN_CSRF_TOKEN = <?php echo json_encode($csrf); ?>;
window.ADMIN_I18N = <?php echo json_encode([
    'details_btn' => t('admin.details_btn'),
    'close_btn' => t('admin.close_btn'),
    'label_phone' => t('admin.label_phone'),
    'label_location' => t('admin.label_location'),
    'label_orders' => t('admin.label_orders'),
    'save' => t('admin.save'),
    'contact_updated' => t('admin.contact_updated'),
    'err_fields' => t('admin.err_fields'),
    'err_update_failed' => t('admin.err_update_failed'),
    'new_client_btn' => t('admin.new_client_btn'),
    'new_client_title' => t('admin.new_client_title'),
    'label_full_name' => t('admin.label_full_name'),
    'label_email' => t('admin.label_email'),
    'label_company' => t('admin.label_company'),
    'label_job' => t('admin.label_job'),
    'label_linkedin' => t('admin.label_linkedin'),
    'label_role' => t('admin.label_role'),
    'label_password' => t('admin.label_password'),
    'create_btn' => t('admin.create_btn'),
    'cancel_btn' => t('admin.cancel_btn'),
    'client_created' => t('admin.client_created'),
    'password_shown_once' => t('admin.password_shown_once'),
    'profile_updated' => t('admin.profile_updated'),
    'label_photo' => t('admin.label_photo'),
    'change_photo' => t('admin.change_photo'),
    'photo_updated' => t('admin.photo_updated'),
    'prestations_title' => t('admin.prestations_title'),
    'add_prestation_btn' => t('admin.add_prestation_btn'),
    'label_service' => t('admin.label_service'),
    'label_price' => t('admin.label_price'),
    'label_billing_status' => t('admin.label_billing_status'),
    'label_date_start' => t('admin.label_date_start'),
    'label_date_end' => t('admin.label_date_end'),
    'label_description' => t('admin.label_description'),
    'no_prestations' => t('admin.no_prestations'),
    'delete_btn' => t('admin.delete_btn'),
    'edit_btn' => t('admin.edit_btn'),
    'confirm_delete_prestation' => t('admin.confirm_delete_prestation'),
    'prestation_saved' => t('admin.prestation_saved'),
    'prestation_deleted' => t('admin.prestation_deleted'),
    'label_satisfaction' => t('admin.label_satisfaction'),
    'label_review_name' => t('admin.label_review_name'),
    'confirm_delete_review' => t('admin.confirm_delete_review'),
    'review_saved' => t('admin.review_saved'),
    'review_deleted' => t('admin.review_deleted'),
    'no_reviews' => t('admin.no_reviews'),
    'err_duplicate_email' => t('admin.err_duplicate_email'),
], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="<?php echo assetUrl('assets/js/admin.js'); ?>"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
