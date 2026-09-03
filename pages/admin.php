<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/i18n.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdmin();

$page_title = t('admin.title');
$csrf = generateCSRFToken();
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="assets/css/admin.css">

<section class="section">
  <div class="container">
    <h1 class="page-hero__title"><?php echo t('admin.title'); ?></h1>

    <nav class="admin-tabs">
      <button class="admin-tab-btn is-active" data-tab="overview"><?php echo t('admin.tab_overview'); ?></button>
      <button class="admin-tab-btn" data-tab="accounts"><?php echo t('admin.tab_accounts'); ?></button>
      <button class="admin-tab-btn" data-tab="rss"><?php echo t('admin.tab_rss'); ?></button>
      <button class="admin-tab-btn" data-tab="invoices"><?php echo t('admin.tab_invoices'); ?></button>
    </nav>

    <div id="admin-tab-overview" class="admin-tab-panel is-active"></div>
    <div id="admin-tab-accounts" class="admin-tab-panel"></div>
    <div id="admin-tab-rss" class="admin-tab-panel"></div>
    <div id="admin-tab-invoices" class="admin-tab-panel"></div>
  </div>
</section>

<script src="assets/js/vendor/chart.min.js"></script>
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
], JSON_UNESCAPED_UNICODE); ?>;
</script>
<script src="assets/js/admin.js"></script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
