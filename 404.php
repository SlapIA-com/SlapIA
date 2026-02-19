<?php
include_once 'includes/config.php';
include_once 'includes/lang.php';

$page_title = "404 - " . t('page_not_found') . " - SlapIA";
$page_description = t('page_not_found');
include 'includes/header.php';

?>

<style>
    /* Override rigid terminal styles to fit in layout */
    body {
        overflow-y: auto !important; /* Allow scrolling for footer */
        height: auto !important;
    }
    .terminal-section {
        min-height: 80vh;
        display: flex;
        flex-direction: column;
        justify-content: center;
        background-color: #0c0c0c; /* Ensure match */
        position: relative;
    }
    .terminal-container {
        position: relative; /* Not fixed anymore */
        height: auto;
        padding: 60px 20px;
    }
    /* Fix Z-index to show behind nav if needed, or normal flow */
</style>

<main class="terminal-section">
    <div class="terminal-container">
        <div class="scanline"></div>
        <div class="terminal-content">
            <div id="terminal-output"></div>
            <div class="input-line">
                <span class="prompt">C:\Users\Visitor></span>
                <span class="cursor">_</span>
            </div>
            
            <div id="choice-buttons" class="hidden">
                <a href="/" class="cmd-btn">[ Y ] <?php echo t('home'); ?> (REBOOT)</a>
                <!-- Report button removed as requested -->
            </div>
        </div>
    </div>
</main>

<script>
    const LANG = "<?php echo $lang; ?>";
</script>
<script src="/assets/js/terminal.js"></script>

<?php include __DIR__ . '/includes/footer.php'; ?>
