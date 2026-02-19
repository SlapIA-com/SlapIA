<?php
include_once 'includes/config.php';
include_once 'includes/lang.php';

$page_title = "404 - " . t('page_not_found') . " - SlapIA";
$page_description = t('page_not_found');
include 'includes/header.php';

?>

<!-- New 404 Styles -->
<link rel="stylesheet" href="/assets/css/404.css">

<main class="error-container">
    <!-- Background Animation -->
    <div class="error-bg">
        <div class="error-blob blob-1"></div>
        <div class="error-blob blob-2"></div>
    </div>

    <!-- Content -->
    <h1 class="big-404">404</h1>

    <!-- Mini CMD Window -->
    <div class="mini-cmd-window">
        <div class="cmd-header">
            <div class="cmd-dots">
                <div class="dot dot-red"></div>
                <div class="dot dot-yellow"></div>
                <div class="dot dot-green"></div>
            </div>
            <div class="cmd-title">root@slapia-server:~</div>
        </div>
        <div class="cmd-body" id="cmd-output">
            <!-- Content will be typed here by JS -->
        </div>
        <div class="cmd-input-area" style="padding: 0 20px 20px 20px;">
            <span class="cmd-prompt">root@slapia:~#</span>
            <span class="cmd-cursor"></span>
        </div>
    </div>

    <a href="/" class="return-home-btn">
        <i class="fas fa-home"></i> <?php echo t('home'); ?>
    </a>

</main>

<script src="/assets/js/404-terminal.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
