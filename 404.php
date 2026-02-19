<?php
include_once __DIR__ . '/includes/config.php';
include_once __DIR__ . '/includes/lang.php';
// Fallback if lang not set
if (!isset($lang))
    $lang = 'fr';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - System Failure</title>
    <link href="https://fonts.googleapis.com/css2?family=Fira+Code:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/terminal.css">
</head>
<body>
    <div class="terminal-container">
        <div class="scanline"></div>
        <div class="terminal-content">
            <div id="terminal-output"></div>
            <div class="input-line">
                <span class="prompt">C:\Users\Visitor></span>
                <span class="cursor">_</span>
            </div>
            
            <div id="choice-buttons" class="hidden">
                <a href="/" class="cmd-btn">[ Y ] REBOOT SYSTEM</a>
                <a href="/contact" class="cmd-btn">[ N ] REPORT BUG</a>
            </div>
        </div>
    </div>
    
    <script>
        const LANG = "<?php echo $lang; ?>";
    </script>
    <script src="/assets/js/terminal.js"></script>
</body>
</html>
