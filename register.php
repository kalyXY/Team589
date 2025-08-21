<?php
/**
 * SCOLARIA - Page d'Inscription
 * Création de compte avec rôle visible uniquement pour l'admin
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Configuration de la page
$currentPage = 'register';
$pageTitle = 'Créer un compte - Scolaria';
$showSidebar = false;
$additionalCSS = ['assets/css/auth.css'];
$additionalJS = ['assets/js/auth.js'];
$bodyClass = 'login-page';

// Cookies de session sécurisés
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_start();

$isAdmin = !empty($_SESSION['role']) && $_SESSION['role'] === 'admin';

// Redirection si déjà connecté non-admin (optionnel: on peut autoriser)
// if (!empty($_SESSION['user_id']) && !$isAdmin) {
// 	header('Location: ' . BASE_URL . 'dashboard.php');
// 	exit;
// }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = trim($_POST['username'] ?? '');
	$email = trim($_POST['email'] ?? '');
	$password = $_POST['password'] ?? '';
	$passwordConfirm = $_POST['password_confirm'] ?? '';
	$rolePosted = trim($_POST['role'] ?? '');

	$allowedRoles = ['admin', 'gestionnaire', 'caissier', 'utilisateur'];
	$assignedRole = $isAdmin && in_array($rolePosted, $allowedRoles, true) ? $rolePosted : 'utilisateur';

	// Validations basiques
	if ($username === '' || $email === '' || $password === '' || $passwordConfirm === '') {
		$error = 'Veuillez remplir tous les champs.';
	} elseif (mb_strlen($username) < 3) {
		$error = "Le nom d'utilisateur doit contenir au moins 3 caractères.";
	} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$error = "Format d'email invalide.";
	} elseif (mb_strlen($password) < 6) {
		$error = 'Le mot de passe doit contenir au moins 6 caractères.';
	} elseif (!hash_equals($password, $passwordConfirm)) {
		$error = 'La confirmation du mot de passe ne correspond pas.';
	} else {
		try {
			$pdo = Database::getConnection();
			// Vérifier unicité username/email
			$check = $pdo->prepare('SELECT id, username, email FROM users WHERE username = :username OR email = :email LIMIT 1');
			$check->bindValue(':username', $username, PDO::PARAM_STR);
			$check->bindValue(':email', $email, PDO::PARAM_STR);
			$check->execute();
			$existing = $check->fetch();
			if ($existing) {
				$error = "Nom d'utilisateur ou email déjà utilisé.";
			} else {
				$hash = password_hash($password, PASSWORD_DEFAULT);
				$ins = $pdo->prepare('INSERT INTO users (username, email, password, role) VALUES (:username, :email, :password, :role)');
				$ins->bindValue(':username', $username, PDO::PARAM_STR);
				$ins->bindValue(':email', $email, PDO::PARAM_STR);
				$ins->bindValue(':password', $hash, PDO::PARAM_STR);
				$ins->bindValue(':role', $assignedRole, PDO::PARAM_STR);
				$ins->execute();

				$success = $isAdmin
					? 'Utilisateur créé avec succès.'
					: 'Compte créé. Vous pouvez maintenant vous connecter.';
			}
		} catch (Throwable $e) {
			if (defined('APP_ENV') && APP_ENV === 'dev') {
				error_log('Register error: ' . $e->getMessage());
			}
			$error = 'Une erreur est survenue. Merci de réessayer plus tard.';
		}
	}
}

// Début du contenu HTML
ob_start();
?>

<div class="auth-wrapper">
	<div class="auth-card">
			<div class="auth-header">
				<div class="auth-logo">
					<i class="fas fa-graduation-cap"></i>
					SCOLARIA
				</div>
				<h2 class="auth-title">Créer un compte</h2>
				<p class="auth-subtitle">Ajouter un utilisateur à Scolaria</p>
			</div>

			<?php if ($error): ?>
				<div class="alert alert-error animate-slide-in">
					<i class="fas fa-exclamation-triangle"></i>
					<?= htmlspecialchars($error) ?>
				</div>
			<?php endif; ?>

			<?php if ($success): ?>
				<div class="alert alert-success animate-slide-in">
					<i class="fas fa-check-circle"></i>
					<?= htmlspecialchars($success) ?>
				</div>
			<?php endif; ?>

			<form class="auth-form" method="POST" action="" id="registerForm">
				<div class="form-group">
					<label for="username" class="form-label">
						<i class="fas fa-user"></i>
						Nom d'utilisateur
					</label>
					<input 
						type="text" 
						id="username" 
						name="username" 
						class="form-control" 
						placeholder="Entrez le nom d'utilisateur"
						value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
						required
						autocomplete="username"
					>
				</div>

				<div class="form-group">
					<label for="email" class="form-label">
						<i class="fas fa-envelope"></i>
						Email
					</label>
					<input 
						type="email" 
						id="email" 
						name="email" 
						class="form-control" 
						placeholder="Entrez l'email"
						value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
						required
						autocomplete="email"
					>
				</div>

				<div class="form-group password-field">
					<label for="password" class="form-label">
						<i class="fas fa-lock"></i>
						Mot de passe
					</label>
					<input 
						type="password" 
						id="password" 
						name="password" 
						class="form-control" 
						placeholder="Entrez le mot de passe"
						required
						autocomplete="new-password"
					>
					<button type="button" class="password-toggle" onclick="togglePassword()">
						<i class="fas fa-eye" id="passwordIcon"></i>
					</button>
				</div>

				<div class="form-group password-field">
					<label for="password_confirm" class="form-label">
						<i class="fas fa-lock"></i>
						Confirmer le mot de passe
					</label>
					<input 
						type="password" 
						id="password_confirm" 
						name="password_confirm" 
						class="form-control" 
						placeholder="Confirmez le mot de passe"
						required
						autocomplete="new-password"
					>
				</div>

				<?php if ($isAdmin): ?>
					<div class="form-group">
						<label for="role" class="form-label">
							<i class="fas fa-user-shield"></i>
							Rôle
						</label>
						<select id="role" name="role" class="form-control">
							<option value="utilisateur" <?= (($_POST['role'] ?? '') === 'utilisateur') ? 'selected' : '' ?>>Utilisateur</option>
							<option value="caissier" <?= (($_POST['role'] ?? '') === 'caissier') ? 'selected' : '' ?>>Caissier</option>
							<option value="gestionnaire" <?= (($_POST['role'] ?? '') === 'gestionnaire') ? 'selected' : '' ?>>Gestionnaire</option>
							<option value="admin" <?= (($_POST['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
						</select>
					</div>
				<?php endif; ?>

				<button type="submit" class="btn btn-primary btn-auth">
					<i class="fas fa-user-plus"></i>
					Créer le compte
				</button>
			</form>

			<div class="auth-links">
				<a href="login.php" class="auth-link">
					<i class="fas fa-sign-in-alt"></i>
					Déjà un compte ? Se connecter
				</a>
			</div>
		</div>
	</div>

<?php
$content = ob_get_clean();

// Inclure le layout d'authentification
include __DIR__ . '/layout/auth.php';
?>


