<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

require_roles(['admin']);

$pdo = Database::getConnection();

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string)($_POST['action'] ?? '');
	try {
		if ($action === 'create') {
			$username = trim((string)($_POST['username'] ?? ''));
			$password = (string)($_POST['password'] ?? '');
			$role = (string)($_POST['role'] ?? 'caissier');
			if ($username === '' || $password === '') { throw new RuntimeException('Champs requis.'); }
			$hash = password_hash($password, PASSWORD_DEFAULT);
			$stmt = $pdo->prepare('INSERT INTO users (username, password, role) VALUES (?, ?, ?)');
			$stmt->execute([$username, $hash, $role]);
			$message = 'Utilisateur créé.'; $messageType = 'success';
		}
		if ($action === 'update') {
			$id = (int)($_POST['id'] ?? 0);
			$username = trim((string)($_POST['username'] ?? ''));
			$role = (string)($_POST['role'] ?? 'caissier');
			if ($id <= 0 || $username === '') { throw new RuntimeException('Données invalides.'); }
			if (isset($_POST['password']) && $_POST['password'] !== '') {
				$hash = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
				$stmt = $pdo->prepare('UPDATE users SET username = ?, password = ?, role = ? WHERE id = ?');
				$stmt->execute([$username, $hash, $role, $id]);
			} else {
				$stmt = $pdo->prepare('UPDATE users SET username = ?, role = ? WHERE id = ?');
				$stmt->execute([$username, $role, $id]);
			}
			$message = 'Utilisateur mis à jour.'; $messageType = 'success';
		}
		if ($action === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id <= 0) { throw new RuntimeException('Utilisateur invalide.'); }
			if ($id === (int)($_SESSION['user_id'] ?? 0)) { throw new RuntimeException('Impossible de supprimer votre propre compte.'); }
			$stmt = $pdo->prepare('DELETE FROM users WHERE id = ?');
			$stmt->execute([$id]);
			$message = 'Utilisateur supprimé.'; $messageType = 'success';
		}
	} catch (Throwable $e) {
		$message = 'Erreur: ' . $e->getMessage(); $messageType = 'error';
	}
}

$users = $pdo->query('SELECT id, username, role, created_at FROM users ORDER BY created_at DESC')->fetchAll() ?: [];

$currentPage = 'users';
$pageTitle = 'Gestion des utilisateurs';
$showSidebar = true;
$additionalCSS = ['assets/css/auth.css'];

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
		<h2 class="clients-title"><i class="fas fa-users-cog"></i> Utilisateurs</h2>
		<button class="btn btn-primary" onclick="openCreate()"><i class="fas fa-user-plus"></i> Nouvel utilisateur</button>
	</div>

	<div class="clients-table card">
		<div class="card-header"><h3 class="card-title">Comptes existants</h3></div>
		<div class="card-body">
			<div class="table-responsive">
				<table>
					<thead><tr><th>Username</th><th>Rôle</th><th>Créé le</th><th>Actions</th></tr></thead>
					<tbody>
						<?php foreach ($users as $u): ?>
						<tr>
							<td><?php echo htmlspecialchars($u['username']); ?></td>
							<td><?php echo htmlspecialchars($u['role']); ?></td>
							<td><?php echo htmlspecialchars($u['created_at']); ?></td>
							<td class="actions">
								<button class="btn btn-sm btn-warning" onclick='openEdit(<?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'><i class="fas fa-edit"></i></button>
								<form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
									<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
								</form>
							</td>
						</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="modal" id="userModal">
		<div class="modal-content">
			<div class="modal-header">
				<h3 class="modal-title" id="userModalTitle"><i class="fas fa-user-cog"></i> Nouvel utilisateur</h3>
				<button class="modal-close" onclick="closeModal('userModal')">&times;</button>
			</div>
			<form method="POST" id="userForm">
				<div class="modal-body">
					<input type="hidden" name="action" id="userAction" value="create">
					<input type="hidden" name="id" id="userId" value="">
					<div class="form-grid">
						<div class="form-group"><label>Username</label><input class="form-control" name="username" id="usernameInput" required></div>
						<div class="form-group"><label>Mot de passe</label><input class="form-control" type="password" name="password" id="passwordInput"></div>
						<div class="form-group"><label>Rôle</label>
							<select class="form-control" name="role" id="roleInput">
								<option value="admin">Administrateur</option>
								<option value="caissier">Caissier</option>
								<option value="gestionnaire">Gestionnaire</option>
								<option value="directeur">Directeur</option>
							</select>
						</div>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-ghost" onclick="closeModal('userModal')">Annuler</button>
					<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
				</div>
			</form>
		</div>
	</div>
</div>

<script>
function openCreate(){
	document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-cog"></i> Nouvel utilisateur';
	document.getElementById('userAction').value = 'create';
	document.getElementById('userForm').reset();
	document.getElementById('userId').value = '';
	openModal('userModal');
}
function openEdit(u){
	document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-edit"></i> Modifier utilisateur';
	document.getElementById('userAction').value = 'update';
	document.getElementById('userId').value = u.id;
	document.getElementById('usernameInput').value = u.username;
	document.getElementById('roleInput').value = u.role;
	document.getElementById('passwordInput').value = '';
	openModal('userModal');
}
function openModal(id){ document.getElementById(id).classList.add('show'); document.body.style.overflow='hidden'; }
function closeModal(id){ document.getElementById(id).classList.remove('show'); document.body.style.overflow='auto'; }
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


