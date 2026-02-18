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
    <title><?php echo t('roi_title'); ?> - SlapIA</title>
    
    <!-- CSS Dependencies -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        body {
            background: transparent;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0; /* Remove padding to let iframe control spacing */
            margin: 0;
            overflow: hidden; /* Prevent scrollbars if possible */
        }
        /* Override specifically for the standalone page */
        .bento-card {
            width: 100%;
            border-radius: 0; /* Optional: if we want to fill the iframe completely, or keep it if iframe has padding */
            /* max-width removed to fill iframe */
        }
        
        /* Remove the outer row margins from the component when embedded */
        .container-fluid > .row {
            margin: 0 !important;
            padding: 0 !important;
        }
    </style>
</head>
<body>

    <div style="width: 100%;">
        <?php include '../includes/roi-calculator.php'; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/roi-calculator.js"></script>
</body>
</html>
