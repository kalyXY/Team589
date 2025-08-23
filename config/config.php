<?php
/**
 * Fichier de configuration pour le module Gestion des Stocks - Scolaria
 * Team589 - Configuration centralisée
 */

// Configuration de la base de données
// Configuration pour Laragon (par défaut)
define('DB_HOST', 'localhost');
define('DB_NAME', 'scolaria');
define('DB_USER', 'root');
define('DB_PASS', ''); // Laragon utilise souvent un mot de passe vide par défaut
define('DB_CHARSET', 'utf8mb4');

// Si Laragon utilise un port différent, décommentez et modifiez :
// define('DB_PORT', '3306');

// Configuration de l'application
define('APP_NAME', 'Scolaria - Gestion des Stocks');
define('APP_VERSION', '1.0');
define('TEAM_NAME', 'Team589');

// Configuration des limites
define('MAX_ITEMS_PER_PAGE', 50);
define('MAX_HISTORY_ITEMS', 100);
define('MIN_STOCK_THRESHOLD', 1);

// Configuration des alertes
define('ENABLE_LOW_STOCK_ALERTS', true);
define('LOW_STOCK_CSS_CLASS', 'low-stock');

// Configuration de sécurité
define('ENABLE_CSRF_PROTECTION', false); // À activer en production
define('SESSION_TIMEOUT', 3600); // 1 heure en secondes

// Configuration des messages
define('SUCCESS_ADD_MESSAGE', 'Article ajouté avec succès !');
define('SUCCESS_UPDATE_MESSAGE', 'Article modifié avec succès !');
define('SUCCESS_DELETE_MESSAGE', 'Article supprimé avec succès !');
define('ERROR_ADD_MESSAGE', 'Erreur lors de l\'ajout de l\'article.');
define('ERROR_UPDATE_MESSAGE', 'Erreur lors de la modification de l\'article.');
define('ERROR_DELETE_MESSAGE', 'Erreur lors de la suppression de l\'article.');
define('ERROR_DB_CONNECTION', 'Erreur de connexion à la base de données.');


// Configuration de l'interface
define('ITEMS_PER_PAGE_OPTIONS', '10,25,50,100');
define('DEFAULT_ITEMS_PER_PAGE', 25);
define('ENABLE_DARK_MODE', false);
define('ENABLE_PRINT_MODE', true);

// Configuration des exports (pour futures évolutions)
define('ENABLE_EXPORT_PDF', false);
define('ENABLE_EXPORT_EXCEL', false);
define('ENABLE_EXPORT_CSV', true);

// Configuration des notifications (pour futures évolutions)
define('ENABLE_EMAIL_NOTIFICATIONS', false);
define('EMAIL_FROM', 'noreply@scolaria.local');
define('EMAIL_ADMIN', 'admin@scolaria.local');

// Configuration du cache (pour futures évolutions)
define('ENABLE_CACHE', false);
define('CACHE_DURATION', 300); // 5 minutes

// URL de base pour générer des liens absolus (se termine par /)
$__scriptName = $_SERVER['SCRIPT_NAME'] ?? '/';
$__basePath = rtrim(str_replace('\\', '/', dirname($__scriptName)), '/');
if ($__basePath === '' || $__basePath === '.' || $__basePath === '/') {
	$__basePath = '/';
} else {
	$__basePath .= '/';
}
define('BASE_URL', $__basePath);

// Configuration de debug
define('DEBUG_MODE', false);
define('LOG_ERRORS', true);
define('LOG_FILE', 'logs/scolaria_errors.log');

// Fonction utilitaire pour obtenir la configuration
function getConfig($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

// Fonction pour valider la configuration
function validateConfig() {
    $required_constants = [
        'DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS',
        'APP_NAME', 'APP_VERSION', 'TEAM_NAME'
    ];
    
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            throw new Exception("Configuration manquante : $constant");
        }
    }
    
    return true;
}

// Fonction pour obtenir la chaîne de connexion PDO
function getDSN() {
    return sprintf(
        "mysql:host=%s;dbname=%s;charset=%s",
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );
}

// Configuration des couleurs de l'interface (correspondant au CSS)
$THEME_COLORS = [
    'primary' => '#2563eb',
    'primary_hover' => '#1d4ed8',
    'success' => '#10b981',
    'warning' => '#f59e0b',
    'danger' => '#ef4444',
    'secondary' => '#6b7280',
    'light_gray' => '#f9fafb',
    'medium_gray' => '#e5e7eb',
    'dark_gray' => '#374151'
];

// Validation de la configuration au chargement
try {
    validateConfig();
} catch (Exception $e) {
    if (DEBUG_MODE) {
        die("Erreur de configuration : " . $e->getMessage());
    } else {
        error_log("Erreur de configuration : " . $e->getMessage());
        die("Erreur de configuration de l'application.");
    }
}
?>