<?php
/**
 * Endpoint AJAX pour marquer toutes les notifications comme lues
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

// Vérifier la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
    exit;
}

try {
    $pdo = Database::getConnection();
    $userId = (int)$_SESSION['user_id'];
    
    // Vérifier si la table notifications existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        // Marquer toutes les notifications comme lues
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE user_id = ? AND is_read = 0
        ");
        $result = $stmt->execute([$userId]);
        
        if ($result) {
            $affectedRows = $stmt->rowCount();
            echo json_encode([
                'success' => true,
                'affected_rows' => $affectedRows
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Erreur lors de la mise à jour']);
        }
    } else {
        // Table inexistante - simulation de succès
        echo json_encode(['success' => true, 'affected_rows' => 0]);
    }
    
} catch (Exception $e) {
    error_log('Erreur lors du marquage de toutes les notifications: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du marquage de toutes les notifications'
    ]);
}
