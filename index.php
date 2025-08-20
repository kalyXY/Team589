<?php
session_start();

// Configuration base de données
class Database {
    private $host = "127.0.0.1";
    private $port = "3306";
    private $db_name = "scolaria";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->db_name};charset=utf8mb4";
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]);
        } catch(PDOException $exception) {
            // N'affichez pas d'erreur détaillée en production; gardez un message générique
            echo "Erreur de connexion : " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Récupération des statistiques
$database = new Database();
$db = $database->getConnection();

$stats = ['total_stocks' => 0, 'stocks_faibles' => 0, 'total_depenses' => 0, 'total_users' => 0];
$db_error = null;

if ($db instanceof PDO) {
    try {
        $stmt = $db->query("SELECT COUNT(*) as total FROM stocks");
        $stats['total_stocks'] = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM stocks WHERE quantite <= seuil");
        $stats['stocks_faibles'] = $stmt->fetch()['total'];
        
        $stmt = $db->query("SELECT SUM(montant) as total FROM depenses");
        $result = $stmt->fetch();
        $stats['total_depenses'] = $result && isset($result['total']) ? (float)$result['total'] : 0;
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM users");
        $stats['total_users'] = $stmt->fetch()['total'];
    } catch(PDOException $e) {
        $db_error = 'Impossible de récupérer les statistiques.';
    }
} else {
    $db_error = 'Base de données indisponible.';
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scolaria - Gestion Scolaire</title>
    <link rel="stylesheet" href="stocks.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body class="app-layout">
    <!-- Header principal -->
    <header class="main-header">
        <div class="header-container">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="brand-info">
                    <h1>Scolaria</h1>
                    <p class="subtitle">Gestion Scolaire Complète</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <span class="user-name">Administrateur</span>
                </div>
                <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">
                    <i class="fas fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Navigation principale -->
    <nav class="main-nav" id="mainNav">
        <div class="nav-container">
            <ul class="nav-menu">
                <li class="nav-item active">
                    <a href="index.php" class="nav-link">
                        <i class="fas fa-home"></i>
                        <span>Accueil</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="stocks.php" class="nav-link">
                        <i class="fas fa-boxes"></i>
                        <span>Gestion des Stocks</span>
                        <?php if ($stats['stocks_faibles'] > 0): ?>
                            <span class="nav-badge"><?php echo $stats['stocks_faibles']; ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showComingSoon('Gestion Financière')">
                        <i class="fas fa-euro-sign"></i>
                        <span>Finances</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showComingSoon('Gestion des Élèves')">
                        <i class="fas fa-user-graduate"></i>
                        <span>Élèves</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showComingSoon('Gestion des Enseignants')">
                        <i class="fas fa-chalkboard-teacher"></i>
                        <span>Enseignants</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showComingSoon('Planning')">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Planning</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="showComingSoon('Rapports')">
                        <i class="fas fa-chart-bar"></i>
                        <span>Rapports</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- Contenu principal -->
    <main class="main-content">
        <div class="container">
            <!-- Section Hero -->
            <section class="hero-section">
                <div class="hero-content">
                    <h1 class="hero-title">
                        <span class="icon"><i class="fas fa-graduation-cap"></i></span>
                        Bienvenue dans Scolaria
                    </h1>
                    <p class="hero-subtitle">
                        Votre solution complète de gestion scolaire - Team589
                    </p>
                    <div class="hero-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['total_stocks']; ?></span>
                            <span class="stat-label">Articles en Stock</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo number_format($stats['total_depenses'], 0, ',', ' '); ?>€</span>
                            <span class="stat-label">Dépenses Totales</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $stats['total_users']; ?></span>
                            <span class="stat-label">Utilisateurs</span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dashboard avec modules -->
            <section class="dashboard-section">
                <h2 class="section-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Tableau de Bord
                </h2>
                
                <div class="modules-grid">
                    <!-- Module Stocks -->
                    <div class="module-card stocks-module" onclick="window.location.href='stocks.php'">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-boxes"></i>
                            </div>
                            <div class="module-status">
                                <?php if ($stats['stocks_faibles'] > 0): ?>
                                    <span class="status-badge warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <?php echo $stats['stocks_faibles']; ?> alertes
                                    </span>
                                <?php else: ?>
                                    <span class="status-badge success">
                                        <i class="fas fa-check-circle"></i>
                                        Tout va bien
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="module-content">
                            <h3>Gestion des Stocks</h3>
                            <p>Gérez votre inventaire, suivez les niveaux de stock et recevez des alertes automatiques.</p>
                            <div class="module-stats">
                                <div class="stat">
                                    <span class="stat-value"><?php echo $stats['total_stocks']; ?></span>
                                    <span class="stat-label">Articles</span>
                                </div>
                                <div class="stat">
                                    <span class="stat-value"><?php echo $stats['stocks_faibles']; ?></span>
                                    <span class="stat-label">Alertes</span>
                                </div>
                            </div>
                        </div>
                        <div class="module-footer">
                            <span class="module-link">
                                Accéder au module
                                <i class="fas fa-arrow-right"></i>
                            </span>
                        </div>
                    </div>
                    <!-- Autres modules à venir -->
                    <div class="module-card coming-soon" onclick="showComingSoon('Gestion Financière')">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-euro-sign"></i>
                            </div>
                            <div class="module-status">
                                <span class="status-badge info">Bientôt</span>
                            </div>
                        </div>
                        <div class="module-content">
                            <h3>Gestion Financière</h3>
                            <p>Suivez les dépenses, gérez les budgets et analysez les coûts.</p>
                        </div>
                    </div>

                    <div class="module-card coming-soon" onclick="showComingSoon('Gestion des Élèves')">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="module-status">
                                <span class="status-badge info">Bientôt</span>
                            </div>
                        </div>
                        <div class="module-content">
                            <h3>Gestion des Élèves</h3>
                            <p>Inscriptions, dossiers scolaires et suivi des élèves.</p>
                        </div>
                    </div>

                    <div class="module-card coming-soon" onclick="showComingSoon('Gestion des Enseignants')">
                        <div class="module-header">
                            <div class="module-icon">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div class="module-status">
                                <span class="status-badge info">Bientôt</span>
                            </div>
                        </div>
                        <div class="module-content">
                            <h3>Gestion des Enseignants</h3>
                            <p>Profils enseignants, affectations et planning.</p>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </main>

    <!-- Alerte de connexion DB -->
    <?php if (!empty($db_error)): ?>
        <div style="max-width:1200px;margin:16px auto;padding:12px 16px;background:#fff3cd;color:#856404;border:1px solid #ffeeba;border-radius:6px;">
            <strong>Attention:</strong> <?php echo htmlspecialchars($db_error); ?>
        </div>
    <?php endif; ?>

    <!-- Modal "Bientôt disponible" -->
    <div id="comingSoonModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2><i class="fas fa-rocket"></i> Bientôt Disponible</h2>
                <span class="close" onclick="closeComingSoonModal()">&times;</span>
            </div>
            <div class="modal-body">
                <div class="coming-soon-content">
                    <div class="coming-soon-icon">
                        <i class="fas fa-tools"></i>
                    </div>
                    <h3 id="comingSoonTitle">Module en Développement</h3>
                    <p>Ce module est actuellement en cours de développement.</p>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-primary" onclick="closeComingSoonModal()">Compris</button>
            </div>
        </div>
    </div>

    <script>
        function toggleMobileMenu() {
            const nav = document.getElementById('mainNav');
            nav.classList.toggle('active');
        }

        function showComingSoon(moduleName) {
            document.getElementById('comingSoonTitle').textContent = moduleName;
            document.getElementById('comingSoonModal').style.display = 'block';
        }

        function closeComingSoonModal() {
            document.getElementById('comingSoonModal').style.display = 'none';
        }

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('comingSoonModal');
            if (event.target === modal) {
                closeComingSoonModal();
            }
        });
    </script>
</body>
</html>
