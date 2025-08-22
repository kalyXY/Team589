<?php
/**
 * Gestion de l'avatar et des notifications pour le header
 * Récupère les informations de l'utilisateur connecté et ses notifications
 */

// Récupération des informations de l'utilisateur connecté
function getUserProfile($userId) {
    $pdo = Database::getConnection();
    
    try {
        // Récupérer les informations depuis la table users existante
        $stmt = $pdo->prepare("
            SELECT id, username, full_name, email, phone, role, status, avatar_path, created_at 
            FROM users 
            WHERE id = ?
        ");
        $stmt->execute([$userId]);
        $profile = $stmt->fetch();
        
        if ($profile) {
            return [
                'avatar' => $profile['avatar_path'],
                'full_name' => $profile['full_name'] ?: $profile['username'],
                'email' => $profile['email'],
                'role' => $profile['role'],
                'username' => $profile['username'],
                'phone' => $profile['phone'],
                'status' => $profile['status']
            ];
        }
        
        // Fallback : informations de base depuis la session
        return [
            'avatar' => null,
            'full_name' => $_SESSION['username'] ?? 'Utilisateur',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 'Utilisateur'
        ];
    } catch (Exception $e) {
        error_log('Erreur lors de la récupération du profil: ' . $e->getMessage());
        return [
            'avatar' => null,
            'full_name' => $_SESSION['username'] ?? 'Utilisateur',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? 'Utilisateur'
        ];
    }
}

// Récupération des notifications de l'utilisateur
function getUserNotifications($userId, $limit = 10) {
    $pdo = Database::getConnection();
    
    try {
        // Vérifier si la table notifications existe
        $stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
        if ($stmt->rowCount() > 0) {
            // Récupérer les notifications non lues
            $stmt = $pdo->prepare("
                SELECT * FROM notifications 
                WHERE user_id = ? AND is_read = 0 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            $notifications = $stmt->fetchAll();
            
            // Compter le total des notifications non lues
            $stmt = $pdo->prepare("
                SELECT COUNT(*) as count 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$userId]);
            $count = $stmt->fetch()['count'];
            
            return [
                'notifications' => $notifications,
                'unread_count' => $count
            ];
        }
        
        // Fallback : notifications factices pour la démo
        return [
            'notifications' => [
                [
                    'id' => 1,
                    'title' => 'Bienvenue sur Scolaria',
                    'message' => 'Votre compte a été configuré avec succès.',
                    'type' => 'info',
                    'created_at' => date('Y-m-d H:i:s')
                ]
            ],
            'unread_count' => 1
        ];
    } catch (Exception $e) {
        error_log('Erreur lors de la récupération des notifications: ' . $e->getMessage());
        return [
            'notifications' => [],
            'unread_count' => 0
        ];
    }
}

// Génération de l'avatar (initiale ou image)
function generateAvatar($profile) {
    if (!empty($profile['avatar']) && file_exists($profile['avatar'])) {
        return '<img src="' . htmlspecialchars($profile['avatar']) . '" alt="Avatar" class="user-avatar-img">';
    }
    
    // Avatar par défaut avec initiale
    $initial = strtoupper(substr($profile['full_name'], 0, 1));
    $colors = ['#3498db', '#e74c3c', '#2ecc71', '#f39c12', '#9b59b6', '#1abc9c'];
    $color = $colors[array_rand($colors)];
    
    return '<div class="user-avatar-initial" style="background-color: ' . $color . '">' . $initial . '</div>';
}

// Récupération des données utilisateur
$userId = $_SESSION['user_id'] ?? 0;
$userProfile = getUserProfile($userId);
$userNotifications = getUserNotifications($userId);
?>
