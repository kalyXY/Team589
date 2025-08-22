<?php
/**
 * Endpoint AJAX pour récupérer les notifications de l'utilisateur connecté
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/auth.php';

// Vérifier l'authentification
session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Non authentifié']);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = Database::getConnection();
    $userId = (int)$_SESSION['user_id'];
    
    // Vérifier si la table notifications existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        // Récupérer les notifications non lues
        $stmt = $pdo->prepare("
            SELECT id, title, message, type, is_read, created_at 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute([$userId]);
        $notifications = $stmt->fetchAll();
        
        // Compter le total des notifications non lues
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE user_id = ? AND is_read = 0
        ");
        $stmt->execute([$userId]);
        $count = $stmt->fetch()['count'];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => (int)$count
        ]);
    } else {
        // Table inexistante - notifications factices pour la démo
        $notifications = [
            [
                'id' => 1,
                'title' => 'Bienvenue sur Scolaria',
                'message' => 'Votre compte a été configuré avec succès.',
                'type' => 'info',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ],
            [
                'id' => 2,
                'title' => 'Stock faible',
                'message' => 'Le stock de "Cahiers 96 pages" est faible.',
                'type' => 'warning',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))
            ]
        ];
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => 2
        ]);
    }
    
} catch (Exception $e) {
    error_log('Erreur lors de la récupération des notifications: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors de la récupération des notifications',
        'notifications' => [],
        'unread_count' => 0
    ]);
}
