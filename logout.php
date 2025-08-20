<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';

// Ensure session uses safe params similar to login/dashboard
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_start();

// Clear all session data
$_SESSION = [];

// Delete the session cookie
if (ini_get('session.use_cookies')) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], (bool) $params['secure'], (bool) $params['httponly']);
}

// Destroy the session
session_destroy();

// Redirect to modern login
header('Location: ' . BASE_URL . 'login.php');
exit;


