-- Script de création des tables pour le système de notifications
-- Scolaria - Système de gestion scolaire
-- Note: Utilise la table users existante avec la colonne avatar_path

-- Table pour les notifications
CREATE TABLE IF NOT EXISTS `notifications` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion de données de test pour les notifications
INSERT IGNORE INTO `notifications` (`user_id`, `title`, `message`, `type`, `is_read`, `created_at`) VALUES
(1, 'Bienvenue sur Scolaria', 'Votre compte a été configuré avec succès. Vous pouvez maintenant utiliser toutes les fonctionnalités de l\'application.', 'info', 0, NOW()),
(1, 'Stock faible détecté', 'Le stock de "Cahiers 96 pages" est faible (quantité: 5). Veuillez commander de nouveaux stocks.', 'warning', 0, NOW()),
(1, 'Nouvelle vente enregistrée', 'Une vente de 45.50 € a été enregistrée avec succès. Ticket #2024-001.', 'success', 0, NOW()),
(1, 'Maintenance prévue', 'Une maintenance est prévue le 15 décembre de 22h00 à 02h00. L\'application sera temporairement indisponible.', 'info', 0, NOW());

-- Note: Les profils utilisateurs sont déjà dans la table users existante
-- avec la colonne avatar_path pour les photos

-- Ajout de contraintes de clés étrangères (optionnel, décommentez si vous voulez les contraintes)
-- ALTER TABLE `notifications` ADD CONSTRAINT `fk_notifications_user_id` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
