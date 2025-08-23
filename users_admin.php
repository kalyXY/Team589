<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

require_roles(['admin']);

$pdo = Database::getConnection();

// Évolutions schéma si nécessaire
$pdo->exec("ALTER TABLE users
	ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) NULL AFTER username,
	ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL AFTER email,
	ADD COLUMN IF NOT EXISTS status ENUM('actif','inactif') NOT NULL DEFAULT 'actif' AFTER role,
	ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) NULL AFTER status");

// Vérifier que la table a bien toutes les colonnes nécessaires
try {
	$pdo->query("SELECT id, username, full_name, email, phone, password, role, status, avatar_path, created_at FROM users LIMIT 1");
} catch (PDOException $e) {
	// Si erreur, créer les colonnes manquantes
	$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS full_name VARCHAR(150) NULL");
	$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30) NULL");
	$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('actif','inactif') NOT NULL DEFAULT 'actif'");
	$pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS avatar_path VARCHAR(255) NULL");
}

function sanitize(string $v, int $max = 255): string { $v = trim($v); return substr($v, 0, $max); }

$toast = ['type' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = (string)($_POST['action'] ?? '');
	try {
		if ($action === 'create') {
			$username = sanitize((string)($_POST['username'] ?? ''), 100);
			$full_name = sanitize((string)($_POST['full_name'] ?? ''), 150);
			$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$_POST['email'] : '';
			$phone = sanitize((string)($_POST['phone'] ?? ''), 30);
			$password = (string)($_POST['password'] ?? '');
			$role = sanitize((string)($_POST['role'] ?? 'caissier'), 50);
			$status = in_array(($_POST['status'] ?? 'actif'), ['actif','inactif'], true) ? (string)$_POST['status'] : 'actif';
			if ($username === '' || $full_name === '' || $email === '' || $password === '') { throw new RuntimeException('Champs requis manquants.'); }
			
			// Vérifier si username ou email existe déjà
			$exists = $pdo->prepare('SELECT 1 FROM users WHERE username = ? OR email = ?');
			$exists->execute([$username, $email]);
			if ($exists->fetch()) { throw new RuntimeException('Nom d\'utilisateur ou email déjà utilisé.'); }
			
			$hash = password_hash($password, PASSWORD_DEFAULT);
			$avatarRel = null;
			if (!empty($_FILES['avatar']['name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
				$mime = mime_content_type($_FILES['avatar']['tmp_name']);
				$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
				if (!isset($allowed[$mime])) { throw new RuntimeException('Image non supportée (jpg/png).'); }
				if (($_FILES['avatar']['size'] ?? 0) > 2 * 1024 * 1024) { throw new RuntimeException('Image > 2Mo.'); }
				$ext = $allowed[$mime];
				$dir = __DIR__ . '/uploads/profiles'; if (!is_dir($dir)) { mkdir($dir, 0775, true); }
				$filename = 'u_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
				$dest = $dir . '/' . $filename;
				if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) { throw new RuntimeException('Échec upload image.'); }
				$avatarRel = 'uploads/profiles/' . $filename;
			}
			$stmt = $pdo->prepare('INSERT INTO users (username, full_name, email, phone, password, role, status, avatar_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([$username, $full_name, $email, $phone, $hash, $role, $status, $avatarRel]);
			$toast = ['type' => 'success', 'message' => 'Utilisateur créé.'];
		}

		if ($action === 'update') {
			$id = (int)($_POST['id'] ?? 0);
			$username = sanitize((string)($_POST['username'] ?? ''), 100);
			$full_name = sanitize((string)($_POST['full_name'] ?? ''), 150);
			$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? (string)$_POST['email'] : '';
			$phone = sanitize((string)($_POST['phone'] ?? ''), 30);
			$role = sanitize((string)($_POST['role'] ?? 'caissier'), 50);
			$status = in_array(($_POST['status'] ?? 'actif'), ['actif','inactif'], true) ? (string)$_POST['status'] : 'actif';
			if ($id <= 0 || $username === '' || $full_name === '' || $email === '') { throw new RuntimeException('Données invalides.'); }
			
			// Vérifier si username ou email existe déjà (sauf pour l'utilisateur actuel)
			$exists = $pdo->prepare('SELECT 1 FROM users WHERE (username = ? OR email = ?) AND id <> ?');
			$exists->execute([$username, $email, $id]);
			if ($exists->fetch()) { throw new RuntimeException('Nom d\'utilisateur ou email déjà utilisé.'); }
			
			// Construction de la requête SQL dynamique
			$updateFields = ['username = ?', 'full_name = ?', 'email = ?', 'phone = ?', 'role = ?', 'status = ?'];
			$params = [$username, $full_name, $email, $phone, $role, $status];
			
			// Ajout du mot de passe si fourni
			if (!empty($_POST['password'])) {
				$updateFields[] = 'password = ?';
				$params[] = password_hash((string)$_POST['password'], PASSWORD_DEFAULT);
			}
			
			// Gestion de l'avatar
			if (!empty($_FILES['avatar']['name']) && is_uploaded_file($_FILES['avatar']['tmp_name'])) {
				$mime = mime_content_type($_FILES['avatar']['tmp_name']);
				$allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
				if (!isset($allowed[$mime])) { throw new RuntimeException('Image non supportée (jpg/png).'); }
				if (($_FILES['avatar']['size'] ?? 0) > 2 * 1024 * 1024) { throw new RuntimeException('Image > 2Mo.'); }
				$ext = $allowed[$mime];
				$dir = __DIR__ . '/uploads/profiles'; 
				if (!is_dir($dir)) { mkdir($dir, 0775, true); }
				$filename = 'u_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
				$dest = $dir . '/' . $filename;
				if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $dest)) { throw new RuntimeException('Échec upload image.'); }
				$avatarRel = 'uploads/profiles/' . $filename;
				$updateFields[] = 'avatar_path = ?';
				$params[] = $avatarRel;
			}
			
			$params[] = $id; // ID pour la clause WHERE
			$sql = 'UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
			$toast = ['type' => 'success', 'message' => 'Utilisateur mis à jour.'];
		}

		if ($action === 'delete') {
			$id = (int)($_POST['id'] ?? 0);
			if ($id <= 0) { throw new RuntimeException('Utilisateur invalide.'); }
			if ($id === (int)($_SESSION['user_id'] ?? 0)) { throw new RuntimeException('Vous ne pouvez pas supprimer votre propre compte.'); }
			$pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
			$toast = ['type' => 'success', 'message' => 'Utilisateur supprimé.'];
		}
	} catch (Throwable $e) {
		$toast = ['type' => 'danger', 'message' => $e->getMessage()];
	}
}

$search = trim((string)($_GET['q'] ?? ''));
$roleFilter = trim((string)($_GET['role'] ?? ''));
$statusFilter = trim((string)($_GET['status'] ?? ''));
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

$where = [];
$params = [];
if ($search !== '') { $where[] = '(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)'; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($roleFilter !== '') { $where[] = 'role = ?'; $params[] = $roleFilter; }
if ($statusFilter !== '') { $where[] = 'status = ?'; $params[] = $statusFilter; }
$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereSql");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$pages = max(1, (int)ceil($total / $perPage));

$listStmt = $pdo->prepare("SELECT id, username, full_name, email, phone, role, status, avatar_path, created_at FROM users $whereSql ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$listStmt->execute($params);
$users = $listStmt->fetchAll();

$currentPage = 'users';
$pageTitle = 'Gestion des utilisateurs';
$showSidebar = true;
$additionalCSS = [
	'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
];
$additionalJS = [
	'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
];

ob_start();
?>

<style>
#usersRoot .card { border-radius: .75rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
#usersRoot .avatar { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; background: var(--primary-light); display: inline-flex; align-items: center; justify-content: center; font-weight: 700; color: var(--primary-color); }
#usersRoot .table thead th { background: var(--bg-tertiary); }
#usersRoot .btn { border-radius: .5rem; }



/* Fix sidebar style with Bootstrap */
.sidebar { 
    background: var(--bg-secondary) !important; 
    color: var(--text-color) !important; 
    border-right: 1px solid var(--border-color) !important; 
}
.sidebar-logo { 
    background: var(--primary-color) !important; 
    color: white !important; 
    padding: 1rem !important; 
    text-align: center !important; 
    font-weight: bold !important; 
}
.nav-section { 
    margin-bottom: 1rem !important; 
}
.nav-section-title { 
    color: var(--text-muted) !important; 
    font-size: 0.75rem !important; 
    font-weight: 600 !important; 
    text-transform: uppercase !important; 
    letter-spacing: 0.5px !important; 
    padding: 0.5rem 1rem !important; 
    margin-bottom: 0.5rem !important; 
}
.nav-item { 
    margin-bottom: 0.25rem !important; 
}
.nav-link { 
    display: flex !important; 
    align-items: center !important; 
    padding: 0.75rem 1rem !important; 
    color: var(--text-color) !important; 
    text-decoration: none !important; 
    border-radius: 0.5rem !important; 
    margin: 0 0.5rem !important; 
    transition: all 0.2s ease !important; 
    background: transparent !important; 
    border: none !important; 
}
.nav-link:hover { 
    background: var(--bg-tertiary) !important; 
    color: var(--primary-color) !important; 
    transform: translateX(4px) !important; 
}
.nav-link.active { 
    background: var(--primary-color) !important; 
    color: white !important; 
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important; 
}
.nav-icon { 
    width: 20px !important; 
    margin-right: 0.75rem !important; 
    text-align: center !important; 
}
.nav-text { 
    font-weight: 500 !important; 
}
</style>

<div id="usersRoot" class="container py-4">
	<?php if ($toast['type']): ?>
		<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index:1080;">
			<div class="toast text-bg-<?php echo $toast['type']==='danger'?'danger':'success'; ?> show" id="notificationToast" role="alert" aria-live="assertive" aria-atomic="true">
				<div class="toast-body d-flex align-items-center">
					<i class="fas fa-<?php echo $toast['type']==='danger'?'exclamation-triangle':'check-circle'; ?> me-2"></i>
					<?php echo htmlspecialchars($toast['message']); ?>
				</div>
				<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
		</div>
	<?php endif; ?>

	<div class="d-flex justify-content-between align-items-center mb-3">
		<h2 class="mb-0"><i class="fas fa-users-cog"></i> Utilisateurs</h2>
		<button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetUserForm()"><i class="fas fa-user-plus"></i> Ajouter un utilisateur</button>
	</div>

	<div class="card shadow-lg">
		<div class="card-body">
			<form class="row g-2 align-items-end mb-3" method="get">
				<div class="col-md-4">
					<label class="form-label">Recherche</label>
					<input type="text" name="q" class="form-control" placeholder="Nom, email, téléphone..." value="<?php echo htmlspecialchars($search); ?>">
				</div>
				<div class="col-md-3">
					<label class="form-label">Rôle</label>
					<select name="role" class="form-select">
						<option value="">Tous</option>
						<?php foreach (['admin','directeur','caissier','gestionnaire','utilisateur'] as $r): ?>
						<option value="<?php echo $r; ?>" <?php echo $roleFilter===$r?'selected':''; ?>><?php echo ucfirst($r); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-3">
					<label class="form-label">Statut</label>
					<select name="status" class="form-select">
						<option value="">Tous</option>
						<?php foreach (['actif','inactif'] as $s): ?>
						<option value="<?php echo $s; ?>" <?php echo $statusFilter===$s?'selected':''; ?>><?php echo ucfirst($s); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="col-md-2">
					<button class="btn btn-outline-secondary w-100"><i class="fas fa-search"></i> Filtrer</button>
				</div>
			</form>

			<div class="table-responsive">
				<table class="table table-hover align-middle">
					<thead>
						<tr>
							<th>Photo</th>
							<th>Nom d'utilisateur</th>
							<th>Nom complet</th>
							<th>Email</th>
							<th>Téléphone</th>
							<th>Rôle</th>
							<th>Créé le</th>
							<th>Statut</th>
							<th class="text-end">Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php if (empty($users)): ?>
						<tr><td colspan="9" class="text-center text-muted">Aucun utilisateur</td></tr>
						<?php else: foreach ($users as $u): ?>
						<tr>
							<td>
								<?php if (!empty($u['avatar_path'])): ?>
									<img src="<?php echo htmlspecialchars($u['avatar_path']); ?>" class="avatar" alt="Avatar">
								<?php else: ?>
									<div class="avatar"><?php echo htmlspecialchars(mb_strtoupper(mb_substr((string)$u['full_name'],0,1))); ?></div>
								<?php endif; ?>
							</td>
							<td><?php echo htmlspecialchars($u['username'] ?? ''); ?></td>
							<td><?php echo htmlspecialchars($u['full_name'] ?? ''); ?></td>
							<td><?php echo htmlspecialchars($u['email'] ?? ''); ?></td>
							<td><?php echo htmlspecialchars($u['phone'] ?? ''); ?></td>
							<td><?php echo htmlspecialchars($u['role'] ?? ''); ?></td>
							<td><?php echo htmlspecialchars($u['created_at'] ?? ''); ?></td>
							<td>
								<span class="badge bg-<?php echo ($u['status'] ?? 'actif')==='actif'?'success':'secondary'; ?>"><?php echo ucfirst((string)$u['status']); ?></span>
							</td>
							<td class="text-end">
								<button class="btn btn-sm btn-warning" onclick='openEdit(<?php echo json_encode($u, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'><i class="fas fa-edit"></i></button>
								<form method="POST" style="display:inline" onsubmit="return confirm('Supprimer cet utilisateur ?')">
									<input type="hidden" name="action" value="delete">
									<input type="hidden" name="id" value="<?php echo (int)$u['id']; ?>">
									<button class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
								</form>
							</td>
						</tr>
						<?php endforeach; endif; ?>
					</tbody>
				</table>
			</div>

			<nav class="mt-3" aria-label="Pagination">
				<ul class="pagination justify-content-end">
					<?php for ($p=1; $p<=$pages; $p++): $qs = http_build_query(['q'=>$search,'role'=>$roleFilter,'status'=>$statusFilter,'page'=>$p]); ?>
					<li class="page-item <?php echo $p===$page?'active':''; ?>"><a class="page-link" href="?<?php echo $qs; ?>"><?php echo $p; ?></a></li>
					<?php endfor; ?>
				</ul>
			</nav>
		</div>
	</div>

	<div class="modal fade" id="userModal" tabindex="-1">
		<div class="modal-dialog modal-lg modal-dialog-scrollable">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="userModalTitle"><i class="fas fa-user-plus"></i> Nouvel utilisateur</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<form method="POST" enctype="multipart/form-data" id="userForm">
					<div class="modal-body">
						<input type="hidden" name="action" id="userAction" value="create">
						<input type="hidden" name="id" id="userId" value="">
						<div class="row g-3">
							<div class="col-md-3 text-center">
								<img id="avatarPreview" class="avatar mb-2" style="width:80px;height:80px;" alt="Avatar"/>
								<input class="form-control" type="file" name="avatar" id="avatarInput" accept="image/png,image/jpeg">
								<small class="text-muted">JPG/PNG max 2Mo</small>
							</div>
							<div class="col-md-9">
								<div class="row g-3">
									<div class="col-md-6">
										<label class="form-label">Nom d'utilisateur</label>
										<input class="form-control" name="username" id="usernameInput" required>
									</div>
									<div class="col-md-6">
										<label class="form-label">Nom complet</label>
										<input class="form-control" name="full_name" id="fullNameInput" required>
									</div>
									<div class="col-md-6">
										<label class="form-label">Téléphone</label>
										<input class="form-control" name="phone" id="phoneInput">
									</div>
									<div class="col-md-6">
										<label class="form-label">Email</label>
										<input type="email" class="form-control" name="email" id="emailInput" required>
									</div>
									<div class="col-md-6">
										<label class="form-label">Rôle</label>
										<select class="form-select" name="role" id="roleInput">
											<option value="admin">Admin</option>
											<option value="directeur">Directeur</option>
											<option value="caissier">Caissier</option>
											<option value="gestionnaire">Gestionnaire</option>
											<option value="utilisateur">Utilisateur</option>
										</select>
									</div>
									<div class="col-md-6">
										<label class="form-label">Mot de passe</label>
										<input type="password" class="form-control" name="password" id="passwordInput" minlength="8" placeholder="********">
										<small id="passwordHelp" class="text-muted">Laisser vide pour ne pas changer (en modification).</small>
									</div>
									<div class="col-md-6">
										<label class="form-label">Statut</label>
										<select class="form-select" name="status" id="statusInput">
											<option value="actif">Actif</option>
											<option value="inactif">Inactif</option>
										</select>
									</div>
								</div>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Annuler</button>
						<button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

<script>
function resetUserForm(){
	document.getElementById('userModalTitle').innerHTML = '<i class="fas fa-user-plus"></i> Nouvel utilisateur';
	document.getElementById('userAction').value = 'create';
	document.getElementById('userId').value = '';
	document.getElementById('userForm').reset();
	document.getElementById('avatarPreview').src = '';
	// Réinitialiser l'aide du mot de passe
	document.getElementById('passwordHelp').textContent = 'Mot de passe requis pour un nouvel utilisateur.';
}
function openEdit(u){
	const modalLabel = document.getElementById('userModalTitle');
	modalLabel.innerHTML = '<i class="fas fa-user-edit"></i> Modifier utilisateur';
	document.getElementById('userAction').value = 'update';
	document.getElementById('userId').value = u.id;
	document.getElementById('usernameInput').value = u.username || '';
	document.getElementById('fullNameInput').value = u.full_name || '';
	document.getElementById('emailInput').value = u.email || '';
	document.getElementById('phoneInput').value = u.phone || '';
	document.getElementById('roleInput').value = u.role || 'utilisateur';
	document.getElementById('statusInput').value = u.status || 'actif';
	const prev = document.getElementById('avatarPreview');
	prev.src = u.avatar_path || '';
	// Changer l'aide du mot de passe pour la modification
	document.getElementById('passwordHelp').textContent = 'Laisser vide pour ne pas changer (en modification).';
	new bootstrap.Modal(document.getElementById('userModal')).show();
}
document.getElementById('avatarInput')?.addEventListener('change', function(){
	const f = this.files && this.files[0]; if (!f) return;
	const url = URL.createObjectURL(f);
	const prev = document.getElementById('avatarPreview'); prev.src = url;
});



document.getElementById('userForm')?.addEventListener('submit', function(e){
	const action = document.getElementById('userAction').value;
	const username = document.getElementById('usernameInput').value.trim();
	const email = document.getElementById('emailInput').value.trim();
	
	if (!username) { e.preventDefault(); showNotification('Nom d\'utilisateur requis', 'danger'); return; }
	if (!email || !/^[^@]+@[^@]+\.[^@]+$/.test(email)) { e.preventDefault(); showNotification('Email invalide', 'danger'); return; }
	
	if (action === 'create') {
		const pwd = document.getElementById('passwordInput').value;
		if (!pwd || pwd.length < 8) { e.preventDefault(); showNotification('Mot de passe min. 8 caractères', 'danger'); return; }
	}
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


