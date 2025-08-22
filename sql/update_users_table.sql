-- Script de mise à jour de la table users pour les nouvelles fonctionnalités
-- Date: 2025-08-22
-- Description: Ajout des colonnes pour photos de profil, nom complet, téléphone, statut

-- Vérifier si les colonnes existent déjà avant de les ajouter
-- Ajout de la colonne full_name (nom complet)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `full_name` VARCHAR(150) NULL AFTER `username`;

-- Ajout de la colonne phone (téléphone)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `phone` VARCHAR(30) NULL AFTER `email`;

-- Ajout de la colonne status (statut actif/inactif)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `status` ENUM('actif','inactif') NOT NULL DEFAULT 'actif' AFTER `role`;

-- Ajout de la colonne avatar_path (chemin vers la photo de profil)
ALTER TABLE `users` 
ADD COLUMN IF NOT EXISTS `avatar_path` VARCHAR(255) NULL AFTER `status`;

-- Mise à jour des données existantes pour remplir les nouvelles colonnes
-- Si full_name est vide, utiliser username comme nom complet
UPDATE `users` 
SET `full_name` = `username` 
WHERE `full_name` IS NULL OR `full_name` = '';

-- Mettre à jour le statut par défaut pour tous les utilisateurs existants
UPDATE `users` 
SET `status` = 'actif' 
WHERE `status` IS NULL;

-- Ajout d'index pour optimiser les performances
-- Index sur full_name pour la recherche
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_full_name` (`full_name`);

-- Index sur phone pour la recherche
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_phone` (`phone`);

-- Index sur status pour le filtrage
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_status` (`status`);

-- Index composite pour la recherche combinée
ALTER TABLE `users` 
ADD INDEX IF NOT EXISTS `idx_search` (`full_name`, `email`, `phone`);

-- Vérification de la structure finale
DESCRIBE `users`;

-- Affichage des utilisateurs existants avec leurs nouvelles données
SELECT 
    id,
    username,
    full_name,
    email,
    phone,
    role,
    status,
    avatar_path,
    created_at
FROM `users`
ORDER BY id;
