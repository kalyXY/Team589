<?php
/**
 * SCOLARIA - Layout de base moderne
 * Structure HTML avec sidebar, header et système de thème
 */
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Scolaria - Gestion logistique scolaire moderne">
    <title><?= isset($pageTitle) ? $pageTitle . ' - Scolaria' : 'Scolaria - Gestion Scolaire' ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= BASE_URL; ?>assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="<?= BASE_URL; ?>assets/css/style.css">
    <?php if (isset($additionalCSS)): ?>
        <?php foreach ($additionalCSS as $css): ?>
            <link rel="stylesheet" href="<?= $css ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Chart.js pour les graphiques -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="app-container">
        <!-- SIDEBAR -->
        <aside class="sidebar" id="sidebar">
            <!-- Logo/Header de la sidebar -->
            <div class="sidebar-header">
                <a href="<?= BASE_URL; ?>dashboard.php" class="sidebar-logo">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Scolaria</span>
                </a>
            </div>
            
            <!-- Navigation -->
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>dashboard.php" class="nav-link <?= ($currentPage === 'dashboard') ? 'active' : '' ?>">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Tableau de bord</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>stocks.php" class="nav-link <?= ($currentPage === 'stocks') ? 'active' : '' ?>">
                            <i class="fas fa-boxes"></i>
                            <span>Gestion des stocks</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>alerts.php" class="nav-link <?= ($currentPage === 'alerts') ? 'active' : '' ?>">
                            <i class="fas fa-bell"></i>
                            <span>Alertes & Réappro</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>finances.php" class="nav-link <?= ($currentPage === 'finances') ? 'active' : '' ?>">
                            <i class="fas fa-chart-line"></i>
                            <span>Gestion financière</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>resources.php" class="nav-link <?= ($currentPage === 'resources') ? 'active' : '' ?>">
                            <i class="fas fa-layer-group"></i>
                            <span>Ressources</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>users.php" class="nav-link <?= ($currentPage === 'users') ? 'active' : '' ?>">
                            <i class="fas fa-users"></i>
                            <span>Utilisateurs</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>reports.php" class="nav-link <?= ($currentPage === 'reports') ? 'active' : '' ?>">
                            <i class="fas fa-file-alt"></i>
                            <span>Rapports</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="<?= BASE_URL; ?>settings.php" class="nav-link <?= ($currentPage === 'settings') ? 'active' : '' ?>">
                            <i class="fas fa-cog"></i>
                            <span>Paramètres</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </aside>
        
        <!-- CONTENU PRINCIPAL -->
        <main class="main-content">
            <!-- HEADER -->
            <header class="header">
                <div class="header-left">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="page-title"><?= $pageTitle ?? 'Tableau de bord' ?></h1>
                </div>
                
                <div class="header-right">
                    <!-- Toggle Dark Mode -->
                    <button class="theme-toggle" id="themeToggle">
                        <i class="fas fa-moon" id="themeIcon"></i>
                        <span id="themeText">Sombre</span>
                    </button>
                    
                    <!-- Notifications -->
                    <div class="notifications" id="notifications">
                        <button class="btn-ghost" onclick="toggleNotifications()">
                            <i class="fas fa-bell"></i>
                            <span class="notification-badge" id="notificationBadge">3</span>
                        </button>
                        
                        <div class="notifications-dropdown" id="notificationsDropdown">
                            <div class="notifications-header">
                                <h4>Notifications</h4>
                                <button class="btn-ghost btn-sm">Tout marquer lu</button>
                            </div>
                            <div class="notifications-list">
                                <div class="notification-item unread">
                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                    <div>
                                        <p>Stock faible: Cahiers A4</p>
                                        <small>Il y a 2 heures</small>
                                    </div>
                                </div>
                                <div class="notification-item">
                                    <i class="fas fa-check-circle text-success"></i>
                                    <div>
                                        <p>Commande livrée</p>
                                        <small>Il y a 1 jour</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Menu Utilisateur -->
                    <div class="user-menu" onclick="toggleUserMenu()">
                        <div class="user-avatar">
                            <?= strtoupper(substr($username ?? 'U', 0, 1)) ?>
                        </div>
                        <div class="user-info">
                            <h4><?= $username ?? 'Utilisateur' ?></h4>
                            <span><?= $role ?? 'Utilisateur' ?></span>
                        </div>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    
                    <!-- Dropdown Menu Utilisateur -->
                    <div class="user-dropdown" id="userDropdown">
                        <a href="<?= BASE_URL; ?>profile.php" class="dropdown-item">
                            <i class="fas fa-user"></i>
                            Mon profil
                        </a>
                        <a href="<?= BASE_URL; ?>settings.php" class="dropdown-item">
                            <i class="fas fa-cog"></i>
                            Paramètres
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="<?= BASE_URL; ?>logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i>
                            Déconnexion
                        </a>
                    </div>
                </div>
            </header>
            
            <!-- CONTENU DE LA PAGE -->
            <div class="content">
                <?php if (isset($breadcrumb) && !empty($breadcrumb)): ?>
                    <nav class="breadcrumb">
                        <?php foreach ($breadcrumb as $index => $crumb): ?>
                            <?php if ($index === count($breadcrumb) - 1): ?>
                                <span class="breadcrumb-current"><?= $crumb['title'] ?></span>
                            <?php else: ?>
                                <a href="<?= $crumb['url'] ?>" class="breadcrumb-link"><?= $crumb['title'] ?></a>
                                <i class="fas fa-chevron-right"></i>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </nav>
                <?php endif; ?>
                
                <!-- Messages Flash -->
                <?php if (isset($_SESSION['flash_message'])): ?>
                    <div class="alert alert-<?= $_SESSION['flash_type'] ?? 'info' ?> animate-slide-in">
                        <i class="fas fa-<?= $_SESSION['flash_type'] === 'success' ? 'check-circle' : ($_SESSION['flash_type'] === 'error' ? 'exclamation-triangle' : 'info-circle') ?>"></i>
                        <?= $_SESSION['flash_message'] ?>
                    </div>
                    <?php 
                    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
                    ?>
                <?php endif; ?>
                
                <!-- Contenu principal de la page -->
                <?= $content ?? '' ?>
            </div>
        </main>
    </div>
    
    <!-- Mobile Overlay -->
    <div class="mobile-overlay" id="mobileOverlay" onclick="closeSidebar()"></div>
    
    <!-- JavaScript -->
    <script src="<?= BASE_URL; ?>assets/js/main.js"></script>
    <?php if (isset($additionalJS)): ?>
        <?php foreach ($additionalJS as $js): ?>
            <script src="<?= $js ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- Page specific scripts -->
    <?php if (isset($inlineJS)): ?>
        <script>
            <?= $inlineJS ?>
        </script>
    <?php endif; ?>
</body>
</html>
