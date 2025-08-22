<?php
/**
 * Test du système de notifications automatiques
 * Simule des alertes de stock et vérifie l'envoi de notifications
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/notification_system.php';
require_once __DIR__ . '/includes/stock_monitor.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Test Système de Notifications - Scolaria</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { background: #f0f0f0; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .warning { background: #fff3cd; color: #856404; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 10px 20px; margin: 5px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .btn:hover { background: #0056b3; }
        .btn-danger { background: #dc3545; }
        .btn-danger:hover { background: #c82333; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #218838; }
    </style>
</head>
<body>
    <h1>🧪 Test du Système de Notifications Automatiques</h1>";

try {
    $pdo = Database::getConnection();
    
    // Test 1: Vérifier l'état actuel des stocks
    echo "<div class='test-section'>
        <h3>📊 État actuel des stocks</h3>";
    
    try {
        // Produits en rupture de stock
        $stmt = $pdo->query("SELECT id, nom_article, quantite, categorie FROM stocks WHERE quantite = 0");
        $outOfStock = $stmt->fetchAll();
        
        echo "<h4>🚨 Produits en rupture de stock</h4>";
        if (!empty($outOfStock)) {
            echo "<table><tr><th>ID</th><th>Article</th><th>Quantité</th><th>Catégorie</th></tr>";
            foreach ($outOfStock as $product) {
                echo "<tr><td>{$product['id']}</td><td>{$product['nom_article']}</td><td>{$product['quantite']}</td><td>{$product['categorie']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='info'>Aucun produit en rupture de stock</p>";
        }
        
        // Produits avec stock faible
        $stmt = $pdo->query("SELECT id, nom_article, quantite, seuil_alerte, categorie FROM stocks WHERE quantite <= seuil_alerte AND quantite > 0");
        $lowStock = $stmt->fetchAll();
        
        echo "<h4>⚠️ Produits avec stock faible</h4>";
        if (!empty($lowStock)) {
            echo "<table><tr><th>ID</th><th>Article</th><th>Quantité</th><th>Seuil</th><th>Catégorie</th></tr>";
            foreach ($lowStock as $product) {
                echo "<tr><td>{$product['id']}</td><td>{$product['nom_article']}</td><td>{$product['quantite']}</td><td>{$product['seuil_alerte']}</td><td>{$product['categorie']}</td></tr>";
            }
            echo "</table>";
        } else {
            echo "<p class='info'>Aucun produit avec stock faible</p>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>Erreur: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Test 2: Vérifier les notifications existantes
    echo "<div class='test-section'>
        <h3>🔔 Notifications existantes</h3>";
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM notifications");
        $result = $stmt->fetch();
        $totalNotifications = $result['total'];
        
        echo "<p><strong>Total des notifications:</strong> {$totalNotifications}</p>";
        
        if ($totalNotifications > 0) {
            $stmt = $pdo->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
            $recentNotifications = $stmt->fetchAll();
            
            echo "<h4>Dernières notifications</h4>";
            echo "<table><tr><th>ID</th><th>Titre</th><th>Message</th><th>Type</th><th>Date</th><th>Lu</th></tr>";
            foreach ($recentNotifications as $notif) {
                $readStatus = $notif['is_read'] ? '✅' : '❌';
                echo "<tr><td>{$notif['id']}</td><td>{$notif['title']}</td><td>{$notif['message']}</td><td>{$notif['type']}</td><td>{$notif['created_at']}</td><td>{$readStatus}</td></tr>";
            }
            echo "</table>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>Erreur: " . $e->getMessage() . "</div>";
    }
    
    echo "</div>";
    
    // Test 3: Actions de test
    echo "<div class='test-section'>
        <h3>🧪 Actions de test</h3>
        
        <p><strong>Testez le système de notifications:</strong></p>
        
        <a href='?action=check_alerts' class='btn btn-success'>🔍 Vérifier les alertes de stock</a>
        <a href='?action=test_notification' class='btn btn-warning'>📢 Tester une notification</a>
        <a href='?action=clear_notifications' class='btn btn-danger'>🗑️ Effacer toutes les notifications</a>
        <a href='?action=add_test_alerts' class='btn btn-info'>⚠️ Créer des alertes de test</a>
    </div>";
    
    // Traitement des actions
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        echo "<div class='test-section'>
            <h3>📋 Résultat de l'action: {$action}</h3>";
        
        switch ($action) {
            case 'check_alerts':
                try {
                    $alertsCount = StockMonitor::checkAndNotify();
                    $report = StockMonitor::generateStockAlertReport();
                    
                    echo "<div class='success'>✅ Vérification des alertes terminée</div>";
                    echo "<p><strong>Alertes détectées:</strong> {$alertsCount}</p>";
                    echo "<p><strong>Total des alertes:</strong> {$report['total_alerts']}</p>";
                    echo "<p><strong>Stocks faibles:</strong> {$report['low_stock_count']}</p>";
                    echo "<p><strong>Ruptures de stock:</strong> {$report['out_of_stock_count']}</p>";
                    
                    if ($alertsCount > 0) {
                        echo "<div class='warning'>⚠️ Des notifications ont été envoyées aux admins et gestionnaires</div>";
                    }
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
                }
                break;
                
            case 'test_notification':
                try {
                    $notificationsCreated = NotificationSystem::createAdminNotification(
                        "🧪 Test de notification",
                        "Ceci est un test du système de notifications automatiques",
                        "info",
                        ['test' => true, 'timestamp' => date('Y-m-d H:i:s')]
                    );
                    
                    echo "<div class='success'>✅ Test de notification terminé</div>";
                    echo "<p><strong>Notifications créées:</strong> {$notificationsCreated}</p>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
                }
                break;
                
            case 'clear_notifications':
                try {
                    $stmt = $pdo->query("DELETE FROM notifications");
                    $deletedCount = $stmt->rowCount();
                    
                    echo "<div class='success'>✅ Nettoyage terminé</div>";
                    echo "<p><strong>Notifications supprimées:</strong> {$deletedCount}</p>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
                }
                break;
                
            case 'add_test_alerts':
                try {
                    // Créer des produits de test avec des stocks faibles
                    $testProducts = [
                        ['Cahier Test A4', 5, 10],
                        ['Stylo Test Rouge', 0, 5],
                        ['Papier Test A3', 3, 8]
                    ];
                    
                    $inserted = 0;
                    foreach ($testProducts as $product) {
                        $stmt = $pdo->prepare("INSERT INTO stocks (nom_article, quantite, seuil_alerte, categorie, prix_vente) VALUES (?, ?, ?, 'Test', 1.00)");
                        if ($stmt->execute($product)) {
                            $inserted++;
                        }
                    }
                    
                    echo "<div class='success'>✅ Produits de test ajoutés</div>";
                    echo "<p><strong>Produits créés:</strong> {$inserted}</p>";
                    echo "<p>Maintenant, vérifiez les alertes pour voir les notifications !</p>";
                    
                } catch (Exception $e) {
                    echo "<div class='error'>❌ Erreur: " . $e->getMessage() . "</div>";
                }
                break;
        }
        
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erreur de connexion: " . $e->getMessage() . "</div>";
}

echo "<p><a href='dashboard.php'>← Retour au Dashboard</a></p>
</body>
</html>";
?>
