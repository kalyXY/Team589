<?php
/**
 * Layout de base pour l'application Scolaria
 * Template réutilisable avec sidebar, header et zone de contenu
 * Team589
 */

// Configuration par défaut
$pageTitle = $pageTitle ?? 'Scolaria';
$currentPage = $currentPage ?? '';
$showSidebar = $showSidebar ?? true;
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
    <title><?php echo htmlspecialchars($pageTitle); ?> - Scolaria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/images/favicon.ico">
    
    <!-- CSS Principal -->
    <link rel="stylesheet" href="assets/css/style.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <!-- CSS Additionnels -->
    <?php foreach ($additionalCSS as $css): ?>
        <link rel="stylesheet" href="<?php echo htmlspecialchars($css); ?>">
    <?php endforeach; ?>
</head>
<body class="<?php echo htmlspecialchars($bodyClass); ?>">
    <div class="app-layout">
        <?php if ($showSidebar): ?>
            <!-- Sidebar -->
            <aside class="sidebar" id="sidebar">
                <!-- Logo -->
                <div class="sidebar-logo">
                    <div class="logo-icon">
                        <i class="fas fa-graduation-cap"></i>
                    </div>
                    <div class="logo-text">Scolaria</div>
                </div>
                
                <!-- Navigation -->
                <nav class="sidebar-nav">
                    <?php include __DIR__ . '/../partials/menu.php'; ?>
                </nav>
            </aside>
        <?php endif; ?>
        
        <!-- Contenu Principal -->
        <main class="main-content">
            <!-- Header -->
            <header class="app-header">
                <div class="header-left">
                    <?php if ($showSidebar): ?>
                        <button class="sidebar-toggle" data-tooltip="Toggle Sidebar (Ctrl+B)">
                            <i class="fas fa-bars"></i>
                        </button>
                    <?php endif; ?>
                    <h1 class="page-title"><?php echo htmlspecialchars($pageTitle); ?></h1>
                </div>
                
                <div class="header-right">
                    <!-- Toggle Dark Mode -->
                    <button class="theme-toggle" data-tooltip="Changer de thème (Ctrl+D)">
                        <i class="fas fa-moon"></i>
                    </button>
                    
                    <!-- Notifications -->
                    <button class="notifications-btn" data-tooltip="Notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notifications-badge" style="display: none;">0</span>
                    </button>
                    
                    <!-- Menu Utilisateur -->
                    <div class="user-menu" data-tooltip="Menu utilisateur">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($_SESSION['username'] ?? 'U', 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Utilisateur'); ?></div>
                            <div class="user-role"><?php echo htmlspecialchars($_SESSION['role'] ?? 'Utilisateur'); ?></div>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Zone de Contenu -->
            <div class="content-area">
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
            </div>
        </main>
    </div>
    
    <!-- JavaScript Principal -->
    <script src="assets/js/main.js"></script>
    
    <!-- JavaScript Additionnels -->
    <?php foreach ($additionalJS as $js): ?>
        <script src="<?php echo htmlspecialchars($js); ?>"></script>
    <?php endforeach; ?>
    
    <!-- Script d'initialisation -->
    <script>
        // Configuration spécifique à la page
        document.addEventListener('DOMContentLoaded', function() {
            // Marquer la page active
            const currentPage = '<?php echo $currentPage; ?>';
            if (currentPage) {
                const activeLink = document.querySelector(`.nav-link[href*="${currentPage}"]`);
                if (activeLink) {
                    activeLink.classList.add('active');
                }
            }
            
            // Initialisation spécifique à la page
            if (typeof pageInit === 'function') {
                pageInit();
            }
        });
    </script>
</body>
</html>