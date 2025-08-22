-- Création de la table categories pour les dépenses
-- Scolaria - Team589

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nom` varchar(100) NOT NULL,
  `couleur` varchar(7) DEFAULT '#007bff',
  `description` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertion des catégories par défaut
INSERT INTO `categories` (`nom`, `couleur`, `description`) VALUES
('Fournitures', '#28a745', 'Fournitures scolaires et de bureau'),
('Équipements', '#007bff', 'Équipements informatiques et mobiliers'),
('Maintenance', '#ffc107', 'Maintenance et réparations'),
('Transport', '#17a2b8', 'Frais de transport et carburant'),
('Formation', '#6f42c1', 'Formations et développement professionnel'),
('Énergie', '#fd7e14', 'Factures d\'électricité, eau, etc.'),
('Divers', '#6c757d', 'Autres dépenses diverses')
ON DUPLICATE KEY UPDATE `updated_at` = current_timestamp();

-- Mise à jour des dépenses existantes pour associer les bonnes catégories
UPDATE `depenses` SET `categorie_id` = (SELECT `id` FROM `categories` WHERE `nom` = 'Fournitures') WHERE `description` LIKE '%fournitures%' OR `description` LIKE '%cahiers%' OR `description` LIKE '%stylos%' OR `description` LIKE '%papier%';

UPDATE `depenses` SET `categorie_id` = (SELECT `id` FROM `categories` WHERE `nom` = 'Équipements') WHERE `description` LIKE '%ordinateurs%' OR `description` LIKE '%imprimantes%' OR `description` LIKE '%projecteurs%';

UPDATE `depenses` SET `categorie_id` = (SELECT `id` FROM `categories` WHERE `nom` = 'Maintenance') WHERE `description` LIKE '%maintenance%' OR `description` LIKE '%réparation%' OR `description` LIKE '%réassort%';

UPDATE `depenses` SET `categorie_id` = (SELECT `id` FROM `categories` WHERE `nom` = 'Transport') WHERE `description` LIKE '%carburant%' OR `description` LIKE '%bus%' OR `description` LIKE '%transport%';

UPDATE `depenses` SET `categorie_id` = (SELECT `id` FROM `categories` WHERE `nom` = 'Formation') WHERE `description` LIKE '%formation%' OR `description` LIKE '%premiers secours%';

UPDATE `depenses` SET `categorie_id` = (SELECT `id` FROM `categories` WHERE `nom` = 'Énergie') WHERE `description` LIKE '%électricité%' OR `description` LIKE '%EDF%';

UPDATE `depenses` SET `categorie_id` = (SELECT `id` FROM `categories` WHERE `nom` = 'Divers') WHERE `categorie_id` IS NULL OR `description` LIKE '%divers%' OR `description` LIKE '%logistique%' OR `description` LIKE '%entretien%';
