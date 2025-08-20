-- Script SQL pour la gestion des stocks - Scolaria (Team589)
-- Mise à jour de la base de données existante 'scolaria'
-- À exécuter sur votre base de données existante

USE scolaria;

-- Vérification et mise à jour de la table stocks existante
-- Ajouter les colonnes manquantes si elles n'existent pas
ALTER TABLE `stocks` 
ADD COLUMN IF NOT EXISTS `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- Table des mouvements (historique) - Création si elle n'existe pas
CREATE TABLE IF NOT EXISTS `mouvements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT,
    `action` VARCHAR(50) NOT NULL, -- ajout, modification, suppression
    `details` TEXT,
    `utilisateur` VARCHAR(100),
    `date_mouvement` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`article_id`) REFERENCES `stocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Mise à jour des données existantes avec de nouveaux articles (optionnel)
-- Vous pouvez commenter cette section si vous voulez garder vos données actuelles

-- Ajout d'articles supplémentaires pour enrichir le catalogue
INSERT IGNORE INTO `stocks` (`nom_article`, `categorie`, `quantite`, `seuil`) VALUES
('Cahiers 100 pages', 'Papeterie', 150, 20),
('Calculatrices scientifiques', 'Matériel scolaire', 25, 5),
('Règles 30cm', 'Matériel scolaire', 45, 10),
('Livres de mathématiques', 'Manuels', 80, 10),
('Chaises scolaires', 'Mobilier', 120, 20),
('Tableaux blancs', 'Matériel enseignant', 8, 3),
('Projecteurs', 'Informatique', 5, 2);

-- Historique des mouvements pour les articles existants et nouveaux
INSERT INTO `mouvements` (`article_id`, `action`, `details`, `utilisateur`) VALUES
-- Mouvements pour les articles existants (ID basés sur vos données actuelles)
(1, 'modification', 'Mise à jour quantité stylos bleus: 120 unités', 'admin'),
(2, 'modification', 'Seuil d\'alerte modifié pour cahiers A4: 60 → 50', 'gestionnaire'),
(3, 'modification', 'Réassort marqueurs effaçables: +20 unités', 'admin'),
(4, 'modification', 'Commande urgente feuilles A3: +50 unités', 'gestionnaire'),
(5, 'modification', 'Remplacement cartouches impression', 'admin');

-- Ajouter des mouvements pour les nouveaux articles (si ajoutés)
-- Ces ID peuvent varier selon l'ordre d'insertion
INSERT INTO `mouvements` (`article_id`, `action`, `details`, `utilisateur`) 
SELECT s.id, 'ajout', CONCAT('Ajout initial: ', s.nom_article, ' (', s.quantite, ' unités)'), 'admin'
FROM `stocks` s 
WHERE s.nom_article IN ('Cahiers 100 pages', 'Calculatrices scientifiques', 'Règles 30cm', 'Livres de mathématiques', 'Chaises scolaires', 'Tableaux blancs', 'Projecteurs');