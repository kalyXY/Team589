-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : mer. 20 août 2025 à 21:03
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `scolaria`
--

-- --------------------------------------------------------

--
-- Structure de la table `budgets`
--

CREATE TABLE IF NOT EXISTS `budgets` (
  `id` int(11) NOT NULL,
  `mois` int(11) NOT NULL CHECK (`mois` between 1 and 12),
  `annee` int(11) NOT NULL,
  `montant_prevu` decimal(10,2) NOT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `budgets`
--

INSERT INTO `budgets` (`id`, `mois`, `annee`, `montant_prevu`, `categorie_id`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 8, 2025, 1000.00, NULL, 'Budget de test', 'admin', '2025-08-20 18:58:10', '2025-08-20 18:58:10'),
(2, 1, 2025, 1000.00, 1, 'Budget fournitures janvier 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(3, 1, 2025, 500.00, 2, 'Budget maintenance janvier 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(4, 1, 2025, 3000.00, 3, 'Budget investissement janvier 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(5, 2, 2025, 1200.00, 1, 'Budget fournitures février 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(6, 2, 2025, 400.00, 2, 'Budget maintenance février 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(7, 3, 2025, 800.00, 1, 'Budget fournitures mars 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(8, 4, 2025, 600.00, 1, 'Budget fournitures avril 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(9, 5, 2025, 900.00, 1, 'Budget fournitures mai 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `couleur` varchar(7) DEFAULT '#3B82F6',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `couleur`, `created_at`, `updated_at`) VALUES
(1, 'Fournitures', 'Matériel scolaire, papeterie, consommables', '#3B82F6', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(2, 'Maintenance', 'Réparations, entretien des équipements', '#EF4444', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(3, 'Investissement', 'Achat d\'équipements, mobilier, informatique', '#10B981', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(4, 'Personnel', 'Salaires, charges sociales, formations', '#8B5CF6', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(5, 'Utilities', 'Électricité, eau, internet, téléphone', '#F59E0B', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(6, 'Transport', 'Déplacements, carburant, transport scolaire', '#06B6D4', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(7, 'Divers', 'Autres dépenses non catégorisées', '#6B7280', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(22, 'test', 'test', '#3bf773', '2025-08-20 18:56:46', '2025-08-20 18:56:46');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

CREATE TABLE `commandes` (
  `id` int(11) NOT NULL,
  `article_id` int(11) NOT NULL,
  `fournisseur_id` int(11) NOT NULL,
  `quantite` int(11) NOT NULL,
  `prix_unitaire` decimal(10,2) DEFAULT 0.00,
  `statut` enum('en attente','validée','livrée','annulée') DEFAULT 'en attente',
  `date_commande` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_livraison_prevue` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(100) DEFAULT 'system'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `commandes`
--

INSERT INTO `commandes` (`id`, `article_id`, `fournisseur_id`, `quantite`, `prix_unitaire`, `statut`, `date_commande`, `date_livraison_prevue`, `notes`, `created_by`) VALUES
(1, 1, 1, 100, 2.50, 'en attente', '2025-08-20 17:06:47', NULL, 'Commande urgente - stock critique', 'system'),
(2, 2, 2, 50, 1.20, 'validée', '2025-08-20 17:06:47', NULL, 'Livraison prévue fin de semaine', 'system');

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

CREATE TABLE `depenses` (
  `id` int(11) NOT NULL,
  `description` varchar(255) NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `date` date NOT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `facture_numero` varchar(50) DEFAULT NULL,
  `fournisseur` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_by` varchar(50) DEFAULT 'admin',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `depenses`
--

INSERT INTO `depenses` (`id`, `description`, `montant`, `date`, `categorie_id`, `facture_numero`, `fournisseur`, `notes`, `created_by`, `created_at`, `updated_at`) VALUES
(1, 'Achat fournitures', 230.50, '2025-03-20', 1, 'FAC-2025-001', 'Papeterie Martin', 'Fournitures diverses', 'admin', '2025-08-20 12:50:10', '2025-08-20 12:50:10'),
(2, 'Maintenance imprimantes', 120.00, '2025-04-20', 2, 'REP-2025-002', 'TechnoService', 'Réparation imprimante bureau', 'admin', '2025-08-20 12:50:10', '2025-08-20 12:50:10'),
(3, 'Achat cahiers', 340.00, '2025-05-20', 1, 'FAC-2025-003', 'Fournitures Plus', 'Cahiers pour élèves', 'admin', '2025-08-20 12:50:10', '2025-08-20 12:50:10'),
(4, 'Réassort marqueurs', 95.20, '2025-06-20', 1, 'FAC-2025-004', 'Papeterie Centrale', 'Marqueurs tableau', 'admin', '2025-08-20 12:50:10', '2025-08-20 12:50:10'),
(5, 'Divers logistique', 180.00, '2025-07-20', 7, 'FAC-2025-005', 'Divers Fournisseurs', 'Frais divers', 'admin', '2025-08-20 12:50:10', '2025-08-20 12:50:10'),
(6, 'Achat papier A4', 210.00, '2025-08-20', 1, 'FAC-2025-006', 'Papeterie Martin', 'Papier A4 blanc', 'admin', '2025-08-20 12:50:10', '2025-08-20 12:50:10'),
(7, 'Achat de cahiers et stylos', 245.50, '2025-01-15', 1, 'FAC-2025-001', 'Papeterie Martin', 'Commande pour les classes de CP', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(8, 'Réparation photocopieur', 180.00, '2025-01-18', 2, 'REP-2025-003', 'TechnoService', 'Remplacement tambour', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(9, 'Ordinateurs portables (x5)', 2500.00, '2025-01-20', 3, 'INV-2025-012', 'InfoPlus', 'Pour la salle informatique', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(10, 'Facture électricité janvier', 320.75, '2025-01-25', 5, 'EDF-2025-01', 'EDF', 'Consommation janvier 2025', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(11, 'Formation premiers secours', 150.00, '2025-01-28', 4, 'FORM-2025-002', 'SecuriFormation', 'Formation obligatoire personnel', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(12, 'Carburant bus scolaire', 95.30, '2025-01-30', 6, 'TOTAL-2025-015', 'Station Total', 'Plein mensuel', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37'),
(13, 'Produits d\'entretien', 78.90, '2025-02-02', 7, 'NET-2025-004', 'CleanPro', 'Détergents et désinfectants', 'admin', '2025-08-20 18:43:37', '2025-08-20 18:43:37');

-- --------------------------------------------------------

--
-- Structure de la table `fournisseurs`
--

CREATE TABLE `fournisseurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `contact` varchar(100) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `fournisseurs`
--

INSERT INTO `fournisseurs` (`id`, `nom`, `contact`, `email`, `telephone`, `adresse`, `created_at`, `updated_at`) VALUES
(1, 'Fournitures Scolaires Plus', 'Marie Dubois', 'marie@fournitures-plus.fr', '01.23.45.67.89', '123 Rue de l\'École, 75001 Paris', '2025-08-20 17:06:47', '2025-08-20 17:06:47'),
(2, 'Papeterie Centrale', 'Jean Martin', 'contact@papeterie-centrale.fr', '01.98.76.54.32', '456 Avenue des Fournitures, 69000 Lyon', '2025-08-20 17:06:47', '2025-08-20 17:06:47'),
(4, 'Peter safari AKILIMALI', 'claude', 'peter23xp@gmail.com', '0974473513', 'Q. BUJOVU AV. MBAYIKI', '2025-08-20 17:12:48', '2025-08-20 17:12:48');

-- --------------------------------------------------------

--
-- Structure de la table `mouvements`
--

CREATE TABLE `mouvements` (
  `id` int(11) NOT NULL,
  `article_id` int(11) DEFAULT NULL,
  `action` varchar(50) NOT NULL COMMENT 'ajout, modification, suppression',
  `details` text DEFAULT NULL,
  `utilisateur` varchar(100) DEFAULT NULL,
  `date_mouvement` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `mouvements`
--

INSERT INTO `mouvements` (`id`, `article_id`, `action`, `details`, `utilisateur`, `date_mouvement`) VALUES
(1, 1, 'ajout', 'Initialisation: Stylos bleus - Quantité: 120, Seuil: 50', 'admin', '2025-08-20 13:49:48'),
(2, 2, 'ajout', 'Initialisation: Cahiers A4 - Quantité: 40, Seuil: 60', 'admin', '2025-08-20 13:49:48'),
(3, 3, 'ajout', 'Initialisation: Marqueurs effaçables - Quantité: 15, Seuil: 30', 'admin', '2025-08-20 13:49:48'),
(4, 4, 'ajout', 'Initialisation: Feuilles A3 - Quantité: 6, Seuil: 10', 'admin', '2025-08-20 13:49:48'),
(5, 5, 'ajout', 'Initialisation: Cartouches impression - Quantité: 3, Seuil: 5', 'admin', '2025-08-20 13:49:48'),
(8, 1, 'modification', 'Quantité mise à jour: stock vérifié', 'gestionnaire', '2025-08-20 13:49:48'),
(9, 2, 'modification', 'Seuil d\'alerte ajusté de 60 à 50 unités', 'admin', '2025-08-20 13:49:48'),
(10, 3, 'modification', 'Stock critique: commande urgente nécessaire', 'gestionnaire', '2025-08-20 13:49:48'),
(11, 4, 'modification', 'Stock très faible: réassort immédiat requis', 'admin', '2025-08-20 13:49:48'),
(12, 5, 'modification', 'Remplacement préventif des cartouches', 'gestionnaire', '2025-08-20 13:49:48'),
(13, 6, 'ajout', 'Nouveau article ajouté: Calculatrices scientifiques - Quantité initiale: 25', 'admin', '2025-08-20 13:49:48'),
(14, 7, 'ajout', 'Nouveau article ajouté: Règles 30cm - Quantité initiale: 45', 'admin', '2025-08-20 13:49:48'),
(15, 8, 'ajout', 'Nouveau article ajouté: Livres de mathématiques - Quantité initiale: 80', 'admin', '2025-08-20 13:49:48'),
(16, 9, 'ajout', 'Nouveau article ajouté: Chaises scolaires - Quantité initiale: 120', 'admin', '2025-08-20 13:49:48'),
(17, 10, 'ajout', 'Nouveau article ajouté: Tableaux blancs - Quantité initiale: 8', 'admin', '2025-08-20 13:49:48'),
(18, 11, 'ajout', 'Nouveau article ajouté: Projecteurs - Quantité initiale: 5', 'admin', '2025-08-20 13:49:48'),
(19, 12, 'ajout', 'Nouveau article ajouté: Ordinateurs portables - Quantité initiale: 12', 'admin', '2025-08-20 13:49:48'),
(20, 13, 'ajout', 'Nouveau article ajouté: Cahiers travaux pratiques - Quantité initiale: 200', 'admin', '2025-08-20 13:49:48');

-- --------------------------------------------------------

--
-- Structure de la table `stocks`
--

CREATE TABLE `stocks` (
  `id` int(11) NOT NULL,
  `nom_article` varchar(150) NOT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `seuil` int(11) NOT NULL,
  `prix_achat` decimal(10,2) NOT NULL DEFAULT 0.00,
  `prix_vente` decimal(10,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `seuil_alerte` int(11) DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stocks`
--

INSERT INTO `stocks` (`id`, `nom_article`, `categorie`, `quantite`, `seuil`, `created_at`, `updated_at`, `seuil_alerte`) VALUES
(1, 'Stylos bleus', 'Fournitures', 120, 50, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10),
(2, 'Cahiers A4', 'Papeterie', 40, 60, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10),
(3, 'Marqueurs effaçables', 'Fournitures', 15, 30, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10),
(4, 'Feuilles A3', 'Papeterie', 6, 10, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10),
(5, 'Cartouches impression', 'Informatique', 3, 5, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10),
(6, 'Calculatrices scientifiques', 'Matériel scolaire', 25, 5, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10),
(7, 'Règles 30cm', 'Matériel scolaire', 45, 10, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10),
(8, 'Livres de mathématiques', 'Manuels', 80, 15, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10),
(9, 'Chaises scolaires', 'Mobilier', 120, 20, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10),
(10, 'Tableaux blancs', 'Matériel enseignant', 8, 3, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10),
(11, 'Projecteurs', 'Informatique', 5, 2, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10),
(12, 'Ordinateurs portables', 'Informatique', 12, 3, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10),
(13, 'Cahiers travaux pratiques', 'Papeterie', 200, 40, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','gestionnaire','caissier','utilisateur') NOT NULL DEFAULT 'utilisateur',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@scolaria.local', '$2y$10$4IWV/38Ka0c/FgDNnoCYSu25WxwaDycCMdr1J8PVc8Gf/xsahvmRu', 'admin', CURRENT_TIMESTAMP()),
(2, 'gestionnaire', 'gestionnaire@scolaria.local', '$2y$10$cMNWtGk4gDqCjt6Buodrf.kqHFi3bfNupK6Fx3VHfDS3mC6GtUDuO', 'gestionnaire', CURRENT_TIMESTAMP()),
(3, 'caissier', 'caissier@scolaria.local', '$2y$10$hZqjKqZIfwWf9DqvFq4HPeE1iY2Gx1rK9rPVp3oXf7wq0sE4H3PlS', 'caissier', CURRENT_TIMESTAMP()),
(4, 'user', 'user@scolaria.local', '$2y$10$4Av1WeP5w8B0QmXrY3zqXuqpG2o3KqZ4m8n4Oe3gXxEJwGf8QWJpe', 'utilisateur', CURRENT_TIMESTAMP());

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `budgets`
--
ALTER TABLE `budgets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_budget` (`mois`,`annee`,`categorie_id`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `idx_periode` (`mois`,`annee`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nom` (`nom`);

--
-- Index pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `article_id` (`article_id`),
  ADD KEY `fournisseur_id` (`fournisseur_id`),
  ADD KEY `idx_commandes_statut` (`statut`),
  ADD KEY `idx_commandes_date` (`date_commande`);

--
-- Index pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `idx_date` (`date`),
  ADD KEY `idx_montant` (`montant`);

--
-- Index pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `mouvements`
--
ALTER TABLE `mouvements`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_article_id` (`article_id`),
  ADD KEY `idx_date_mouvement` (`date_mouvement`),
  ADD KEY `idx_action` (`action`);

--
-- Index pour la table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stocks_seuil` (`quantite`,`seuil_alerte`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `budgets`
--
ALTER TABLE `budgets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `depenses`
--
ALTER TABLE `depenses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `fournisseurs`
--
ALTER TABLE `fournisseurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `mouvements`
--
ALTER TABLE `mouvements`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=28;

--
-- AUTO_INCREMENT pour la table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `depenses_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`);

--
-- Contraintes pour la table `mouvements`
--
ALTER TABLE `mouvements`
  ADD CONSTRAINT `mouvements_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;

-- --------------------------------------------------------

--
-- Structure de la vue `v_depenses_rapport`
--

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

-- --------------------------------------------------------

--
-- Structure de la vue `v_budgets_comparaison`
--

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

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
