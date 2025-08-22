<?php
/**
 * SCOLARIA - Module Gestion Financière
 * Suivi des dépenses, budgets, rapports et exports
 * Team589
 */

session_start();

// Vérification de session requise
if (empty($_SESSION['user_id']) || empty($_SESSION['username']) || empty($_SESSION['role'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Configuration de la page
$currentPage = 'finances';
$pageTitle = 'Gestion Financière';
$showSidebar = true;
$additionalCSS = ['assets/css/finances.css'];
$additionalJS = [];
$bodyClass = 'finances-page';

class FinancesManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Liste toutes les dépenses avec filtres
     */
    public function listExpenses($filters = []) {
        try {
            $sql = "SELECT d.*, c.nom as categorie_nom, c.couleur as categorie_couleur 
                    FROM depenses d 
                    LEFT JOIN categories c ON d.categorie_id = c.id 
                    WHERE 1=1";
            $params = [];
            
            if (!empty($filters['date_debut'])) {
                $sql .= " AND d.date >= ?";
                $params[] = $filters['date_debut'];
            }
            
            if (!empty($filters['date_fin'])) {
                $sql .= " AND d.date <= ?";
                $params[] = $filters['date_fin'];
            }
            
            if (!empty($filters['categorie_id'])) {
                $sql .= " AND d.categorie_id = ?";
                $params[] = $filters['categorie_id'];
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (d.description LIKE ? OR d.fournisseur LIKE ?)";
                $params[] = '%' . $filters['search'] . '%';
                $params[] = '%' . $filters['search'] . '%';
            }
            
            $sql .= " ORDER BY d.date DESC, d.id DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur listExpenses: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ajoute une nouvelle dépense
     */
    public function addExpense($data) {
        try {
            // Validation des données requises
            if (empty($data['description']) || empty($data['montant']) || empty($data['date'])) {
                error_log("Erreur addExpense: Données requises manquantes");
                return false;
            }
            
            // Validation du montant
            if (!is_numeric($data['montant']) || floatval($data['montant']) <= 0) {
                error_log("Erreur addExpense: Montant invalide - " . $data['montant']);
                return false;
            }
            
            // Validation de la date
            if (!strtotime($data['date'])) {
                error_log("Erreur addExpense: Date invalide - " . $data['date']);
                return false;
            }
            
            // Préparation des valeurs
            $categorieId = (!empty($data['categorie_id']) && is_numeric($data['categorie_id'])) ? (int)$data['categorie_id'] : null;
            $factureNumero = isset($data['facture_numero']) ? trim($data['facture_numero']) : '';
            $fournisseur = isset($data['fournisseur']) ? trim($data['fournisseur']) : '';
            $notes = isset($data['notes']) ? trim($data['notes']) : '';
            $createdBy = isset($_SESSION['username']) ? $_SESSION['username'] : 'admin';
            
            $sql = "INSERT INTO depenses (description, montant, date, categorie_id, facture_numero, fournisseur, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            $params = [
                trim($data['description']),
                floatval($data['montant']),
                $data['date'],
                $categorieId,
                $factureNumero,
                $fournisseur,
                $notes,
                $createdBy
            ];
            
            $result = $stmt->execute($params);
            
            if ($result) {
                error_log("Dépense ajoutée avec succès - ID: " . $this->pdo->lastInsertId());
            } else {
                error_log("Erreur addExpense: Échec de l'exécution de la requête");
            }
            
            return $result;
            
        } catch (PDOException $e) {
            error_log("Erreur addExpense PDO: " . $e->getMessage());
            error_log("Données reçues: " . print_r($data, true));
            return false;
        } catch (Exception $e) {
            error_log("Erreur addExpense générale: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour une dépense
     */
    public function updateExpense($id, $data) {
        try {
            $sql = "UPDATE depenses SET description = ?, montant = ?, date = ?, categorie_id = ?, 
                    facture_numero = ?, fournisseur = ?, notes = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $data['description'],
                $data['montant'],
                $data['date'],
                $data['categorie_id'] ?: null,
                $data['facture_numero'] ?? '',
                $data['fournisseur'] ?? '',
                $data['notes'] ?? '',
                $id
            ]);
        } catch (PDOException $e) {
            error_log("Erreur updateExpense: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime une dépense
     */
    public function deleteExpense($id) {
        try {
            $sql = "DELETE FROM depenses WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur deleteExpense: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Liste toutes les catégories
     */
    public function listCategories() {
        try {
            $sql = "SELECT * FROM categories ORDER BY nom ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur listCategories: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ajoute une nouvelle catégorie
     */
    public function addCategory($nom, $description = '', $couleur = '#3B82F6') {
        try {
            $sql = "INSERT INTO categories (nom, description, couleur) VALUES (?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$nom, $description, $couleur]);
        } catch (PDOException $e) {
            error_log("Erreur addCategory: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Génère les données pour les rapports
     */
    public function generateReports($annee = null) {
        if (!$annee) $annee = date('Y');
        
        try {
            // Dépenses par mois
            $sql = "SELECT MONTH(date) as mois, SUM(montant) as total 
                    FROM depenses 
                    WHERE YEAR(date) = ? 
                    GROUP BY MONTH(date) 
                    ORDER BY mois";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$annee]);
            $depensesParMois = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Recettes (ventes) par mois
            try {
                $sql = "SELECT MONTH(date_vente) AS mois, SUM(COALESCE(v.total, v.quantite * v.prix_unitaire, 0)) AS total
                        FROM ventes v
                        WHERE YEAR(date_vente) = ?
                        GROUP BY MONTH(date_vente)
                        ORDER BY mois";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$annee]);
                $recettesParMois = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                $recettesParMois = [];
            }

            // Bénéfice net par mois
            $mapDep = [];
            foreach ($depensesParMois as $d) { $mapDep[(int)$d['mois']] = (float)$d['total']; }
            $mapRec = [];
            foreach ($recettesParMois as $r) { $mapRec[(int)$r['mois']] = (float)$r['total']; }
            $netParMois = [];
            for ($m = 1; $m <= 12; $m++) {
                $dep = $mapDep[$m] ?? 0.0;
                $rec = $mapRec[$m] ?? 0.0;
                $netParMois[] = ['mois' => $m, 'total' => $rec - $dep];
            }

            // Dépenses par catégorie
            $sql = "SELECT c.nom, c.couleur, SUM(d.montant) as total 
                    FROM depenses d 
                    LEFT JOIN categories c ON d.categorie_id = c.id 
                    WHERE YEAR(d.date) = ? 
                    GROUP BY c.id, c.nom, c.couleur 
                    ORDER BY total DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$annee]);
            $depensesParCategorie = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Top 5 articles les plus rentables
            try {
                $sqlTop = "SELECT s.nom_article, SUM(v.quantite*(v.prix_unitaire - s.prix_achat)) as benefice
                           FROM ventes v JOIN stocks s ON v.article_id = s.id 
                           GROUP BY v.article_id, s.nom_article 
                           ORDER BY benefice DESC LIMIT 5";
                $topProfitable = $this->pdo->query($sqlTop)->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e) {
                $topProfitable = [];
            }
            
            return [
                'depenses_par_mois' => $depensesParMois,
                'recettes_par_mois' => $recettesParMois ?? [],
                'net_par_mois' => $netParMois,
                'depenses_par_categorie' => $depensesParCategorie,
                'top_profitable' => $topProfitable
            ];
        } catch (PDOException $e) {
            error_log("Erreur generateReports: " . $e->getMessage());
            return ['depenses_par_mois' => [], 'recettes_par_mois' => [], 'net_par_mois' => [], 'depenses_par_categorie' => [], 'top_profitable' => []];
        }
    }
    
    /**
     * Récupère les indicateurs financiers
     */
    public function getFinancialIndicators() {
        // Initialisation des variables avec des valeurs par défaut
        $totalDepensesMois = 0;
        $totalDepensesAnnee = 0;
        $totalRecettesMois = 0;
        $totalRecettesAnnee = 0;
        $categorieMax = null;
        $nombreDepenses = 0;
        
        try {
            $currentMonth = date('Y-m');
            $currentYear = date('Y');
            
            // Total dépenses ce mois
            $sql = "SELECT SUM(montant) as total FROM depenses WHERE DATE_FORMAT(date, '%Y-%m') = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$currentMonth]);
            $totalDepensesMois = $stmt->fetchColumn() ?: 0;
            
            // Total dépenses cumulées cette année
            $sql = "SELECT SUM(montant) as total FROM depenses WHERE YEAR(date) = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$currentYear]);
            $totalDepensesAnnee = $stmt->fetchColumn() ?: 0;

            // Recettes (ventes) ce mois
            try {
                $sql = "SELECT SUM(COALESCE(v.total, v.quantite * v.prix_unitaire, 0)) FROM ventes v WHERE DATE_FORMAT(date_vente, '%Y-%m') = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$currentMonth]);
                $totalRecettesMois = $stmt->fetchColumn() ?: 0;
            } catch (Throwable $e) {
                $totalRecettesMois = 0; // Valeur par défaut en cas d'erreur
            }

            // Recettes cumulées cette année
            try {
                $sql = "SELECT SUM(COALESCE(v.total, v.quantite * v.prix_unitaire, 0)) FROM ventes v WHERE YEAR(date_vente) = ?";
                $stmt = $this->pdo->prepare($sql);
                $stmt->execute([$currentYear]);
                $totalRecettesAnnee = $stmt->fetchColumn() ?: 0;
            } catch (Throwable $e) {
                $totalRecettesAnnee = 0; // Valeur par défaut en cas d'erreur
            }
            
            // Catégorie la plus coûteuse ce mois
            $sql = "SELECT c.nom, SUM(d.montant) as total 
                    FROM depenses d 
                    LEFT JOIN categories c ON d.categorie_id = c.id 
                    WHERE DATE_FORMAT(d.date, '%Y-%m') = ? 
                    GROUP BY c.id, c.nom 
                    ORDER BY total DESC 
                    LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$currentMonth]);
            $categorieMax = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Nombre de dépenses ce mois
            $sql = "SELECT COUNT(*) FROM depenses WHERE DATE_FORMAT(date, '%Y-%m') = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$currentMonth]);
            $nombreDepenses = $stmt->fetchColumn() ?: 0;
            
            return [
                'total_depenses_mois' => (float)$totalDepensesMois,
                'total_depenses_annee' => (float)$totalDepensesAnnee,
                'total_recettes_mois' => (float)$totalRecettesMois,
                'total_recettes_annee' => (float)$totalRecettesAnnee,
                'benefice_net_mois' => (float)($totalRecettesMois - $totalDepensesMois),
                'benefice_net_annee' => (float)($totalRecettesAnnee - $totalDepensesAnnee),
                'categorie_max' => $categorieMax,
                'nombre_depenses' => $nombreDepenses
            ];
        } catch (PDOException $e) {
            error_log("Erreur getFinancialIndicators: " . $e->getMessage());
            return [
                'total_depenses_mois' => (float)$totalDepensesMois,
                'total_depenses_annee' => (float)$totalDepensesAnnee,
                'total_recettes_mois' => (float)$totalRecettesMois,
                'total_recettes_annee' => (float)$totalRecettesAnnee,
                'benefice_net_mois' => (float)($totalRecettesMois - $totalDepensesMois),
                'benefice_net_annee' => (float)($totalRecettesAnnee - $totalDepensesAnnee),
                'categorie_max' => $categorieMax,
                'nombre_depenses' => $nombreDepenses
            ];
        }
    }
    
    /**
     * Ajoute un budget mensuel
     */
    public function addBudget($mois, $annee, $montant, $categorieId = null, $notes = '') {
        try {
            $sql = "INSERT INTO budgets (mois, annee, montant_prevu, categorie_id, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    montant_prevu = VALUES(montant_prevu), 
                    notes = VALUES(notes)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                $mois, $annee, $montant, $categorieId, $notes, $_SESSION['username'] ?? 'admin'
            ]);
        } catch (PDOException $e) {
            error_log("Erreur addBudget: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifie les alertes budgétaires
     */
    public function checkBudgetAlerts($mois = null, $annee = null) {
        if (!$mois) $mois = date('n');
        if (!$annee) $annee = date('Y');
        
        try {
            $sql = "SELECT * FROM v_budgets_comparaison WHERE mois = ? AND annee = ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$mois, $annee]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur checkBudgetAlerts: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Export PDF des dépenses
     */
    public function exportPDF($filters = []) {
        require_once __DIR__ . '/vendor/tcpdf/tcpdf.php';
        
        $expenses = $this->listExpenses($filters);
        
        $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $pdf->SetCreator('Scolaria Team589');
        $pdf->SetAuthor('Scolaria');
        $pdf->SetTitle('Rapport des Dépenses');
        $pdf->SetSubject('Gestion Financière');
        
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
        $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
        $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);
        $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);
        
        $pdf->AddPage();
        
        $html = '<h1>Rapport des Dépenses - Scolaria</h1>';
        $html .= '<p>Généré le ' . date('d/m/Y à H:i') . '</p>';
        $html .= '<table border="1" cellpadding="5">';
        $html .= '<tr><th>Date</th><th>Description</th><th>Montant</th><th>Catégorie</th><th>Fournisseur</th></tr>';
        
        $total = 0; // Sera calculé dynamiquement
        foreach ($expenses as $expense) {
            $html .= '<tr>';
            $html .= '<td>' . date('d/m/Y', strtotime($expense['date'])) . '</td>';
            $html .= '<td>' . htmlspecialchars($expense['description']) . '</td>';
            $html .= '<td>' . number_format($expense['montant'], 2) . ' €</td>';
            $html .= '<td>' . htmlspecialchars($expense['categorie_nom'] ?? 'Non catégorisé') . '</td>';
            $html .= '<td>' . htmlspecialchars($expense['fournisseur'] ?? '') . '</td>';
            $html .= '</tr>';
            $total += $expense['montant'];
        }
        
        $html .= '<tr><td colspan="2"><strong>TOTAL</strong></td><td><strong>' . number_format($total, 2) . ' €</strong></td><td colspan="2"></td></tr>';
        $html .= '</table>';
        
        $pdf->writeHTML($html, true, false, true, false, '');
        
        $filename = 'depenses_' . date('Y-m-d') . '.pdf';
        $pdf->Output($filename, 'D');
        exit;
    }
    
    /**
     * Export Excel/CSV des dépenses
     */
    public function exportExcel($filters = [], $format = 'csv') {
        $expenses = $this->listExpenses($filters);
        
        $filename = 'depenses_' . date('Y-m-d') . '.' . $format;
        
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        
        $output = fopen('php://output', 'w');
        
        // En-têtes
        fputcsv($output, [
            'ID', 'Date', 'Description', 'Montant', 'Catégorie', 
            'Facture', 'Fournisseur', 'Notes', 'Créé le'
        ], ';');
        
        // Données
        foreach ($expenses as $expense) {
            fputcsv($output, [
                $expense['id'],
                $expense['date'],
                $expense['description'],
                $expense['montant'],
                $expense['categorie_nom'] ?? 'Non catégorisé',
                $expense['facture_numero'],
                $expense['fournisseur'],
                $expense['notes'],
                $expense['created_at']
            ], ';');
        }
        
        fclose($output);
        exit;
    }
}

// Initialisation
$financesManager = new FinancesManager();
$message = '';
$messageType = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_expense':
            // Validation côté serveur
            $errors = [];
            
            if (empty($_POST['description'])) {
                $errors[] = "La description est requise";
            }
            if (empty($_POST['montant']) || !is_numeric($_POST['montant']) || floatval($_POST['montant']) <= 0) {
                $errors[] = "Le montant doit être un nombre positif";
            }
            if (empty($_POST['date']) || !strtotime($_POST['date'])) {
                $errors[] = "La date est requise et doit être valide";
            }
            
            if (empty($errors)) {
                if ($financesManager->addExpense($_POST)) {
                    $message = "Dépense ajoutée avec succès.";
                    $messageType = 'success';
                } else {
                    $message = "Erreur lors de l'ajout de la dépense. Vérifiez les logs pour plus de détails.";
                    $messageType = 'error';
                }
            } else {
                $message = "Erreurs de validation : " . implode(", ", $errors);
                $messageType = 'error';
            }
            break;
            
        case 'update_expense':
            $id = (int)$_POST['expense_id'];
            if ($financesManager->updateExpense($id, $_POST)) {
                $message = "Dépense mise à jour avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de la mise à jour.";
                $messageType = 'error';
            }
            break;
            
        case 'delete_expense':
            $id = (int)$_POST['expense_id'];
            if ($financesManager->deleteExpense($id)) {
                $message = "Dépense supprimée avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de la suppression.";
                $messageType = 'error';
            }
            break;
            
        case 'add_category':
            if ($financesManager->addCategory($_POST['nom'], $_POST['description'] ?? '', $_POST['couleur'] ?? '#3B82F6')) {
                $message = "Catégorie ajoutée avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de l'ajout de la catégorie.";
                $messageType = 'error';
            }
            break;
            
        case 'add_budget':
            if ($financesManager->addBudget($_POST['mois'], $_POST['annee'], $_POST['montant_prevu'], $_POST['categorie_id'] ?: null, $_POST['notes'] ?? '')) {
                $message = "Budget enregistré avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de l'enregistrement du budget.";
                $messageType = 'error';
            }
            break;
    }
}

// Traitement des exports
if (isset($_GET['export'])) {
    $filters = [
        'date_debut' => $_GET['date_debut'] ?? '',
        'date_fin' => $_GET['date_fin'] ?? '',
        'categorie_id' => $_GET['categorie_id'] ?? '',
        'search' => $_GET['search'] ?? ''
    ];
    
    if ($_GET['export'] === 'pdf') {
        $financesManager->exportPDF($filters);
    } elseif ($_GET['export'] === 'excel') {
        $financesManager->exportExcel($filters, 'csv');
    }
}

// Récupération des données
$expenses = $financesManager->listExpenses(['limit' => 50]);
$categories = $financesManager->listCategories();
$indicators = $financesManager->getFinancialIndicators();
$reports = $financesManager->generateReports();
$budgetAlerts = $financesManager->checkBudgetAlerts();

// Début du contenu HTML
ob_start();
?>

<!-- Messages de notification -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <div><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<!-- Indicateurs financiers -->
<div class="finance-indicators">
    <div class="finance-card danger">
        <div class="finance-card-header">
            <h3 class="finance-card-title">Dépenses (mois)</h3>
            <div class="finance-card-icon">
                <i class="fas fa-receipt"></i>
            </div>
        </div>
        <div class="finance-card-value"><?php echo number_format($indicators['total_depenses_mois'], 2); ?>€</div>
        <div class="finance-card-subtitle"><?php echo $indicators['nombre_depenses']; ?> dépenses</div>
    </div>
    
    <div class="finance-card success">
        <div class="finance-card-header">
            <h3 class="finance-card-title">Recettes (mois)</h3>
            <div class="finance-card-icon">
                <i class="fas fa-cash-register"></i>
            </div>
    </div>
        <div class="finance-card-value"><?php echo number_format($indicators['total_recettes_mois'], 2); ?>€</div>
        <div class="finance-card-subtitle">Ventes</div>
    </div>

    <div class="finance-card info">
        <div class="finance-card-header">
            <h3 class="finance-card-title">Bénéfice net (mois)</h3>
            <div class="finance-card-icon">
                <i class="fas fa-chart-line"></i>
            </div>
        </div>
        <div class="finance-card-value"><?php echo number_format($indicators['benefice_net_mois'], 2); ?>€</div>
        <div class="finance-card-subtitle">Recettes - Dépenses</div>
    </div>
    
    <div class="finance-card warning">
        <div class="finance-card-header">
            <h3 class="finance-card-title">Catégorie principale</h3>
            <div class="finance-card-icon">
                <i class="fas fa-tags"></i>
            </div>
        </div>
        <div class="finance-card-value" style="font-size: 1.2rem;">
            <?php echo $indicators['categorie_max']['nom'] ?? 'Aucune'; ?>
        </div>
        <div class="finance-card-subtitle">
            <?php echo $indicators['categorie_max'] ? number_format($indicators['categorie_max']['total'], 2) . '€' : '0€'; ?>
        </div>
    </div>
    
    <div class="finance-card warning">
        <div class="finance-card-header">
            <h3 class="finance-card-title">Bénéfice net (année)</h3>
            <div class="finance-card-icon">
                <i class="fas fa-coins"></i>
            </div>
        </div>
        <div class="finance-card-value"><?php echo number_format($indicators['benefice_net_annee'], 2); ?>€</div>
        <div class="finance-card-subtitle">Année <?php echo date('Y'); ?></div>
    </div>
</div>

<!-- Navigation par onglets -->
<div class="card">
    <div class="finance-tabs">
        <button class="finance-tab active" onclick="showFinanceSection('expenses')">
            <i class="fas fa-receipt"></i> Dépenses
        </button>
        <button class="finance-tab" onclick="showFinanceSection('reports')">
            <i class="fas fa-chart-bar"></i> Rapports
        </button>
        <button class="finance-tab" onclick="showFinanceSection('budget')">
            <i class="fas fa-calculator"></i> Budget
        </button>
        <button class="finance-tab" onclick="showFinanceSection('categories')">
            <i class="fas fa-tags"></i> Catégories
        </button>
        <button class="finance-tab" onclick="showFinanceSection('export')">
            <i class="fas fa-download"></i> Export
        </button>
    </div>

    <!-- Section Dépenses -->
    <div id="expenses-section" class="finance-section active">
        <!-- Formulaire d'ajout de dépense -->
        <div class="finance-form">
            <div class="finance-form-header">
                <h3 class="finance-form-title">
                    <i class="fas fa-plus"></i> Nouvelle Dépense
                </h3>
            </div>
            
            <form method="POST" id="expenseForm">
                <input type="hidden" name="action" value="add_expense">
                
                <div class="finance-form-grid">
                    <div class="form-group">
                        <label for="description">Description *</label>
                        <input type="text" id="description" name="description" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="montant">Montant (€) *</label>
                        <input type="number" id="montant" name="montant" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date *</label>
                        <input type="date" id="date" name="date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="categorie_id">Catégorie</label>
                        <select id="categorie_id" name="categorie_id" class="form-control">
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="facture_numero">N° Facture</label>
                        <input type="text" id="facture_numero" name="facture_numero" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="fournisseur">Fournisseur</label>
                        <input type="text" id="fournisseur" name="fournisseur" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="notes">Notes</label>
                    <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="finance-form-actions">
                    <button type="reset" class="btn btn-outline">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Filtres -->
        <div class="finance-filters">
            <div class="form-group">
                <label for="filter_date_debut">Date début</label>
                <input type="date" id="filter_date_debut" class="form-control">
            </div>
            <div class="form-group">
                <label for="filter_date_fin">Date fin</label>
                <input type="date" id="filter_date_fin" class="form-control">
            </div>
            <div class="form-group">
                <label for="filter_categorie">Catégorie</label>
                <select id="filter_categorie" class="form-control">
                    <option value="">Toutes les catégories</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo $category['id']; ?>">
                            <?php echo htmlspecialchars($category['nom']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="filter_search">Recherche</label>
                <input type="text" id="filter_search" class="form-control" placeholder="Description ou fournisseur">
            </div>
            <button class="btn btn-primary" onclick="filterExpenses()">
                <i class="fas fa-filter"></i> Filtrer
            </button>
        </div>
        
        <!-- Tableau des dépenses -->
        <div class="finance-table">
            <div class="finance-table-header">
                <h3 class="finance-table-title">
                    <i class="fas fa-list"></i> Liste des Dépenses
                </h3>
                <div class="finance-table-actions">
                    <span class="badge"><?php echo count($expenses); ?> dépenses</span>
                </div>
            </div>
            
            <div class="finance-table-responsive">
                <table id="expensesTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Description</th>
                            <th>Montant</th>
                            <th>Catégorie</th>
                            <th>Fournisseur</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($expenses)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Aucune dépense trouvée</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($expenses as $expense): ?>
                                <tr>
                                    <td><?php echo date('d/m/Y', strtotime($expense['date'])); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($expense['description']); ?></strong>
                                        <?php if ($expense['facture_numero']): ?>
                                            <br><small>Facture: <?php echo htmlspecialchars($expense['facture_numero']); ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="amount"><?php echo number_format($expense['montant'], 2); ?>€</td>
                                    <td>
                                        <?php if ($expense['categorie_nom']): ?>
                                            <span class="category-badge" style="background-color: <?php echo $expense['categorie_couleur']; ?>">
                                                <?php echo htmlspecialchars($expense['categorie_nom']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Non catégorisé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($expense['fournisseur'] ?? ''); ?></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline" onclick="editExpense(<?php echo htmlspecialchars(json_encode($expense)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" onclick="deleteExpense(<?php echo $expense['id']; ?>, '<?php echo htmlspecialchars($expense['description']); ?>')">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section Rapports -->
    <div id="reports-section" class="finance-section">
        <div class="finance-charts">
            <div class="finance-chart-container">
                <div class="finance-chart-header">
                    <h3 class="finance-chart-title">Dépenses par Mois</h3>
                </div>
                <div class="finance-chart-canvas">
                    <canvas id="monthlyExpensesChart"></canvas>
                </div>
            </div>
            <div class="finance-chart-container">
                <div class="finance-chart-header">
                    <h3 class="finance-chart-title">Recettes par Mois</h3>
                </div>
                <div class="finance-chart-canvas">
                    <canvas id="monthlyRevenueChart"></canvas>
                </div>
            </div>
            <div class="finance-chart-container">
                <div class="finance-chart-header">
                    <h3 class="finance-chart-title">Bénéfice net par Mois</h3>
                </div>
                <div class="finance-chart-canvas">
                    <canvas id="monthlyNetChart"></canvas>
                </div>
            </div>
            
            <div class="finance-chart-container">
                <div class="finance-chart-header">
                    <h3 class="finance-chart-title">Répartition par Catégorie</h3>
                </div>
                <div class="finance-chart-canvas">
                    <canvas id="categoryChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card" style="margin-top: var(--spacing-xl)">
            <div class="card-header">
                <h3 class="card-title">Top 5 articles les plus rentables</h3>
                <p class="card-subtitle">Calculé sur l'ensemble des ventes enregistrées</p>
            </div>
            <div class="card-body">
                <div class="finance-table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Article</th>
                                <th>Bénéfice (€)</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($reports['top_profitable'])): ?>
                            <tr><td colspan="2" class="text-center">Aucune donnée</td></tr>
                        <?php else: foreach ($reports['top_profitable'] as $row): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['nom_article']); ?></td>
                                <td><?php echo number_format((float)$row['benefice'], 2); ?>€</td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Budget -->
    <div id="budget-section" class="finance-section">
        <!-- Alertes budgétaires -->
        <?php if (!empty($budgetAlerts)): ?>
            <div style="margin-bottom: var(--spacing-lg);">
                <h3>Alertes Budgétaires</h3>
                <?php foreach ($budgetAlerts as $alert): ?>
                    <div class="budget-alert <?php echo $alert['statut']; ?>">
                        <i class="fas fa-<?php echo $alert['statut'] === 'normal' ? 'check-circle' : ($alert['statut'] === 'attention' ? 'exclamation-triangle' : 'times-circle'); ?>"></i>
                        <div>
                            <strong><?php echo htmlspecialchars($alert['categorie_nom'] ?? 'Budget global'); ?></strong> - 
                            <?php echo date('F Y', mktime(0, 0, 0, $alert['mois'], 1, $alert['annee'])); ?>:
                            <?php echo number_format($alert['montant_reel'], 2); ?>€ / <?php echo number_format($alert['montant_prevu'], 2); ?>€
                            (<?php echo $alert['difference'] >= 0 ? 'Reste: ' . number_format($alert['difference'], 2) . '€' : 'Dépassement: ' . number_format(abs($alert['difference']), 2) . '€'; ?>)
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
        <!-- Formulaire budget -->
        <div class="finance-form">
            <div class="finance-form-header">
                <h3 class="finance-form-title">
                    <i class="fas fa-calculator"></i> Nouveau Budget
                </h3>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_budget">
                
                <div class="finance-form-grid">
                    <div class="form-group">
                        <label for="budget_mois">Mois *</label>
                        <select id="budget_mois" name="mois" class="form-control" required>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $i == date('n') ? 'selected' : ''; ?>>
                                    <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget_annee">Année *</label>
                        <input type="number" id="budget_annee" name="annee" class="form-control" value="<?php echo date('Y'); ?>" min="2020" max="2030" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget_montant">Montant prévu (€) *</label>
                        <input type="number" id="budget_montant" name="montant_prevu" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="budget_categorie">Catégorie (optionnel)</label>
                        <select id="budget_categorie" name="categorie_id" class="form-control">
                            <option value="">Budget global</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="budget_notes">Notes</label>
                    <textarea id="budget_notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="finance-form-actions">
                    <button type="reset" class="btn btn-outline">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Enregistrer Budget
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Section Catégories -->
    <div id="categories-section" class="finance-section">
        <div class="finance-form">
            <div class="finance-form-header">
                <h3 class="finance-form-title">
                    <i class="fas fa-plus"></i> Nouvelle Catégorie
                </h3>
            </div>
            
            <form method="POST">
                <input type="hidden" name="action" value="add_category">
                
                <div class="finance-form-grid">
                    <div class="form-group">
                        <label for="category_nom">Nom *</label>
                        <input type="text" id="category_nom" name="nom" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="category_couleur">Couleur</label>
                        <input type="color" id="category_couleur" name="couleur" class="form-control" value="#3B82F6">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category_description">Description</label>
                    <textarea id="category_description" name="description" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="finance-form-actions">
                    <button type="reset" class="btn btn-outline">Annuler</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer Catégorie
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Liste des catégories -->
        <div class="finance-table">
            <div class="finance-table-header">
                <h3 class="finance-table-title">
                    <i class="fas fa-tags"></i> Catégories Existantes
                </h3>
            </div>
            
            <div class="finance-table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Description</th>
                            <th>Couleur</th>
                            <th>Créée le</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td>
                                    <span class="category-badge" style="background-color: <?php echo $category['couleur']; ?>">
                                        <?php echo htmlspecialchars($category['nom']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($category['description'] ?? ''); ?></td>
                                <td>
                                    <div style="width: 20px; height: 20px; background-color: <?php echo $category['couleur']; ?>; border-radius: 50%; display: inline-block;"></div>
                                    <?php echo $category['couleur']; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($category['created_at'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Section Export -->
    <div id="export-section" class="finance-section">
        <div class="finance-form">
            <div class="finance-form-header">
                <h3 class="finance-form-title">
                    <i class="fas fa-download"></i> Exporter les Données
                </h3>
            </div>
            
            <form id="exportForm">
                <div class="finance-form-grid">
                    <div class="form-group">
                        <label for="export_date_debut">Date début</label>
                        <input type="date" id="export_date_debut" name="date_debut" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="export_date_fin">Date fin</label>
                        <input type="date" id="export_date_fin" name="date_fin" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="export_categorie">Catégorie</label>
                        <select id="export_categorie" name="categorie_id" class="form-control">
                            <option value="">Toutes les catégories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="export_search">Recherche</label>
                        <input type="text" id="export_search" name="search" class="form-control" placeholder="Description ou fournisseur">
                    </div>
                </div>
                
                <div class="export-buttons">
                    <a href="#" class="export-btn pdf" onclick="exportData('pdf')">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                    <a href="#" class="export-btn excel" onclick="exportData('excel')">
                        <i class="fas fa-file-excel"></i> Export Excel/CSV
                    </a>
                </div>
            </form>
        </div>
        
        <div class="finance-empty-state">
            <div class="finance-empty-icon">
                <i class="fas fa-download"></i>
            </div>
            <h3 class="finance-empty-title">Exportation des Données</h3>
            <p class="finance-empty-text">
                Sélectionnez vos critères de filtrage ci-dessus, puis choisissez le format d'export souhaité.
                Les exports incluront toutes les dépenses correspondant à vos critères.
            </p>
        </div>
    </div>
</div>

<!-- Modal d'édition de dépense -->
<div id="editExpenseModal" class="modal finance-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Modifier la Dépense</h3>
            <button class="close-btn" onclick="closeModal('editExpenseModal')">&times;</button>
        </div>
        
        <form method="POST" id="editExpenseForm">
            <div class="modal-body">
                <input type="hidden" name="action" value="update_expense">
                <input type="hidden" name="expense_id" id="edit_expense_id">
                
                <div class="finance-form-grid">
                    <div class="form-group">
                        <label for="edit_description">Description *</label>
                        <input type="text" id="edit_description" name="description" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_montant">Montant (€) *</label>
                        <input type="number" id="edit_montant" name="montant" class="form-control" step="0.01" min="0.01" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_date">Date *</label>
                        <input type="date" id="edit_date" name="date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_categorie_id">Catégorie</label>
                        <select id="edit_categorie_id" name="categorie_id" class="form-control">
                            <option value="">Sélectionner une catégorie</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>">
                                    <?php echo htmlspecialchars($category['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_facture_numero">N° Facture</label>
                        <input type="text" id="edit_facture_numero" name="facture_numero" class="form-control">
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_fournisseur">Fournisseur</label>
                        <input type="text" id="edit_fournisseur" name="fournisseur" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_notes">Notes</label>
                    <textarea id="edit_notes" name="notes" class="form-control" rows="3"></textarea>
                </div>
            </div>
            
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editExpenseModal')">Annuler</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Mettre à jour
                </button>
            </div>
        </form>
    </div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Données pour les graphiques
const monthlyExpenses = <?php echo json_encode($reports['depenses_par_mois']); ?>;
const monthlyRevenue = <?php echo json_encode($reports['recettes_par_mois']); ?>;
const monthlyNet = <?php echo json_encode($reports['net_par_mois']); ?>;
const categoryData = <?php echo json_encode($reports['depenses_par_categorie']); ?>;

// Gestion des onglets
function showFinanceSection(sectionName) {
    // Masquer toutes les sections
    document.querySelectorAll('.finance-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Réinitialiser tous les onglets
    document.querySelectorAll('.finance-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Afficher la section sélectionnée
    const targetSection = document.getElementById(sectionName + '-section');
    if (targetSection) {
        targetSection.classList.add('active');
    }
    
    // Activer l'onglet sélectionné
    event.target.classList.add('active');
    
    // Initialiser les graphiques si on affiche les rapports
    if (sectionName === 'reports') {
        setTimeout(initCharts, 100);
    }
}

// Initialisation des graphiques
function initCharts() {
    const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];

    // Dépenses mensuelles
    const expensesCtx = document.getElementById('monthlyExpensesChart');
    if (expensesCtx && !expensesCtx.chart) {
        const monthNames = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        const labels = [];
        const values = [];
        
        for (let i = 1; i <= 12; i++) {
            labels.push(monthNames[i-1]);
            const found = monthlyExpenses.find(item => parseInt(item.mois) === i);
            values.push(found ? parseFloat(found.total) : 0);
        }
        
        expensesCtx.chart = new Chart(expensesCtx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Dépenses (€)',
                    data: values,
                    borderColor: '#10B981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
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
                        ticks: {
                            callback: function(value) {
                                return value + '€';
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Recettes mensuelles
    const revenueCtx = document.getElementById('monthlyRevenueChart');
    if (revenueCtx && !revenueCtx.chart) {
        const labels = [];
        const values = [];
        for (let i = 1; i <= 12; i++) {
            labels.push(monthNames[i-1]);
            const found = monthlyRevenue.find(item => parseInt(item.mois) === i);
            values.push(found ? parseFloat(found.total) : 0);
        }
        revenueCtx.chart = new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels,
                datasets: [{
                    label: 'Recettes (€)',
                    data: values,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: '#3B82F6',
                    borderWidth: 2,
                    borderRadius: 6,
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }

    // Bénéfice net mensuel
    const netCtx = document.getElementById('monthlyNetChart');
    if (netCtx && !netCtx.chart) {
        const labels = [];
        const values = [];
        for (let i = 1; i <= 12; i++) {
            labels.push(monthNames[i-1]);
            const found = monthlyNet.find(item => parseInt(item.mois) === i);
            values.push(found ? parseFloat(found.total) : 0);
        }
        netCtx.chart = new Chart(netCtx, {
            type: 'line',
            data: {
                labels,
                datasets: [{
                    label: 'Bénéfice net (€)',
                    data: values,
                    borderColor: '#F59E0B',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
        });
    }

    // Graphique des catégories
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && !categoryCtx.chart && categoryData.length > 0) {
        categoryCtx.chart = new Chart(categoryCtx, {
            type: 'doughnut',
            data: {
                labels: categoryData.map(item => item.nom || 'Non catégorisé'),
                datasets: [{
                    data: categoryData.map(item => parseFloat(item.total)),
                    backgroundColor: categoryData.map(item => item.couleur || '#6B7280'),
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }
}

// Validation du formulaire
document.getElementById('expenseForm').addEventListener('submit', function(e) {
    let isValid = true;
    let errors = [];
    
    // Validation de la description
    const description = document.getElementById('description').value.trim();
    if (!description) {
        errors.push('La description est requise');
        isValid = false;
    }
    
    // Validation du montant
    const montant = parseFloat(document.getElementById('montant').value);
    if (isNaN(montant) || montant <= 0) {
        errors.push('Le montant doit être un nombre positif');
        isValid = false;
    }
    
    // Validation de la date
    const date = document.getElementById('date').value;
    if (!date) {
        errors.push('La date est requise');
        isValid = false;
    }
    
    if (!isValid) {
        e.preventDefault();
        			showError('Erreurs de validation :\n' + errors.join('\n'));
        return false;
    }
    
    // Désactiver le bouton pour éviter les doubles soumissions
    const submitBtn = e.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
    
    // Réactiver après 5 secondes (sécurité)
    setTimeout(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
    }, 5000);
});

// Filtrage des dépenses
function filterExpenses() {
    const dateDebut = document.getElementById('filter_date_debut').value;
    const dateFin = document.getElementById('filter_date_fin').value;
    const categorie = document.getElementById('filter_categorie').value;
    const search = document.getElementById('filter_search').value;
    
    const params = new URLSearchParams();
    if (dateDebut) params.append('date_debut', dateDebut);
    if (dateFin) params.append('date_fin', dateFin);
    if (categorie) params.append('categorie_id', categorie);
    if (search) params.append('search', search);
    
    window.location.href = '?' + params.toString();
}

// Édition d'une dépense
function editExpense(expense) {
    document.getElementById('edit_expense_id').value = expense.id;
    document.getElementById('edit_description').value = expense.description;
    document.getElementById('edit_montant').value = expense.montant;
    document.getElementById('edit_date').value = expense.date;
    document.getElementById('edit_categorie_id').value = expense.categorie_id || '';
    document.getElementById('edit_facture_numero').value = expense.facture_numero || '';
    document.getElementById('edit_fournisseur').value = expense.fournisseur || '';
    document.getElementById('edit_notes').value = expense.notes || '';
    
    document.getElementById('editExpenseModal').classList.add('show');
}

// Suppression d'une dépense
function deleteExpense(id, description) {
    if (confirm('Êtes-vous sûr de vouloir supprimer la dépense "' + description + '" ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_expense">
            <input type="hidden" name="expense_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Export des données
function exportData(format) {
    const form = document.getElementById('exportForm');
    const formData = new FormData(form);
    const params = new URLSearchParams();
    
    for (let [key, value] of formData.entries()) {
        if (value) params.append(key, value);
    }
    
    params.append('export', format);
    window.location.href = '?' + params.toString();
}

// Gestion des modales
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('show');
}

// Fermeture des modales en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Initialiser les graphiques si on est sur l'onglet rapports
    if (document.getElementById('reports-section').classList.contains('active')) {
        initCharts();
    }
});
</script>

<?php
$content = ob_get_clean();

// Inclure le layout de base
include __DIR__ . '/layout/base.php';
?>