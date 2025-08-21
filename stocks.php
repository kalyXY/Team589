<?php
/**
 * Module Gestion des Stocks - Scolaria (Team589)
 * Application de gestion de logistique scolaire avec nouveau design
 */

session_start();

// Simulation de session utilisateur si nécessaire
if (!isset($_SESSION['username'])) {
    $_SESSION['username'] = 'Admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['user_id'] = 1;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/components/stats-card.php';
require_once __DIR__ . '/components/data-table.php';

// Configuration de la page
$currentPage = 'stocks';
$pageTitle = 'Gestion des Stocks';
$additionalCSS = [];
$additionalJS = ['assets/js/stocks.js'];

// Configuration de la base de données (fallback si config non disponible)
class StockDatabase {
    private $host = "localhost";
    private $db_name = "scolaria";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
        // Essayer d'utiliser la classe Database globale d'abord
        if (class_exists('Database')) {
            try {
                return Database::getConnection();
            } catch (Exception $e) {
                // Fallback vers la connexion locale
            }
        }
        
        $this->conn = null;
        try {
            $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
        } catch(PDOException $exception) {
            echo "Erreur de connexion : " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Classe pour la gestion des stocks
class StockManager {
    private $conn;
    private $table_stocks = "stocks";
    private $table_mouvements = "mouvements";

    public function __construct($db) {
        $this->conn = $db;
    }

    // Lister tous les articles
    public function listStocks($search = '', $category_filter = '', $low_stock_only = false) {
        $query = "SELECT * FROM " . $this->table_stocks . " WHERE 1=1";
        $params = array();

        if (!empty($search)) {
            $query .= " AND (nom_article LIKE :search OR categorie LIKE :search)";
            $params[':search'] = '%' . $search . '%';
        }

        if (!empty($category_filter)) {
            $query .= " AND categorie = :category";
            $params[':category'] = $category_filter;
        }

        if ($low_stock_only) {
            $query .= " AND quantite <= seuil";
        }

        $query .= " ORDER BY created_at DESC";

        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt;
    }

    // Ajouter un article
    public function addStock($nom, $categorie, $quantite, $seuil, $prix_achat, $prix_vente) {
        $query = "INSERT INTO " . $this->table_stocks . " (nom_article, categorie, quantite, seuil, prix_achat, prix_vente) VALUES (:nom, :categorie, :quantite, :seuil, :prix_achat, :prix_vente)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nom", $nom);
        $stmt->bindParam(":categorie", $categorie);
        $stmt->bindParam(":quantite", $quantite);
        $stmt->bindParam(":seuil", $seuil);
        $stmt->bindParam(":prix_achat", $prix_achat);
        $stmt->bindParam(":prix_vente", $prix_vente);

        if ($stmt->execute()) {
            $article_id = $this->conn->lastInsertId();
            $this->logMovement($article_id, 'ajout', "Ajout de $quantite $nom", 'admin');
            return true;
        }
        return false;
    }

    // Modifier un article
    public function updateStock($id, $nom, $categorie, $quantite, $seuil, $prix_achat, $prix_vente) {
        // Récupérer l'ancien état pour l'historique
        $old_query = "SELECT * FROM " . $this->table_stocks . " WHERE id = :id";
        $old_stmt = $this->conn->prepare($old_query);
        $old_stmt->bindParam(":id", $id);
        $old_stmt->execute();
        $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);

        $query = "UPDATE " . $this->table_stocks . " SET nom_article = :nom, categorie = :categorie, quantite = :quantite, seuil = :seuil, prix_achat = :prix_achat, prix_vente = :prix_vente WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":nom", $nom);
        $stmt->bindParam(":categorie", $categorie);
        $stmt->bindParam(":quantite", $quantite);
        $stmt->bindParam(":seuil", $seuil);
        $stmt->bindParam(":prix_achat", $prix_achat);
        $stmt->bindParam(":prix_vente", $prix_vente);

        if ($stmt->execute()) {
            $details = "Modification de $nom - Quantité: {$old_data['quantite']} → $quantite, Seuil: {$old_data['seuil']} → $seuil, Prix achat: " . ($old_data['prix_achat'] ?? '0') . " → $prix_achat, Prix vente: " . ($old_data['prix_vente'] ?? '0') . " → $prix_vente";
            $this->logMovement($id, 'modification', $details, 'admin');
            return true;
        }
        return false;
    }

    // Supprimer un article
    public function deleteStock($id) {
        // Récupérer les infos de l'article avant suppression
        $info_query = "SELECT nom_article, quantite FROM " . $this->table_stocks . " WHERE id = :id";
        $info_stmt = $this->conn->prepare($info_query);
        $info_stmt->bindParam(":id", $id);
        $info_stmt->execute();
        $article_info = $info_stmt->fetch(PDO::FETCH_ASSOC);

        if ($article_info) {
            $this->logMovement($id, 'suppression', "Suppression de l'article: {$article_info['nom_article']} (Quantité: {$article_info['quantite']})", 'admin');
        }

        $query = "DELETE FROM " . $this->table_stocks . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        return $stmt->execute();
    }

    // Obtenir un article par ID
    public function getStockById($id) {
        $query = "SELECT * FROM " . $this->table_stocks . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Enregistrer un mouvement dans l'historique
    public function logMovement($article_id, $action, $details, $utilisateur) {
        $query = "INSERT INTO " . $this->table_mouvements . " (article_id, action, details, utilisateur) VALUES (:article_id, :action, :details, :utilisateur)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":article_id", $article_id);
        $stmt->bindParam(":action", $action);
        $stmt->bindParam(":details", $details);
        $stmt->bindParam(":utilisateur", $utilisateur);
        
        return $stmt->execute();
    }

    // Obtenir l'historique des mouvements
    public function getMovements($limit = 50) {
        $query = "SELECT m.*, s.nom_article FROM " . $this->table_mouvements . " m 
                  LEFT JOIN " . $this->table_stocks . " s ON m.article_id = s.id 
                  ORDER BY m.date_mouvement DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":limit", $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    // Obtenir les catégories distinctes
    public function getCategories() {
        $query = "SELECT DISTINCT categorie FROM " . $this->table_stocks . " ORDER BY categorie";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
    
    // Compter le nombre total de mouvements
    public function countMovements() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_mouvements;
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
}

// Traitement des requêtes AJAX et POST
$database = new StockDatabase();
$db = $database->getConnection();
$stockManager = new StockManager($db);

$message = '';
$message_type = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $nom = trim($_POST['nom_article']);
                $categorie = trim($_POST['categorie']);
                $quantite = intval($_POST['quantite']);
                $seuil = intval($_POST['seuil']);
                $prix_achat = isset($_POST['prix_achat']) ? (float) $_POST['prix_achat'] : 0.0;
                $prix_vente = isset($_POST['prix_vente']) ? (float) $_POST['prix_vente'] : 0.0;
                
                if ($stockManager->addStock($nom, $categorie, $quantite, $seuil, $prix_achat, $prix_vente)) {
                    $message = "Article ajouté avec succès !";
                    $message_type = "success";
                } else {
                    $message = "Erreur lors de l'ajout de l'article.";
                    $message_type = "error";
                }
                break;

            case 'update':
                $id = intval($_POST['id']);
                $nom = trim($_POST['nom_article']);
                $categorie = trim($_POST['categorie']);
                $quantite = intval($_POST['quantite']);
                $seuil = intval($_POST['seuil']);
                $prix_achat = isset($_POST['prix_achat']) ? (float) $_POST['prix_achat'] : 0.0;
                $prix_vente = isset($_POST['prix_vente']) ? (float) $_POST['prix_vente'] : 0.0;
                
                if ($stockManager->updateStock($id, $nom, $categorie, $quantite, $seuil, $prix_achat, $prix_vente)) {
                    $message = "Article modifié avec succès !";
                    $message_type = "success";
                } else {
                    $message = "Erreur lors de la modification de l'article.";
                    $message_type = "error";
                }
                break;

            case 'delete':
                $id = intval($_POST['id']);
                if ($stockManager->deleteStock($id)) {
                    $message = "Article supprimé avec succès !";
                    $message_type = "success";
                } else {
                    $message = "Erreur lors de la suppression de l'article.";
                    $message_type = "error";
                }
                break;
        }
    }
}

// Traitement des requêtes AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'get_stock':
            $id = intval($_GET['id']);
            $stock = $stockManager->getStockById($id);
            echo json_encode($stock);
            exit;
            
        case 'search':
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $low_stock = isset($_GET['low_stock']) && $_GET['low_stock'] == '1';
            
            $stocks = $stockManager->listStocks($search, $category, $low_stock);
            $results = [];
            while ($row = $stocks->fetch(PDO::FETCH_ASSOC)) {
                $results[] = $row;
            }
            echo json_encode($results);
            exit;
            
        case 'count_movements':
            $count = $stockManager->countMovements();
            echo json_encode(['count' => $count]);
            exit;
    }
}

// Récupération des données pour l'affichage
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$low_stock_only = isset($_GET['low_stock']);

$stocks = $stockManager->listStocks($search, $category_filter, $low_stock_only);
$categories = $stockManager->getCategories();
$movements = $stockManager->getMovements(20);
// Début du contenu HTML
ob_start();
?>

<!-- Messages de notification -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?>">
        <i class="alert-icon fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <div><?php echo $message; ?></div>
    </div>
<?php endif; ?>

<!-- Statistiques -->
<?php
try {
    $totalArticles = (int)($db->query('SELECT COUNT(*) AS c FROM stocks')->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    $lowStockCount = (int)($db->query('SELECT COUNT(*) AS c FROM stocks WHERE quantite <= seuil')->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    $categoryCount = (int)($db->query('SELECT COUNT(DISTINCT categorie) AS c FROM stocks')->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
    $movementsCount = (int)($db->query('SELECT COUNT(*) AS c FROM mouvements')->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
} catch (Throwable $e) {
    $totalArticles = $totalArticles ?? 0;
    $lowStockCount = $lowStockCount ?? 0;
    $categoryCount = $categoryCount ?? 0;
    $movementsCount = $movementsCount ?? 0;
}

$stockStats = [
    [
        'title' => 'Total Articles',
        'value' => $totalArticles,
        'icon' => 'fas fa-boxes',
        'type' => 'primary',
        'subtitle' => 'Articles en stock'
    ],
    [
        'title' => 'Stocks Faibles',
        'value' => $lowStockCount,
        'icon' => 'fas fa-exclamation-triangle',
        'type' => 'warning',
        'subtitle' => 'Nécessitent attention'
    ],
    [
        'title' => 'Catégories',
        'value' => $categoryCount,
        'icon' => 'fas fa-tags',
        'type' => 'success',
        'subtitle' => 'Types d\'articles'
    ],
    [
        'title' => 'Mouvements',
        'value' => $movementsCount,
        'icon' => 'fas fa-history',
        'type' => 'info',
        'subtitle' => 'Activité récente'
    ]
];
renderStatsGrid($stockStats);
?>

<!-- Section des contrôles -->
<div class="card" style="margin-bottom: var(--spacing-lg);">
    <div class="card-header">
        <h3 class="card-title">Recherche et Filtres</h3>
    </div>
    <div class="card-body">
        <div style="display: flex; gap: var(--spacing-lg); align-items: end; flex-wrap: wrap;">
            <div class="form-group" style="flex: 1; min-width: 250px; margin-bottom: 0;">
                <label class="form-label">Rechercher</label>
                <input type="text" id="searchInput" class="form-control" placeholder="Rechercher par nom ou catégorie..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="form-group" style="min-width: 200px; margin-bottom: 0;">
                <label class="form-label">Catégorie</label>
                <select id="categoryFilter" class="form-control">
                    <option value="">Toutes les catégories</option>
                    <?php while ($cat = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                        <option value="<?php echo htmlspecialchars($cat['categorie']); ?>" 
                                <?php echo $category_filter == $cat['categorie'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['categorie']); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            
            <div class="form-group" style="margin-bottom: 0;">
                <label class="form-label" style="visibility: hidden;">Action</label>
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                        <input type="checkbox" id="lowStockFilter" <?php echo $low_stock_only ? 'checked' : ''; ?>>
                        <span>Stock faible uniquement</span>
                    </label>
                    <button class="btn btn-primary" onclick="openAddModal()">
                        <i class="fas fa-plus"></i> Ajouter un article
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Navigation par onglets -->
<div class="card">
    <div style="display: flex; border-bottom: 1px solid var(--border-color);">
        <button class="btn btn-ghost" id="stocksTab" onclick="showTab('stocks')" style="border-radius: 0; border-bottom: 3px solid var(--primary-color);">
            <i class="fas fa-list"></i> Liste des Articles
        </button>
        <button class="btn btn-ghost" id="historyTab" onclick="showTab('history')" style="border-radius: 0; border-bottom: 3px solid transparent;">
            <i class="fas fa-history"></i> Historique
        </button>
    </div>

    <!-- Onglet Liste des articles -->
    <div id="stocks-tab" class="tab-content" style="display: block; padding: var(--spacing-lg);">
        <?php
        // Préparer les données pour le tableau
        $stocksData = [];
        while ($row = $stocks->fetch(PDO::FETCH_ASSOC)) {
            $prixAchat = isset($row['prix_achat']) ? (float)$row['prix_achat'] : 0.0;
            $prixVente = isset($row['prix_vente']) ? (float)$row['prix_vente'] : 0.0;
            $marge = $prixVente - $prixAchat;
            $stocksData[] = [
                'id' => $row['id'],
                'nom_article' => $row['nom_article'],
                'categorie' => $row['categorie'],
                'quantite' => $row['quantite'],
                'seuil' => $row['seuil'],
                'prix_achat' => number_format($prixAchat, 2, ',', ' '),
                'prix_vente' => number_format($prixVente, 2, ',', ' '),
                'marge' => number_format($marge, 2, ',', ' '),
                'created_at' => $row['created_at'],
                'low_stock' => $row['quantite'] <= $row['seuil']
            ];
        }
        
        $stocksTableConfig = [
            'title' => 'Liste des Articles',
            'subtitle' => 'Gestion complète du stock',
            'id' => 'stocksTable',
            'search' => false, // Désactivé car on a notre propre recherche
            'export' => true,
            'columns' => [
                ['key' => 'id', 'label' => 'ID', 'sortable' => true, 'type' => 'text'],
                ['key' => 'nom_article', 'label' => 'Nom de l\'article', 'sortable' => true, 'type' => 'text'],
                ['key' => 'categorie', 'label' => 'Catégorie', 'sortable' => true, 'type' => 'text'],
                ['key' => 'quantite', 'label' => 'Quantité', 'sortable' => true, 'type' => 'number', 'class' => 'text-center'],
                ['key' => 'seuil', 'label' => 'Seuil', 'sortable' => true, 'type' => 'number', 'class' => 'text-center'],
                ['key' => 'prix_achat', 'label' => 'Prix achat (€)', 'sortable' => true, 'type' => 'text', 'class' => 'text-right'],
                ['key' => 'prix_vente', 'label' => 'Prix vente (€)', 'sortable' => true, 'type' => 'text', 'class' => 'text-right'],
                ['key' => 'marge', 'label' => 'Marge (€)', 'sortable' => true, 'type' => 'text', 'class' => 'text-right'],
                ['key' => 'created_at', 'label' => 'Date d\'ajout', 'sortable' => true, 'type' => 'datetime']
            ],
            'actions' => [
                [
                    'icon' => 'fas fa-edit',
                    'class' => 'btn-warning',
                    'title' => 'Modifier',
                    'url' => 'javascript:openEditModal({id})'
                ],
                [
                    'icon' => 'fas fa-trash',
                    'class' => 'btn-danger',
                    'title' => 'Supprimer',
                    'url' => 'javascript:confirmDelete({id}, \'{nom_article}\')'
                ]
            ],
            'data' => $stocksData
        ];
        
        renderDataTable($stocksTableConfig);
        ?>
    </div>

    <!-- Onglet Historique -->
    <div id="history-tab" class="tab-content" style="display: none; padding: var(--spacing-lg);">
        <?php
        // Préparer les données pour l'historique
        $historyData = [];
        while ($movement = $movements->fetch(PDO::FETCH_ASSOC)) {
            $historyData[] = [
                'date_mouvement' => $movement['date_mouvement'],
                'action' => $movement['action'],
                'nom_article' => $movement['nom_article'] ?? 'Article supprimé',
                'details' => $movement['details'],
                'utilisateur' => $movement['utilisateur']
            ];
        }
        
        $historyTableConfig = [
            'title' => 'Historique des Mouvements',
            'subtitle' => 'Suivi de toutes les actions effectuées',
            'id' => 'historyTable',
            'search' => true,
            'export' => true,
            'columns' => [
                ['key' => 'date_mouvement', 'label' => 'Date', 'sortable' => true, 'type' => 'datetime'],
                ['key' => 'action', 'label' => 'Action', 'sortable' => true, 'type' => 'badge', 'badgeClass' => [
                    'ajout' => 'success',
                    'modification' => 'warning',
                    'suppression' => 'danger'
                ]],
                ['key' => 'nom_article', 'label' => 'Article', 'sortable' => true, 'type' => 'text'],
                ['key' => 'details', 'label' => 'Détails', 'sortable' => false, 'type' => 'text'],
                ['key' => 'utilisateur', 'label' => 'Utilisateur', 'sortable' => true, 'type' => 'text']
            ],
            'data' => $historyData
        ];
        
        renderDataTable($historyTableConfig);
        ?>
    </div>
</div>

<!-- Modal pour ajouter/modifier un article -->
<div id="stockModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3 class="modal-title" id="modalTitle">
                <i class="fas fa-plus-circle"></i> Ajouter un article
            </h3>
            <button class="modal-close" onclick="closeModal('stockModal')">&times;</button>
        </div>
        <form id="stockForm" method="POST">
            <div class="modal-body">
                <input type="hidden" id="stockId" name="id">
                <input type="hidden" id="formAction" name="action" value="add">
                
                <div class="form-group">
                    <label class="form-label" for="nom_article">Nom de l'article</label>
                    <input type="text" id="nom_article" name="nom_article" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label" for="categorie">Catégorie</label>
                    <input type="text" id="categorie" name="categorie" class="form-control" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label" for="quantite">Quantité</label>
                        <input type="number" id="quantite" name="quantite" class="form-control" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="seuil">Seuil d'alerte</label>
                        <input type="number" id="seuil" name="seuil" class="form-control" min="1" required>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-lg);">
                    <div class="form-group">
                        <label class="form-label" for="prix_achat">Prix d'achat (€)</label>
                        <input type="number" step="0.01" id="prix_achat" name="prix_achat" class="form-control" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label" for="prix_vente">Prix de vente (€)</label>
                        <input type="number" step="0.01" id="prix_vente" name="prix_vente" class="form-control" min="0" required>
                    </div>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-ghost" onclick="closeModal('stockModal')">Annuler</button>
                <button type="submit" class="btn btn-primary" id="submitBtn">
                    <i class="fas fa-save"></i> Ajouter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de confirmation de suppression -->
<div id="deleteModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <div class="modal-header">
            <h3 class="modal-title">
                <i class="fas fa-exclamation-triangle" style="color: var(--warning-color);"></i> 
                Confirmer la suppression
            </h3>
            <button class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer l'article "<strong id="deleteItemName"></strong>" ?</p>
            <div class="alert alert-warning" style="margin-top: var(--spacing-md);">
                <i class="alert-icon fas fa-exclamation-triangle"></i>
                <div>Cette action est irréversible.</div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-ghost" onclick="closeModal('deleteModal')">Annuler</button>
            <button type="button" class="btn btn-danger" onclick="executeDelete()">
                <i class="fas fa-trash"></i> Supprimer
            </button>
        </div>
    </div>
</div>

    <script>
        // Helpers modales
        function openModal(id){ var el=document.getElementById(id); if(el){ el.style.display='block'; } }
        function closeModal(id){ var el=document.getElementById(id); if(el){ el.style.display='none'; } }

        // Ouvrir formulaire d'ajout
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Ajouter un article';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Ajouter';
            document.getElementById('submitBtn').className = 'btn btn-primary';
            document.getElementById('stockForm').reset();
            document.getElementById('stockId').value = '';
            openModal('stockModal');
        }

        // Ouvrir formulaire d'édition
        function openEditModal(id) {
            fetch(`?ajax=get_stock&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    if (!data) return;
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Modifier l\'article';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('submitBtn').textContent = 'Modifier';
                    document.getElementById('submitBtn').className = 'btn btn-warning';
                    document.getElementById('stockId').value = data.id;
                    document.getElementById('nom_article').value = data.nom_article || '';
                    document.getElementById('categorie').value = data.categorie || '';
                    document.getElementById('quantite').value = data.quantite || 0;
                    document.getElementById('seuil').value = data.seuil || 1;
                    if (data.prix_achat) document.getElementById('prix_achat').value = data.prix_achat;
                    if (data.prix_vente) document.getElementById('prix_vente').value = data.prix_vente;
                    openModal('stockModal');
                })
                .catch(()=>{});
        }

        // Suppression
        let deleteId = null;
        function confirmDelete(id, name) {
            deleteId = id;
            document.getElementById('deleteItemName').textContent = name;
            openModal('deleteModal');
        }
        function executeDelete() {
            if (!deleteId) return;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="'+deleteId+'">';
            document.body.appendChild(form);
            form.submit();
        }

        // Validation formulaire
        document.getElementById('stockForm').addEventListener('submit', function(e){
            const q = parseInt(document.getElementById('quantite').value || '0', 10);
            const s = parseInt(document.getElementById('seuil').value || '1', 10);
            const pa = parseFloat(document.getElementById('prix_achat').value || '0');
            const pv = parseFloat(document.getElementById('prix_vente').value || '0');
            if (q < 0 || s < 1 || isNaN(pa) || pa < 0 || isNaN(pv) || pv < 0) {
                e.preventDefault();
                alert('Vérifiez les champs: quantités >= 0, seuil >= 1, prix >= 0');
            }
        });

        // Recherche (reload serveur)
        function performSearch(){
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const lowStock = document.getElementById('lowStockFilter').checked;
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (category) params.append('category', category);
            if (lowStock) params.append('low_stock', '1');
            window.location.search = params.toString();
        }
        let __t;
        document.getElementById('searchInput').addEventListener('input', function(){ clearTimeout(__t); __t = setTimeout(performSearch, 300); });
        document.getElementById('categoryFilter').addEventListener('change', performSearch);
        document.getElementById('lowStockFilter').addEventListener('change', performSearch);

        // Fermer modales en cliquant à l'extérieur
        window.addEventListener('click', function(e){
            const sm = document.getElementById('stockModal');
            const dm = document.getElementById('deleteModal');
            if (e.target === sm) closeModal('stockModal');
            if (e.target === dm) closeModal('deleteModal');
        });
    </script>

<?php
$content = ob_get_clean();

// Inclure le layout de base
include __DIR__ . '/layout/base.php';
?>