-- Script de mise à jour pour base de données existante
-- Scolaria Team589 - Module Finances

-- =============================
-- Module Authentification
-- =============================
-- Mettre à jour la table users vers le nouveau schéma
-- Étape 1: ajouter la colonne email en NULL pour éviter l'échec sur données existantes
ALTER TABLE `users`
    MODIFY COLUMN `username` varchar(100) NOT NULL,
    ADD COLUMN IF NOT EXISTS `email` varchar(150) NULL AFTER `username`,
    MODIFY COLUMN `password` varchar(255) NOT NULL,
    MODIFY COLUMN `role` enum('admin','gestionnaire','caissier','directeur','utilisateur') NOT NULL DEFAULT 'utilisateur',
    ADD COLUMN IF NOT EXISTS `created_at` timestamp NOT NULL DEFAULT current_timestamp() AFTER `role`;

-- Étape 2: renseigner des emails uniques pour les enregistrements vides
UPDATE `users` SET `email` = CONCAT(`username`, '@scolaria.local')
WHERE (`email` IS NULL OR `email` = '') AND `username` IS NOT NULL;

-- Étape 3: désambigüiser les doublons d'emails non vides restants en suffixant l'ID
UPDATE `users` u
JOIN (
  SELECT `email`
  FROM `users`
  WHERE `email` IS NOT NULL AND `email` <> ''
  GROUP BY `email`
  HAVING COUNT(*) > 1
) d ON u.`email` = d.`email`
SET u.`email` = CASE
  WHEN LOCATE('@', u.`email`) > 0 THEN CONCAT(SUBSTRING_INDEX(u.`email`, '@', 1), '+', u.`id`, '@', SUBSTRING_INDEX(u.`email`, '@', -1))
  ELSE CONCAT(u.`email`, '+', u.`id`, '@scolaria.local')
END;

-- Étape 4: rendre la colonne NOT NULL après normalisation
ALTER TABLE `users`
  MODIFY COLUMN `email` varchar(150) NOT NULL;

-- Ajouter index uniques si absents
CREATE UNIQUE INDEX IF NOT EXISTS `uniq_users_username` ON `users` (`username`);
CREATE UNIQUE INDEX IF NOT EXISTS `uniq_users_email` ON `users` (`email`);

-- Ajouter utilisateur caissier de démo si absent
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`)
VALUES ('caissier', 'caissier@scolaria.local', '$2y$10$hZqjKqZIfwWf9DqvFq4HPeE1iY2Gx1rK9rPVp3oXf7wq0sE4H3PlS', 'caissier');

-- Ajouter utilisateur directeur de démo si absent
INSERT IGNORE INTO `users` (`username`, `email`, `password`, `role`)
VALUES ('directeur', 'directeur@scolaria.local', '$2y$10$hZqjKqZIfwWf9DqvFq4HPeE1iY2Gx1rK9rPVp3oXf7wq0sE4H3PlS', 'directeur');

-- Mise à jour de la table depenses (ajouter colonnes manquantes)
ALTER TABLE `depenses` 
ADD COLUMN IF NOT EXISTS `categorie_id` int(11) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `facture_numero` varchar(50) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `fournisseur` varchar(100) DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `notes` text DEFAULT NULL,
ADD COLUMN IF NOT EXISTS `created_by` varchar(50) DEFAULT 'admin',
ADD COLUMN IF NOT EXISTS `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp();

-- Modifier la colonne description pour la rendre NOT NULL
ALTER TABLE `depenses` MODIFY COLUMN `description` varchar(255) NOT NULL;

-- Ajouter les index manquants
CREATE INDEX IF NOT EXISTS `idx_date_depenses` ON `depenses`(`date`);
CREATE INDEX IF NOT EXISTS `idx_categorie_depenses` ON `depenses`(`categorie_id`);
CREATE INDEX IF NOT EXISTS `idx_montant_depenses` ON `depenses`(`montant`);

-- Ajouter la contrainte de clé étrangère
ALTER TABLE `depenses` 
ADD CONSTRAINT `depenses_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories`(`id`) ON DELETE SET NULL;

-- Insérer des données de test supplémentaires (éviter les doublons)
INSERT IGNORE INTO `depenses` (`description`, `montant`, `date`, `categorie_id`, `facture_numero`, `fournisseur`, `notes`, `created_by`) VALUES
('Achat de cahiers et stylos', 245.50, '2025-01-15', 1, 'FAC-2025-001', 'Papeterie Martin', 'Commande pour les classes de CP', 'admin'),
('Réparation photocopieur', 180.00, '2025-01-18', 2, 'REP-2025-003', 'TechnoService', 'Remplacement tambour', 'admin'),
('Ordinateurs portables (x5)', 2500.00, '2025-01-20', 3, 'INV-2025-012', 'InfoPlus', 'Pour la salle informatique', 'admin'),
('Facture électricité janvier', 320.75, '2025-01-25', 5, 'EDF-2025-01', 'EDF', 'Consommation janvier 2025', 'admin'),
('Formation premiers secours', 150.00, '2025-01-28', 4, 'FORM-2025-002', 'SecuriFormation', 'Formation obligatoire personnel', 'admin'),
('Carburant bus scolaire', 95.30, '2025-01-30', 6, 'TOTAL-2025-015', 'Station Total', 'Plein mensuel', 'admin'),
('Produits d\'entretien', 78.90, '2025-02-02', 7, 'NET-2025-004', 'CleanPro', 'Détergents et désinfectants', 'admin');

-- Insérer des budgets supplémentaires (éviter les doublons)
INSERT IGNORE INTO `budgets` (`mois`, `annee`, `montant_prevu`, `categorie_id`, `notes`, `created_by`) VALUES
(1, 2025, 1000.00, 1, 'Budget fournitures janvier 2025', 'admin'),
(1, 2025, 500.00, 2, 'Budget maintenance janvier 2025', 'admin'),
(1, 2025, 3000.00, 3, 'Budget investissement janvier 2025', 'admin'),
(2, 2025, 1200.00, 1, 'Budget fournitures février 2025', 'admin'),
(2, 2025, 400.00, 2, 'Budget maintenance février 2025', 'admin'),
(3, 2025, 800.00, 1, 'Budget fournitures mars 2025', 'admin'),
(4, 2025, 600.00, 1, 'Budget fournitures avril 2025', 'admin'),
(5, 2025, 900.00, 1, 'Budget fournitures mai 2025', 'admin');

-- Créer les vues pour les rapports
CREATE OR REPLACE VIEW `v_depenses_rapport` AS
SELECT 
    `d`.`id` AS `id`,
    `d`.`description` AS `description`,
    `d`.`montant` AS `montant`,
    `d`.`date` AS `date`,
    `d`.`facture_numero` AS `facture_numero`,
    `d`.`fournisseur` AS `fournisseur`,
    `c`.`nom` AS `categorie_nom`,
    `c`.`couleur` AS `categorie_couleur`,
    YEAR(`d`.`date`) AS `annee`,
    MONTH(`d`.`date`) AS `mois`,
    DATE_FORMAT(`d`.`date`, '%Y-%m') AS `periode`
FROM `depenses` `d`
LEFT JOIN `categories` `c` ON `d`.`categorie_id` = `c`.`id`
ORDER BY `d`.`date` DESC;

CREATE OR REPLACE VIEW `v_budgets_comparaison` AS
SELECT 
    `b`.`id` AS `id`,
    `b`.`mois` AS `mois`,
    `b`.`annee` AS `annee`,
    `b`.`montant_prevu` AS `montant_prevu`,
    `c`.`nom` AS `categorie_nom`,
    `c`.`couleur` AS `categorie_couleur`,
    COALESCE(SUM(`d`.`montant`), 0) AS `montant_reel`,
    (`b`.`montant_prevu` - COALESCE(SUM(`d`.`montant`), 0)) AS `difference`,
    CASE 
        WHEN COALESCE(SUM(`d`.`montant`), 0) > `b`.`montant_prevu` THEN 'depassement'
        WHEN COALESCE(SUM(`d`.`montant`), 0) > (`b`.`montant_prevu` * 0.8) THEN 'attention'
        ELSE 'normal'
    END AS `statut`
FROM `budgets` `b`
LEFT JOIN `categories` `c` ON `b`.`categorie_id` = `c`.`id`
LEFT JOIN `depenses` `d` ON `d`.`categorie_id` = `b`.`categorie_id` 
    AND MONTH(`d`.`date`) = `b`.`mois` 
    AND YEAR(`d`.`date`) = `b`.`annee`
GROUP BY `b`.`id`, `b`.`mois`, `b`.`annee`, `b`.`montant_prevu`, `c`.`nom`, `c`.`couleur`
ORDER BY `b`.`annee` DESC, `b`.`mois` DESC;

-- =============================
-- Module Stocks
-- =============================
-- Ajout des colonnes de prix
ALTER TABLE `stocks`
    ADD COLUMN IF NOT EXISTS `prix_achat` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `seuil`,
    ADD COLUMN IF NOT EXISTS `prix_vente` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `prix_achat`,
    ADD COLUMN IF NOT EXISTS `code_barres` VARCHAR(64) NULL AFTER `prix_vente`;

-- =============================
-- Module POS (Sales)
-- =============================
CREATE TABLE IF NOT EXISTS `sales` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sales_items` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  KEY `product_id` (`product_id`),
  CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `stocks` (`id`) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table transactions liée aux ventes (paiements)
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `sale_id` int(11) NOT NULL,
  `mode_paiement` enum('cash','mobile_money','card','transfer') NOT NULL DEFAULT 'cash',
  `montant` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `sale_id` (`sale_id`),
  CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table alertes basique pour stock faible
CREATE TABLE IF NOT EXISTS `alertes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `article_id` int(11) NOT NULL,
  `type` enum('faible','rupture') NOT NULL DEFAULT 'faible',
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `article_id` (`article_id`),
  CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;