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
            background: transparent; /* Allows embedding on different backgrounds if iframe allows */
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            margin: 0;
        }
        /* Override specifically for the standalone page to look good */
        .bento-card {
            width: 100%;
            max-width: 900px;
            margin: 0 auto;
        }
    </style>
</head>
<body>

    <div class="container-fluid">
        <?php include '../includes/roi-calculator.php'; ?>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/roi-calculator.js"></script>
</body>
</html>
