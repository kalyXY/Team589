<?php
/**
 * Paramètres & Sécurité – Mama Sophie – Scolaria
 * Technologies: PHP/MySQL/HTML/CSS/JS + Bootstrap 5
 * Accès: Admin uniquement
 */

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Vérification stricte du rôle Admin
if (!isset($_SESSION['role']) || strtolower((string)$_SESSION['role']) !== 'admin') {
	header('Location: access_denied.php');
	exit;
}

// Page config
$currentPage = 'settings';
$pageTitle = 'Paramètres & Sécurité';
// Ajouter Bootstrap 5
$additionalCSS = [
	'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css'
];
$additionalJS = [
	'https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js'
];

// Connexion DB
$pdo = Database::getConnection();

// Créer les tables nécessaires si absentes (pas de données mockées)
// Table: school_settings (singleton row id=1)
$pdo->exec("CREATE TABLE IF NOT EXISTS school_settings (
	id INT PRIMARY KEY DEFAULT 1,
	school_name VARCHAR(200) NOT NULL DEFAULT '',
	address TEXT NULL,
	phone VARCHAR(50) NULL,
	email VARCHAR(150) NULL,
	logo_path VARCHAR(255) NULL,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// S'assurer qu'une ligne existe
$pdo->exec("INSERT IGNORE INTO school_settings (id, school_name) VALUES (1, '')");

// Table: system_config (singleton row id=1)
$pdo->exec("CREATE TABLE IF NOT EXISTS system_config (
	id INT PRIMARY KEY DEFAULT 1,
	min_stock_threshold INT NOT NULL DEFAULT 1,
	payment_modes JSON NULL,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
$pdo->exec("INSERT IGNORE INTO system_config (id, min_stock_threshold, payment_modes) VALUES (1, 1, JSON_ARRAY())");

// Table: roles_custom (facultatif, complémentaire au enum users.role)
$pdo->exec("CREATE TABLE IF NOT EXISTS roles_custom (
	id INT AUTO_INCREMENT PRIMARY KEY,
	name VARCHAR(100) NOT NULL UNIQUE,
	permissions JSON NULL,
	created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Table: login_history
$pdo->exec("CREATE TABLE IF NOT EXISTS login_history (
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL,
	ip_address VARCHAR(45) NULL,
	user_agent VARCHAR(255) NULL,
	logged_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX (user_id),
	INDEX (logged_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Table: security_flags (pour global logout historisé)
$pdo->exec("CREATE TABLE IF NOT EXISTS security_flags (
	flag VARCHAR(64) PRIMARY KEY,
	value_text TEXT NULL,
	updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Helpers sécurités
function sanitize_string(string $value, int $maxLen = 500): string {
	$value = trim($value);
	$value = substr($value, 0, $maxLen);
	return $value;
}

function json_response(array $payload) {
	header('Content-Type: application/json');
	echo json_encode($payload);
	exit;
}

$toast = ['type' => '', 'message' => ''];

// Gestion des POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';

	try {
		if ($action === 'save_school_info') {
			$school_name = sanitize_string($_POST['school_name'] ?? '', 200);
			$address = sanitize_string($_POST['address'] ?? '', 1000);
			$phone = sanitize_string($_POST['phone'] ?? '', 50);
			$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $_POST['email'] : null;

			$logoPath = null;
			if (!empty($_FILES['logo']['name']) && is_uploaded_file($_FILES['logo']['tmp_name'])) {
				$allowed = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/jpg' => 'jpg', 'image/webp' => 'webp'];
				$mime = mime_content_type($_FILES['logo']['tmp_name']);
				if (!isset($allowed[$mime])) {
					throw new RuntimeException('Format de logo non supporté. Utilisez PNG/JPG/WebP.');
				}
				$ext = $allowed[$mime];
				$destDir = __DIR__ . '/assets/images';
				if (!is_dir($destDir)) { mkdir($destDir, 0775, true); }
				$destRel = 'assets/images/school_logo.' . $ext;
				$destAbs = __DIR__ . '/' . $destRel;
				// Supprimer anciens logos éventuels
				foreach (['png','jpg','webp'] as $oldExt) {
					$old = __DIR__ . '/assets/images/school_logo.' . $oldExt;
					if (is_file($old)) { @unlink($old); }
				}
				if (!move_uploaded_file($_FILES['logo']['tmp_name'], $destAbs)) {
					throw new RuntimeException('Échec de l\'upload du logo.');
				}
				$logoPath = $destRel;
			}

			$sql = "UPDATE school_settings
				SET school_name = :name, address = :addr, phone = :phone, email = :email" . ($logoPath ? ", logo_path = :logo" : '') . "
				WHERE id = 1";
			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':name', $school_name);
			$stmt->bindValue(':addr', $address);
			$stmt->bindValue(':phone', $phone);
			$stmt->bindValue(':email', $email);
			if ($logoPath) { $stmt->bindValue(':logo', $logoPath); }
			$stmt->execute();
			$toast = ['type' => 'success', 'message' => 'Informations de l\'école enregistrées.'];
		}

		if ($action === 'update_system_config') {
			$minStock = max(0, (int)($_POST['min_stock_threshold'] ?? 0));
			$modes = $_POST['payment_modes'] ?? [];
			if (!is_array($modes)) { $modes = []; }
			// normaliser
			$validModes = ['cash','mobile_money','card'];
			$normalized = array_values(array_intersect($validModes, array_map('strtolower', $modes)));
			$json = json_encode($normalized);

			$stmt = $pdo->prepare("UPDATE system_config SET min_stock_threshold = :thr, payment_modes = CAST(:pm AS JSON) WHERE id = 1");
			$stmt->bindValue(':thr', $minStock, PDO::PARAM_INT);
			$stmt->bindValue(':pm', $json, PDO::PARAM_STR);
			$stmt->execute();
			$toast = ['type' => 'success', 'message' => 'Configuration système mise à jour.'];
		}

		if ($action === 'save_role') {
			$roleName = sanitize_string($_POST['role_name'] ?? '', 100);
			$permissions = $_POST['permissions'] ?? [];
			if (!is_array($permissions)) { $permissions = []; }
			$permissions = array_values(array_unique(array_map('strtolower', $permissions)));
			$json = json_encode($permissions);
			// upsert simple
			$stmt = $pdo->prepare("INSERT INTO roles_custom (name, permissions) VALUES (:n, CAST(:p AS JSON))
				ON DUPLICATE KEY UPDATE permissions = VALUES(permissions), updated_at = NOW()");
			$stmt->bindValue(':n', $roleName);
			$stmt->bindValue(':p', $json);
			$stmt->execute();
			$toast = ['type' => 'success', 'message' => 'Rôle enregistré.'];
		}

		if ($action === 'change_password') {
			$userId = (int)($_SESSION['user_id'] ?? 0);
			$old = (string)($_POST['old_password'] ?? '');
			$new = (string)($_POST['new_password'] ?? '');
			$confirm = (string)($_POST['confirm_password'] ?? '');
			if ($new === '' || $new !== $confirm) {
				throw new RuntimeException('Les mots de passe ne correspondent pas.');
			}
			// Charger hash
			$stmt = $pdo->prepare('SELECT password FROM users WHERE id = :id');
			$stmt->execute([':id' => $userId]);
			$row = $stmt->fetch();
			if (!$row || !password_verify($old, (string)$row['password'])) {
				throw new RuntimeException('Ancien mot de passe invalide.');
			}
			$newHash = password_hash($new, PASSWORD_DEFAULT);
			$pdo->prepare('UPDATE users SET password = :p WHERE id = :id')->execute([':p' => $newHash, ':id' => $userId]);
			$toast = ['type' => 'success', 'message' => 'Mot de passe mis à jour.'];
		}

		if ($action === 'force_logout_all') {
			// Écrit un indicateur qui peut être consulté par le middleware d'auth ultérieurement
			$stmt = $pdo->prepare("INSERT INTO security_flags (flag, value_text) VALUES ('global_logout', NOW())
				ON DUPLICATE KEY UPDATE value_text = VALUES(value_text), updated_at = NOW()");
			$stmt->execute();
			$toast = ['type' => 'success', 'message' => 'Toutes les sessions seront déconnectées.'];
		}
	} catch (Throwable $e) {
		$toast = ['type' => 'danger', 'message' => $e->getMessage()];
	}
}

// Charger données actuelles
$school = $pdo->query('SELECT * FROM school_settings WHERE id = 1')->fetch() ?: [];
$config = $pdo->query('SELECT * FROM system_config WHERE id = 1')->fetch() ?: [];
$rolesCustom = $pdo->query('SELECT * FROM roles_custom ORDER BY name')->fetchAll() ?: [];

// Obtenir liste des rôles existants depuis users.role (enum) + customs
$rolesExisting = [];
try {
	$schemaStmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'role'");
	$roleCol = $schemaStmt->fetch();
	if ($roleCol && isset($roleCol['Type'])) {
		if (preg_match("/enum\((.+)\)/i", $roleCol['Type'], $m)) {
			$vals = array_map(function($s){ return trim($s, "'\""); }, explode(',', $m[1]));
			$rolesExisting = $vals;
		}
	}
} catch (Throwable $e) {
	$rolesExisting = [];
}

// Historique des connexions (20 derniers)
$history = $pdo->query('SELECT lh.*, u.username FROM login_history lh LEFT JOIN users u ON u.id = lh.user_id ORDER BY lh.logged_at DESC LIMIT 20')->fetchAll() ?: [];

// Rendu
ob_start();
?>
<style>
/* Scope Bootstrap look to settings root to avoid global CSS conflicts */
#settingsRoot .card { border-radius: .75rem; box-shadow: 0 .5rem 1rem rgba(0,0,0,.08); }
#settingsRoot .nav-tabs .nav-link { font-weight: 600; }
#settingsRoot .nav-tabs .nav-link.active { color: #0d6efd; }
#settingsRoot .btn { border-radius: .5rem; }
#settingsRoot .table > :not(caption) > * > * { padding: .75rem 1rem; }
#settingsRoot .form-control, #settingsRoot .form-check-input { box-shadow: none; }

/* Ré-assertion du style du menu latéral face aux classes Bootstrap */
.sidebar-logo {
	background: var(--primary-color) !important;
	color: white !important;
	padding: 1rem !important;
	text-align: center !important;
	font-weight: bold !important;
}
.sidebar .nav-link {
	display: flex;
	align-items: center;
	gap: var(--spacing-md);
	padding: var(--spacing-md) var(--spacing-lg);
	color: var(--text-secondary);
	text-decoration: none;
	font-weight: 500;
	transition: all var(--transition-fast);
	position: relative;
	border-radius: 0;
	background-color: transparent !important;
	border: none !important;
}
.sidebar .nav-link:hover {
	background-color: var(--primary-light) !important;
	color: var(--primary-color) !important;
}
.sidebar .nav-link.active {
	background-color: var(--primary-color) !important;
	color: var(--text-white) !important;
}
.sidebar .nav-link.active::before {
	content: '';
	position: absolute;
	left: 0;
	top: 0;
	bottom: 0;
	width: 4px;
	background-color: var(--secondary-color);
}
.sidebar .nav-icon { width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center; font-size: var(--font-size-lg); }
.sidebar .nav-text { white-space: nowrap; }
</style>

<div id="settingsRoot" class="container py-4">
	<?php if ($toast['type']): ?>
		<div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;">
			<div class="toast text-bg-<?php echo $toast['type'] === 'danger' ? 'danger' : 'success'; ?> show" id="notificationToast" role="alert" aria-live="assertive" aria-atomic="true">
				<div class="toast-body d-flex align-items-center">
					<i class="fas fa-<?php echo $toast['type'] === 'danger' ? 'exclamation-triangle' : 'check-circle'; ?> me-2"></i>
					<?php echo htmlspecialchars($toast['message']); ?>
				</div>
				<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
			</div>
		</div>
	<?php endif; ?>

	<ul class="nav nav-tabs" id="settingsTabs" role="tablist">
		<li class="nav-item" role="presentation">
			<button class="nav-link active" id="school-tab" data-bs-toggle="tab" data-bs-target="#school" type="button" role="tab" aria-controls="school" aria-selected="true"><i class="fas fa-school"></i> Informations école</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="system-tab" data-bs-toggle="tab" data-bs-target="#system" type="button" role="tab" aria-controls="system" aria-selected="false"><i class="fas fa-sliders-h"></i> Configuration système</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="security-tab" data-bs-toggle="tab" data-bs-target="#security" type="button" role="tab" aria-controls="security" aria-selected="false"><i class="fas fa-user-shield"></i> Sécurité & utilisateurs</button>
		</li>
		<li class="nav-item" role="presentation">
			<button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account" type="button" role="tab" aria-controls="account" aria-selected="false"><i class="fas fa-user-cog"></i> Compte administrateur</button>
		</li>
	</ul>

	<div class="tab-content pt-3" id="settingsTabsContent">
		<!-- Onglet 1: Informations de l'école -->
		<div class="tab-pane fade show active" id="school" role="tabpanel" aria-labelledby="school-tab">
			<div class="card shadow-lg rounded-3">
				<div class="card-body">
					<h5 class="card-title mb-3">Informations de l'école</h5>
					<form class="row g-3" method="post" enctype="multipart/form-data" id="schoolForm">
						<input type="hidden" name="action" value="save_school_info">
						<div class="col-md-3 text-center">
							<div class="mb-3">
								<img id="logoPreview" src="<?php echo htmlspecialchars($school['logo_path'] ?? ''); ?>" class="img-thumbnail" alt="Logo" style="max-height: 160px; <?php echo empty($school['logo_path']) ? 'display:none;' : ''; ?>">
							</div>
							<div>
								<label for="logo" class="form-label">Logo (PNG/JPG/WebP)</label>
								<input class="form-control" type="file" id="logo" name="logo" accept="image/png,image/jpeg,image/jpg,image/webp">
							</div>
						</div>
						<div class="col-md-9">
							<div class="row g-3">
								<div class="col-12">
									<label class="form-label">Nom de l'école</label>
									<input type="text" class="form-control" name="school_name" required value="<?php echo htmlspecialchars($school['school_name'] ?? ''); ?>">
								</div>
								<div class="col-12">
									<label class="form-label">Adresse complète</label>
									<textarea class="form-control" name="address" rows="3"><?php echo htmlspecialchars($school['address'] ?? ''); ?></textarea>
								</div>
								<div class="col-md-6">
									<label class="form-label">Téléphone</label>
									<input type="text" class="form-control" name="phone" value="<?php echo htmlspecialchars($school['phone'] ?? ''); ?>">
								</div>
								<div class="col-md-6">
									<label class="form-label">Email</label>
									<input type="email" class="form-control" name="email" value="<?php echo htmlspecialchars($school['email'] ?? ''); ?>">
								</div>
							</div>
						</div>
						<div class="col-12 text-end">
							<button class="btn btn-primary"><i class="fas fa-save"></i> Enregistrer</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Onglet 2: Configuration système -->
		<div class="tab-pane fade" id="system" role="tabpanel" aria-labelledby="system-tab">
			<div class="card shadow-lg rounded-3">
				<div class="card-body">
					<h5 class="card-title mb-3">Configuration système</h5>
					<form class="row g-3" method="post" id="systemForm">
						<input type="hidden" name="action" value="update_system_config">
						<div class="col-md-4">
							<label class="form-label">Seuil minimum de stock</label>
							<input type="number" min="0" class="form-control" name="min_stock_threshold" value="<?php echo (int)($config['min_stock_threshold'] ?? 1); ?>" required>
						</div>
						<div class="col-md-8">
							<label class="form-label">Modes de paiement</label>
							<?php
								$selected = [];
								if (!empty($config['payment_modes'])) {
									$decoded = json_decode((string)$config['payment_modes'], true);
									if (is_array($decoded)) { $selected = $decoded; }
								}
							?>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="checkbox" id="pmCash" name="payment_modes[]" value="cash" <?php echo in_array('cash', $selected, true) ? 'checked' : ''; ?>>
								<label class="form-check-label" for="pmCash">Espèces</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="checkbox" id="pmMM" name="payment_modes[]" value="mobile_money" <?php echo in_array('mobile_money', $selected, true) ? 'checked' : ''; ?>>
								<label class="form-check-label" for="pmMM">Mobile Money</label>
							</div>
							<div class="form-check form-check-inline">
								<input class="form-check-input" type="checkbox" id="pmCard" name="payment_modes[]" value="card" <?php echo in_array('card', $selected, true) ? 'checked' : ''; ?>>
								<label class="form-check-label" for="pmCard">Carte</label>
							</div>
						</div>
						<div class="col-12 text-end">
							<button class="btn btn-warning"><i class="fas fa-save"></i> Mettre à jour</button>
						</div>
					</form>
				</div>
			</div>
		</div>

		<!-- Onglet 3: Sécurité & Utilisateurs -->
		<div class="tab-pane fade" id="security" role="tabpanel" aria-labelledby="security-tab">
			<div class="row g-3">
				<div class="col-lg-6">
					<div class="card shadow-lg rounded-3 h-100">
						<div class="card-body">
							<h5 class="card-title mb-3">Gestion des rôles</h5>
							<p class="text-muted">Rôles existants (schéma users.role):</p>
							<div class="mb-3">
								<?php if (!empty($rolesExisting)): ?>
									<span class="badge bg-secondary me-1 mb-1"><?php echo implode('</span> <span class="badge bg-secondary me-1 mb-1">', array_map('htmlspecialchars', $rolesExisting)); ?></span>
								<?php else: ?>
									<em>Aucun rôle détecté depuis la colonne enum.</em>
								<?php endif; ?>
							</div>

							<h6 class="mt-3">Ajouter/Modifier un rôle (custom)</h6>
							<form class="row g-2" method="post">
								<input type="hidden" name="action" value="save_role">
								<div class="col-12">
									<label class="form-label">Nom du rôle</label>
									<input type="text" name="role_name" class="form-control" required>
								</div>
								<div class="col-12">
									<label class="form-label">Permissions</label>
									<div class="d-flex flex-wrap gap-3">
										<div class="form-check">
											<input class="form-check-input" type="checkbox" value="stocks.read" name="permissions[]" id="perm1">
											<label class="form-check-label" for="perm1">stocks.read</label>
										</div>
										<div class="form-check">
											<input class="form-check-input" type="checkbox" value="stocks.write" name="permissions[]" id="perm2">
											<label class="form-check-label" for="perm2">stocks.write</label>
										</div>
										<div class="form-check">
											<input class="form-check-input" type="checkbox" value="finances.read" name="permissions[]" id="perm3">
											<label class="form-check-label" for="perm3">finances.read</label>
										</div>
										<div class="form-check">
											<input class="form-check-input" type="checkbox" value="finances.write" name="permissions[]" id="perm4">
											<label class="form-check-label" for="perm4">finances.write</label>
										</div>
									</div>
								</div>
								<div class="col-12 text-end">
									<button class="btn btn-success"><i class="fas fa-save"></i> Enregistrer le rôle</button>
								</div>
							</form>

							<?php if (!empty($rolesCustom)): ?>
								<div class="mt-3">
									<h6>Rôles personnalisés</h6>
									<ul class="list-group">
										<?php foreach ($rolesCustom as $rc): ?>
											<li class="list-group-item d-flex justify-content-between align-items-center">
												<span>
													<strong><?php echo htmlspecialchars($rc['name']); ?></strong>
													<small class="text-muted">Permissions: <?php echo htmlspecialchars($rc['permissions']); ?></small>
												</span>
											</li>
										<?php endforeach; ?>
									</ul>
								</div>
							<?php endif; ?>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="card shadow-lg rounded-3 h-100">
						<div class="card-body">
							<h5 class="card-title mb-3">Historique des connexions</h5>
							<div class="table-responsive">
								<table class="table table-hover align-middle">
									<thead class="table-light">
										<tr>
											<th>Utilisateur</th>
											<th>IP</th>
											<th>Date/Heure</th>
										</tr>
									</thead>
									<tbody>
										<?php if (empty($history)): ?>
											<tr><td colspan="3" class="text-center text-muted">Aucun historique disponible</td></tr>
										<?php else: foreach ($history as $h): ?>
											<tr>
												<td><?php echo htmlspecialchars($h['username'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($h['ip_address'] ?? ''); ?></td>
												<td><?php echo htmlspecialchars($h['logged_at'] ?? ''); ?></td>
											</tr>
										<?php endforeach; endif; ?>
									</tbody>
								</table>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Onglet 4: Compte administrateur -->
		<div class="tab-pane fade" id="account" role="tabpanel" aria-labelledby="account-tab">
			<div class="card shadow-lg rounded-3">
				<div class="card-body">
					<h5 class="card-title mb-3">Compte administrateur</h5>
					<form class="row g-3" method="post" id="passwordForm">
						<input type="hidden" name="action" value="change_password">
						<div class="col-md-4">
							<label class="form-label">Ancien mot de passe</label>
							<input type="password" name="old_password" class="form-control" required>
						</div>
						<div class="col-md-4">
							<label class="form-label">Nouveau mot de passe</label>
							<input type="password" name="new_password" class="form-control" required minlength="8">
						</div>
						<div class="col-md-4">
							<label class="form-label">Confirmation</label>
							<input type="password" name="confirm_password" class="form-control" required minlength="8">
						</div>
						<div class="col-12 d-flex justify-content-between">
							<button class="btn btn-primary"><i class="fas fa-key"></i> Mettre à jour le mot de passe</button>
							<form method="post" class="ms-auto">
								<input type="hidden" name="action" value="force_logout_all">
								<button class="btn btn-outline-danger" onclick="return confirm('Déconnecter toutes les sessions ?');"><i class="fas fa-sign-out-alt"></i> Déconnexion toutes sessions</button>
							</form>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
// Prévisualisation du logo
document.getElementById('logo')?.addEventListener('change', function() {
	const file = this.files && this.files[0];
	if (!file) return;
	const url = URL.createObjectURL(file);
	const img = document.getElementById('logoPreview');
	if (img) { img.src = url; img.style.display = 'inline-block'; }
});

// Validation simple côté client
document.getElementById('systemForm')?.addEventListener('submit', function(e){
	const thr = parseInt(this.min_stock_threshold.value || '0', 10);
	if (isNaN(thr) || thr < 0) {
		e.preventDefault();
		showError('Seuil minimum invalide');
	}
});

document.getElementById('passwordForm')?.addEventListener('submit', function(e){
	const np = this.new_password.value;
	const cp = this.confirm_password.value;
	if (np.length < 8 || np !== cp) {
		e.preventDefault();
		showError('Mot de passe: au moins 8 caractères et confirmation identique.');
	}
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


