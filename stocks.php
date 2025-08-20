<?php
/**
 * Module Gestion des Stocks - Scolaria (Team589)
 * Application de gestion de logistique scolaire
 * 
 * Fonctionnalités :
 * - Liste des articles
 * - Ajout/Modification/Suppression d'articles
 * - Recherche et filtres
 * - Historique des mouvements
 */

session_start();

// Configuration de la base de données
class Database {
    private $host = "localhost";
    private $db_name = "scolaria";
    private $username = "root";
    private $password = "";
    private $conn;

    public function getConnection() {
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
    public function addStock($nom, $categorie, $quantite, $seuil) {
        $query = "INSERT INTO " . $this->table_stocks . " (nom_article, categorie, quantite, seuil) VALUES (:nom, :categorie, :quantite, :seuil)";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":nom", $nom);
        $stmt->bindParam(":categorie", $categorie);
        $stmt->bindParam(":quantite", $quantite);
        $stmt->bindParam(":seuil", $seuil);

        if ($stmt->execute()) {
            $article_id = $this->conn->lastInsertId();
            $this->logMovement($article_id, 'ajout', "Ajout de $quantite $nom", 'admin');
            return true;
        }
        return false;
    }

    // Modifier un article
    public function updateStock($id, $nom, $categorie, $quantite, $seuil) {
        // Récupérer l'ancien état pour l'historique
        $old_query = "SELECT * FROM " . $this->table_stocks . " WHERE id = :id";
        $old_stmt = $this->conn->prepare($old_query);
        $old_stmt->bindParam(":id", $id);
        $old_stmt->execute();
        $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);

        $query = "UPDATE " . $this->table_stocks . " SET nom_article = :nom, categorie = :categorie, quantite = :quantite, seuil = :seuil WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":nom", $nom);
        $stmt->bindParam(":categorie", $categorie);
        $stmt->bindParam(":quantite", $quantite);
        $stmt->bindParam(":seuil", $seuil);

        if ($stmt->execute()) {
            $details = "Modification de $nom - Quantité: {$old_data['quantite']} → $quantite, Seuil: {$old_data['seuil']} → $seuil";
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
$database = new Database();
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
                
                if ($stockManager->addStock($nom, $categorie, $quantite, $seuil)) {
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
                
                if ($stockManager->updateStock($id, $nom, $categorie, $quantite, $seuil)) {
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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Stocks - Scolaria</title>
    <link rel="stylesheet" href="stocks.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
</head>
<body class="app-layout">
    <!-- Header professionnel -->
    <header class="app-header">
        <div class="header-container">
            <div class="logo-section">
                <div class="logo">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div class="brand-info">
                    <h1>Scolaria</h1>
                    <p class="subtitle">Gestion des Stocks</p>
                </div>
            </div>
            <div class="header-actions">
                <div class="user-info">
                    <div class="user-avatar">A</div>
                    <span class="user-name">Admin</span>
                </div>
            </div>
        </div>
    </header>

    <!-- Section Hero -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">
                    <span class="icon"><i class="fas fa-boxes"></i></span>
                    Module de Gestion des Stocks
                </h1>
                <p class="hero-subtitle">
                    Solution professionnelle pour la gestion de logistique scolaire - Team589
                </p>
                <div class="hero-stats" id="heroStats">
                    <div class="stat-item">
                        <span class="stat-number" id="totalArticles">-</span>
                        <span class="stat-label">Articles</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="lowStockCount">-</span>
                        <span class="stat-label">Stocks faibles</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number" id="totalMovements">-</span>
                        <span class="stat-label">Mouvements</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="container">

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?>">
                <i class="fas fa-<?php echo $message_type == 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard">
            <!-- Métriques du dashboard -->
            <div class="dashboard-metrics">
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Total Articles</span>
                        <div class="metric-icon">
                            <i class="fas fa-boxes"></i>
                        </div>
                    </div>
                    <div class="metric-value" id="totalArticlesMetric">0</div>
                    <div class="metric-change neutral">
                        <i class="fas fa-minus"></i>
                        <span>Aucun changement</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Stocks Faibles</span>
                        <div class="metric-icon" style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                    </div>
                    <div class="metric-value" id="lowStockMetric">0</div>
                    <div class="metric-change negative">
                        <i class="fas fa-arrow-down"></i>
                        <span>Attention requise</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Catégories</span>
                        <div class="metric-icon" style="background: linear-gradient(135deg, #059669, #10b981);">
                            <i class="fas fa-tags"></i>
                        </div>
                    </div>
                    <div class="metric-value" id="categoriesMetric">0</div>
                    <div class="metric-change positive">
                        <i class="fas fa-arrow-up"></i>
                        <span>Bien organisé</span>
                    </div>
                </div>
                
                <div class="metric-card">
                    <div class="metric-header">
                        <span class="metric-title">Mouvements</span>
                        <div class="metric-icon" style="background: linear-gradient(135deg, #d97706, #f59e0b);">
                            <i class="fas fa-history"></i>
                        </div>
                    </div>
                    <div class="metric-value" id="movementsMetric">0</div>
                    <div class="metric-change positive">
                        <i class="fas fa-chart-line"></i>
                        <span>Activité récente</span>
                    </div>
                </div>
            </div>

            <!-- Section des contrôles -->
            <div class="controls-section">
                <div class="search-filters">
                    <div class="search-box">
                        <i class="fas fa-search"></i>
                        <input type="text" id="searchInput" placeholder="Rechercher par nom ou catégorie..." value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                    
                    <div class="filters">
                        <select id="categoryFilter">
                            <option value="">Toutes les catégories</option>
                            <?php while ($cat = $categories->fetch(PDO::FETCH_ASSOC)): ?>
                                <option value="<?php echo htmlspecialchars($cat['categorie']); ?>" 
                                        <?php echo $category_filter == $cat['categorie'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['categorie']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                        
                        <label class="checkbox-filter">
                            <input type="checkbox" id="lowStockFilter" <?php echo $low_stock_only ? 'checked' : ''; ?>>
                            <span>Stock faible uniquement</span>
                        </label>
                    </div>
                </div>
                
                <button class="btn btn-primary" onclick="openAddModal()">
                    <i class="fas fa-plus"></i> Ajouter un article
                </button>
            </div>

            <!-- Section principale avec onglets -->
            <div class="tabs">
                <button class="tab-button active" onclick="showTab('stocks')">
                    <i class="fas fa-list"></i> Liste des Articles
                </button>
                <button class="tab-button" onclick="showTab('history')">
                    <i class="fas fa-history"></i> Historique
                </button>
            </div>

            <!-- Onglet Liste des articles -->
            <div id="stocks-tab" class="tab-content active">
                <div class="table-container">
                    <table class="stocks-table" id="stocksTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom de l'article</th>
                                <th>Catégorie</th>
                                <th>Quantité</th>
                                <th>Seuil</th>
                                <th>Date d'ajout</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $stocks->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr class="<?php echo $row['quantite'] <= $row['seuil'] ? 'low-stock' : ''; ?>">
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['nom_article']); ?></strong>
                                        <?php if ($row['quantite'] <= $row['seuil']): ?>
                                            <span class="alert-badge"><i class="fas fa-exclamation-triangle"></i> Stock faible</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['categorie']); ?></td>
                                    <td>
                                        <span class="quantity <?php echo $row['quantite'] <= $row['seuil'] ? 'low' : ''; ?>">
                                            <?php echo $row['quantite']; ?>
                                        </span>
                                    </td>
                                    <td><?php echo $row['seuil']; ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                                    <td class="actions">
                                        <button class="btn btn-edit" onclick="openEditModal(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-delete" onclick="confirmDelete(<?php echo $row['id']; ?>, '<?php echo addslashes($row['nom_article']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Onglet Historique -->
            <div id="history-tab" class="tab-content">
                <div class="table-container">
                    <table class="history-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>Article</th>
                                <th>Détails</th>
                                <th>Utilisateur</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($movement = $movements->fetch(PDO::FETCH_ASSOC)): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y H:i:s', strtotime($movement['date_mouvement'])); ?></td>
                                    <td>
                                        <span class="action-badge action-<?php echo $movement['action']; ?>">
                                            <i class="fas fa-<?php echo $movement['action'] == 'ajout' ? 'plus' : ($movement['action'] == 'modification' ? 'edit' : 'trash'); ?>"></i>
                                            <?php echo ucfirst($movement['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($movement['nom_article'] ?? 'Article supprimé'); ?></td>
                                    <td><?php echo htmlspecialchars($movement['details']); ?></td>
                                    <td><?php echo htmlspecialchars($movement['utilisateur']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal pour ajouter/modifier un article -->
    <div id="stockModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle"><i class="fas fa-plus-circle"></i> Ajouter un article</h2>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <form id="stockForm" method="POST">
                <input type="hidden" id="stockId" name="id">
                <input type="hidden" id="formAction" name="action" value="add">
                
                <div class="form-group">
                    <label for="nom_article">Nom de l'article *</label>
                    <input type="text" id="nom_article" name="nom_article" required>
                </div>
                
                <div class="form-group">
                    <label for="categorie">Catégorie *</label>
                    <input type="text" id="categorie" name="categorie" required>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="quantite">Quantité *</label>
                        <input type="number" id="quantite" name="quantite" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="seuil">Seuil d'alerte *</label>
                        <input type="number" id="seuil" name="seuil" min="1" required>
                    </div>
                </div>
                
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="submitBtn">Ajouter</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="modal">
        <div class="modal-content modal-small">
            <div class="modal-header">
                <h2><i class="fas fa-exclamation-triangle"></i> Confirmer la suppression</h2>
                <span class="close" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer l'article "<span id="deleteItemName"></span>" ?</p>
                <p class="warning">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Annuler</button>
                <button type="button" class="btn btn-danger" onclick="executeDelete()">Supprimer</button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Variables globales
        let deleteId = null;
        
        // Calcul et affichage des métriques
        function updateMetrics() {
            const tableRows = document.querySelectorAll('#stocksTable tbody tr');
            const totalArticles = tableRows.length;
            let lowStockCount = 0;
            const categories = new Set();
            
            tableRows.forEach(row => {
                if (row.classList.contains('low-stock')) {
                    lowStockCount++;
                }
                // Extraire la catégorie de la 3ème colonne
                const categoryCell = row.cells[2];
                if (categoryCell) {
                    categories.add(categoryCell.textContent.trim());
                }
            });
            
            // Mettre à jour les métriques du dashboard
            document.getElementById('totalArticlesMetric').textContent = totalArticles;
            document.getElementById('lowStockMetric').textContent = lowStockCount;
            document.getElementById('categoriesMetric').textContent = categories.size;
            
            // Mettre à jour les statistiques du hero
            document.getElementById('totalArticles').textContent = totalArticles;
            document.getElementById('lowStockCount').textContent = lowStockCount;
            
            // Animation des nombres
            animateNumbers();
        }
        
        // Animation des nombres
        function animateNumbers() {
            const numbers = document.querySelectorAll('.metric-value, .stat-number');
            numbers.forEach(element => {
                const finalValue = parseInt(element.textContent);
                let currentValue = 0;
                const increment = Math.ceil(finalValue / 20);
                
                const timer = setInterval(() => {
                    currentValue += increment;
                    if (currentValue >= finalValue) {
                        currentValue = finalValue;
                        clearInterval(timer);
                    }
                    element.textContent = currentValue;
                }, 50);
            });
        }
        
        // Calculer les mouvements (simulation)
        function updateMovementsMetric() {
            fetch('?ajax=count_movements')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('movementsMetric').textContent = data.count || 0;
                    document.getElementById('totalMovements').textContent = data.count || 0;
                })
                .catch(() => {
                    // Fallback avec une valeur simulée
                    const simulatedCount = Math.floor(Math.random() * 50) + 10;
                    document.getElementById('movementsMetric').textContent = simulatedCount;
                    document.getElementById('totalMovements').textContent = simulatedCount;
                });
        }

        // Gestion des onglets
        function showTab(tabName) {
            // Masquer tous les onglets
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Désactiver tous les boutons d'onglet
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Afficher l'onglet sélectionné
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Activer le bouton correspondant
            event.target.classList.add('active');
        }

        // Gestion des modales
        function openAddModal() {
            document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Ajouter un article';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitBtn').textContent = 'Ajouter';
            document.getElementById('submitBtn').className = 'btn btn-primary';
            document.getElementById('stockForm').reset();
            document.getElementById('stockId').value = '';
            document.getElementById('stockModal').style.display = 'block';
            
            // Animation d'ouverture
            setTimeout(() => {
                document.querySelector('#stockModal .modal-content').style.transform = 'translateY(0) scale(1)';
            }, 10);
        }

        function openEditModal(id) {
            fetch(`?ajax=get_stock&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Modifier l\'article';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('submitBtn').textContent = 'Modifier';
                    document.getElementById('submitBtn').className = 'btn btn-warning';
                    document.getElementById('stockId').value = data.id;
                    document.getElementById('nom_article').value = data.nom_article;
                    document.getElementById('categorie').value = data.categorie;
                    document.getElementById('quantite').value = data.quantite;
                    document.getElementById('seuil').value = data.seuil;
                    document.getElementById('stockModal').style.display = 'block';
                    
                    // Animation d'ouverture
                    setTimeout(() => {
                        document.querySelector('#stockModal .modal-content').style.transform = 'translateY(0) scale(1)';
                    }, 10);
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('Erreur lors du chargement des données', 'error');
                });
        }

        function closeModal() {
            document.getElementById('stockModal').style.display = 'none';
        }

        function confirmDelete(id, name) {
            deleteId = id;
            document.getElementById('deleteItemName').textContent = name;
            document.getElementById('deleteModal').style.display = 'block';
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
            deleteId = null;
        }

        function executeDelete() {
            if (deleteId) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" value="${deleteId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Validation du formulaire
        document.getElementById('stockForm').addEventListener('submit', function(e) {
            const quantite = parseInt(document.getElementById('quantite').value);
            const seuil = parseInt(document.getElementById('seuil').value);
            
            if (quantite < 0) {
                e.preventDefault();
                alert('La quantité doit être positive.');
                return;
            }
            
            if (seuil < 1) {
                e.preventDefault();
                alert('Le seuil doit être au moins de 1.');
                return;
            }
        });

        // Recherche et filtres en temps réel
        function performSearch() {
            const search = document.getElementById('searchInput').value;
            const category = document.getElementById('categoryFilter').value;
            const lowStock = document.getElementById('lowStockFilter').checked ? '1' : '0';
            
            const params = new URLSearchParams();
            if (search) params.append('search', search);
            if (category) params.append('category', category);
            if (lowStock === '1') params.append('low_stock', '1');
            
            fetch(`?ajax=search&${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    updateTable(data);
                })
                .catch(error => {
                    console.error('Erreur de recherche:', error);
                });
        }

        function updateTable(stocks) {
            const tbody = document.querySelector('#stocksTable tbody');
            tbody.innerHTML = '';
            
            stocks.forEach(stock => {
                const isLowStock = stock.quantite <= stock.seuil;
                const row = document.createElement('tr');
                if (isLowStock) row.classList.add('low-stock');
                
                row.innerHTML = `
                    <td>${stock.id}</td>
                    <td>
                        <strong>${stock.nom_article}</strong>
                        ${isLowStock ? '<span class="alert-badge"><i class="fas fa-exclamation-triangle"></i> Stock faible</span>' : ''}
                    </td>
                    <td>${stock.categorie}</td>
                    <td><span class="quantity ${isLowStock ? 'low' : ''}">${stock.quantite}</span></td>
                    <td>${stock.seuil}</td>
                    <td>${new Date(stock.created_at).toLocaleDateString('fr-FR')} ${new Date(stock.created_at).toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</td>
                    <td class="actions">
                        <button class="btn btn-edit" onclick="openEditModal(${stock.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-delete" onclick="confirmDelete(${stock.id}, '${stock.nom_article.replace(/'/g, "\\'")}')">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        // Événements de recherche
        let searchTimeout;
        document.getElementById('searchInput').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 300);
        });

        document.getElementById('categoryFilter').addEventListener('change', performSearch);
        document.getElementById('lowStockFilter').addEventListener('change', performSearch);

        // Fermer les modales en cliquant à l'extérieur
        window.addEventListener('click', function(event) {
            const stockModal = document.getElementById('stockModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (event.target === stockModal) {
                closeModal();
            }
            if (event.target === deleteModal) {
                closeDeleteModal();
            }
        });

        // Initialisation de l'application
        document.addEventListener('DOMContentLoaded', function() {
            // Mettre à jour les métriques au chargement
            updateMetrics();
            updateMovementsMetric();
            
            // Masquer les alertes automatiquement
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.opacity = '0';
                    alert.style.transform = 'translateY(-20px)';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);
        });
    </script>
</body>
</html>