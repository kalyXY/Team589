<?php
/**
 * Script cron pour vérifier automatiquement les alertes de stock
 * À exécuter régulièrement (ex: toutes les heures) pour vérifier les alertes
 * 
 * Usage:
 * - Ajouter dans crontab: 0 * * * * php /path/to/cron_check_stock_alerts.php
 * - Ou exécuter manuellement: php cron_check_stock_alerts.php
 */

// Démarrer la session si nécessaire
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Inclure les fichiers nécessaires
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/notification_system.php';
require_once __DIR__ . '/includes/stock_monitor.php';

// Configuration
$logFile = __DIR__ . '/logs/stock_alerts.log';
$maxNotificationsPerRun = 50; // Limiter le nombre de notifications par exécution

// Créer le dossier de logs s'il n'existe pas
$logDir = dirname($logFile);
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Fonction de logging
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
    
    // Écrire dans le fichier de log
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Afficher dans la console si exécuté manuellement
    if (php_sapi_name() !== 'cli') {
        echo $logMessage;
    }
}

// Vérifier si le script est exécuté en mode CLI ou web
$isCli = php_sapi_name() === 'cli';

if ($isCli) {
    writeLog("=== Début de la vérification des alertes de stock ===");
} else {
    echo "<!DOCTYPE html>
    <html>
    <head>
        <title>Vérification des Alertes de Stock - Scolaria</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            .log-entry { background: #f0f0f0; padding: 10px; margin: 5px 0; border-radius: 5px; font-family: monospace; }
            .success { background: #d4edda; color: #155724; }
            .error { background: #f8d7da; color: #721c24; }
            .warning { background: #fff3cd; color: #856404; }
            .info { background: #d1ecf1; color: #0c5460; }
        </style>
    </head>
    <body>
        <h1>🔍 Vérification des Alertes de Stock</h1>
        <p><strong>Exécuté le:</strong> " . date('Y-m-d H:i:s') . "</p>";
}

try {
    // Vérifier la connexion à la base de données
    $pdo = Database::getConnection();
    writeLog("✅ Connexion à la base de données établie");
    
    // Vérifier si la table notifications existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() == 0) {
        writeLog("⚠️ Table notifications non trouvée, création en cours...");
        NotificationSystem::createNotification(1, "Test", "Test", "info");
        writeLog("✅ Table notifications créée");
    }
    
    // Vérifier les alertes de stock existantes
    writeLog("🔍 Vérification des alertes de stock...");
    
    // Produits en rupture de stock
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stocks WHERE quantite = 0");
    $outOfStockCount = $stmt->fetch()['count'];
    
    // Produits avec stock faible
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stocks WHERE quantite <= seuil_alerte AND quantite > 0");
    $lowStockCount = $stmt->fetch()['count'];
    
    $totalAlerts = $outOfStockCount + $lowStockCount;
    
    writeLog("📊 État des stocks: {$outOfStockCount} ruptures, {$lowStockCount} stocks faibles");
    
    if ($totalAlerts > 0) {
        // Vérifier si des notifications ont déjà été envoyées récemment (dans les dernières 24h)
        $stmt = $pdo->query("
            SELECT COUNT(*) as count FROM notifications 
            WHERE type IN ('error', 'warning') 
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            AND (title LIKE '%rupture%' OR title LIKE '%stock faible%')
        ");
        $recentNotifications = $stmt->fetch()['count'];
        
        if ($recentNotifications < $maxNotificationsPerRun) {
            writeLog("📢 Envoi des notifications d'alerte...");
            
            // Vérifier et notifier des alertes
            $notificationsSent = StockMonitor::checkAndNotify();
            
            writeLog("✅ {$notificationsSent} notifications d'alerte envoyées");
            
            // Générer un rapport
            $report = StockMonitor::generateStockAlertReport();
            writeLog("📋 Rapport généré: {$report['total_alerts']} alertes totales");
            
        } else {
            writeLog("⚠️ Trop de notifications récentes ({$recentNotifications}), envoi limité");
        }
    } else {
        writeLog("✅ Aucune alerte de stock détectée");
    }
    
    // Nettoyer les anciennes notifications (plus de 30 jours)
    writeLog("🧹 Nettoyage des anciennes notifications...");
    $cleanupResult = NotificationSystem::cleanupOldNotifications(30);
    if ($cleanupResult) {
        writeLog("✅ Nettoyage des anciennes notifications terminé");
    } else {
        writeLog("⚠️ Erreur lors du nettoyage des notifications");
    }
    
    // Statistiques finales
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
    $totalNotifications = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0");
    $unreadNotifications = $stmt->fetch()['unread'];
    
    writeLog("📊 Statistiques finales: {$totalNotifications} notifications totales, {$unreadNotifications} non lues");
    
    if ($isCli) {
        writeLog("=== Fin de la vérification des alertes de stock ===");
    } else {
        $notificationsSentDisplay = isset($notificationsSent) ? $notificationsSent : 0;
        echo "<div class='log-entry success'>
            <strong>✅ Vérification terminée avec succès</strong><br>
            <strong>Alertes détectées:</strong> {$totalAlerts}<br>
            <strong>Notifications envoyées:</strong> {$notificationsSentDisplay}<br>
            <strong>Total des notifications:</strong> {$totalNotifications}<br>
            <strong>Notifications non lues:</strong> {$unreadNotifications}
        </div>";
        
        echo "<p><a href='dashboard.php'>← Retour au Dashboard</a></p>
        </body>
        </html>";
    }
    
} catch (Exception $e) {
    $errorMessage = "❌ Erreur critique: " . $e->getMessage();
    writeLog($errorMessage);
    
    if (!$isCli) {
        echo "<div class='log-entry error'>{$errorMessage}</div>";
        echo "<p><a href='dashboard.php'>← Retour au Dashboard</a></p>
        </body>
        </html>";
    }
    
    // En cas d'erreur critique, notifier les admins
    try {
        NotificationSystem::notifySystemIssue(
            "Erreur du script de vérification des alertes",
            $e->getMessage()
        );
        writeLog("📢 Notification d'erreur envoyée aux admins");
    } catch (Exception $notifError) {
        writeLog("❌ Impossible d'envoyer la notification d'erreur: " . $notifError->getMessage());
    }
}
?>
