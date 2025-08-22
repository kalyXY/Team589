<?php
/**
 * Moniteur de stock pour Scolaria
 * Vérifie automatiquement les alertes de stock et envoie des notifications
 */

require_once __DIR__ . '/notification_system.php';

class StockMonitor {
    
    /**
     * Vérifier les alertes de stock et envoyer des notifications
     */
    public static function checkAndNotify() {
        try {
            // Vérifier les alertes de stock
            $alertsCount = NotificationSystem::checkStockAlerts();
            
            if ($alertsCount > 0) {
                error_log("StockMonitor: {$alertsCount} alertes de stock détectées et notifiées");
            }
            
            return $alertsCount;
        } catch (Exception $e) {
            error_log('Erreur lors de la vérification des alertes de stock: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Vérifier un produit spécifique après modification
     */
    public static function checkProductAfterUpdate($productId) {
        try {
            $pdo = Database::getConnection();
            
            // Récupérer les informations du produit
            $stmt = $pdo->prepare("SELECT id, nom_article, quantite, seuil_alerte FROM stocks WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if (!$product) {
                return false;
            }
            
            // Vérifier si le produit est en rupture de stock
            if ($product['quantite'] == 0) {
                NotificationSystem::notifyStockOut($product['id'], $product['nom_article'], $product['quantite']);
                return true;
            }
            
            // Vérifier si le produit a un stock faible
            if ($product['quantite'] <= $product['seuil_alerte']) {
                NotificationSystem::notifyLowStock($product['id'], $product['nom_article'], $product['quantite'], $product['seuil_alerte']);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erreur lors de la vérification du produit: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notifier d'un mouvement de stock
     */
    public static function notifyStockMovement($productId, $action, $quantity, $userName) {
        try {
            $pdo = Database::getConnection();
            
            // Récupérer le nom du produit
            $stmt = $pdo->prepare("SELECT nom_article FROM stocks WHERE id = ?");
            $stmt->execute([$productId]);
            $product = $stmt->fetch();
            
            if ($product) {
                NotificationSystem::notifyStockMovement($productId, $product['nom_article'], $action, $quantity, $userName);
                return true;
            }
            
            return false;
        } catch (Exception $e) {
            error_log('Erreur lors de la notification de mouvement de stock: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Vérifier tous les produits avec des stocks faibles
     */
    public static function getLowStockProducts() {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->query("
                SELECT id, nom_article, quantite, seuil_alerte, categorie 
                FROM stocks 
                WHERE quantite <= seuil_alerte 
                ORDER BY quantite ASC, nom_article ASC
            ");
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des produits à stock faible: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Vérifier tous les produits en rupture de stock
     */
    public static function getOutOfStockProducts() {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->query("
                SELECT id, nom_article, quantite, categorie 
                FROM stocks 
                WHERE quantite = 0 
                ORDER BY nom_article ASC
            ");
            
            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log('Erreur lors de la récupération des produits en rupture: ' . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Générer un rapport d'alertes de stock
     */
    public static function generateStockAlertReport() {
        try {
            $lowStock = self::getLowStockProducts();
            $outOfStock = self::getOutOfStockProducts();
            
            $report = [
                'timestamp' => date('Y-m-d H:i:s'),
                'low_stock_count' => count($lowStock),
                'out_of_stock_count' => count($outOfStock),
                'low_stock_products' => $lowStock,
                'out_of_stock_products' => $outOfStock,
                'total_alerts' => count($lowStock) + count($outOfStock)
            ];
            
            return $report;
        } catch (Exception $e) {
            error_log('Erreur lors de la génération du rapport d\'alertes: ' . $e->getMessage());
            return null;
        }
    }
}

// Si ce fichier est appelé directement, exécuter la vérification
if (basename(__FILE__) == basename($_SERVER['SCRIPT_NAME'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $alertsCount = StockMonitor::checkAndNotify();
        $report = StockMonitor::generateStockAlertReport();
        
        echo json_encode([
            'success' => true,
            'alerts_checked' => $alertsCount,
            'report' => $report,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}
?>
