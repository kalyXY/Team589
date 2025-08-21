-- Script de mise à jour pour base de données existante
-- Scolaria Team589 - Module Finances

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