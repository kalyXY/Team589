<?php
/**
 * Gestion des dépenses - Administrateur
 * Scolaria - Team589
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

// Vérification des droits d'administrateur
require_roles(['admin']);

// Définition des fonctions de gestion des dépenses
function handleListDepenses($pdo) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $month = trim($_GET['month'] ?? '');
    $year = trim($_GET['year'] ?? '');
    
    // Construction de la requête avec jointure pour récupérer le nom de la catégorie
    $whereConditions = [];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(d.description LIKE ? OR d.fournisseur LIKE ? OR d.facture_numero LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if ($category) {
        $whereConditions[] = "c.nom = ?";
        $params[] = $category;
    }
    
    if ($month) {
        $whereConditions[] = "MONTH(d.date) = ?";
        $params[] = $month;
    }
    
    if ($year) {
        $whereConditions[] = "YEAR(d.date) = ?";
        $params[] = $year;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Requête pour le total
    $countSql = "SELECT COUNT(*) FROM depenses d LEFT JOIN categories c ON d.categorie_id = c.id $whereClause";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();
    
    // Requête pour les données avec jointure
    $sql = "SELECT d.*, c.nom as categorie_nom FROM depenses d 
            LEFT JOIN categories c ON d.categorie_id = c.id 
            $whereClause ORDER BY d.date DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $depenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'depenses' => $depenses,
        'total' => $total,
        'page' => $page,
        'limit' => $limit
    ]);
}

function handleGetDepense($pdo) {
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        return;
    }
    
    $sql = "SELECT d.*, c.nom as categorie_nom FROM depenses d 
            LEFT JOIN categories c ON d.categorie_id = c.id 
            WHERE d.id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    $depense = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$depense) {
        echo json_encode(['success' => false, 'message' => 'Dépense non trouvée']);
        return;
    }
    
    echo json_encode([
        'success' => true,
        'depense' => $depense
    ]);
}

function handleGetStats($pdo) {
    $currentMonth = date('n');
    $currentYear = date('Y');
    
    // Total général
    $totalSql = "SELECT COALESCE(SUM(montant), 0) FROM depenses";
    $total = (float) $pdo->query($totalSql)->fetchColumn();
    
    // Total du mois
    $moisSql = "SELECT COALESCE(SUM(montant), 0) FROM depenses WHERE MONTH(date) = ? AND YEAR(date) = ?";
    $moisStmt = $pdo->prepare($moisSql);
    $moisStmt->execute([$currentMonth, $currentYear]);
    $mois = (float) $moisStmt->fetchColumn();
    
    // Total de l'année
    $anneeSql = "SELECT COALESCE(SUM(montant), 0) FROM depenses WHERE YEAR(date) = ?";
    $anneeStmt = $pdo->prepare($anneeSql);
    $anneeStmt->execute([$currentYear]);
    $annee = (float) $anneeStmt->fetchColumn();
    
    // Nombre total
    $countSql = "SELECT COUNT(*) FROM depenses";
    $count = (int) $pdo->query($countSql)->fetchColumn();
    
    echo json_encode([
        'success' => true,
        'stats' => [
            'total' => $total,
            'mois' => $mois,
            'annee' => $annee,
            'count' => $count
        ]
    ]);
}

function handleSaveDepense($pdo, $input) {
    // Validation des données
    $errors = [];
    
    if (empty($input['description'])) {
        $errors[] = "La description est requise";
    }
    
    if (empty($input['montant']) || !is_numeric($input['montant']) || floatval($input['montant']) <= 0) {
        $errors[] = "Le montant doit être un nombre positif";
    }
    
    if (empty($input['date']) || !strtotime($input['date'])) {
        $errors[] = "La date est requise et doit être valide";
    }
    
    if (!empty($errors)) {
        echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
        return;
    }
    
    $pdo->beginTransaction();
    
    try {
        // Gérer la catégorie - si c'est une chaîne, chercher ou créer l'ID
        $categorieId = null;
        if (!empty($input['categorie'])) {
            // Vérifier si la catégorie existe
            $catSql = "SELECT id FROM categories WHERE nom = ?";
            $catStmt = $pdo->prepare($catSql);
            $catStmt->execute([$input['categorie']]);
            $categorieId = $catStmt->fetchColumn();
            
            // Si la catégorie n'existe pas, la créer
            if (!$categorieId) {
                $insertCatSql = "INSERT INTO categories (nom, couleur) VALUES (?, '#007bff')";
                $insertCatStmt = $pdo->prepare($insertCatSql);
                $insertCatStmt->execute([$input['categorie']]);
                $categorieId = $pdo->lastInsertId();
            }
        }
        
        if (empty($input['id'])) {
            // Nouvelle dépense
            $sql = "INSERT INTO depenses (description, montant, date, categorie_id, fournisseur, facture_numero, notes, created_by, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['description'],
                $input['montant'],
                $input['date'],
                $categorieId,
                $input['fournisseur'] ?? null,
                $input['facture_numero'] ?? null,
                $input['notes'] ?? null,
                $_SESSION['username'] ?? 'admin'
            ]);
            
            $message = "Dépense créée avec succès";
        } else {
            // Modification
            $sql = "UPDATE depenses SET 
                    description = ?, montant = ?, date = ?, categorie_id = ?, 
                    fournisseur = ?, facture_numero = ?, notes = ?, updated_at = NOW() 
                    WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['description'],
                $input['montant'],
                $input['date'],
                $categorieId,
                $input['fournisseur'] ?? null,
                $input['facture_numero'] ?? null,
                $input['notes'] ?? null,
                $input['id']
            ]);
            
            $message = "Dépense modifiée avec succès";
        }
        
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage()
        ]);
    }
}

function handleDeleteDepense($pdo, $input) {
    $id = (int)($input['id'] ?? 0);
    
    if ($id <= 0) {
        echo json_encode(['success' => false, 'message' => 'ID invalide']);
        return;
    }
    
    try {
        $sql = "DELETE FROM depenses WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Dépense supprimée avec succès'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Dépense non trouvée'
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Erreur lors de la suppression: ' . $e->getMessage()
        ]);
    }
}

function handleExportDepenses($pdo) {
    $search = trim($_GET['search'] ?? '');
    $category = trim($_GET['category'] ?? '');
    $month = trim($_GET['month'] ?? '');
    $year = trim($_GET['year'] ?? '');
    
    // Construction de la requête avec jointure
    $whereConditions = [];
    $params = [];
    
    if ($search) {
        $whereConditions[] = "(d.description LIKE ? OR d.fournisseur LIKE ? OR d.facture_numero LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    if ($category) {
        $whereConditions[] = "c.nom = ?";
        $params[] = $category;
    }
    
    if ($month) {
        $whereConditions[] = "MONTH(d.date) = ?";
        $params[] = $month;
    }
    
    if ($year) {
        $whereConditions[] = "YEAR(d.date) = ?";
        $params[] = $year;
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT d.*, c.nom as categorie_nom FROM depenses d 
            LEFT JOIN categories c ON d.categorie_id = c.id 
            $whereClause ORDER BY d.date DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $depenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Génération du CSV
    $filename = 'depenses_' . date('Y-m-d_H-i-s') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
    header('Pragma: public');
    
    $output = fopen('php://output', 'w');
    
    // En-têtes
    fputcsv($output, [
        'ID', 'Date', 'Description', 'Montant', 'Catégorie', 
        'Fournisseur', 'N° Facture', 'Notes', 'Créé par', 'Créé le'
    ], ';');
    
    // Données
    foreach ($depenses as $depense) {
        fputcsv($output, [
            $depense['id'],
            $depense['date'],
            $depense['description'],
            $depense['montant'],
            $depense['categorie_nom'] ?? '',
            $depense['fournisseur'] ?? '',
            $depense['facture_numero'] ?? '',
            $depense['notes'] ?? '',
            $depense['created_by'] ?? '',
            $depense['created_at'] ?? ''
        ], ';');
    }
    
    fclose($output);
    exit;
}

// Traitement des actions AJAX (GET)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $pdo = Database::getConnection();
        
        switch ($_GET['action']) {
            case 'list':
                handleListDepenses($pdo);
                break;
            case 'get':
                handleGetDepense($pdo);
                break;
            case 'stats':
                handleGetStats($pdo);
                break;
            case 'export':
                handleExportDepenses($pdo);
                break;
            default:
                echo json_encode(['success' => false, 'message' => 'Action GET inconnue: ' . $_GET['action']]);
        }
        exit;
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        exit;
    }
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    
    // Log pour débogage
    error_log("=== DÉBUT TRAITEMENT POST ===");
    error_log("Méthode: " . $_SERVER['REQUEST_METHOD']);
    error_log("GET params: " . print_r($_GET, true));
    error_log("POST params: " . print_r($_POST, true));
    error_log("Raw input: " . file_get_contents('php://input'));
    
    $action = $_GET['action'] ?? null;
    error_log("Action détectée: " . ($action ?? 'NULL'));
    
    if (!$action) {
        error_log("ERREUR: Aucune action spécifiée");
        echo json_encode(['success' => false, 'message' => 'Aucune action spécifiée']);
        exit;
    }
    
    try {
        $pdo = Database::getConnection();
        $input = json_decode(file_get_contents('php://input'), true);
        
        error_log("Input JSON décodé: " . print_r($input, true));
        
        switch ($action) {
            case 'save':
                error_log("Traitement de l'action save");
                handleSaveDepense($pdo, $input);
                break;
            case 'delete':
                error_log("Traitement de l'action delete");
                handleDeleteDepense($pdo, $input);
                break;
            default:
                error_log("Action inconnue: " . $action);
                echo json_encode(['success' => false, 'message' => 'Action inconnue: ' . $action]);
        }
        exit;
    } catch (Exception $e) {
        error_log("Erreur lors du traitement: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur: ' . $e->getMessage()]);
        exit;
    }
}

// Configuration de la page
$currentPage = 'admin_depenses';
$pageTitle = 'Gestion des Dépenses - Admin';
$showSidebar = true;
$additionalCSS = ['assets/css/admin.css'];
$additionalJS = ['assets/js/admin-depenses.js'];

// Charger les catégories pour les filtres et le formulaire
function loadCategories($pdo) {
    $sql = "SELECT id, nom FROM categories ORDER BY nom";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Début du contenu HTML
ob_start();
?>

<div class="admin-container">
    <div class="admin-header">
        <h1><i class="fas fa-money-bill-wave"></i> Gestion des Dépenses</h1>
        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fas fa-plus"></i> Nouvelle Dépense
        </button>
    </div>

    <!-- Filtres et recherche -->
    <div class="admin-filters">
        <div class="row">
            <div class="col-md-3">
                <input type="text" id="searchInput" class="form-control" placeholder="Rechercher...">
            </div>
            <div class="col-md-2">
                <select id="categoryFilter" class="form-control">
                    <option value="">Toutes catégories</option>
                    <?php
                    try {
                        $pdo = Database::getConnection();
                        $categories = loadCategories($pdo);
                        foreach ($categories as $category) {
                            echo "<option value='{$category['nom']}'>{$category['nom']}</option>";
                        }
                    } catch (Exception $e) {
                        // En cas d'erreur, utiliser les catégories par défaut
                        $defaultCategories = ['Fournitures', 'Équipements', 'Maintenance', 'Transport', 'Formation', 'Énergie', 'Divers'];
                        foreach ($defaultCategories as $category) {
                            echo "<option value='$category'>$category</option>";
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="monthFilter" class="form-control">
                    <option value="">Tous les mois</option>
                    <?php
                    for ($i = 1; $i <= 12; $i++) {
                        $monthName = date('F', mktime(0, 0, 0, $i, 1));
                        $selected = ($i == date('n')) ? 'selected' : '';
                        echo "<option value='$i' $selected>$monthName</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-2">
                <select id="yearFilter" class="form-control">
                    <option value="">Toutes les années</option>
                    <?php
                    $currentYear = date('Y');
                    for ($year = $currentYear; $year >= $currentYear - 5; $year--) {
                        $selected = ($year == $currentYear) ? 'selected' : '';
                        echo "<option value='$year' $selected>$year</option>";
                    }
                    ?>
                </select>
            </div>
            <div class="col-md-3">
                <button class="btn btn-secondary" onclick="resetFilters()">
                    <i class="fas fa-refresh"></i> Réinitialiser
                </button>
                <button class="btn btn-success" onclick="exportData()">
                    <i class="fas fa-download"></i> Exporter
                </button>
            </div>
        </div>
    </div>

    <!-- Statistiques -->
    <div class="admin-stats">
        <div class="row">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-money-bill-wave"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="totalDepenses">0.00 €</h3>
                        <p>Total Dépenses</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-calendar"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="depensesMois">0.00 €</h3>
                        <p>Ce mois</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="depensesAnnee">0.00 €</h3>
                        <p>Cette année</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-list"></i>
                    </div>
                    <div class="stat-content">
                        <h3 id="nombreDepenses">0</h3>
                        <p>Nombre total</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tableau des dépenses -->
    <div class="admin-table-container">
        <table class="table table-striped table-hover" id="depensesTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Description</th>
                    <th>Montant</th>
                    <th>Catégorie</th>
                    <th>Fournisseur</th>
                    <th>Facture</th>
                    <th>Créé par</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="depensesTableBody">
                <!-- Les données seront chargées via JavaScript -->
            </tbody>
        </table>
        
        <!-- Pagination -->
        <div class="pagination-container">
            <nav>
                <ul class="pagination" id="pagination">
                    <!-- Pagination générée via JavaScript -->
                </ul>
            </nav>
        </div>
    </div>
</div>

<!-- Modal Ajout/Modification Dépense -->
<div class="modal fade" id="depenseModal" tabindex="-1" aria-labelledby="depenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="depenseModalLabel">Nouvelle Dépense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="depenseForm">
                <div class="modal-body">
                    <input type="hidden" id="depenseId" name="id">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="description" class="form-label">Description *</label>
                                <input type="text" class="form-control" id="description" name="description" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="montant" class="form-label">Montant (€) *</label>
                                <input type="number" class="form-control" id="montant" name="montant" step="0.01" min="0" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="date" class="form-label">Date *</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categorie" class="form-label">Catégorie</label>
                                <select class="form-control" id="categorie" name="categorie">
                                    <option value="">Sélectionner...</option>
                                    <?php
                                    try {
                                        $pdo = Database::getConnection();
                                        $categories = loadCategories($pdo);
                                        foreach ($categories as $category) {
                                            echo "<option value='{$category['nom']}'>{$category['nom']}</option>";
                                        }
                                    } catch (Exception $e) {
                                        // En cas d'erreur, utiliser les catégories par défaut
                                        $defaultCategories = ['Fournitures', 'Équipements', 'Maintenance', 'Transport', 'Formation', 'Énergie', 'Divers'];
                                        foreach ($defaultCategories as $category) {
                                            echo "<option value='$category'>$category</option>";
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="fournisseur" class="form-label">Fournisseur</label>
                                <input type="text" class="form-control" id="fournisseur" name="fournisseur">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="facture_numero" class="form-label">N° Facture</label>
                                <input type="text" class="form-control" id="facture_numero" name="facture_numero">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="notes" class="form-label">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmation Suppression -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirmer la suppression</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Êtes-vous sûr de vouloir supprimer cette dépense ?</p>
                <p><strong>Description:</strong> <span id="deleteDescription"></span></p>
                <p><strong>Montant:</strong> <span id="deleteMontant"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">Supprimer</button>
            </div>
        </div>
    </div>
</div>



<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>
