<?php
/**
 * Configuration spécifique pour le module Alertes & Réapprovisionnement
 * Scolaria Team589
 */

// Configuration des alertes
define('ALERTS_ENABLED', true);
define('ALERTS_AUTO_REFRESH_INTERVAL', 300000); // 5 minutes en millisecondes
define('ALERTS_EMAIL_ENABLED', false);
define('ALERTS_EMAIL_FROM', 'alerts@scolaria.local');
define('ALERTS_EMAIL_ADMIN', 'admin@scolaria.local');

// Configuration des seuils par défaut
define('DEFAULT_STOCK_THRESHOLD', 10);
define('CRITICAL_STOCK_THRESHOLD', 0);
define('LOW_STOCK_MULTIPLIER', 1.5); // Facteur pour calculer les quantités de commande suggérées

// Configuration des commandes
define('MAX_ORDERS_DISPLAY', 50);
define('ORDER_STATUSES', ['en attente', 'validée', 'livrée', 'annulée']);
define('DEFAULT_ORDER_STATUS', 'en attente');

// Configuration des fournisseurs
define('MAX_SUPPLIERS_DISPLAY', 100);
define('SUPPLIER_REQUIRED_FIELDS', ['nom']);
define('SUPPLIER_EMAIL_VALIDATION', true);

// Configuration de l'interface
define('ALERTS_THEME_PRIMARY', '#2563eb');
define('ALERTS_THEME_SUCCESS', '#10b981');
define('ALERTS_THEME_WARNING', '#f59e0b');
define('ALERTS_THEME_DANGER', '#ef4444');

// Configuration des notifications
define('NOTIFICATION_DURATION', 5000); // 5 secondes
define('NOTIFICATION_AUTO_HIDE', true);

// Configuration de la pagination
define('ALERTS_PAGINATION_ENABLED', true);
define('ALERTS_ITEMS_PER_PAGE', 25);

// Configuration des exports (pour futures évolutions)
define('ALERTS_EXPORT_PDF_ENABLED', false);
define('ALERTS_EXPORT_CSV_ENABLED', true);
define('ALERTS_EXPORT_EXCEL_ENABLED', false);

// Configuration de la sécurité
define('ALERTS_CSRF_PROTECTION', false); // À activer en production
define('ALERTS_INPUT_SANITIZATION', true);
define('ALERTS_SQL_INJECTION_PROTECTION', true);

// Configuration des logs
define('ALERTS_LOGGING_ENABLED', true);
define('ALERTS_LOG_LEVEL', 'ERROR'); // DEBUG, INFO, WARNING, ERROR
define('ALERTS_LOG_FILE', 'logs/alerts.log');

// Configuration du cache
define('ALERTS_CACHE_ENABLED', false);
define('ALERTS_CACHE_DURATION', 300); // 5 minutes
define('ALERTS_CACHE_TYPE', 'file'); // file, redis, memcached

// Messages personnalisables
define('ALERTS_MSG_SUCCESS_ORDER', 'Commande créée avec succès.');
define('ALERTS_MSG_SUCCESS_SUPPLIER', 'Fournisseur ajouté avec succès.');
define('ALERTS_MSG_SUCCESS_THRESHOLD', 'Seuil d\'alerte mis à jour avec succès.');
define('ALERTS_MSG_ERROR_ORDER', 'Erreur lors de la création de la commande.');
define('ALERTS_MSG_ERROR_SUPPLIER', 'Erreur lors de l\'ajout du fournisseur.');
define('ALERTS_MSG_ERROR_THRESHOLD', 'Erreur lors de la mise à jour du seuil.');

// Configuration des couleurs d'alertes
$ALERTS_COLORS = [
    'rupture' => [
        'background' => '#fef2f2',
        'border' => '#ef4444',
        'text' => '#991b1b',
        'badge' => '#ef4444'
    ],
    'faible' => [
        'background' => '#fffbeb',
        'border' => '#f59e0b',
        'text' => '#92400e',
        'badge' => '#f59e0b'
    ],
    'normal' => [
        'background' => '#f0fdf4',
        'border' => '#10b981',
        'text' => '#065f46',
        'badge' => '#10b981'
    ]
];

// Configuration des statuts de commandes avec couleurs
$ORDER_STATUS_COLORS = [
    'en attente' => [
        'background' => '#fef3c7',
        'text' => '#92400e',
        'icon' => 'fa-clock'
    ],
    'validée' => [
        'background' => '#d1fae5',
        'text' => '#065f46',
        'icon' => 'fa-check'
    ],
    'livrée' => [
        'background' => '#dbeafe',
        'text' => '#1e40af',
        'icon' => 'fa-truck'
    ],
    'annulée' => [
        'background' => '#fee2e2',
        'text' => '#991b1b',
        'icon' => 'fa-times'
    ]
];

// Configuration des règles de validation
$VALIDATION_RULES = [
    'order' => [
        'article_id' => ['required', 'integer', 'min:1'],
        'fournisseur_id' => ['required', 'integer', 'min:1'],
        'quantite' => ['required', 'integer', 'min:1'],
        'prix_unitaire' => ['numeric', 'min:0'],
        'notes' => ['string', 'max:1000']
    ],
    'supplier' => [
        'nom' => ['required', 'string', 'max:150'],
        'contact' => ['string', 'max:100'],
        'email' => ['email', 'max:150'],
        'telephone' => ['string', 'max:20'],
        'adresse' => ['string', 'max:1000']
    ],
    'threshold' => [
        'article_id' => ['required', 'integer', 'min:1'],
        'new_threshold' => ['required', 'integer', 'min:0']
    ]
];

// Configuration des permissions (pour futures évolutions)
$ALERTS_PERMISSIONS = [
    'view_alerts' => true,
    'create_orders' => true,
    'update_orders' => true,
    'delete_orders' => false,
    'manage_suppliers' => true,
    'update_thresholds' => true,
    'view_statistics' => true,
    'export_data' => false
];

// Configuration des templates d'email (pour futures évolutions)
$EMAIL_TEMPLATES = [
    'stock_alert' => [
        'subject' => 'Alerte Stock - {article_name}',
        'template' => 'emails/stock_alert.html'
    ],
    'order_created' => [
        'subject' => 'Nouvelle commande créée - #{order_id}',
        'template' => 'emails/order_created.html'
    ],
    'order_delivered' => [
        'subject' => 'Commande livrée - #{order_id}',
        'template' => 'emails/order_delivered.html'
    ]
];

// Configuration des webhooks (pour futures évolutions)
$WEBHOOK_ENDPOINTS = [
    'stock_alert' => null,
    'order_created' => null,
    'order_status_changed' => null
];

// Fonctions utilitaires pour la configuration

/**
 * Récupère une configuration d'alerte
 */
function getAlertsConfig($key, $default = null) {
    $configKey = 'ALERTS_' . strtoupper($key);
    return defined($configKey) ? constant($configKey) : $default;
}

/**
 * Récupère les couleurs pour un type d'alerte
 */
function getAlertColors($type) {
    global $ALERTS_COLORS;
    return $ALERTS_COLORS[$type] ?? $ALERTS_COLORS['normal'];
}

/**
 * Récupère les couleurs pour un statut de commande
 */
function getOrderStatusColors($status) {
    global $ORDER_STATUS_COLORS;
    return $ORDER_STATUS_COLORS[$status] ?? $ORDER_STATUS_COLORS['en attente'];
}

/**
 * Récupère les règles de validation pour un type
 */
function getValidationRules($type) {
    global $VALIDATION_RULES;
    return $VALIDATION_RULES[$type] ?? [];
}

/**
 * Vérifie si une permission est accordée
 */
function hasAlertsPermission($permission) {
    global $ALERTS_PERMISSIONS;
    return $ALERTS_PERMISSIONS[$permission] ?? false;
}

/**
 * Génère une configuration JavaScript pour le frontend
 */
function getAlertsJSConfig() {
    return [
        'autoRefreshInterval' => ALERTS_AUTO_REFRESH_INTERVAL,
        'notificationDuration' => NOTIFICATION_DURATION,
        'notificationAutoHide' => NOTIFICATION_AUTO_HIDE,
        'theme' => [
            'primary' => ALERTS_THEME_PRIMARY,
            'success' => ALERTS_THEME_SUCCESS,
            'warning' => ALERTS_THEME_WARNING,
            'danger' => ALERTS_THEME_DANGER
        ],
        'validation' => [
            'emailEnabled' => SUPPLIER_EMAIL_VALIDATION,
            'requiredFields' => SUPPLIER_REQUIRED_FIELDS
        ]
    ];
}

/**
 * Valide la configuration des alertes
 */
function validateAlertsConfig() {
    $required_constants = [
        'ALERTS_ENABLED',
        'DEFAULT_STOCK_THRESHOLD',
        'MAX_ORDERS_DISPLAY',
        'MAX_SUPPLIERS_DISPLAY'
    ];
    
    foreach ($required_constants as $constant) {
        if (!defined($constant)) {
            throw new Exception("Configuration d'alertes manquante : $constant");
        }
    }
    
    // Validation des valeurs
    if (DEFAULT_STOCK_THRESHOLD < 0) {
        throw new Exception("Le seuil de stock par défaut doit être positif");
    }
    
    if (MAX_ORDERS_DISPLAY <= 0) {
        throw new Exception("Le nombre maximum de commandes à afficher doit être positif");
    }
    
    return true;
}

/**
 * Initialise les logs d'alertes
 */
function initAlertsLogging() {
    if (ALERTS_LOGGING_ENABLED) {
        $logDir = dirname(ALERTS_LOG_FILE);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        if (!is_writable($logDir)) {
            error_log("Répertoire de logs d'alertes non accessible en écriture : $logDir");
        }
    }
}

/**
 * Log une action d'alerte
 */
function logAlertsAction($level, $message, $context = []) {
    if (!ALERTS_LOGGING_ENABLED) {
        return;
    }
    
    $levels = ['DEBUG' => 0, 'INFO' => 1, 'WARNING' => 2, 'ERROR' => 3];
    $currentLevel = $levels[ALERTS_LOG_LEVEL] ?? 3;
    $messageLevel = $levels[$level] ?? 0;
    
    if ($messageLevel >= $currentLevel) {
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        file_put_contents(ALERTS_LOG_FILE, $logMessage, FILE_APPEND | LOCK_EX);
    }
}

// Validation de la configuration au chargement
try {
    validateAlertsConfig();
    initAlertsLogging();
    logAlertsAction('INFO', 'Configuration des alertes chargée avec succès');
} catch (Exception $e) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        die("Erreur de configuration des alertes : " . $e->getMessage());
    } else {
        error_log("Erreur de configuration des alertes : " . $e->getMessage());
        logAlertsAction('ERROR', 'Erreur de configuration', ['error' => $e->getMessage()]);
    }
}

// Export de la configuration pour JavaScript (si nécessaire)
if (isset($_GET['js_config']) && $_GET['js_config'] === '1') {
    header('Content-Type: application/json');
    echo json_encode(getAlertsJSConfig());
    exit;
}
?>