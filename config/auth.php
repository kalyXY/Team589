<?php
declare(strict_types=1);

/**
 * Helpers d'authentification et d'autorisations
 */

require_once __DIR__ . '/config.php';

/**
 * Démarre une session sécurisée si besoin
 */
function ensure_session_started(): void {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		session_set_cookie_params([
			'lifetime' => 0,
			'path' => '/',
			'domain' => '',
			'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
			'httponly' => true,
			'samesite' => 'Lax',
		]);
		session_start();
	}
}

/**
 * Exige un utilisateur connecté
 */
function require_login(): void {
	ensure_session_started();
	if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
		header('Location: ' . BASE_URL . 'login.php');
		exit;
	}
}

/**
 * Exige que l'utilisateur ait au moins un des rôles fournis
 * @param string[] $roles
 */
function require_roles(array $roles): void {
	require_login();
	$role = (string)($_SESSION['role'] ?? '');
	if (!in_array($role, $roles, true)) {
		header('Location: ' . BASE_URL . 'access_denied.php');
		exit;
	}
}

/**
 * Calcul de la page d'atterrissage selon le rôle
 */
function landing_for_role(string $role): string {
	$r = strtolower(trim($role));
	switch ($r) {
		case 'admin':
		case 'gestionnaire':
			return 'dashboard.php';
		case 'caissier':
			return 'dashboard_caissier.php';
		case 'directeur':
			return 'dashboard_directeur.php';
		default:
			return 'dashboard.php';
	}
}


