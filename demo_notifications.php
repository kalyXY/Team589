<?php
/**
 * Démonstration du système de notifications automatiques
 * Montre comment les notifications sont générées pour les alertes de stock
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/notification_system.php';
require_once __DIR__ . '/includes/stock_monitor.php';

session_start();

// Créer une session de test si nécessaire
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'Admin Test';
    $_SESSION['role'] = 'admin';
}

echo "<!DOCTYPE html>
<html>
<head>
    <title>Démo Notifications Automatiques - Scolaria</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .demo-section { background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .demo-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; text-align: center; }
        .demo-title { font-size: 2.5em; margin: 0; }
        .demo-subtitle { font-size: 1.2em; margin: 10px 0 0 0; opacity: 0.9; }
        .btn { display: inline-block; padding: 12px 24px; margin: 10px; background: #007bff; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; transition: all 0.3s; }
        .btn:hover { background: #0056b3; transform: translateY(-2px); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-info { background: #17a2b8; }
        .btn-info:hover { background: #138496; }
        .status-box { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; margin: 15px 0; }
        .status-success { border-color: #28a745; background: #d4edda; color: #155724; }
        .status-warning { border-color: #ffc107; background: #fff3cd; color: #856404; }
        .status-error { border-color: #dc3545; background: #f8d7da; color: #721c24; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; margin: 20px 0; }
        .feature-card { background: white; padding: 20px; border-radius: 8px; border-left: 4px solid #007bff; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .feature-icon { font-size: 2em; margin-bottom: 15px; }
        .log-section { background: #2d3748; color: #e2e8f0; padding: 20px; border-radius: 8px; font-family: 'Courier New', monospace; max-height: 400px; overflow-y: auto; }
        .log-entry { margin: 5px 0; padding: 5px; border-radius: 4px; }
        .log-success { background: #22543d; }
        .log-warning { background: #744210; }
        .log-error { background: #742a2a; }
        .log-info { background: #2a4365; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='demo-header'>
            <h1 class='demo-title'>🚀 Démonstration</h1>
            <p class='demo-subtitle'>Système de Notifications Automatiques Scolaria</p>
        </div>";

// Traitement des actions
$logMessages = [];
$action = $_GET['action'] ?? '';

if ($action) {
    try {
        switch ($action) {
            case 'check_alerts':
                $logMessages[] = ['type' => 'info', 'message' => '🔍 Vérification des alertes de stock...'];
                
                $alertsCount = StockMonitor::checkAndNotify();
                $report = StockMonitor::generateStockAlertReport();
                
                $logMessages[] = ['type' => 'success', 'message' => "✅ {$alertsCount} alertes détectées et notifiées"];
                $logMessages[] = ['type' => 'info', 'message' => "📊 Total des alertes: {$report['total_alerts']}"];
                $logMessages[] = ['type' => 'warning', 'message' => "⚠️ Stocks faibles: {$report['low_stock_count']}"];
                $logMessages[] = ['type' => 'error', 'message' => "🚨 Ruptures de stock: {$report['out_of_stock_count']}"];
                break;
                
            case 'test_notification':
                $logMessages[] = ['type' => 'info', 'message' => '📢 Test de notification...'];
                
                $notificationsCreated = NotificationSystem::createAdminNotification(
                    "🧪 Test de démonstration",
                    "Ceci est un test du système de notifications automatiques - " . date('H:i:s'),
                    "info",
                    ['demo' => true, 'timestamp' => date('Y-m-d H:i:s')]
                );
                
                $logMessages[] = ['type' => 'success', 'message' => "✅ {$notificationsCreated} notifications de test créées"];
                break;
                
            case 'create_stock_alert':
                $logMessages[] = ['type' => 'info', 'message' => '⚠️ Création d\'une alerte de stock...'];
                
                // Créer un produit avec stock faible
                $pdo = Database::getConnection();
                $stmt = $pdo->prepare("INSERT INTO stocks (nom_article, quantite, seuil_alerte, categorie, prix_vente) VALUES (?, ?, ?, 'Test', 1.00)");
                
                if ($stmt->execute(['Produit Test Alert', 3, 10])) {
                    $productId = $pdo->lastInsertId();
                    
                    // Vérifier et notifier
                    StockMonitor::checkProductAfterUpdate($productId);
                    
                    $logMessages[] = ['type' => 'success', 'message' => "✅ Produit de test créé (ID: {$productId}) avec stock faible"];
                    $logMessages[] = ['type' => 'warning', 'message' => "⚠️ Notification d'alerte envoyée aux admins et gestionnaires"];
                } else {
                    $logMessages[] = ['type' => 'error', 'message' => "❌ Erreur lors de la création du produit de test"];
                }
                break;
                
            case 'clear_test_data':
                $logMessages[] = ['type' => 'info', 'message' => '🧹 Nettoyage des données de test...'];
                
                $pdo = Database::getConnection();
                $stmt = $pdo->query("DELETE FROM stocks WHERE nom_article LIKE '%Test%'");
                $deletedCount = $stmt->rowCount();
                
                $logMessages[] = ['type' => 'success', 'message' => "✅ {$deletedCount} produits de test supprimés"];
                break;
        }
    } catch (Exception $e) {
        $logMessages[] = ['type' => 'error', 'message' => "❌ Erreur: " . $e->getMessage()];
    }
}

// Affichage du statut actuel
echo "<div class='demo-section'>
    <h2>📊 État Actuel du Système</h2>
    
    <div class='grid'>";

try {
    $pdo = Database::getConnection();
    
    // Vérifier les notifications
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
    $totalNotifications = $stmt->fetch()['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0");
    $unreadNotifications = $stmt->fetch()['unread'];
    
    // Vérifier les alertes de stock
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stocks WHERE quantite = 0");
    $outOfStockCount = $stmt->fetch()['count'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM stocks WHERE quantite <= seuil_alerte AND quantite > 0");
    $lowStockCount = $stmt->fetch()['count'];
    
    echo "<div class='feature-card'>
        <div class='feature-icon'>🔔</div>
        <h3>Notifications</h3>
        <p><strong>Total:</strong> {$totalNotifications}</p>
        <p><strong>Non lues:</strong> {$unreadNotifications}</p>
    </div>";
    
    echo "<div class='feature-card'>
        <div class='feature-icon'>🚨</div>
        <h3>Alertes de Stock</h3>
        <p><strong>Ruptures:</strong> {$outOfStockCount}</p>
        <p><strong>Stocks faibles:</strong> {$lowStockCount}</p>
    </div>";
    
    echo "<div class='feature-card'>
        <div class='feature-icon'>👥</div>
        <h3>Destinataires</h3>
        <p><strong>Admins:</strong> Toutes les notifications</p>
        <p><strong>Gestionnaires:</strong> Alertes de stock</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='status-box status-error'>
        <strong>❌ Erreur de connexion:</strong> " . $e->getMessage() . "
    </div>";
}

echo "</div>
</div>";

// Actions de démonstration
echo "<div class='demo-section'>
    <h2>🧪 Actions de Démonstration</h2>
    
    <p>Testez le système de notifications automatiques :</p>
    
    <a href='?action=check_alerts' class='btn btn-success'>🔍 Vérifier les Alertes de Stock</a>
    <a href='?action=test_notification' class='btn btn-info'>📢 Tester une Notification</a>
    <a href='?action=create_stock_alert' class='btn btn-warning'>⚠️ Créer une Alerte de Stock</a>
    <a href='?action=clear_test_data' class='btn btn-danger'>🧹 Nettoyer les Données de Test</a>
</div>";

// Log des actions
if (!empty($logMessages)) {
    echo "<div class='demo-section'>
        <h2>📝 Log des Actions</h2>
        
        <div class='log-section'>";
    
    foreach ($logMessages as $log) {
        $logClass = "log-{$log['type']}";
        echo "<div class='log-entry {$logClass}'>{$log['message']}</div>";
    }
    
    echo "</div>
    </div>";
}

// Fonctionnalités du système
echo "<div class='demo-section'>
    <h2>🚀 Fonctionnalités du Système</h2>
    
    <div class='grid'>
        <div class='feature-card'>
            <div class='feature-icon'>🔄</div>
            <h3>Vérification Automatique</h3>
            <p>Le système vérifie automatiquement les alertes de stock et envoie des notifications aux utilisateurs concernés.</p>
        </div>
        
        <div class='feature-card'>
            <div class='feature-icon'>📱</div>
            <h3>Notifications en Temps Réel</h3>
            <p>Les notifications apparaissent instantanément dans l'icône de notification du header pour les admins et gestionnaires.</p>
        </div>
        
        <div class='feature-card'>
            <div class='feature-icon'>⚡</div>
            <h3>Déclenchement Automatique</h3>
            <p>Les notifications sont déclenchées automatiquement lors des modifications de stock, ajouts, suppressions, etc.</p>
        </div>
        
        <div class='feature-card'>
            <div class='feature-icon'>🎯</div>
            <h3>Destinataires Ciblés</h3>
            <p>Chaque type de notification est envoyé aux utilisateurs appropriés selon leur rôle et responsabilités.</p>
        </div>
    </div>
</div>";

// Instructions d'utilisation
echo "<div class='demo-section'>
    <h2>📖 Comment Utiliser</h2>
    
    <div class='status-box status-info'>
        <h3>1. Vérification Manuelle</h3>
        <p>Cliquez sur <strong>\"Vérifier les Alertes de Stock\"</strong> pour déclencher manuellement la vérification des alertes.</p>
    </div>
    
    <div class='status-box status-info'>
        <h3>2. Test des Notifications</h3>
        <p>Cliquez sur <strong>\"Tester une Notification\"</strong> pour créer une notification de test visible dans le header.</p>
    </div>
    
    <div class='status-box status-info'>
        <h3>3. Création d'Alerte</h3>
        <p>Cliquez sur <strong>\"Créer une Alerte de Stock\"</strong> pour simuler un produit avec stock faible et voir les notifications automatiques.</p>
    </div>
    
    <div class='status-box status-warning'>
        <h3>4. Vérification dans le Header</h3>
        <p>Après chaque action, vérifiez l'icône de notification dans le header de l'application pour voir les nouvelles notifications.</p>
    </div>
</div>";

// Liens de navigation
echo "<div class='demo-section'>
    <h2>🔗 Navigation</h2>
    
    <a href='dashboard.php' class='btn btn-success'>🏠 Dashboard Principal</a>
    <a href='stocks.php' class='btn btn-info'>📦 Gestion des Stocks</a>
    <a href='pos.php' class='btn btn-warning'>💳 Point de Vente</a>
</div>
</div>

<script>
// Auto-refresh des notifications toutes les 10 secondes
setInterval(function() {
    if (window.location.search.includes('action=')) {
        // Ne pas rafraîchir si une action est en cours
        return;
    }
    
    // Rafraîchir la page pour mettre à jour les statistiques
    setTimeout(function() {
        window.location.reload();
    }, 10000);
}, 10000);
</script>

</body>
</html>";
?>
