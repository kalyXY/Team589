<?php
/**
 * Module Alertes & Réapprovisionnement - Scolaria Team589
 * Gestion des alertes de stock, commandes et fournisseurs
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/components/stats-card.php';
require_once __DIR__ . '/components/data-table.php';

// Configuration de la page
$currentPage = 'alerts';
$pageTitle = 'Alertes & Réapprovisionnement';
$showSidebar = true;
$additionalCSS = ['assets/css/alerts.css'];
$additionalJS = [];
$bodyClass = 'alerts-page';

class AlertsManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Récupère les articles avec stock faible ou en rupture
     */
    public function getLowStockItems() {
        try {
            $sql = "SELECT 
                        s.id,
                        s.nom_article,
                        s.quantite,
                        COALESCE(s.seuil, s.seuil_alerte) AS seuil,
                        s.categorie,
                        COALESCE(s.prix_achat, 0) AS prix_achat,
                        COALESCE(s.prix_vente, 0) AS prix_vente,
                        CASE 
                            WHEN s.quantite = 0 THEN 'rupture'
                            WHEN s.quantite <= COALESCE(s.seuil, s.seuil_alerte) THEN 'faible'
                            ELSE 'normal'
                        END AS niveau_alerte,
                        CASE 
                            WHEN s.quantite = 0 THEN 'Rupture de stock'
                            WHEN s.quantite <= COALESCE(s.seuil, s.seuil_alerte) THEN 'Stock faible'
                            ELSE 'Stock normal'
                        END AS message_alerte
                    FROM stocks s
                    WHERE s.quantite <= COALESCE(s.seuil, s.seuil_alerte)
                    ORDER BY 
                        CASE 
                            WHEN s.quantite = 0 THEN 1
                            WHEN s.quantite <= COALESCE(s.seuil, s.seuil_alerte) THEN 2
                            ELSE 3
                        END,
                        s.quantite ASC";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getLowStockItems: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Met à jour le seuil d'alerte d'un article
     */
    public function updateThreshold($articleId, $newThreshold) {
        try {
            $sql = "UPDATE stocks SET seuil_alerte = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$newThreshold, $articleId]);
        } catch (PDOException $e) {
            error_log("Erreur updateThreshold: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Crée une nouvelle commande de réapprovisionnement
     */
    public function createOrder($articleId, $fournisseurId, $quantite, $prixUnitaire = 0, $notes = '') {
        try {
            // Si prix non fourni, utiliser le prix_achat du stock
            if ($prixUnitaire <= 0) {
                $stmt = $this->pdo->prepare('SELECT COALESCE(prix_achat,0) FROM stocks WHERE id = ?');
                $stmt->execute([$articleId]);
                $prixUnitaire = (float) ($stmt->fetchColumn() ?: 0);
            }
            $sql = "INSERT INTO commandes (article_id, fournisseur_id, quantite, prix_unitaire, notes, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            $createdBy = $_SESSION['username'] ?? 'system';
            return $stmt->execute([$articleId, $fournisseurId, $quantite, $prixUnitaire, $notes, $createdBy]);
        } catch (PDOException $e) {
            error_log("Erreur createOrder: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère toutes les commandes avec détails
     */
    public function listOrders($limit = 50) {
        try {
            $sql = "SELECT 
                        c.id,
                        c.quantite,
                        c.prix_unitaire,
                        c.statut,
                        c.date_commande,
                        c.date_livraison_prevue,
                        c.notes,
                        s.nom_article as article_nom,
                        s.categorie as article_categorie,
                        f.nom as fournisseur_nom,
                        f.contact as fournisseur_contact,
                        f.email as fournisseur_email,
                        f.telephone as fournisseur_telephone,
                        (c.quantite * COALESCE(NULLIF(c.prix_unitaire,0), s.prix_achat)) as montant_total
                    FROM commandes c
                    JOIN stocks s ON c.article_id = s.id
                    JOIN fournisseurs f ON c.fournisseur_id = f.id
                    ORDER BY c.date_commande DESC
                    LIMIT ?";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur listOrders: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Met à jour le statut d'une commande
     */
    public function updateOrderStatus($orderId, $newStatus) {
        try {
            $this->pdo->beginTransaction();

            // Récupérer ancien statut et infos commande
            $stmt = $this->pdo->prepare('SELECT statut, article_id, quantite FROM commandes WHERE id = ? FOR UPDATE');
            $stmt->execute([$orderId]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$order) {
                $this->pdo->rollBack();
                return false;
            }

            // Mettre à jour le statut
            $stmt = $this->pdo->prepare('UPDATE commandes SET statut = ? WHERE id = ?');
            $stmt->execute([$newStatus, $orderId]);

            // Si livraison et ancien statut différent, incrémenter le stock
            if ($newStatus === 'livrée' && $order['statut'] !== 'livrée') {
                $stmt = $this->pdo->prepare('UPDATE stocks SET quantite = quantite + :qte WHERE id = :article_id');
                $stmt->execute([
                    ':qte' => (int)$order['quantite'],
                    ':article_id' => (int)$order['article_id']
                ]);

                // Log mouvement
                $stmt = $this->pdo->prepare('INSERT INTO mouvements (article_id, action, details, utilisateur) VALUES (?, ?, ?, ?)');
                $stmt->execute([(int)$order['article_id'], 'ajout', 'Réception commande fournisseur (+'.$order['quantite'].')', $_SESSION['username'] ?? 'system']);
            }

            $this->pdo->commit();
            return true;
        } catch (PDOException $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            error_log("Erreur updateOrderStatus: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les fournisseurs
     */
    public function getSuppliers() {
        try {
            $sql = "SELECT * FROM fournisseurs ORDER BY nom ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getSuppliers: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Ajoute un nouveau fournisseur
     */
    public function addSupplier($nom, $contact, $email, $telephone, $adresse) {
        try {
            $sql = "INSERT INTO fournisseurs (nom, contact, email, telephone, adresse) VALUES (?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$nom, $contact, $email, $telephone, $adresse]);
        } catch (PDOException $e) {
            error_log("Erreur addSupplier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Met à jour un fournisseur
     */
    public function updateSupplier($id, $nom, $contact, $email, $telephone, $adresse) {
        try {
            $sql = "UPDATE fournisseurs SET nom = ?, contact = ?, email = ?, telephone = ?, adresse = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$nom, $contact, $email, $telephone, $adresse, $id]);
        } catch (PDOException $e) {
            error_log("Erreur updateSupplier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Supprime un fournisseur
     */
    public function deleteSupplier($id) {
        try {
            // Vérifier s'il y a des commandes liées
            $checkSql = "SELECT COUNT(*) FROM commandes WHERE fournisseur_id = ?";
            $checkStmt = $this->pdo->prepare($checkSql);
            $checkStmt->execute([$id]);
            
            if ($checkStmt->fetchColumn() > 0) {
                return false; // Ne peut pas supprimer, il y a des commandes liées
            }
            
            $sql = "DELETE FROM fournisseurs WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Erreur deleteSupplier: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Récupère tous les articles pour les sélecteurs
     */
    public function getAllArticles() {
        try {
            $sql = "SELECT id, nom_article, quantite, COALESCE(seuil, seuil_alerte) AS seuil, categorie FROM stocks ORDER BY nom_article ASC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getAllArticles: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les statistiques des alertes
     */
    public function getAlertStats() {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_articles,
                        SUM(CASE WHEN quantite = 0 THEN 1 ELSE 0 END) as ruptures,
                        SUM(CASE WHEN quantite > 0 AND quantite <= seuil_alerte THEN 1 ELSE 0 END) as stock_faible,
                        COUNT(CASE WHEN quantite <= seuil_alerte THEN 1 END) as total_alertes
                    FROM stocks";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getAlertStats: " . $e->getMessage());
            return ['total_articles' => 0, 'ruptures' => 0, 'stock_faible' => 0, 'total_alertes' => 0];
        }
    }
}

// Initialisation
$alertsManager = new AlertsManager();
$message = '';
$messageType = '';

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'update_threshold':
            $articleId = (int)$_POST['article_id'];
            $newThreshold = (int)$_POST['new_threshold'];
            
            if ($alertsManager->updateThreshold($articleId, $newThreshold)) {
                $message = "Seuil d'alerte mis à jour avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de la mise à jour du seuil.";
                $messageType = 'error';
            }
            break;
            
        case 'create_order':
            $articleId = (int)$_POST['article_id'];
            $fournisseurId = (int)$_POST['fournisseur_id'];
            $quantite = (int)$_POST['quantite'];
            $prixUnitaire = (float)$_POST['prix_unitaire'];
            $notes = $_POST['notes'] ?? '';
            
            if ($alertsManager->createOrder($articleId, $fournisseurId, $quantite, $prixUnitaire, $notes)) {
                $message = "Commande créée avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de la création de la commande.";
                $messageType = 'error';
            }
            break;
            
        case 'update_order_status':
            $orderId = (int)$_POST['order_id'];
            $newStatus = $_POST['new_status'];
            
            if ($alertsManager->updateOrderStatus($orderId, $newStatus)) {
                $message = "Statut de la commande mis à jour.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de la mise à jour du statut.";
                $messageType = 'error';
            }
            break;
            
        case 'add_supplier':
            $nom = $_POST['nom'];
            $contact = $_POST['contact'];
            $email = $_POST['email'];
            $telephone = $_POST['telephone'];
            $adresse = $_POST['adresse'];
            
            if ($alertsManager->addSupplier($nom, $contact, $email, $telephone, $adresse)) {
                $message = "Fournisseur ajouté avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de l'ajout du fournisseur.";
                $messageType = 'error';
            }
            break;
            
        case 'update_supplier':
            $id = (int)$_POST['supplier_id'];
            $nom = $_POST['nom'];
            $contact = $_POST['contact'];
            $email = $_POST['email'];
            $telephone = $_POST['telephone'];
            $adresse = $_POST['adresse'];
            
            if ($alertsManager->updateSupplier($id, $nom, $contact, $email, $telephone, $adresse)) {
                $message = "Fournisseur mis à jour avec succès.";
                $messageType = 'success';
            } else {
                $message = "Erreur lors de la mise à jour du fournisseur.";
                $messageType = 'error';
            }
            break;
            
        case 'delete_supplier':
            $id = (int)$_POST['supplier_id'];
            
            if ($alertsManager->deleteSupplier($id)) {
                $message = "Fournisseur supprimé avec succès.";
                $messageType = 'success';
            } else {
                $message = "Impossible de supprimer le fournisseur (commandes liées).";
                $messageType = 'error';
            }
            break;
    }
}

// Récupération des données
$lowStockItems = $alertsManager->getLowStockItems();
$orders = $alertsManager->listOrders();
$suppliers = $alertsManager->getSuppliers();
$articles = $alertsManager->getAllArticles();
$stats = $alertsManager->getAlertStats();
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

<!-- Statistiques -->
<?php
$alertStats = [
    [
        'title' => 'Alertes Actives',
        'value' => $stats['total_alertes'],
        'icon' => 'fas fa-exclamation-triangle',
        'type' => 'warning',
        'subtitle' => 'Articles nécessitant attention'
    ],
    [
        'title' => 'Ruptures de Stock',
        'value' => $stats['ruptures'],
        'icon' => 'fas fa-times-circle',
        'type' => 'danger',
        'subtitle' => 'Articles en rupture totale'
    ],
    [
        'title' => 'Stocks Faibles',
        'value' => $stats['stock_faible'],
        'icon' => 'fas fa-arrow-down',
        'type' => 'warning',
        'subtitle' => 'Articles sous le seuil'
    ],
    [
        'title' => 'Commandes Totales',
        'value' => count($orders),
        'icon' => 'fas fa-shopping-cart',
        'type' => 'success',
        'subtitle' => 'Commandes en cours'
    ]
];
renderStatsGrid($alertStats);
?>

<!-- Navigation par onglets -->
<div class="card">
    <div class="tab-navigation">
        <button class="tab-button active" id="alertsTab" onclick="showSection('alerts')">
            <i class="fas fa-exclamation-triangle"></i> Alertes en Cours
        </button>
        <button class="tab-button" id="ordersTab" onclick="showSection('orders')">
            <i class="fas fa-shopping-cart"></i> Commandes
        </button>
        <button class="tab-button" id="suppliersTab" onclick="showSection('suppliers')">
            <i class="fas fa-truck"></i> Fournisseurs
        </button>
    </div>

    <!-- Section Alertes -->
    <div id="alerts-section" class="tab-content active">
        <?php if (empty($lowStockItems)): ?>
            <div class="empty-state">
                <i class="fas fa-check-circle empty-state-icon"></i>
                <h3>Aucune alerte active</h3>
                <p>Tous les stocks sont au-dessus du seuil d'alerte.</p>
            </div>
        <?php else: ?>
            <div class="alerts-grid">
                <?php foreach ($lowStockItems as $item): ?>
                    <div class="alert-card <?php echo $item['niveau_alerte'] === 'rupture' ? 'danger' : 'warning'; ?>">
                        <div class="alert-card-header">
                            <h4 class="alert-card-title"><?php echo htmlspecialchars($item['nom_article']); ?></h4>
                            <span class="alert-badge <?php echo $item['niveau_alerte'] === 'rupture' ? 'danger' : 'warning'; ?>">
                                <?php echo $item['niveau_alerte'] === 'rupture' ? 'RUPTURE' : 'FAIBLE'; ?>
                            </span>
                        </div>
                        <div class="alert-card-content">
                            <div>
                                <div class="alert-quantity <?php echo $item['niveau_alerte'] === 'rupture' ? 'danger' : 'warning'; ?>">
                                    <?php echo $item['quantite']; ?>
                                </div>
                                <div class="alert-threshold">
                                    Seuil: <?php echo (int)$item['seuil']; ?>
                                </div>
                            </div>
                            <div class="alert-details">
                                <div><strong>Catégorie:</strong> <?php echo htmlspecialchars($item['categorie']); ?></div>
                                <div><strong>Prix achat:</strong> <?php echo number_format((float)$item['prix_achat'], 2, ',', ' '); ?>€</div>
                                <div><strong>Prix vente:</strong> <?php echo number_format((float)$item['prix_vente'], 2, ',', ' '); ?>€</div>
                                <?php $perte = max(0, (float)$item['prix_vente'] - (float)$item['prix_achat']) * (int)$item['seuil']; ?>
                                <div><strong>Perte potentielle:</strong> <?php echo number_format($perte, 2, ',', ' '); ?>€</div>
                            </div>
                        </div>
                        
                        <div class="alert-actions">
                            <button class="btn btn-primary btn-sm" onclick="openOrderModal(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars($item['nom_article']); ?>')">
                                <i class="fas fa-plus"></i> Commander
                            </button>
                            <button class="btn btn-outline btn-sm" onclick="openThresholdModal(<?php echo $item['id']; ?>, <?php echo (int)$item['seuil']; ?>, '<?php echo htmlspecialchars($item['nom_article']); ?>')">
                                <i class="fas fa-cog"></i> Modifier Seuil
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

        <!-- Section Commandes -->
        <div id="orders-section" class="tab-content">
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-shopping-cart"></i> Commandes de Réapprovisionnement</h2>
                    <button class="btn btn-primary" onclick="openOrderModal()">
                        <i class="fas fa-plus"></i> Nouvelle Commande
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Article</th>
                                <th>Fournisseur</th>
                                <th>Quantité</th>
                                <th>Prix Unit.</th>
                                <th>Total</th>
                                <th>Statut</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Aucune commande trouvée</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['article_nom']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($order['article_categorie']); ?></small>
                                        </td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($order['fournisseur_nom']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($order['fournisseur_contact']); ?></small>
                                        </td>
                                        <td><?php echo $order['quantite']; ?></td>
                                        <td><?php echo number_format($order['prix_unitaire'], 2); ?>€</td>
                                        <td><?php echo number_format($order['montant_total'], 2); ?>€</td>
                                        <td>
                                            <span class="status-badge <?php echo str_replace(' ', '-', $order['statut']); ?>">
                                                <?php echo ucfirst($order['statut']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($order['date_commande'])); ?></td>
                                        <td>
                                            <select onchange="updateOrderStatus(<?php echo $order['id']; ?>, this.value)" class="form-control" style="width: auto; display: inline-block;">
                                                <option value="en attente" <?php echo $order['statut'] === 'en attente' ? 'selected' : ''; ?>>En attente</option>
                                                <option value="validée" <?php echo $order['statut'] === 'validée' ? 'selected' : ''; ?>>Validée</option>
                                                <option value="livrée" <?php echo $order['statut'] === 'livrée' ? 'selected' : ''; ?>>Livrée</option>
                                                <option value="annulée" <?php echo $order['statut'] === 'annulée' ? 'selected' : ''; ?>>Annulée</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section Fournisseurs -->
        <div id="suppliers-section" class="tab-content">
            <div class="data-table">
                <div class="table-header">
                    <h2><i class="fas fa-truck"></i> Gestion des Fournisseurs</h2>
                    <button class="btn btn-primary" onclick="openSupplierModal()">
                        <i class="fas fa-plus"></i> Nouveau Fournisseur
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom</th>
                                <th>Contact</th>
                                <th>Email</th>
                                <th>Téléphone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($suppliers)): ?>
                                <tr>
                                    <td colspan="6" class="text-center">Aucun fournisseur trouvé</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($suppliers as $supplier): ?>
                                    <tr>
                                        <td>#<?php echo $supplier['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($supplier['nom']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($supplier['contact']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                        <td><?php echo htmlspecialchars($supplier['telephone']); ?></td>
                                        <td>
                                            <button class="btn btn-secondary btn-sm" onclick="editSupplier(<?php echo htmlspecialchars(json_encode($supplier)); ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button class="btn btn-warning btn-sm" onclick="deleteSupplier(<?php echo $supplier['id']; ?>, '<?php echo htmlspecialchars($supplier['nom']); ?>')">
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
    </div>  
  <!-- Modales -->
    
    <!-- Modal Nouvelle Commande -->
    <div id="orderModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-plus"></i> Nouvelle Commande</h3>
                <button class="close-btn" onclick="closeModal('orderModal')">&times;</button>
            </div>
            
            <form method="POST" onsubmit="return validateOrderForm()">
                <input type="hidden" name="action" value="create_order">
                <input type="hidden" id="order_article_id" name="article_id">
                
                <div class="form-group">
                    <label for="order_article_select">Article *</label>
                    <select id="order_article_select" name="article_id" class="form-control" required>
                        <option value="">Sélectionner un article</option>
                        <?php foreach ($articles as $article): ?>
                            <option value="<?php echo $article['id']; ?>" 
                                    data-stock="<?php echo $article['quantite']; ?>"
                                    data-seuil="<?php echo $article['seuil_alerte']; ?>">
                                <?php echo htmlspecialchars($article['nom']); ?> 
                                (Stock: <?php echo $article['quantite']; ?>, Seuil: <?php echo $article['seuil_alerte']; ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="order_supplier">Fournisseur *</label>
                    <select id="order_supplier" name="fournisseur_id" class="form-control" required>
                        <option value="">Sélectionner un fournisseur</option>
                        <?php foreach ($suppliers as $supplier): ?>
                            <option value="<?php echo $supplier['id']; ?>">
                                <?php echo htmlspecialchars($supplier['nom']); ?> - <?php echo htmlspecialchars($supplier['contact']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="order_quantity">Quantité *</label>
                    <input type="number" id="order_quantity" name="quantite" class="form-control" min="1" required>
                    <div id="quantity_error" class="error-message" style="display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label for="order_price">Prix unitaire (€)</label>
                    <input type="number" id="order_price" name="prix_unitaire" class="form-control" min="0" step="0.01" value="0">
                </div>
                
                <div class="form-group">
                    <label for="order_notes">Notes</label>
                    <textarea id="order_notes" name="notes" class="form-control" rows="3" placeholder="Notes optionnelles..."></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Créer la Commande
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('orderModal')">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Modifier Seuil -->
    <div id="thresholdModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-cog"></i> Modifier le Seuil d'Alerte</h3>
                <button class="close-btn" onclick="closeModal('thresholdModal')">&times;</button>
            </div>
            
            <form method="POST" onsubmit="return validateThresholdForm()">
                <input type="hidden" name="action" value="update_threshold">
                <input type="hidden" id="threshold_article_id" name="article_id">
                
                <div class="form-group">
                    <label>Article</label>
                    <p id="threshold_article_name" style="font-weight: bold; color: var(--dark-gray);"></p>
                </div>
                
                <div class="form-group">
                    <label for="new_threshold">Nouveau seuil d'alerte *</label>
                    <input type="number" id="new_threshold" name="new_threshold" class="form-control" min="0" required>
                    <div id="threshold_error" class="error-message" style="display: none;"></div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Mettre à Jour
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('thresholdModal')">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Fournisseur -->
    <div id="supplierModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 id="supplier_modal_title"><i class="fas fa-plus"></i> Nouveau Fournisseur</h3>
                <button class="close-btn" onclick="closeModal('supplierModal')">&times;</button>
            </div>
            
            <form method="POST" onsubmit="return validateSupplierForm()">
                <input type="hidden" id="supplier_action" name="action" value="add_supplier">
                <input type="hidden" id="supplier_id" name="supplier_id">
                
                <div class="form-group">
                    <label for="supplier_nom">Nom de l'entreprise *</label>
                    <input type="text" id="supplier_nom" name="nom" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="supplier_contact">Personne de contact</label>
                    <input type="text" id="supplier_contact" name="contact" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="supplier_email">Email</label>
                    <input type="email" id="supplier_email" name="email" class="form-control">
                    <div id="email_error" class="error-message" style="display: none;"></div>
                </div>
                
                <div class="form-group">
                    <label for="supplier_telephone">Téléphone</label>
                    <input type="tel" id="supplier_telephone" name="telephone" class="form-control">
                </div>
                
                <div class="form-group">
                    <label for="supplier_adresse">Adresse</label>
                    <textarea id="supplier_adresse" name="adresse" class="form-control" rows="3"></textarea>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> <span id="supplier_submit_text">Ajouter</span>
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('supplierModal')">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Gestion des onglets
        function showSection(sectionName) {
            // Masquer toutes les sections
            const sections = document.querySelectorAll('.alerts-section');
            sections.forEach(section => section.classList.remove('active'));
            
            // Désactiver tous les onglets
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Afficher la section sélectionnée
            document.getElementById(sectionName + '-section').classList.add('active');
            
            // Activer l'onglet correspondant
            event.target.classList.add('active');
        }

        // Gestion des modales
        function openModal(modalId) {
            document.getElementById(modalId).classList.add('show');
            document.body.style.overflow = 'hidden';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('show');
            document.body.style.overflow = 'auto';
            
            // Reset des formulaires
            const form = document.querySelector(`#${modalId} form`);
            if (form) {
                form.reset();
                // Masquer les messages d'erreur
                const errors = form.querySelectorAll('.error-message');
                errors.forEach(error => error.style.display = 'none');
                // Retirer les classes d'erreur
                const inputs = form.querySelectorAll('.form-control.error');
                inputs.forEach(input => input.classList.remove('error'));
            }
        }

        // Fermer les modales en cliquant à l'extérieur
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        }

        // Modal Commande
        function openOrderModal(articleId = null, articleName = null) {
            if (articleId) {
                document.getElementById('order_article_id').value = articleId;
                document.getElementById('order_article_select').value = articleId;
                document.getElementById('order_article_select').disabled = true;
            } else {
                document.getElementById('order_article_select').disabled = false;
            }
            openModal('orderModal');
        }

        // Modal Seuil
        function openThresholdModal(articleId, currentThreshold, articleName) {
            document.getElementById('threshold_article_id').value = articleId;
            document.getElementById('new_threshold').value = currentThreshold;
            document.getElementById('threshold_article_name').textContent = articleName;
            openModal('thresholdModal');
        }

        // Modal Fournisseur
        function openSupplierModal() {
            document.getElementById('supplier_modal_title').innerHTML = '<i class="fas fa-plus"></i> Nouveau Fournisseur';
            document.getElementById('supplier_action').value = 'add_supplier';
            document.getElementById('supplier_submit_text').textContent = 'Ajouter';
            openModal('supplierModal');
        }

        function editSupplier(supplier) {
            document.getElementById('supplier_modal_title').innerHTML = '<i class="fas fa-edit"></i> Modifier Fournisseur';
            document.getElementById('supplier_action').value = 'update_supplier';
            document.getElementById('supplier_id').value = supplier.id;
            document.getElementById('supplier_nom').value = supplier.nom;
            document.getElementById('supplier_contact').value = supplier.contact || '';
            document.getElementById('supplier_email').value = supplier.email || '';
            document.getElementById('supplier_telephone').value = supplier.telephone || '';
            document.getElementById('supplier_adresse').value = supplier.adresse || '';
            document.getElementById('supplier_submit_text').textContent = 'Modifier';
            openModal('supplierModal');
        }

        function deleteSupplier(id, name) {
            if (confirm(`Êtes-vous sûr de vouloir supprimer le fournisseur "${name}" ?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_supplier">
                    <input type="hidden" name="supplier_id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Mise à jour du statut des commandes
        function updateOrderStatus(orderId, newStatus) {
            if (newStatus) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="update_order_status">
                    <input type="hidden" name="order_id" value="${orderId}">
                    <input type="hidden" name="new_status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Validations
        function validateOrderForm() {
            let isValid = true;
            
            const quantity = document.getElementById('order_quantity');
            const quantityError = document.getElementById('quantity_error');
            
            // Validation quantité
            if (!quantity.value || quantity.value <= 0) {
                quantity.classList.add('error');
                quantityError.textContent = 'La quantité doit être supérieure à 0';
                quantityError.style.display = 'block';
                isValid = false;
            } else {
                quantity.classList.remove('error');
                quantityError.style.display = 'none';
            }
            
            return isValid;
        }

        function validateThresholdForm() {
            let isValid = true;
            
            const threshold = document.getElementById('new_threshold');
            const thresholdError = document.getElementById('threshold_error');
            
            // Validation seuil
            if (!threshold.value || threshold.value < 0) {
                threshold.classList.add('error');
                thresholdError.textContent = 'Le seuil doit être supérieur ou égal à 0';
                thresholdError.style.display = 'block';
                isValid = false;
            } else {
                threshold.classList.remove('error');
                thresholdError.style.display = 'none';
            }
            
            return isValid;
        }

        function validateSupplierForm() {
            let isValid = true;
            
            const email = document.getElementById('supplier_email');
            const emailError = document.getElementById('email_error');
            
            // Validation email (si rempli)
            if (email.value && !isValidEmail(email.value)) {
                email.classList.add('error');
                emailError.textContent = 'Format d\'email invalide';
                emailError.style.display = 'block';
                isValid = false;
            } else {
                email.classList.remove('error');
                emailError.style.display = 'none';
            }
            
            return isValid;
        }

        function isValidEmail(email) {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return emailRegex.test(email);
        }

        // Auto-refresh des alertes (optionnel)
        function refreshAlerts() {
            // Recharger la page toutes les 5 minutes pour mettre à jour les alertes
            setTimeout(() => {
                window.location.reload();
            }, 300000); // 5 minutes
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Démarrer le refresh automatique
            refreshAlerts();
            
            // Ajouter des écouteurs d'événements pour la validation en temps réel
            const quantityInput = document.getElementById('order_quantity');
            if (quantityInput) {
                quantityInput.addEventListener('input', function() {
                    if (this.value > 0) {
                        this.classList.remove('error');
                        document.getElementById('quantity_error').style.display = 'none';
                    }
                });
            }
            
            const thresholdInput = document.getElementById('new_threshold');
            if (thresholdInput) {
                thresholdInput.addEventListener('input', function() {
                    if (this.value >= 0) {
                        this.classList.remove('error');
                        document.getElementById('threshold_error').style.display = 'none';
                    }
                });
            }
            
            const emailInput = document.getElementById('supplier_email');
            if (emailInput) {
                emailInput.addEventListener('input', function() {
                    if (!this.value || isValidEmail(this.value)) {
                        this.classList.remove('error');
                        document.getElementById('email_error').style.display = 'none';
                    }
                });
            }
        });
    </script>

<?php
$content = ob_get_clean();

// Inclure le layout de base
include __DIR__ . '/layout/base.php';
?>