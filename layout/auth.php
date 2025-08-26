<?php
/**
 * Layout d'authentification pour Scolaria
 * Template minimal pour les pages de connexion/inscription
 * Team589
 */

// Configuration par défaut
$pageTitle = $pageTitle ?? 'Scolaria';
$bodyClass = $bodyClass ?? '';
$additionalCSS = $additionalCSS ?? [];
$additionalJS = $additionalJS ?? [];
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Scolaria - Application de gestion logistique scolaire">
    <meta name="author" content="Team589">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Font Awesome local first, CDN fallback loaded by fa-loader.js -->
    <link rel="stylesheet" href="assets/vendor/fontawesome/css/all.min.css">
    <style>
    .fa,.fas,.far{font-family:"Font Awesome 6 Free" !important;}
    .fas{font-weight:900 !important;}
    .far{font-weight:400 !important;}
    .fab{font-family:"Font Awesome 6 Brands" !important;font-weight:400 !important;}
    </style>
    
    <!-- CSS Additionnels -->
    <?php foreach ($additionalCSS as $css): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <?php
    // Afficher le contenu de la page
    if (isset($content)) {
        echo $content;
    } else {
        // Contenu par défaut ou inclusion de fichier
        if (isset($contentFile) && file_exists($contentFile)) {
            include $contentFile;
        }
    }
    ?>
    
    <!-- JavaScript Principal (version allégée pour l'auth) -->
    <script src="assets/js/main.js"></script>
    <script src="assets/js/fa-loader.js"></script>
    <script src="assets/js/fa-v5-shim.js"></script>
    
    <!-- JavaScript Additionnels -->
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
    
    <!-- Script d'initialisation -->
    <script>
        // Configuration spécifique à la page d'authentification
        document.addEventListener('DOMContentLoaded', function() {
            // Initialisation du thème
            if (typeof initTheme === 'function') {
                initTheme();
            }
            
            // Initialisation spécifique à la page
            if (typeof pageInit === 'function') {
                pageInit();
            }
        });
    </script>
</body>
</html>