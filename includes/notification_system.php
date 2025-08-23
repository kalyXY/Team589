<?php
/**
 * Système de notifications automatiques pour Scolaria
 * Gère l'envoi de notifications pour les alertes de stock et autres événements
 */

require_once __DIR__ . '/../config/db.php';

class NotificationSystem {
    
    /**
     * Créer une notification pour les utilisateurs avec un rôle spécifique
     */
    public static function createNotificationForRole($title, $message, $type, $role, $data = null) {
        try {
            $pdo = Database::getConnection();
            
            // Récupérer tous les utilisateurs avec le rôle spécifié
            $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ? AND status = 'actif'");
            $stmt->execute([$role]);
            $users = $stmt->fetchAll();
            
            $notificationsCreated = 0;
            foreach ($users as $user) {
                if (self::createNotification($user['id'], $title, $message, $type, $data)) {
                    $notificationsCreated++;
                }
            }
            
            return $notificationsCreated;
        } catch (Exception $e) {
            error_log('Erreur lors de la création des notifications par rôle: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Créer une notification pour les admins et gestionnaires
     */
    public static function createAdminNotification($title, $message, $type = 'warning', $data = null) {
        $notificationsCreated = 0;
        
        // Notifier les admins
        $notificationsCreated += self::createNotificationForRole($title, $message, $type, 'admin', $data);
        
        // Notifier les gestionnaires
        $notificationsCreated += self::createNotificationForRole($title, $message, $type, 'gestionnaire', $data);
        
        return $notificationsCreated;
    }
    
    /**
     * Créer une notification pour un utilisateur spécifique
     */
    public static function createNotification($userId, $title, $message, $type = 'info', $data = null) {
        try {
            $pdo = Database::getConnection();
            
            // Vérifier si la table notifications existe
            $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
            if ($stmt->rowCount() == 0) {
                // Créer la table si elle n'existe pas
                self::createNotificationsTable($pdo);
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, data, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $jsonData = $data ? json_encode($data) : null;
            $result = $stmt->execute([$userId, $title, $message, $type, $jsonData]);
            
            return $result;
        } catch (Exception $e) {
            error_log('Erreur lors de la création de notification: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Créer la table notifications si elle n'existe pas
     */
    private static function createNotificationsTable($pdo) {
        try {
            $sql = "CREATE TABLE IF NOT EXISTS `notifications` (
                `id` int(11) NOT NULL AUTO_INCREMENT,
                `user_id` int(11) NOT NULL,
                `title` varchar(255) NOT NULL,
                `message` text NOT NULL,
                `type` enum('info','success','warning','error') NOT NULL DEFAULT 'info',
                `is_read` tinyint(1) NOT NULL DEFAULT 0,
                `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `read_at` timestamp NULL DEFAULT NULL,
                `data` json DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `idx_notifications_user_id` (`user_id`),
                KEY `idx_notifications_is_read` (`is_read`),
                KEY `idx_notifications_created_at` (`created_at`),
                KEY `idx_notifications_type` (`type`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            return true;
        } catch (Exception $e) {
            error_log('Erreur lors de la création de la table notifications: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Notifier d'une rupture de stock
     */
    public static function notifyStockOut($productId, $productName, $currentQuantity) {
        $title = "🚨 Rupture de stock détectée";
        $message = "Le produit \"{$productName}\" est en rupture de stock (Quantité: {$currentQuantity})";
        
        $data = [
            'product_id' => $productId,
            'product_name' => $productName,
            'current_quantity' => $currentQuantity,
            'action_required' => 'reapprovisionnement'
        ];
        
        return self::createAdminNotification($title, $message, 'error', $data);
    }
    
    /**
     * Notifier d'un stock faible
     */
    public static function notifyLowStock($productId, $productName, $currentQuantity, $threshold) {
        $title = "⚠️ Stock faible détecté";
        $message = "Le produit \"{$productName}\" a un stock faible (Quantité: {$currentQuantity}, Seuil: {$threshold})";
        
        $data = [
            'product_id' => $productId,
            'product_name' => $productName,
            'current_quantity' => $currentQuantity,
            'threshold' => $threshold,
            'action_required' => 'commande'
        ];
        
        return self::createAdminNotification($title, $message, 'warning', $data);
    }
    
    /**
     * Notifier d'une nouvelle vente
     */
    public static function notifyNewSale($saleId, $total, $clientName) {
        $title = "💰 Nouvelle vente enregistrée";
        $message = "Vente #{$saleId} de " . number_format($total, 2, ',', ' ') . " $ pour {$clientName}";
        
        $data = [
            'sale_id' => $saleId,
            'total' => $total,
            'client_name' => $clientName,
            'action_required' => 'suivi'
        ];
        
        return self::createAdminNotification($title, $message, 'success', $data);
    }
    
    /**
     * Notifier d'un mouvement de stock
     */
    public static function notifyStockMovement($productId, $productName, $action, $quantity, $userName) {
        $title = "📦 Mouvement de stock";
        $message = "{$action} de {$quantity} unité(s) du produit \"{$productName}\" par {$userName}";
        
        $data = [
            'product_id' => $productId,
            'product_name' => $productName,
            'action' => $action,
            'quantity' => $quantity,
            'user_name' => $userName,
            'action_required' => 'verification'
        ];
        
        return self::createAdminNotification($title, $message, 'info', $data);
    }
    
    /**
     * Notifier d'une dépense importante
     */
    public static function notifyExpense($expenseId, $amount, $description) {
        $title = "💸 Dépense importante enregistrée";
        $message = "Dépense de " . number_format($amount, 2, ',', ' ') . " $ : {$description}";
        
        $data = [
            'expense_id' => $expenseId,
            'amount' => $amount,
            'description' => $description,
            'action_required' => 'validation'
        ];
        
        return self::createAdminNotification($title, $message, 'warning', $data);
    }
    
    /**
     * Notifier d'un problème système
     */
    public static function notifySystemIssue($issue, $details) {
        $title = "🔧 Problème système détecté";
        $message = "{$issue}: {$details}";
        
        $data = [
            'issue' => $issue,
            'details' => $details,
            'action_required' => 'maintenance'
        ];
        
        return self::createAdminNotification($title, $message, 'error', $data);
    }
    
    /**
     * Vérifier et notifier des alertes de stock
     */
    public static function checkStockAlerts() {
        try {
            $pdo = Database::getConnection();
            
            // Vérifier les ruptures de stock
            $stmt = $pdo->query("SELECT id, nom_article, quantite FROM stocks WHERE quantite = 0");
            $outOfStock = $stmt->fetchAll();
            
            foreach ($outOfStock as $product) {
                self::notifyStockOut($product['id'], $product['nom_article'], $product['quantite']);
            }
            
            // Vérifier les stocks faibles
            $stmt = $pdo->query("SELECT id, nom_article, quantite, seuil_alerte FROM stocks WHERE quantite <= seuil_alerte AND quantite > 0");
            $lowStock = $stmt->fetchAll();
            
            foreach ($lowStock as $product) {
                self::notifyLowStock($product['id'], $product['nom_article'], $product['quantite'], $product['seuil_alerte']);
            }
            
            return count($outOfStock) + count($lowStock);
        } catch (Exception $e) {
            error_log('Erreur lors de la vérification des alertes de stock: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Nettoyer les anciennes notifications
     */
    public static function cleanupOldNotifications($daysOld = 30) {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
            $result = $stmt->execute([$daysOld]);
            
            return $result;
        } catch (Exception $e) {
            error_log('Erreur lors du nettoyage des notifications: ' . $e->getMessage());
            return false;
        }
    }
}
?>
