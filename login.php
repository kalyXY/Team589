<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Configure secure session cookies
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_start();

$errorMessage = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$username = isset($_POST['username']) ? trim((string) $_POST['username']) : '';
	$password = isset($_POST['password']) ? (string) $_POST['password'] : '';

	if ($username === '' || $password === '') {
		$errorMessage = 'Veuillez renseigner le nom d\'utilisateur et le mot de passe.';
	} else {
		try {
			$pdo = Database::getConnection();
			// Prevent timing attacks by always preparing/executing
			$stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1');
			$stmt->bindValue(':username', $username, PDO::PARAM_STR);
			$stmt->execute();
			$user = $stmt->fetch();

			if ($user && is_string($user['password']) && password_verify($password, $user['password'])) {
				// Regenerate session ID to prevent fixation
				session_regenerate_id(true);
				$_SESSION['user_id'] = (int) $user['id'];
				$_SESSION['username'] = (string) $user['username'];
				$_SESSION['role'] = (string) $user['role'];
				header('Location: dashboard.php');
				exit;
			}

			$errorMessage = 'Identifiants invalides. Veuillez réessayer.';
		} catch (Throwable $e) {
			if (APP_ENV === 'dev') {
				error_log('Login error: ' . $e->getMessage());
			}
			$errorMessage = 'Une erreur est survenue. Merci de réessayer plus tard.';
		}
	}
}
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Connexion · Scolaria</title>
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
</head>
<body>
	<div class="auth-wrapper">
		<div class="auth-card" role="main">
			<div class="auth-logo" aria-label="Logo Scolaria">SCOLARIA</div>
			<h1 class="auth-title">Connexion</h1>
			<?php if (!empty($errorMessage)): ?>
				<div class="alert-error" role="alert"><?php echo htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8'); ?></div>
			<?php endif; ?>
			<form id="loginForm" class="auth-form" method="post" action="<?php echo htmlspecialchars(BASE_URL . 'login.php', ENT_QUOTES, 'UTF-8'); ?>" novalidate>
				<div class="form-group">
					<label for="username">Nom d'utilisateur</label>
					<input type="text" id="username" name="username" placeholder="Votre identifiant" required autocomplete="username">
				</div>
				<div class="form-group">
					<label for="password">Mot de passe</label>
					<input type="password" id="password" name="password" placeholder="Votre mot de passe" required autocomplete="current-password">
				</div>
				<button type="submit" class="btn btn-primary">Connexion</button>
			</form>
		</div>
	</div>

	<script>
	(function () {
		'use strict';
		var form = document.getElementById('loginForm');
		form.addEventListener('submit', function (e) {
			var username = document.getElementById('username');
			var password = document.getElementById('password');
			var errors = [];
			if (!username.value.trim()) { errors.push('username'); }
			if (!password.value.trim()) { errors.push('password'); }
			if (errors.length > 0) {
				e.preventDefault();
				alert('Veuillez remplir tous les champs.');
			}
		});
	})();
	</script>
</body>
</html>


