-- ========================================
-- MIGRATION POUR BASE SCOLARIA EXISTANTE
-- ========================================
-- Ce script adapte votre base de données existante 'scolaria'
-- pour être compatible avec le module de gestion des stocks

USE scolaria;

-- 1. Vérifier et ajouter la colonne updated_at si elle n'existe pas
SET @sql = (
    SELECT IF(
        (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_SCHEMA = 'scolaria' 
         AND TABLE_NAME = 'stocks' 
         AND COLUMN_NAME = 'updated_at') = 0,
        'ALTER TABLE stocks ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
        'SELECT "Column updated_at already exists" as message'
    )
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Créer la table mouvements si elle n'existe pas
CREATE TABLE IF NOT EXISTS `mouvements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT,
    `action` VARCHAR(50) NOT NULL COMMENT 'ajout, modification, suppression',
    `details` TEXT,
    `utilisateur` VARCHAR(100),
    `date_mouvement` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_article_id` (`article_id`),
    INDEX `idx_date_mouvement` (`date_mouvement`),
    INDEX `idx_action` (`action`),
    FOREIGN KEY (`article_id`) REFERENCES `stocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Créer des mouvements d'historique pour les articles existants
-- Cela créera un historique "fictif" pour vos données actuelles
INSERT IGNORE INTO `mouvements` (`article_id`, `action`, `details`, `utilisateur`) 
SELECT 
    s.id,
    'ajout',
    CONCAT('Initialisation: ', s.nom_article, ' - Quantité: ', s.quantite, ', Seuil: ', s.seuil),
    'admin'
FROM `stocks` s
WHERE NOT EXISTS (
    SELECT 1 FROM `mouvements` m WHERE m.article_id = s.id AND m.action = 'ajout'
);

-- 4. Ajouter quelques mouvements récents pour vos données existantes
-- (Basé sur les données que vous avez partagées)
INSERT IGNORE INTO `mouvements` (`article_id`, `action`, `details`, `utilisateur`) VALUES
-- Pour les stylos bleus (ID 1 probablement)
((SELECT id FROM stocks WHERE nom_article = 'Stylos bleus' LIMIT 1), 'modification', 'Quantité mise à jour: stock vérifié', 'gestionnaire'),

-- Pour les cahiers A4 (ID 2 probablement)  
((SELECT id FROM stocks WHERE nom_article = 'Cahiers A4' LIMIT 1), 'modification', 'Seuil d\'alerte ajusté de 60 à 50 unités', 'admin'),

-- Pour les marqueurs effaçables (ID 3 probablement)
((SELECT id FROM stocks WHERE nom_article = 'Marqueurs effaçables' LIMIT 1), 'modification', 'Stock critique: commande urgente nécessaire', 'gestionnaire'),

-- Pour les feuilles A3 (ID 4 probablement)
((SELECT id FROM stocks WHERE nom_article = 'Feuilles A3' LIMIT 1), 'modification', 'Stock très faible: réassort immédiat requis', 'admin'),

-- Pour les cartouches (ID 5 probablement)
((SELECT id FROM stocks WHERE nom_article = 'Cartouches impression' LIMIT 1), 'modification', 'Remplacement préventif des cartouches', 'gestionnaire');

-- 5. Optionnel: Ajouter quelques articles supplémentaires pour enrichir le catalogue
-- Vous pouvez commenter cette section si vous ne voulez que vos données actuelles
INSERT IGNORE INTO `stocks` (`nom_article`, `categorie`, `quantite`, `seuil`) VALUES
('Calculatrices scientifiques', 'Matériel scolaire', 25, 5),
('Règles 30cm', 'Matériel scolaire', 45, 10),
('Livres de mathématiques', 'Manuels', 80, 15),
('Chaises scolaires', 'Mobilier', 120, 20),
('Tableaux blancs', 'Matériel enseignant', 8, 3),
('Projecteurs', 'Informatique', 5, 2),
('Ordinateurs portables', 'Informatique', 12, 3),
('Cahiers travaux pratiques', 'Papeterie', 200, 40);

-- 6. Créer des mouvements pour les nouveaux articles (si ajoutés)
INSERT IGNORE INTO `mouvements` (`article_id`, `action`, `details`, `utilisateur`) 
SELECT 
    s.id,
    'ajout',
    CONCAT('Nouveau article ajouté: ', s.nom_article, ' - Quantité initiale: ', s.quantite),
    'admin'
FROM `stocks` s 
WHERE s.nom_article IN (
    'Calculatrices scientifiques', 'Règles 30cm', 'Livres de mathématiques', 
    'Chaises scolaires', 'Tableaux blancs', 'Projecteurs',
    'Ordinateurs portables', 'Cahiers travaux pratiques'
)
AND NOT EXISTS (
    SELECT 1 FROM `mouvements` m 
    WHERE m.article_id = s.id AND m.action = 'ajout'
);

-- 7. Mise à jour des statistiques et vérifications
SELECT 
    'Migration terminée!' as status,
    (SELECT COUNT(*) FROM stocks) as total_articles,
    (SELECT COUNT(*) FROM mouvements) as total_mouvements,
    (SELECT COUNT(*) FROM stocks WHERE quantite <= seuil) as stocks_faibles;

-- 8. Afficher un résumé des données
SELECT 'RÉSUMÉ DES STOCKS' as info;
SELECT 
    id,
    nom_article,
    categorie,
    quantite,
    seuil,
    CASE 
        WHEN quantite <= seuil THEN '⚠️ STOCK FAIBLE'
        WHEN quantite <= seuil * 1.5 THEN '⚡ ATTENTION'
        ELSE '✅ OK'
    END as statut,
    created_at
FROM stocks 
ORDER BY 
    CASE WHEN quantite <= seuil THEN 1 ELSE 2 END,
    categorie,
    nom_article;

SELECT 'DERNIERS MOUVEMENTS' as info;
SELECT 
    m.id,
    s.nom_article,
    m.action,
    m.details,
    m.utilisateur,
    m.date_mouvement
FROM mouvements m
LEFT JOIN stocks s ON m.article_id = s.id
ORDER BY m.date_mouvement DESC
LIMIT 10;