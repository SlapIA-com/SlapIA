<?php
// Standalone ROI Calculator Page for Embedding
include_once '../includes/config.php';
include_once '../includes/lang.php';

// Force specific language if needed, otherwise auto-detect
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex"> <!-- Prevent indexing of this standalone component -->
    
    <!-- Open Graph / Discord Embeds -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="https://slapia.com/Calcule-ROI-IA">
    <meta property="og:title" content="<?php echo t('roi_title'); ?> - SlapIA">
    <meta property="og:description" content="<?php echo t('roi_subtitle'); ?> | <?php echo t('roi_disclaimer'); ?>">
    <meta property="og:image" content="https://slapia.com/assets/img/logo.png">
    
    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="https://slapia.com/Calcule-ROI-IA">
    <meta property="twitter:title" content="<?php echo t('roi_title'); ?> - SlapIA">
    <meta property="twitter:description" content="<?php echo t('roi_subtitle'); ?> | <?php echo t('roi_disclaimer'); ?>">
    <meta property="twitter:image" content="https://slapia.com/assets/img/logo.png">

    <title><?php echo t('roi_title'); ?> - SlapIA</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        body {
            /* If 'embed' param is present, transparent. Else, let style.css handle the background (noise + color). */
            background-color: <?php echo isset($_GET['embed']) ? 'transparent !important' : ''; ?>;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
            overflow: hidden; 
        }
        /* Override specifically for the standalone page */
        .bento-card {
            width: 100%;
            height: 100%;
            max-width: 900px; /* Restrict width to look like a card */
            border-radius: 32px;
            margin: 0 auto;
            background: #0a0a0a; /* Ensure dark card background */
            border: 1px solid rgba(255, 255, 255, 0.08); /* Subtle border */
        }
        
        /* Disable hover zoom effect as requested */
        .bento-card:hover {
            transform: none !important;
            box-shadow: none !important;
        }

        /* Remove the outer row margins from the component when embedded */
        .container-fluid > .row {
            margin: 0 !important;
            padding: 0 !important;
        }

        /* Mobile Optimization */
        @media (max-width: 768px) {
            body {
                padding: 10px !important;
                align-items: flex-start; /* Allow scrolling if content is tall */
                overflow-y: auto; /* Enable vertical scroll on mobile */
            }
            .bento-card {
                padding: 20px !important;
                border-radius: 24px;
            }
            .display-4 {
                font-size: 2.5rem; /* Smaller hero numbers */
            }
            h2 {
                font-size: 1.5rem;
            }
            /* Adjust stacking if needed */
            .col-lg-5, .col-lg-6 {
                margin-bottom: 2rem;
            }
            /* Share modal adjustments */
            .share-modal-content {
                width: 95%;
                margin: 10px;
            }
        }
    </style>
</head>
<body>

    <!-- Aurora Background (Only if not embedded) -->
    <?php if (!isset($_GET['embed'])): ?>
    <div class="aurora-bg">
        <div class="aurora-orb orb-1"></div>
        <div class="aurora-orb orb-2"></div>
        <div class="aurora-orb orb-3"></div>
    </div>
    <?php
endif; ?>

    <div style="width: 100%;">
        <?php include '../includes/roi-calculator.php'; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/roi-calculator.js"></script>
</body>
</html>
