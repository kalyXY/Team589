<?php
declare(strict_types=1);

/**
 * Scolaria - Simple MVC front controller / router
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Basic autoloader for controllers and models
spl_autoload_register(function (string $className): void {
    $paths = [
        __DIR__ . '/controllers/' . $className . '.php',
        __DIR__ . '/models/' . $className . '.php',
    ];
    foreach ($paths as $filePath) {
        if (is_readable($filePath)) {
            require_once $filePath;
            return;
        }
    }
});

/**
 * Sanitize a string coming from URL parameters
 */
function sanitize(string $value): string
{
    return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
}

// Routing: controller=c, action=a
$controllerParam = isset($_GET['c']) ? sanitize($_GET['c']) : 'home';
$actionParam = isset($_GET['a']) ? sanitize($_GET['a']) : 'index';

// Security: allow only alphanumeric and underscore
if (!preg_match('/^[A-Za-z0-9_]+$/', $controllerParam)) {
    $controllerParam = 'home';
}
if (!preg_match('/^[A-Za-z0-9_]+$/', $actionParam)) {
    $actionParam = 'index';
}

$controllerClass = ucfirst(strtolower($controllerParam)) . 'Controller';
$actionMethod = strtolower($actionParam);

try {
    if (!class_exists($controllerClass)) {
        throw new RuntimeException('Controller not found: ' . $controllerClass);
    }

    $controllerInstance = new $controllerClass();

    if (!method_exists($controllerInstance, $actionMethod)) {
        throw new RuntimeException('Action not found: ' . $actionMethod);
    }

    // Dispatch
    $controllerInstance->$actionMethod();
} catch (Throwable $e) {
    http_response_code(500);
    // Simple error page without leaking sensitive details in production
    $isDev = defined('APP_ENV') && APP_ENV === 'dev';
    $message = $isDev ? $e->getMessage() : 'Une erreur est survenue.';
    echo '<!doctype html><html lang="fr"><head><meta charset="utf-8"><title>Erreur</title>';
    echo '<link rel="stylesheet" href="assets/css/style.css"></head><body>';
    echo '<div class="container"><h1>Erreur</h1><p>' . $message . '</p></div>';
    echo '</body></html>';
    if ($isDev) {
        error_log($e);
    }
}


