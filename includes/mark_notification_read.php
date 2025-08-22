<?php
/**
 * Endpoint AJAX pour marquer une notification comme lue
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
    
    // Récupérer les données JSON
    $input = json_decode(file_get_contents('php://input'), true);
    $notificationId = (int)($input['notification_id'] ?? 0);
    
    if ($notificationId <= 0) {
        echo json_encode(['success' => false, 'error' => 'ID de notification invalide']);
        exit;
    }
    
    // Vérifier si la table notifications existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
    if ($stmt->rowCount() > 0) {
        // Marquer la notification comme lue
        $stmt = $pdo->prepare("
            UPDATE notifications 
            SET is_read = 1, read_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $result = $stmt->execute([$notificationId, $userId]);
        
        if ($result && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Notification non trouvée ou déjà lue']);
        }
    } else {
        // Table inexistante - simulation de succès
        echo json_encode(['success' => true]);
    }
    
} catch (Exception $e) {
    error_log('Erreur lors du marquage de la notification: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Erreur lors du marquage de la notification'
    ]);
}
