<?php
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
		$friendly = null;
		if ($e instanceof PDOException) {
			$msg = $e->getMessage();
			if ($e->getCode() === '23000' || stripos($msg, 'Duplicate') !== false || stripos($msg, 'duplicat') !== false) {
				$friendly = 'Téléphone ou email déjà utilisé';
			}
		}
		$message = $friendly ?? ('Erreur: ' . $e->getMessage());
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
?>

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