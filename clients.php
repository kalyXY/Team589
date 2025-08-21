<?php /*
/**
 * Scolaria - Clients
 * Gestion clients et historique d'achats
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Session + contrôle d'accès
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
    header('Location: ' . BASE_URL . 'login.php');
    exit;
}

$role = (string)($_SESSION['role'] ?? '');
$canWrite = in_array($role, ['admin', 'gestionnaire'], true);

// Endpoints AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json; charset=utf-8');
    $pdo = Database::getConnection();

    try {
        if ($_GET['ajax'] === 'search') {
            $q = trim((string)($_GET['q'] ?? ''));
            $sql = "SELECT id, first_name, last_name, phone, email, address, created_at
                    FROM clients
                    WHERE (first_name LIKE :q OR last_name LIKE :q OR phone LIKE :q)
                    ORDER BY last_name ASC, first_name ASC
                    LIMIT 100";
            $stmt = $pdo->prepare($sql);
            $like = '%' . $q . '%';
            $stmt->bindValue(':q', $like, PDO::PARAM_STR);
            $stmt->execute();
            echo json_encode($stmt->fetchAll() ?: []);
            exit;
        }

        if ($_GET['ajax'] === 'history') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) { echo json_encode([]); exit; }
            $sql = "SELECT sa.id AS sale_id, sa.created_at AS sale_date, sa.total AS sale_total,
                           st.nom_article AS product_name, si.quantity, si.price
                    FROM sales sa
                    LEFT JOIN sales_items si ON si.sale_id = sa.id
                    LEFT JOIN stocks st ON st.id = si.product_id
                    WHERE sa.client_id = :cid
                    ORDER BY sa.created_at DESC, sa.id DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':cid', $id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetchAll() ?: []);
            exit;
        }

        if ($_GET['ajax'] === 'get') {
            $id = (int)($_GET['id'] ?? 0);
            if ($id <= 0) { echo json_encode(null); exit; }
            $stmt = $pdo->prepare('SELECT id, first_name, last_name, phone, email, address, created_at FROM clients WHERE id = :id');
            $stmt->bindValue(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            echo json_encode($stmt->fetch() ?: null);
            exit;
        }

        echo json_encode(['error' => 'action inconnue']);
        exit;
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['error' => 'server_error']);
        exit;
    }
}

// Actions POST (CRUD)
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canWrite) {
    $action = (string)($_POST['action'] ?? '');
    $pdo = Database::getConnection();

    try {
        if ($action === 'add') {
            $first = trim((string)($_POST['first_name'] ?? ''));
            $last = trim((string)($_POST['last_name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));

            if ($first === '' || $last === '') { throw new RuntimeException('Nom et prénom requis'); }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Email invalide'); }

            $stmt = $pdo->prepare('INSERT INTO clients (first_name, last_name, phone, email, address) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([$first, $last, $phone, $email, $address]);
            $message = 'Client ajouté avec succès';
            $messageType = 'success';
        } elseif ($action === 'update') {
            $id = (int)($_POST['id'] ?? 0);
            $first = trim((string)($_POST['first_name'] ?? ''));
            $last = trim((string)($_POST['last_name'] ?? ''));
            $phone = trim((string)($_POST['phone'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $address = trim((string)($_POST['address'] ?? ''));
            if ($id <= 0) { throw new RuntimeException('Client invalide'); }
            if ($first === '' || $last === '') { throw new RuntimeException('Nom et prénom requis'); }
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) { throw new RuntimeException('Email invalide'); }

            $stmt = $pdo->prepare('UPDATE clients SET first_name = ?, last_name = ?, phone = ?, email = ?, address = ? WHERE id = ?');
            $stmt->execute([$first, $last, $phone, $email, $address, $id]);
            $message = 'Client modifié avec succès';
            $messageType = 'success';
        } elseif ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { throw new RuntimeException('Client invalide'); }
            // Vérifier ventes liées
            $checkStmt = $pdo->prepare('SELECT COUNT(*) FROM sales WHERE client_id = ?');
            $checkStmt->execute([$id]);
            $cnt = (int)$checkStmt->fetchColumn();
            if ($cnt > 0) {
                throw new RuntimeException('Impossible de supprimer: ventes associées au client');
            }
            $stmt = $pdo->prepare('DELETE FROM clients WHERE id = ?');
            $stmt->execute([$id]);
            $message = 'Client supprimé';
            $messageType = 'success';
        }
    } catch (Throwable $e) {
        $message = 'Erreur: ' . $e->getMessage();
        $messageType = 'error';
    }
}

// Charger la liste initiale
try {
    $pdo = Database::getConnection();
    $clientsStmt = $pdo->query('SELECT id, first_name, last_name, phone, email, address, created_at FROM clients ORDER BY last_name ASC, first_name ASC LIMIT 200');
    $clients = $clientsStmt->fetchAll() ?: [];
} catch (Throwable $e) {
    $clients = [];
}

// Configuration de la page
$currentPage = 'clients';
$pageTitle = 'Clients';
$showSidebar = true;
$additionalCSS = ['assets/css/clients.css'];

// Début du contenu HTML
ob_start();
*/ ?>

<div class="clients-page">
    <?php if ($message): ?>
        <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
            <div><?php echo htmlspecialchars($message); ?></div>
        </div>
    <?php endif; ?>

    <div class="clients-header">
        <div class="clients-search">
            <input type="text" id="searchInput" class="form-control" placeholder="Rechercher par nom ou téléphone">
        </div>
        <?php if ($canWrite): ?>
        <button class="btn btn-primary" onclick="openClientModal()"><i class="fas fa-user-plus"></i> Nouveau client</button>
        <?php endif; ?>
    </div>

    <div class="clients-table card">
        <div class="card-header">
            <h3 class="card-title">Liste des clients</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="clientsTable">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Téléphone</th>
                            <th>Email</th>
                            <th>Adresse</th>
                            <th>Créé le</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="clientsBody">
                        <?php foreach ($clients as $c): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($c['last_name'] . ' ' . $c['first_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($c['phone'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['email'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['address'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($c['created_at']); ?></td>
                                <td class="actions">
                                    <button class="btn btn-sm" onclick="viewHistory(<?php echo (int)$c['id']; ?>)"><i class="fas fa-history"></i></button>
                                    <?php if ($canWrite): ?>
                                    <button class="btn btn-sm btn-warning" onclick='editClient(<?php echo json_encode($c, JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG); ?>)'><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-sm btn-danger" onclick="deleteClient(<?php echo (int)$c['id']; ?>, '<?php echo htmlspecialchars($c['last_name'] . ' ' . $c['first_name'], ENT_QUOTES); ?>')"><i class="fas fa-trash"></i></button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Client -->
    <div id="clientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="clientModalTitle"><i class="fas fa-user"></i> Nouveau client</h3>
                <button class="modal-close" onclick="closeModal('clientModal')">&times;</button>
            </div>
            <form method="POST" id="clientForm">
                <div class="modal-body">
                    <input type="hidden" name="action" id="clientAction" value="add">
                    <input type="hidden" name="id" id="clientId" value="">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Prénom *</label>
                            <input type="text" name="first_name" id="first_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Nom *</label>
                            <input type="text" name="last_name" id="last_name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label>Téléphone</label>
                            <input type="text" name="phone" id="phone" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" id="email" class="form-control">
                        </div>
                        <div class="form-group" style="grid-column: 1 / -1;">
                            <label>Adresse</label>
                            <textarea name="address" id="address" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-ghost" onclick="closeModal('clientModal')">Annuler</button>
                    <button type="submit" class="btn btn-primary" id="clientSubmit"><i class="fas fa-save"></i> Enregistrer</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Historique -->
    <div id="historyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title"><i class="fas fa-history"></i> Historique d'achats</h3>
                <button class="modal-close" onclick="closeModal('historyModal')">&times;</button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Ticket</th>
                                <th>Date</th>
                                <th>Article</th>
                                <th>Quantité</th>
                                <th>Prix</th>
                                <th>Total ligne</th>
                                <th>Total ticket</th>
                            </tr>
                        </thead>
                        <tbody id="historyBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
async function searchClients() {
    const q = document.getElementById('searchInput').value.trim();
    const res = await fetch('clients.php?ajax=search&q=' + encodeURIComponent(q));
    const data = await res.json();
    const body = document.getElementById('clientsBody');
    body.innerHTML = '';
    (data || []).forEach(c => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><strong>${escapeHtml(c.last_name || '')} ${escapeHtml(c.first_name || '')}</strong></td>
            <td>${escapeHtml(c.phone || '')}</td>
            <td>${escapeHtml(c.email || '')}</td>
            <td>${escapeHtml(c.address || '')}</td>
            <td>${escapeHtml(c.created_at || '')}</td>
            <td class="actions">
                <button class="btn btn-sm" onclick="viewHistory(${c.id})"><i class="fas fa-history"></i></button>
                <?php if ($canWrite): ?>
                <button class="btn btn-sm btn-warning" onclick="editClientById(${c.id})"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteClient(${c.id}, '__NAME__')"><i class="fas fa-trash"></i></button>
                <?php endif; ?>
            </td>`
            .replace('__NAME__', escapeHtml((c.last_name || '') + ' ' + (c.first_name || '')));
        body.appendChild(tr);
    });
}

function openClientModal() {
    document.getElementById('clientModalTitle').innerHTML = '<i class="fas fa-user"></i> Nouveau client';
    document.getElementById('clientAction').value = 'add';
    document.getElementById('clientForm').reset();
    document.getElementById('clientId').value = '';
    openModal('clientModal');
}

function editClient(c) {
    document.getElementById('clientModalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Modifier client';
    document.getElementById('clientAction').value = 'update';
    document.getElementById('clientId').value = c.id;
    document.getElementById('first_name').value = c.first_name || '';
    document.getElementById('last_name').value = c.last_name || '';
    document.getElementById('phone').value = c.phone || '';
    document.getElementById('email').value = c.email || '';
    document.getElementById('address').value = c.address || '';
    openModal('clientModal');
}

async function editClientById(id) {
    try {
        const res = await fetch('clients.php?ajax=get&id=' + encodeURIComponent(id));
        const data = await res.json();
        if (!data) { alert('Client introuvable'); return; }
        editClient(data);
    } catch (e) {
        alert('Erreur lors du chargement du client');
    }
}

function deleteClient(id, name) {
    if (!confirm('Supprimer le client: ' + name + ' ?')) return;
    const form = document.createElement('form');
    form.method = 'POST';
    form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                     '<input type="hidden" name="id" value="' + id + '">';
    document.body.appendChild(form);
    form.submit();
}

async function viewHistory(id) {
    const res = await fetch('clients.php?ajax=history&id=' + encodeURIComponent(id));
    const data = await res.json();
    const body = document.getElementById('historyBody');
    body.innerHTML = '';
    (data || []).forEach(r => {
        const tr = document.createElement('tr');
        const lineTotal = (parseFloat(r.quantity || 0) * parseFloat(r.price || 0)).toFixed(2);
        tr.innerHTML = `
            <td>#${r.sale_id}</td>
            <td>${escapeHtml(r.sale_date || '')}</td>
            <td>${escapeHtml(r.product_name || '')}</td>
            <td>${r.quantity || 0}</td>
            <td>${(parseFloat(r.price || 0)).toFixed(2)} €</td>
            <td>${lineTotal} €</td>
            <td>${(parseFloat(r.sale_total || 0)).toFixed(2)} €</td>
        `;
        body.appendChild(tr);
    });
    openModal('historyModal');
}

function openModal(id) { document.getElementById(id).classList.add('show'); document.body.style.overflow='hidden'; }
function closeModal(id) { document.getElementById(id).classList.remove('show'); document.body.style.overflow='auto'; }

function escapeHtml(str) {
    return String(str).replace(/[&<>'"]/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','\'':'&#39;','"':'&quot;'}[s]));
}

document.addEventListener('DOMContentLoaded', () => {
    const input = document.getElementById('searchInput');
    if (input) {
        input.addEventListener('input', () => { clearTimeout(window.__to); window.__to = setTimeout(searchClients, 300); });
    }
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>

<?php
/**
 * SCOLARIA - Module Gestion des Clients
 * Mama Sophie School Supplies - Gestion des clients (parents, élèves, acheteurs)
 * Team589
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

// Configuration de la page
$currentPage = 'clients';
$pageTitle = 'Gestion des Clients';
$showSidebar = true;
$additionalCSS = ['assets/css/clients.css'];
$additionalJS = [];
$bodyClass = 'clients-page';

class ClientsManager {
    private $pdo;
    
    public function __construct() {
        $this->pdo = Database::getConnection();
    }
    
    /**
     * Liste tous les clients avec filtres et recherche
     */
    public function listClients($filters = []) {
        try {
            $sql = "SELECT c.*, 
                           COUNT(s.id) as total_purchases,
                           COALESCE(SUM(s.total_amount), 0) as total_spent,
                           MAX(s.sale_date) as last_purchase
                    FROM clients c 
                    LEFT JOIN sales s ON c.id = s.client_id 
                    WHERE 1=1";
            $params = [];
            
            // Filtre par recherche (nom, téléphone, email)
            if (!empty($filters['search'])) {
                $sql .= " AND (c.first_name LIKE ? OR c.last_name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)";
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Filtre par type de client
            if (!empty($filters['client_type'])) {
                $sql .= " AND c.client_type = ?";
                $params[] = $filters['client_type'];
            }
            
            $sql .= " GROUP BY c.id ORDER BY c.created_at DESC";
            
            if (!empty($filters['limit'])) {
                $sql .= " LIMIT ?";
                $params[] = (int)$filters['limit'];
            }
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur listClients: " . $e->getMessage());
            return [];
        }
    } 
   
    /**
     * Ajoute un nouveau client
     */
    public function addClient($data) {
        try {
            // Validation des données requises
            if (empty($data['first_name']) || empty($data['last_name'])) {
                return ['success' => false, 'message' => 'Le prénom et le nom sont requis'];
            }
            
            // Vérifier l'unicité du téléphone et email
            if (!empty($data['phone'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM clients WHERE phone = ? AND id != ?");
                $stmt->execute([$data['phone'], $data['id'] ?? 0]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Ce numéro de téléphone existe déjà'];
                }
            }
            
            if (!empty($data['email'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $data['id'] ?? 0]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Cette adresse email existe déjà'];
                }
            }
            
            $sql = "INSERT INTO clients (first_name, last_name, phone, email, address, client_type, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->pdo->prepare($sql);
            
            $result = $stmt->execute([
                trim($data['first_name']),
                trim($data['last_name']),
                !empty($data['phone']) ? trim($data['phone']) : null,
                !empty($data['email']) ? trim($data['email']) : null,
                !empty($data['address']) ? trim($data['address']) : null,
                $data['client_type'] ?? 'autre',
                !empty($data['notes']) ? trim($data['notes']) : null
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Client ajouté avec succès', 'id' => $this->pdo->lastInsertId()];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de l\'ajout du client'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur addClient: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()];
        }
    }
    
    /**
     * Met à jour un client
     */
    public function updateClient($id, $data) {
        try {
            // Validation des données requises
            if (empty($data['first_name']) || empty($data['last_name'])) {
                return ['success' => false, 'message' => 'Le prénom et le nom sont requis'];
            }
            
            // Vérifier l'unicité du téléphone et email (exclure le client actuel)
            if (!empty($data['phone'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM clients WHERE phone = ? AND id != ?");
                $stmt->execute([$data['phone'], $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Ce numéro de téléphone existe déjà'];
                }
            }
            
            if (!empty($data['email'])) {
                $stmt = $this->pdo->prepare("SELECT id FROM clients WHERE email = ? AND id != ?");
                $stmt->execute([$data['email'], $id]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Cette adresse email existe déjà'];
                }
            }
            
            $sql = "UPDATE clients SET first_name = ?, last_name = ?, phone = ?, email = ?, 
                    address = ?, client_type = ?, notes = ? WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            
            $result = $stmt->execute([
                trim($data['first_name']),
                trim($data['last_name']),
                !empty($data['phone']) ? trim($data['phone']) : null,
                !empty($data['email']) ? trim($data['email']) : null,
                !empty($data['address']) ? trim($data['address']) : null,
                $data['client_type'] ?? 'autre',
                !empty($data['notes']) ? trim($data['notes']) : null,
                $id
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Client mis à jour avec succès'];
            } else {
                return ['success' => false, 'message' => 'Erreur lors de la mise à jour'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur updateClient: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur de base de données: ' . $e->getMessage()];
        }
    }    
  
  /**
     * Supprime un client
     */
    public function deleteClient($id) {
        try {
            // Vérifier s'il y a des ventes liées
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM sales WHERE client_id = ?");
            $stmt->execute([$id]);
            $salesCount = $stmt->fetchColumn();
            
            if ($salesCount > 0) {
                return ['success' => false, 'message' => "Impossible de supprimer ce client car il a $salesCount vente(s) associée(s)"];
            }
            
            $sql = "DELETE FROM clients WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            $result = $stmt->execute([$id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Client supprimé avec succès'];
            } else {
                return ['success' => false, 'message' => 'Client non trouvé'];
            }
            
        } catch (PDOException $e) {
            error_log("Erreur deleteClient: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression'];
        }
    }
    
    /**
     * Récupère un client par son ID
     */
    public function getClient($id) {
        try {
            $sql = "SELECT c.*, 
                           COUNT(s.id) as total_purchases,
                           COALESCE(SUM(s.total_amount), 0) as total_spent,
                           COALESCE(AVG(s.total_amount), 0) as avg_purchase,
                           MAX(s.sale_date) as last_purchase,
                           MIN(s.sale_date) as first_purchase
                    FROM clients c 
                    LEFT JOIN sales s ON c.id = s.client_id 
                    WHERE c.id = ?
                    GROUP BY c.id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getClient: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Récupère l'historique des achats d'un client
     */
    public function getClientPurchaseHistory($clientId, $limit = 50) {
        try {
            $sql = "SELECT s.*, c.first_name, c.last_name 
                    FROM sales s 
                    JOIN clients c ON s.client_id = c.id 
                    WHERE s.client_id = ? 
                    ORDER BY s.sale_date DESC 
                    LIMIT ?";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([$clientId, $limit]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erreur getClientPurchaseHistory: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Récupère les statistiques des clients
     */
    public function getClientsStats() {
        try {
            $stats = [];
            
            // Total clients
            $stmt = $this->pdo->query("SELECT COUNT(*) FROM clients");
            $stats['total_clients'] = $stmt->fetchColumn();
            
            // Clients par type
            $stmt = $this->pdo->query("SELECT client_type, COUNT(*) as count FROM clients GROUP BY client_type");
            $clientTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $stats['by_type'] = [
                'parent' => 0,
                'eleve' => 0,
                'acheteur_regulier' => 0,
                'autre' => 0
            ];
            
            foreach ($clientTypes as $type) {
                $stats['by_type'][$type['client_type']] = $type['count'];
            }
            
            // Clients avec achats
            $stmt = $this->pdo->query("SELECT COUNT(DISTINCT client_id) FROM sales WHERE client_id IS NOT NULL");
            $stats['clients_with_purchases'] = $stmt->fetchColumn();
            
            // Chiffre d'affaires total
            $stmt = $this->pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM sales");
            $stats['total_revenue'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Erreur getClientsStats: " . $e->getMessage());
            return [
                'total_clients' => 0,
                'by_type' => ['parent' => 0, 'eleve' => 0, 'acheteur_regulier' => 0, 'autre' => 0],
                'clients_with_purchases' => 0,
                'total_revenue' => 0
            ];
        }
    }
}// Init
ialisation
$clientsManager = new ClientsManager();
$message = '';
$messageType = '';
$response = [];

// Traitement des requêtes AJAX
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'search':
            $search = $_GET['search'] ?? '';
            $type = $_GET['type'] ?? '';
            $clients = $clientsManager->listClients([
                'search' => $search,
                'client_type' => $type,
                'limit' => 100
            ]);
            echo json_encode(['success' => true, 'clients' => $clients]);
            exit;
            
        case 'get_client':
            $id = (int)($_GET['id'] ?? 0);
            $client = $clientsManager->getClient($id);
            if ($client) {
                echo json_encode(['success' => true, 'client' => $client]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
            }
            exit;
            
        case 'get_history':
            $id = (int)($_GET['id'] ?? 0);
            $client = $clientsManager->getClient($id);
            $history = $clientsManager->getClientPurchaseHistory($id);
            if ($client) {
                echo json_encode(['success' => true, 'client' => $client, 'history' => $history]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Client non trouvé']);
            }
            exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
    exit;
}

// Traitement des actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_client':
            $response = $clientsManager->addClient($_POST);
            $message = $response['message'];
            $messageType = $response['success'] ? 'success' : 'error';
            break;
            
        case 'update_client':
            $id = (int)$_POST['client_id'];
            $response = $clientsManager->updateClient($id, $_POST);
            $message = $response['message'];
            $messageType = $response['success'] ? 'success' : 'error';
            break;
            
        case 'delete_client':
            $id = (int)$_POST['client_id'];
            $response = $clientsManager->deleteClient($id);
            $message = $response['message'];
            $messageType = $response['success'] ? 'success' : 'error';
            break;
    }
    
    // Redirection pour éviter la resoumission
    if (!empty($message)) {
        $_SESSION['flash_message'] = $message;
        $_SESSION['flash_type'] = $messageType;
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

// Récupération des messages flash
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    $messageType = $_SESSION['flash_type'];
    unset($_SESSION['flash_message'], $_SESSION['flash_type']);
}

// Récupération des données
$clients = $clientsManager->listClients(['limit' => 100]);
$stats = $clientsManager->getClientsStats();

// Types de clients pour les sélecteurs
$clientTypes = [
    'parent' => 'Parent',
    'eleve' => 'Élève',
    'acheteur_regulier' => 'Acheteur Régulier',
    'autre' => 'Autre'
];

// Début du contenu HTML
ob_start();
?><!-- Me
ssages de notification -->
<?php if ($message): ?>
    <div class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
        <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <div><?php echo htmlspecialchars($message); ?></div>
    </div>
<?php endif; ?>

<!-- Header de la page -->
<div class="clients-header">
    <h1 class="clients-title">
        <i class="fas fa-users"></i>
        Gestion des Clients
        <span style="font-size: 0.7em; color: var(--text-muted); font-weight: normal;">Mama Sophie School Supplies</span>
    </h1>
    <div class="clients-actions">
        <button class="btn btn-primary" onclick="toggleClientForm()">
            <i class="fas fa-plus"></i> Nouveau Client
        </button>
        <button class="btn btn-outline" onclick="exportClients()">
            <i class="fas fa-download"></i> Exporter
        </button>
    </div>
</div>

<!-- Statistiques -->
<div class="clients-stats">
    <div class="client-stat-card">
        <div class="client-stat-header">
            <h3 class="client-stat-title">Total Clients</h3>
            <div class="client-stat-icon">
                <i class="fas fa-users"></i>
            </div>
        </div>
        <div class="client-stat-value"><?php echo $stats['total_clients']; ?></div>
        <div class="client-stat-subtitle">Clients enregistrés</div>
    </div>
    
    <div class="client-stat-card parents">
        <div class="client-stat-header">
            <h3 class="client-stat-title">Parents</h3>
            <div class="client-stat-icon">
                <i class="fas fa-user-friends"></i>
            </div>
        </div>
        <div class="client-stat-value"><?php echo $stats['by_type']['parent']; ?></div>
        <div class="client-stat-subtitle">Parents d'élèves</div>
    </div>
    
    <div class="client-stat-card regular">
        <div class="client-stat-header">
            <h3 class="client-stat-title">Acheteurs Réguliers</h3>
            <div class="client-stat-icon">
                <i class="fas fa-star"></i>
            </div>
        </div>
        <div class="client-stat-value"><?php echo $stats['by_type']['acheteur_regulier']; ?></div>
        <div class="client-stat-subtitle">Clients fidèles</div>
    </div>
    
    <div class="client-stat-card students">
        <div class="client-stat-header">
            <h3 class="client-stat-title">Chiffre d'Affaires</h3>
            <div class="client-stat-icon">
                <i class="fas fa-dollar-sign"></i>
            </div>
        </div>
        <div class="client-stat-value"><?php echo number_format($stats['total_revenue'], 0); ?>$</div>
        <div class="client-stat-subtitle">Total des ventes</div>
    </div>
</div>

<!-- Formulaire d'ajout/modification -->
<div class="client-form-container">
    <div class="client-form-header">
        <h2 class="client-form-title">
            <i class="fas fa-user-plus"></i>
            <span id="form-title">Nouveau Client</span>
        </h2>
        <button class="client-form-toggle" onclick="toggleClientForm()">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <form class="client-form" id="clientForm" method="POST">
        <input type="hidden" name="action" value="add_client" id="form-action">
        <input type="hidden" name="client_id" id="client-id">
        
        <div class="client-form-grid">
            <div class="form-group">
                <label for="first_name">Prénom *</label>
                <input type="text" id="first_name" name="first_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="last_name">Nom *</label>
                <input type="text" id="last_name" name="last_name" class="form-control" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Téléphone</label>
                <input type="tel" id="phone" name="phone" class="form-control" placeholder="+243 XX XXX XXXX">
            </div>
            
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" class="form-control" placeholder="client@email.com">
            </div>
            
            <div class="form-group">
                <label for="client_type">Type de Client</label>
                <select id="client_type" name="client_type" class="form-control">
                    <?php foreach ($clientTypes as $value => $label): ?>
                        <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="form-group client-form-row">
            <label for="address">Adresse</label>
            <textarea id="address" name="address" class="form-control" rows="2" placeholder="Adresse complète du client"></textarea>
        </div>
        
        <div class="form-group client-form-row">
            <label for="notes">Notes</label>
            <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="Notes additionnelles sur le client"></textarea>
        </div>
        
        <div class="client-form-actions">
            <button type="button" class="btn btn-outline" onclick="resetClientForm()">Annuler</button>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> <span id="submit-text">Enregistrer</span>
            </button>
        </div>
    </form>
</div><!-
- Recherche et filtres -->
<div class="clients-search-container">
    <div class="clients-search-header">
        <h3 class="clients-search-title">Recherche et Filtres</h3>
    </div>
    
    <div class="clients-search-grid">
        <div class="search-input">
            <input type="text" id="search-input" class="form-control" placeholder="Rechercher par nom, téléphone ou email..." onkeyup="searchClients()">
            <i class="fas fa-search"></i>
        </div>
        
        <div class="form-group">
            <select id="type-filter" class="form-control" onchange="searchClients()">
                <option value="">Tous les types</option>
                <?php foreach ($clientTypes as $value => $label): ?>
                    <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <select id="sort-filter" class="form-control" onchange="sortClients()">
                <option value="name">Trier par nom</option>
                <option value="date">Plus récents</option>
                <option value="purchases">Plus d'achats</option>
                <option value="amount">Plus gros montant</option>
            </select>
        </div>
        
        <button class="btn btn-outline" onclick="clearFilters()">
            <i class="fas fa-times"></i> Effacer
        </button>
    </div>
</div>

<!-- Liste des clients -->
<div class="clients-list-container">
    <div class="clients-list-header">
        <h3 class="clients-list-title">
            <i class="fas fa-list"></i> Liste des Clients
        </h3>
        <span class="clients-count" id="clients-count"><?php echo count($clients); ?></span>
    </div>
    
    <div class="clients-table-container">
        <table class="clients-table" id="clients-table">
            <thead>
                <tr>
                    <th>Client</th>
                    <th>Contact</th>
                    <th>Type</th>
                    <th>Statistiques</th>
                    <th>Dernière Visite</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="clients-tbody">
                <?php if (empty($clients)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="clients-empty-state">
                                <div class="clients-empty-icon">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h3 class="clients-empty-title">Aucun client trouvé</h3>
                                <p class="clients-empty-text">Commencez par ajouter votre premier client</p>
                                <button class="btn btn-primary" onclick="toggleClientForm()">
                                    <i class="fas fa-plus"></i> Ajouter un Client
                                </button>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($clients as $client): ?>
                        <tr data-client-id="<?php echo $client['id']; ?>" class="client-row">
                            <td>
                                <div class="client-info">
                                    <div class="client-name">
                                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>
                                    </div>
                                    <?php if ($client['notes']): ?>
                                        <div class="client-contact" title="<?php echo htmlspecialchars($client['notes']); ?>">
                                            <i class="fas fa-sticky-note"></i> <?php echo substr(htmlspecialchars($client['notes']), 0, 30) . (strlen($client['notes']) > 30 ? '...' : ''); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <div class="client-info">
                                    <?php if ($client['phone']): ?>
                                        <div class="client-contact">
                                            <i class="fas fa-phone"></i> <?php echo htmlspecialchars($client['phone']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if ($client['email']): ?>
                                        <div class="client-contact">
                                            <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($client['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td>
                                <span class="client-type-badge <?php echo $client['client_type']; ?>">
                                    <i class="fas fa-<?php echo $client['client_type'] === 'parent' ? 'user-friends' : ($client['client_type'] === 'eleve' ? 'graduation-cap' : ($client['client_type'] === 'acheteur_regulier' ? 'star' : 'user')); ?>"></i>
                                    <?php echo $clientTypes[$client['client_type']] ?? 'Autre'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="client-stats-mini">
                                    <div class="client-stat-mini">
                                        <i class="fas fa-shopping-cart"></i>
                                        <span class="value"><?php echo $client['total_purchases']; ?></span> achats
                                    </div>
                                    <div class="client-stat-mini">
                                        <i class="fas fa-dollar-sign"></i>
                                        <span class="value"><?php echo number_format($client['total_spent'], 0); ?>$</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($client['last_purchase']): ?>
                                    <span title="<?php echo date('d/m/Y H:i', strtotime($client['last_purchase'])); ?>">
                                        <?php echo date('d/m/Y', strtotime($client['last_purchase'])); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">Aucun achat</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="client-actions">
                                    <button class="client-action-btn view" onclick="viewClientHistory(<?php echo $client['id']; ?>)" title="Voir l'historique">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="client-action-btn edit" onclick="editClient(<?php echo $client['id']; ?>)" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="client-action-btn delete" onclick="deleteClient(<?php echo $client['id']; ?>, '<?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name']); ?>')" title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div><!--
 Modal Historique Client -->
<div id="clientHistoryModal" class="modal client-history-modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-history"></i> Historique des Achats</h3>
            <button class="close-btn" onclick="closeModal('clientHistoryModal')">&times;</button>
        </div>
        
        <div class="modal-body" id="client-history-content">
            <!-- Contenu chargé dynamiquement -->
        </div>
    </div>
</div>

<!-- JavaScript -->
<script>
// Variables globales
let allClients = <?php echo json_encode($clients); ?>;
let filteredClients = [...allClients];

// Gestion du formulaire
function toggleClientForm() {
    const form = document.querySelector('.client-form');
    const container = document.querySelector('.client-form-container');
    
    if (form.classList.contains('active')) {
        form.classList.remove('active');
        setTimeout(() => {
            container.style.display = 'none';
        }, 300);
    } else {
        container.style.display = 'block';
        setTimeout(() => {
            form.classList.add('active');
        }, 10);
        resetClientForm();
    }
}

function resetClientForm() {
    document.getElementById('clientForm').reset();
    document.getElementById('form-action').value = 'add_client';
    document.getElementById('client-id').value = '';
    document.getElementById('form-title').textContent = 'Nouveau Client';
    document.getElementById('submit-text').textContent = 'Enregistrer';
}

// Recherche en temps réel
function searchClients() {
    const searchTerm = document.getElementById('search-input').value.toLowerCase();
    const typeFilter = document.getElementById('type-filter').value;
    
    filteredClients = allClients.filter(client => {
        const matchesSearch = !searchTerm || 
            client.first_name.toLowerCase().includes(searchTerm) ||
            client.last_name.toLowerCase().includes(searchTerm) ||
            (client.phone && client.phone.toLowerCase().includes(searchTerm)) ||
            (client.email && client.email.toLowerCase().includes(searchTerm));
            
        const matchesType = !typeFilter || client.client_type === typeFilter;
        
        return matchesSearch && matchesType;
    });
    
    updateClientsTable();
}

// Tri des clients
function sortClients() {
    const sortBy = document.getElementById('sort-filter').value;
    
    filteredClients.sort((a, b) => {
        switch (sortBy) {
            case 'name':
                return (a.first_name + ' ' + a.last_name).localeCompare(b.first_name + ' ' + b.last_name);
            case 'date':
                return new Date(b.created_at) - new Date(a.created_at);
            case 'purchases':
                return (b.total_purchases || 0) - (a.total_purchases || 0);
            case 'amount':
                return (b.total_spent || 0) - (a.total_spent || 0);
            default:
                return 0;
        }
    });
    
    updateClientsTable();
}

// Effacer les filtres
function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('type-filter').value = '';
    document.getElementById('sort-filter').value = 'name';
    filteredClients = [...allClients];
    updateClientsTable();
}

// Mettre à jour le tableau
function updateClientsTable() {
    const tbody = document.getElementById('clients-tbody');
    const count = document.getElementById('clients-count');
    
    count.textContent = filteredClients.length;
    
    if (filteredClients.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6">
                    <div class="clients-empty-state">
                        <div class="clients-empty-icon">
                            <i class="fas fa-search"></i>
                        </div>
                        <h3 class="clients-empty-title">Aucun résultat</h3>
                        <p class="clients-empty-text">Aucun client ne correspond à vos critères de recherche</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    const clientTypes = <?php echo json_encode($clientTypes); ?>;
    
    tbody.innerHTML = filteredClients.map(client => {
        const typeIcons = {
            'parent': 'user-friends',
            'eleve': 'graduation-cap',
            'acheteur_regulier': 'star',
            'autre': 'user'
        };
        
        return `
            <tr data-client-id="${client.id}" class="client-row">
                <td>
                    <div class="client-info">
                        <div class="client-name">${client.first_name} ${client.last_name}</div>
                        ${client.notes ? `<div class="client-contact" title="${client.notes}">
                            <i class="fas fa-sticky-note"></i> ${client.notes.substring(0, 30)}${client.notes.length > 30 ? '...' : ''}
                        </div>` : ''}
                    </div>
                </td>
                <td>
                    <div class="client-info">
                        ${client.phone ? `<div class="client-contact">
                            <i class="fas fa-phone"></i> ${client.phone}
                        </div>` : ''}
                        ${client.email ? `<div class="client-contact">
                            <i class="fas fa-envelope"></i> ${client.email}
                        </div>` : ''}
                    </div>
                </td>
                <td>
                    <span class="client-type-badge ${client.client_type}">
                        <i class="fas fa-${typeIcons[client.client_type] || 'user'}"></i>
                        ${clientTypes[client.client_type] || 'Autre'}
                    </span>
                </td>
                <td>
                    <div class="client-stats-mini">
                        <div class="client-stat-mini">
                            <i class="fas fa-shopping-cart"></i>
                            <span class="value">${client.total_purchases || 0}</span> achats
                        </div>
                        <div class="client-stat-mini">
                            <i class="fas fa-dollar-sign"></i>
                            <span class="value">${Math.round(client.total_spent || 0)}$</span>
                        </div>
                    </div>
                </td>
                <td>
                    ${client.last_purchase ? 
                        new Date(client.last_purchase).toLocaleDateString('fr-FR') : 
                        '<span class="text-muted">Aucun achat</span>'
                    }
                </td>
                <td>
                    <div class="client-actions">
                        <button class="client-action-btn view" onclick="viewClientHistory(${client.id})" title="Voir l'historique">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="client-action-btn edit" onclick="editClient(${client.id})" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="client-action-btn delete" onclick="deleteClient(${client.id}, '${client.first_name} ${client.last_name}')" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}// Édition 
d'un client
function editClient(id) {
    fetch(`?ajax=get_client&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const client = data.client;
                
                // Remplir le formulaire
                document.getElementById('client-id').value = client.id;
                document.getElementById('first_name').value = client.first_name;
                document.getElementById('last_name').value = client.last_name;
                document.getElementById('phone').value = client.phone || '';
                document.getElementById('email').value = client.email || '';
                document.getElementById('address').value = client.address || '';
                document.getElementById('client_type').value = client.client_type;
                document.getElementById('notes').value = client.notes || '';
                
                // Changer le mode du formulaire
                document.getElementById('form-action').value = 'update_client';
                document.getElementById('form-title').textContent = 'Modifier Client';
                document.getElementById('submit-text').textContent = 'Mettre à jour';
                
                // Afficher le formulaire
                const form = document.querySelector('.client-form');
                const container = document.querySelector('.client-form-container');
                container.style.display = 'block';
                setTimeout(() => {
                    form.classList.add('active');
                }, 10);
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement des données du client');
        });
}

// Suppression d'un client
function deleteClient(id, name) {
    if (confirm(`Êtes-vous sûr de vouloir supprimer le client "${name}" ?\n\nCette action est irréversible.`)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="delete_client">
            <input type="hidden" name="client_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

// Voir l'historique d'un client
function viewClientHistory(id) {
    fetch(`?ajax=get_history&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const client = data.client;
                const history = data.history;
                
                const initials = (client.first_name.charAt(0) + client.last_name.charAt(0)).toUpperCase();
                
                const historyHtml = `
                    <div class="client-history-header">
                        <div class="client-history-info">
                            <div class="client-avatar">${initials}</div>
                            <div class="client-history-details">
                                <h3>${client.first_name} ${client.last_name}</h3>
                                <p>${client.phone || ''} ${client.email ? '• ' + client.email : ''}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="client-history-stats">
                        <div class="history-stat">
                            <div class="history-stat-value">${client.total_purchases || 0}</div>
                            <div class="history-stat-label">Achats</div>
                        </div>
                        <div class="history-stat">
                            <div class="history-stat-value">${Math.round(client.total_spent || 0)}$</div>
                            <div class="history-stat-label">Total Dépensé</div>
                        </div>
                        <div class="history-stat">
                            <div class="history-stat-value">${Math.round(client.avg_purchase || 0)}$</div>
                            <div class="history-stat-label">Panier Moyen</div>
                        </div>
                    </div>
                    
                    ${history.length > 0 ? `
                        <table class="client-history-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Produit</th>
                                    <th>Qté</th>
                                    <th>Prix Unit.</th>
                                    <th>Total</th>
                                    <th>Paiement</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${history.map(sale => `
                                    <tr>
                                        <td>${new Date(sale.sale_date).toLocaleDateString('fr-FR')}</td>
                                        <td>${sale.product_name}</td>
                                        <td>${sale.quantity}</td>
                                        <td>${parseFloat(sale.unit_price).toFixed(2)}$</td>
                                        <td><strong>${parseFloat(sale.total_amount).toFixed(2)}$</strong></td>
                                        <td><span class="payment-method-badge ${sale.payment_method}">${sale.payment_method}</span></td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    ` : `
                        <div class="clients-empty-state">
                            <div class="clients-empty-icon">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                            <h3 class="clients-empty-title">Aucun achat</h3>
                            <p class="clients-empty-text">Ce client n'a encore effectué aucun achat</p>
                        </div>
                    `}
                `;
                
                document.getElementById('client-history-content').innerHTML = historyHtml;
                document.getElementById('clientHistoryModal').classList.add('show');
            } else {
                alert('Erreur: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Erreur:', error);
            alert('Erreur lors du chargement de l\'historique');
        });
}

// Export des clients
function exportClients() {
    const csvContent = "data:text/csv;charset=utf-8," + 
        "Prénom,Nom,Téléphone,Email,Type,Adresse,Total Achats,Total Dépensé\n" +
        filteredClients.map(client => 
            `"${client.first_name}","${client.last_name}","${client.phone || ''}","${client.email || ''}","${client.client_type}","${client.address || ''}","${client.total_purchases || 0}","${client.total_spent || 0}"`
        ).join("\n");
    
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement("a");
    link.setAttribute("href", encodedUri);
    link.setAttribute("download", `clients_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
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

// Validation du formulaire
document.getElementById('clientForm').addEventListener('submit', function(e) {
    const firstName = document.getElementById('first_name').value.trim();
    const lastName = document.getElementById('last_name').value.trim();
    
    if (!firstName || !lastName) {
        e.preventDefault();
        alert('Le prénom et le nom sont obligatoires');
        return false;
    }
    
    // Validation email si fourni
    const email = document.getElementById('email').value.trim();
    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        e.preventDefault();
        alert('Format d\'email invalide');
        return false;
    }
});

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    // Masquer le formulaire au chargement
    document.querySelector('.client-form-container').style.display = 'none';
    
    // Tri initial par nom
    sortClients();
});
</script>

<?php
$content = ob_get_clean();

// Inclure le layout de base
include __DIR__ . '/layout/base.php';
?>