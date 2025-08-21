<?php
/**
 * SCOLARIA - Dashboard Moderne
 * Utilisation du nouveau design system avec sidebar
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/components/stats-card.php';
require_once __DIR__ . '/components/data-table.php';

// Sécurité session + contrôle d'accès (repris de dashboard.php)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['username']) || empty($_SESSION['role'])) {
    if (isset($_GET['action'])) {
        header('Content-Type: application/json; charset=utf-8', true, 401);
        echo json_encode(['error' => 'unauthorized']);
        exit;
    }
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$username = (string) $_SESSION['username'];
$role = (string) $_SESSION['role'];

// Endpoints JSON (ex: dépenses mensuelles)
if (isset($_GET['action']) && $_GET['action'] === 'expenses_json') {
    $pdo = Database::getConnection();
    try {
        $sql = 'SELECT YEAR(`date`) AS y, MONTH(`date`) AS m, SUM(montant) AS total
                FROM depenses
                GROUP BY y, m
                ORDER BY y, m';
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll() ?: [];
        $labels = [];
        $data = [];
        $totalCumul = 0.0;
        foreach ($rows as $r) {
            $label = sprintf('%04d-%02d', (int) $r['y'], (int) $r['m']);
            $labels[] = $label;
            $val = (float) $r['total'];
            $data[] = $val;
            $totalCumul += $val;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'labels' => $labels,
            'data' => $data,
            'total' => $totalCumul,
        ]);
        exit;
    } catch (Throwable $e) {
        header('Content-Type: application/json; charset=utf-8', true, 500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }
}

// Configuration de la page
$currentPage = 'dashboard';
$pageTitle = 'Tableau de bord';
$additionalCSS = [];
$additionalJS = [];

// Capacités role
$canManageStocks = in_array($role, ['admin', 'gestionnaire'], true);
$canViewAlerts = in_array($role, ['admin', 'gestionnaire'], true);
$canViewCosts = in_array($role, ['admin'], true);

// Données de démonstration / DB pour les statistiques
$dashboardStats = [
    [
        'title' => 'Articles en stock',
        'value' => 1247,
        'icon' => 'fas fa-boxes',
        'type' => 'primary',
        'change' => '+12.5%',
        'changeType' => 'positive',
        'subtitle' => 'Articles disponibles',
        'link' => '/stocks.php'
    ],
    [
        'title' => 'Alertes actives',
        'value' => 8,
        'icon' => 'fas fa-exclamation-triangle',
        'type' => 'warning',
        'change' => '-25%',
        'changeType' => 'positive',
        'subtitle' => 'Nécessitent une attention',
        'link' => '/alerts.php'
    ],
    [
        'title' => 'Budget mensuel',
        'value' => '€24,580',
        'icon' => 'fas fa-euro-sign',
        'type' => 'success',
        'change' => '+8.2%',
        'changeType' => 'negative',
        'subtitle' => 'Dépenses ce mois-ci',
        'link' => '/finances.php'
    ],
    [
        'title' => 'Utilisateurs actifs',
        'value' => 156,
        'icon' => 'fas fa-users',
        'type' => 'primary',
        'change' => '+5.1%',
        'changeType' => 'positive',
        'subtitle' => 'Connectés cette semaine',
        'link' => '/users.php'
    ]
];

// Données pour le tableau des dernières activités (exemple)
$recentActivities = [
    [
        'id' => 1,
        'user' => 'Marie Dubois',
        'action' => 'Ajout de stock',
        'item' => 'Cahiers A4',
        'quantity' => 50,
        'date' => '2024-01-15 14:30:00',
        'status' => 'Terminé'
    ],
    [
        'id' => 2,
        'user' => 'Jean Martin',
        'action' => 'Commande',
        'item' => 'Stylos bleus',
        'quantity' => 100,
        'date' => '2024-01-15 11:15:00',
        'status' => 'En cours'
    ],
    [
        'id' => 3,
        'user' => 'Sophie Laurent',
        'action' => 'Sortie de stock',
        'item' => 'Papier A3',
        'quantity' => 25,
        'date' => '2024-01-15 09:45:00',
        'status' => 'Terminé'
    ],
    [
        'id' => 4,
        'user' => 'Pierre Durand',
        'action' => 'Inventaire',
        'item' => 'Classeurs',
        'quantity' => 200,
        'date' => '2024-01-14 16:20:00',
        'status' => 'Terminé'
    ]
];

// Configuration du tableau
$tableConfig = [
    'title' => 'Activités récentes',
    'subtitle' => 'Dernières actions effectuées dans le système',
    'id' => 'recentActivitiesTable',
    'search' => true,
    'export' => true,
    'columns' => [
        [
            'key' => 'user',
            'label' => 'Utilisateur',
            'sortable' => true,
            'type' => 'text'
        ],
        [
            'key' => 'action',
            'label' => 'Action',
            'sortable' => true,
            'type' => 'text'
        ],
        [
            'key' => 'item',
            'label' => 'Article',
            'sortable' => true,
            'type' => 'link',
            'linkUrl' => '/stocks/view/{id}'
        ],
        [
            'key' => 'quantity',
            'label' => 'Quantité',
            'sortable' => true,
            'type' => 'text',
            'class' => 'text-center'
        ],
        [
            'key' => 'date',
            'label' => 'Date',
            'sortable' => true,
            'type' => 'datetime'
        ],
        [
            'key' => 'status',
            'label' => 'Statut',
            'type' => 'badge',
            'badgeClass' => [
                'Terminé' => 'success',
                'En cours' => 'warning',
                'Annulé' => 'error'
            ]
        ]
    ],
    'actions' => [
        [
            'icon' => 'fas fa-eye',
            'class' => 'view',
            'title' => 'Voir les détails',
            'url' => '/activity/view/{id}'
        ],
        [
            'icon' => 'fas fa-edit',
            'class' => 'edit',
            'title' => 'Modifier',
            'url' => '/activity/edit/{id}'
        ]
    ],
    'data' => $recentActivities
];

// Données pour les graphiques (fallback si endpoint indisponible)
$monthlyExpenses = [
    ['month' => 'Jan', 'amount' => 15420],
    ['month' => 'Fév', 'amount' => 18350],
    ['month' => 'Mar', 'amount' => 22100],
    ['month' => 'Avr', 'amount' => 19800],
    ['month' => 'Mai', 'amount' => 24580]
];

$stockCategories = [
    ['category' => 'Papeterie', 'count' => 450, 'color' => '#1E88E5'],
    ['category' => 'Informatique', 'count' => 120, 'color' => '#43A047'],
    ['category' => 'Mobilier', 'count' => 85, 'color' => '#FF6B35'],
    ['category' => 'Nettoyage', 'count' => 200, 'color' => '#FFA726']
];

// Charger données DB réelles pour tuiles si disponible
try {
    $pdo = Database::getConnection();
    $row = $pdo->query('SELECT COUNT(*) AS c FROM stocks')->fetch();
    $totalArticlesCount = (int) ($row['c'] ?? 0);

    $sqlUnder = 'SELECT id, nom_article, categorie, quantite, seuil FROM stocks WHERE quantite <= seuil ORDER BY quantite ASC, nom_article ASC LIMIT 10';
    $underThreshold = $pdo->query($sqlUnder)->fetchAll() ?: [];

    // Ajuster stats en fonction des données réelles
    $dashboardStats[0]['value'] = $totalArticlesCount;
    $dashboardStats[1]['value'] = count($underThreshold);

    $row = $pdo->query('SELECT SUM(montant) AS t FROM depenses')->fetch();
    $totalExpenses = (float) ($row['t'] ?? 0);
    $dashboardStats[2]['value'] = '€' . number_format($totalExpenses, 0, ',', ' ');
} catch (Throwable $e) {
    // Silent fallback to demo data
}

// Début du contenu HTML
ob_start();
?>

<!-- Cartes de statistiques -->
<?php renderStatsGrid($dashboardStats); ?>

<!-- Graphiques -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: var(--spacing-xl); margin-bottom: var(--spacing-xl);">
    <!-- Graphique des dépenses -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Évolution des dépenses</h3>
            <p class="card-subtitle">Dépenses mensuelles sur les 5 derniers mois</p>
        </div>
        <div class="card-body">
            <canvas id="expensesChart" height="300"></canvas>
        </div>
    </div>
    
    <!-- Répartition des stocks -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Répartition des stocks</h3>
            <p class="card-subtitle">Par catégorie d'articles</p>
        </div>
        <div class="card-body">
            <canvas id="stockChart" height="300"></canvas>
        </div>
    </div>
</div>
<!-- Alertes importantes -->
<div class="card" style="margin-bottom: var(--spacing-xl);">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-exclamation-triangle" style="color: var(--warning-color);"></i>
            Alertes importantes
        </h3>
    </div>
    <div class="card-body">
        <div class="alert alert-warning">
            <i class="alert-icon fas fa-exclamation-triangle"></i>
            <div>
                <strong>Stock faible :</strong> Cahiers A4 (Quantité : 12)
                <br><small>Seuil d'alerte : 20 unités</small>
            </div>
            <a href="stocks.php" class="btn btn-sm btn-warning">Réapprovisionner</a>
        </div>
        
        <div class="alert alert-danger">
            <i class="alert-icon fas fa-times-circle"></i>
            <div>
                <strong>Rupture de stock :</strong> Stylos rouges
                <br><small>Dernière sortie : il y a 2 jours</small>
            </div>
            <a href="alerts.php" class="btn btn-sm btn-primary">Commande urgente</a>
        </div>
        
        <div class="alert alert-info">
            <i class="alert-icon fas fa-info-circle"></i>
            <div>
                <strong>Commande en attente :</strong> Papier A3 (100 unités)
                <br><small>Livraison prévue : 18/01/2024</small>
            </div>
            <a href="alerts.php" class="btn btn-sm btn-outline">Suivre</a>
        </div>
    </div>
</div>
<!-- Tableau des activités récentes -->
<?php renderDataTable($tableConfig); ?>

<!-- Actions rapides -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Actions rapides</h3>
        <p class="card-subtitle">Raccourcis vers les fonctions principales</p>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: var(--spacing-lg);">
            <a href="stocks.php" class="card" style="text-decoration: none; color: inherit; padding: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 50px; height: 50px; background: var(--primary-color); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-size: var(--font-size-lg);">
                    <i class="fas fa-plus"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-lg);">Ajouter un article</h4>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">Ajouter un nouvel article au stock</p>
                </div>
            </a>
            
            <a href="alerts.php" class="card" style="text-decoration: none; color: inherit; padding: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 50px; height: 50px; background: var(--secondary-color); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-size: var(--font-size-lg);">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-lg);">Nouvelle commande</h4>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">Créer une commande fournisseur</p>
                </div>
            </a>
            
            <a href="stocks.php" class="card" style="text-decoration: none; color: inherit; padding: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 50px; height: 50px; background: var(--warning-color); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-size: var(--font-size-lg);">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-lg);">Inventaire</h4>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">Lancer un inventaire</p>
                </div>
            </a>
            
            <a href="dashboard.php" class="card" style="text-decoration: none; color: inherit; padding: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 50px; height: 50px; background: var(--info-color); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-size: var(--font-size-lg);">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-lg);">Générer un rapport</h4>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">Créer un rapport personnalisé</p>
                </div>
            </a>
        </div>
    </div>
</div>

<!-- Responsive pour les graphiques -->
<style>
@media (max-width: 768px) {
    div[style*="grid-template-columns: 2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>

<!-- JavaScript pour les graphiques -->
<script>
function pageInit() {
    // Configuration globale pour Chart.js
    Chart.defaults.font.family = 'Poppins';
    Chart.defaults.color = getComputedStyle(document.documentElement).getPropertyValue('--text-secondary');
    
    // Graphique des dépenses
    const expensesCtx = document.getElementById('expensesChart');
    if (expensesCtx) {
        new Chart(expensesCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($monthlyExpenses, 'month')) ?>,
                datasets: [{
                    label: 'Dépenses (€)',
                    data: <?= json_encode(array_column($monthlyExpenses, 'amount')) ?>,
                    borderColor: '#1E88E5',
                    backgroundColor: 'rgba(30, 136, 229, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#1E88E5',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        },
                        ticks: {
                            callback: function(value) {
                                return '€' + value.toLocaleString();
                            }
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }
    
    // Graphique en donut pour les stocks
    const stockCtx = document.getElementById('stockChart');
    if (stockCtx) {
        new Chart(stockCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($stockCategories, 'category')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($stockCategories, 'count')) ?>,
                    backgroundColor: <?= json_encode(array_column($stockCategories, 'color')) ?>,
                    borderWidth: 0,
                    cutout: '60%'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });
    }
}
</script>

<?php
$content = ob_get_clean();

// Inclure le layout de base
include __DIR__ . '/layout/base.php';
?>
