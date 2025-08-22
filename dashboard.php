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
        $totalCumul = 4746.15;
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

// Endpoint JSON: ventes mensuelles
if (isset($_GET['action']) && $_GET['action'] === 'sales_monthly_json') {
    try {
        $pdo = Database::getConnection();
        $sql = 'SELECT MONTH(date_vente) AS mois, SUM(total) AS montant
                FROM ventes
                WHERE YEAR(date_vente) = YEAR(CURDATE())
                GROUP BY mois
                ORDER BY mois';
        $stmt = $pdo->query($sql);
        $rows = $stmt->fetchAll() ?: [];

        $labels = [];
        $data = [];
        $moisNoms = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',7=>'Juil',8=>'Août',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];
        foreach ($rows as $r) {
            $m = (int) ($r['mois'] ?? 0);
            $labels[] = $moisNoms[$m] ?? (string) $m;
            $data[] = (float) ($r['montant'] ?? 0);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'labels' => $labels,
            'data' => $data,
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
        'value' => 13,
        'icon' => 'fas fa-boxes',
        'type' => 'primary',
        'change' => '+12.5%',
        'changeType' => 'positive',
        'subtitle' => 'Articles disponibles',
        'link' => '/stocks.php'
    ],
    [
        'title' => 'Alertes actives',
        'value' => 13,
        'icon' => 'fas fa-exclamation-triangle',
        'type' => 'warning',
        'change' => '-25%',
        'changeType' => 'positive',
        'subtitle' => 'Nécessitent une attention',
        'link' => '/alerts.php'
    ],
    [
        'title' => 'Budget mensuel',
        'value' => '€4 746',
        'icon' => 'fas fa-euro-sign',
        'type' => 'success',
        'change' => '+8.2%',
        'changeType' => 'negative',
        'subtitle' => 'Dépenses ce mois-ci',
        'link' => '/finances.php'
    ],
    [
        'title' => 'Utilisateurs',
        'value' => 13,
        'icon' => 'fas fa-users',
        'type' => 'primary',
        'change' => '+5.1%',
        'changeType' => 'positive',
        'subtitle' => 'Utilisateurs enregistrés',
        'link' => '/users.php'
    ]
];

// Données pour le tableau des dernières activités (DB)
$recentActivities = [];

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
                'ajout' => 'success',
                'modification' => 'warning',
                'suppression' => 'error'
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

// Données pour les graphiques (alimentées par la DB)
$monthlyExpenses = [];

$stockCategories = [];

// Ventes par mois (rempli via DB ci-dessous)
$salesByMonth = [];

// Top articles vendus
$topSellingProducts = [];

// Ventes du jour (fallback)
$todaySalesCount = 0;
$todaySalesAmount = 0;
$recentSales = [];

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
    // Activités récentes depuis mouvements
    try {
        $sqlMov = 'SELECT m.id, m.utilisateur AS user, m.action, m.date_mouvement AS date, s.nom_article AS item
                   FROM mouvements m LEFT JOIN stocks s ON m.article_id = s.id
                   ORDER BY m.date_mouvement DESC, m.id DESC
                   LIMIT 10';
        $rows = $pdo->query($sqlMov)->fetchAll() ?: [];
        $recentActivities = array_map(function($r){
            return [
                'id' => (int)($r['id'] ?? 0),
                'user' => (string)($r['user'] ?? ''),
                'action' => (string)($r['action'] ?? ''),
                'item' => (string)($r['item'] ?? ''),
                'quantity' => '',
                'date' => (string)($r['date'] ?? ''),
                'status' => (string)($r['action'] ?? '')
            ];
        }, $rows);
        $tableConfig['data'] = $recentActivities;
    } catch (Throwable $ignore) {}

    // Utilisateurs (total)
    try {
        $row = $pdo->query('SELECT COUNT(*) AS c FROM users')->fetch();
        $dashboardStats[3]['value'] = (int) ($row['c'] ?? 0);
        $dashboardStats[3]['subtitle'] = 'Utilisateurs enregistrés';
    } catch (Throwable $ignore) {}

    // Ventes du jour: nombre + montant (depuis sales)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) AS n, COALESCE(SUM(total),0) AS t FROM sales WHERE DATE(created_at) = CURDATE()");
        $r = $stmt->fetch() ?: [];
        $todaySalesCount = (int) ($r['n'] ?? 0);
        $todaySalesAmount = (float) ($r['t'] ?? 0);
    } catch (Throwable $ignore) {
        // table sales peut ne pas exister
    }

    // Ventes par mois (année courante) depuis sales
    try {
        $sqlSales = 'SELECT MONTH(created_at) AS m, SUM(total) AS montant
                     FROM sales
                     WHERE YEAR(created_at) = YEAR(CURDATE())
                     GROUP BY m ORDER BY m';
        $rows = $pdo->query($sqlSales)->fetchAll() ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['m']] = (float) ($r['montant'] ?? 0);
        }
        $moisNoms = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',7=>'Juil',8=>'Août',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];
        $salesByMonth = [];
        for ($i = 1; $i <= 12; $i++) {
            $salesByMonth[] = ['month' => $moisNoms[$i], 'amount' => (float) ($map[$i] ?? 0)];
        }
    } catch (Throwable $ignore) {
        // fallback déjà défini
    }

    // Articles les plus vendus (agrégé à partir de sales_items)
    try {
        $sqlTop = 'SELECT st.nom_article, SUM(si.quantity) AS total_vendu
                   FROM sales_items si
                   JOIN stocks st ON st.id = si.product_id
                   GROUP BY si.product_id, st.nom_article
                   ORDER BY total_vendu DESC
                   LIMIT 5';
        $topSellingProducts = $pdo->query($sqlTop)->fetchAll() ?: $topSellingProducts;
    } catch (Throwable $ignore) {
        // fallback déjà défini
    }

    // Dernières ventes (aujourd'hui)
    try {
        $q = $pdo->query("SELECT sa.id, sa.total, sa.created_at, COALESCE(CONCAT(c.last_name, ' ', c.first_name), 'Client par défaut') AS client_name, (
            SELECT t.payment_method FROM transactions t WHERE t.sale_id = sa.id ORDER BY t.paid_at ASC LIMIT 1
        ) AS payment_method
        FROM sales sa
        LEFT JOIN clients c ON c.id = sa.client_id
        WHERE DATE(sa.created_at) = CURDATE()
        ORDER BY sa.created_at DESC
        LIMIT 10");
        $recentSales = $q->fetchAll() ?: [];
    } catch (Throwable $ignore) {}
} catch (Throwable $e) {
    // Silent fallback to demo data
}

// Dépenses mensuelles (année courante) pour le graphique
try {
    $pdo = Database::getConnection();
    $rows = $pdo->query("SELECT MONTH(`date`) AS m, SUM(montant) AS total FROM depenses GROUP BY m ORDER BY m")->fetchAll() ?: [];
    $moisNoms = [1=>'Jan',2=>'Fév',3=>'Mar',4=>'Avr',5=>'Mai',6=>'Juin',7=>'Juil',8=>'Août',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Déc'];
    $map = [];
    foreach ($rows as $r) { $map[(int)$r['m']] = (float) ($r['total'] ?? 0); }
    $monthlyExpenses = [];
    for ($i=1;$i<=12;$i++) { $monthlyExpenses[] = ['month'=>$moisNoms[$i], 'amount'=>(float)($map[$i] ?? 0)]; }
} catch (Throwable $ignore) { $monthlyExpenses = []; }

// Répartition des stocks par catégorie
try {
    $pdo = Database::getConnection();
    $rows = $pdo->query("SELECT COALESCE(categorie,'Autre') AS category, COUNT(*) AS cnt FROM stocks GROUP BY category ORDER BY cnt DESC")->fetchAll() ?: [];
    $palette = ['#1E88E5','#43A047','#FF6B35','#FFA726','#8E44AD','#00ACC1','#D81B60','#7CB342'];
    $stockCategories = [];
    $idx = 0;
    foreach ($rows as $r) {
        $stockCategories[] = [
            'category' => (string)$r['category'],
            'count' => (int)$r['cnt'],
            'color' => $palette[$idx % count($palette)]
        ];
        $idx++;
    }
} catch (Throwable $ignore) { $stockCategories = []; }

// Ajouter la tuile "Ventes du jour"
$dashboardStats[] = [
    'title' => 'Ventes du jour',
    'value' => number_format($todaySalesCount, 0, ',', ' '),
    'icon' => 'fas fa-cash-register',
    'type' => 'success',
    'change' => '',
    'changeType' => 'positive',
    'subtitle' => 'Montant: €' . number_format($todaySalesAmount, 2, ',', ' '),
    'link' => '/pos.php'
];

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

<!-- Graphique des ventes mensuelles -->
<div class="card" style="margin-bottom: var(--spacing-xl);">
    <div class="card-header">
        <h3 class="card-title">Ventes par mois</h3>
        <p class="card-subtitle">Année en cours</p>
    </div>
    <div class="card-body">
        <canvas id="salesChart" height="320"></canvas>
    </div>
</div>

<!-- Articles les plus vendus -->
<div class="card" style="margin-bottom: var(--spacing-xl);">
    <div class="card-header">
        <h3 class="card-title">Articles les plus vendus</h3>
        <p class="card-subtitle">Top 5 par quantité vendue</p>
    </div>
    <div class="card-body">
        <div style="display: grid; grid-template-columns: 1fr; gap: var(--spacing-sm);">
            <?php if (!empty($topSellingProducts)): ?>
                <?php foreach ($topSellingProducts as $p): ?>
                    <div class="list-item" style="display:flex; align-items:center; justify-content:space-between; padding: var(--spacing-sm) var(--spacing-md); border:1px solid var(--border-color); border-radius: var(--radius-md);">
                        <div style="display:flex; align-items:center; gap: var(--spacing-sm);">
                            <i class="fas fa-box"></i>
                            <span><?= htmlspecialchars((string)($p['nom_article'] ?? '')) ?></span>
                        </div>
                        <span class="badge badge-primary">x<?= (int)($p['total_vendu'] ?? 0) ?></span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted">Aucune vente enregistrée.</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Dernières ventes (aujourd'hui) -->
<div class="card" style="margin-bottom: var(--spacing-xl);">
    <div class="card-header">
        <h3 class="card-title">Dernières ventes (aujourd'hui)</h3>
        <p class="card-subtitle">Mises à jour en temps réel</p>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Ticket</th><th>Client</th><th>Mode de paiement</th><th>Total</th><th>Date</th></tr></thead>
                <tbody>
                    <?php if (!empty($recentSales)): foreach ($recentSales as $r): ?>
                    <tr>
                        <td>#<?php echo (int)($r['id'] ?? 0); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['client_name'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['payment_method'] ?? '-')); ?></td>
                        <td><?php echo number_format((float)($r['total'] ?? 0), 2, ',', ' '); ?> €</td>
                        <td><?php echo htmlspecialchars((string)($r['created_at'] ?? '')); ?></td>
                    </tr>
                    <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-muted">Aucune vente aujourd'hui.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
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

            <a href="pos.php" class="card" style="text-decoration: none; color: inherit; padding: var(--spacing-lg); display: flex; align-items: center; gap: var(--spacing-md);">
                <div style="width: 50px; height: 50px; background: var(--success-color); border-radius: var(--radius-lg); display: flex; align-items: center; justify-content: center; color: white; font-size: var(--font-size-lg);">
                    <i class="fas fa-cash-register"></i>
                </div>
                <div>
                    <h4 style="margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-lg);">Ouvrir POS</h4>
                    <p style="margin: 0; color: var(--text-muted); font-size: var(--font-size-sm);">Accéder au point de vente</p>
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

    // Graphique des ventes par mois
    const salesCtx = document.getElementById('salesChart');
    if (salesCtx) {
        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($salesByMonth, 'month')) ?>,
                datasets: [{
                    label: 'Ventes (€)',
                    data: <?= json_encode(array_map(fn($r) => (float)$r['amount'], $salesByMonth)) ?>,
                    backgroundColor: 'rgba(16, 185, 129, 0.2)',
                    borderColor: '#10B981',
                    borderWidth: 2,
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0, 0, 0, 0.05)' },
                        ticks: {
                            callback: function(value) { return '€' + value.toLocaleString(); }
                        }
                    },
                    x: { grid: { display: false } }
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
