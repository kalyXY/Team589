-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : jeu. 21 août 2025 à 17:06
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

SET FOREIGN_KEY_CHECKS=0;


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

DROP TABLE IF EXISTS `budgets`;
CREATE TABLE `budgets` (
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

DROP TABLE IF EXISTS `categories`;
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
(7, 'Divers', 'Autres dépenses non catégorisées', '#6B7280', '2025-08-20 18:43:37', '2025-08-20 18:43:37');

-- --------------------------------------------------------

--
-- Structure de la table `clients`
--

DROP TABLE IF EXISTS `clients`;
CREATE TABLE `clients` (
  `id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `address` text DEFAULT NULL,
  `client_type` enum('parent','eleve','acheteur_regulier','autre') DEFAULT 'autre',
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Déchargement des données de la table `clients`
--

INSERT INTO `clients` (`id`, `first_name`, `last_name`, `phone`, `email`, `address`, `client_type`, `notes`, `created_at`, `updated_at`) VALUES
(1, 'Marie', 'Dubois', '+243 81 234 5678', 'marie.dubois@email.com', '123 Avenue Kasavubu, Kinshasa', 'parent', 'Mère de 2 enfants en primaire', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(2, 'Jean', 'Mukendi', '+243 82 345 6789', 'jean.mukendi@email.com', '456 Boulevard Lumumba, Gombe', 'parent', 'Père de 3 enfants', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(3, 'Sophie', 'Tshimanga', '+243 83 456 7890', 'sophie.tshimanga@email.com', '789 Rue de la Paix, Lemba', 'acheteur_regulier', 'Achète régulièrement des fournitures', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(4, 'Pierre', 'Kabongo', '+243 84 567 8901', 'pierre.kabongo@email.com', '321 Avenue Mobutu, Kinshasa', 'parent', 'Parent délégué de classe', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(5, 'Grace', 'Mbuyi', '+243 85 678 9012', 'grace.mbuyi@email.com', '654 Rue Victoire, Matete', 'parent', 'Mère célibataire, 1 enfant', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(6, 'David', 'Nkomo', '+243 86 789 0123', 'david.nkomo@email.com', '987 Boulevard Triomphal, Ngaliema', 'acheteur_regulier', 'Professeur, achète pour sa classe', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(7, 'Esther', 'Kalala', '+243 87 890 1234', 'esther.kalala@email.com', '147 Avenue Liberation, Bandalungwa', 'parent', 'Mère de jumeaux', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(8, 'Joseph', 'Mwamba', '+243 88 901 2345', 'joseph.mwamba@email.com', '258 Rue Université, Lemba', 'autre', 'Directeur d\'école partenaire', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(9, 'Chantal', 'Ilunga', '+243 89 012 3456', 'chantal.ilunga@email.com', '369 Avenue Kasa-Vubu, Barumbu', 'parent', 'Présidente association des parents', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(10, 'Emmanuel', 'Kasongo', '+243 90 123 4567', 'emmanuel.kasongo@email.com', '741 Boulevard du 30 Juin, Gombe', 'acheteur_regulier', 'Grossiste en fournitures scolaires', '2025-08-21 11:14:44', '2025-08-21 11:14:44'),
(22, 'Peter', 'AKILIMALI', '0974473513', 'peter23xp@gmail.com', 'Q. BUJOVU AV. MBAYIKI', 'autre', NULL, '2025-08-21 13:25:30', '2025-08-21 13:25:30');

-- --------------------------------------------------------

--
-- Structure de la table `commandes`
--

DROP TABLE IF EXISTS `commandes`;
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
(2, 2, 2, 50, 1.20, 'validée', '2025-08-20 17:06:47', NULL, 'Livraison prévue fin de semaine', 'system'),
(3, 2, 4, 12, 123.00, 'en attente', '2025-08-21 10:45:03', NULL, 'TEST', 'admin'),
(4, 2, 4, 12, 123.00, 'en attente', '2025-08-21 10:45:12', NULL, 'TEST', 'admin');

-- --------------------------------------------------------

--
-- Structure de la table `depenses`
--

DROP TABLE IF EXISTS `depenses`;
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

DROP TABLE IF EXISTS `fournisseurs`;
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

DROP TABLE IF EXISTS `mouvements`;
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
-- Structure de la table `sales`
--

DROP TABLE IF EXISTS `sales`;
CREATE TABLE `sales` (
  `id` int(11) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `total` decimal(10,2) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sales`
--

INSERT INTO `sales` (`id`, `client_id`, `total`, `created_at`) VALUES
(1, 1, 12.50, '2025-01-15 08:30:00'),
(2, 1, 7.50, '2025-01-15 08:35:00'),
(3, 1, 12.50, '2025-01-15 08:30:00'),
(4, 1, 7.50, '2025-01-15 08:35:00'),
(5, 1, 12.50, '2025-01-15 08:30:00'),
(6, 1, 7.50, '2025-01-15 08:35:00'),
(7, 1, 12.50, '2025-01-15 08:30:00'),
(8, 1, 7.50, '2025-01-15 08:35:00');

-- --------------------------------------------------------

--
-- Structure de la table `sales_items`
--

DROP TABLE IF EXISTS `sales_items`;
CREATE TABLE `sales_items` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `price` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `sales_items`
--

INSERT INTO `sales_items` (`id`, `sale_id`, `product_id`, `quantity`, `price`) VALUES
(1, 2, 1, 10, 0.75);

-- --------------------------------------------------------

--
-- Structure de la table `transactions`
--

DROP TABLE IF EXISTS `transactions`;
CREATE TABLE `transactions` (
  `id` int(11) NOT NULL,
  `sale_id` int(11) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `payment_method` enum('cash','mobile_money','card','transfer') NOT NULL,
  `reference` varchar(100) DEFAULT NULL,
  `paid_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `alertes`
--

DROP TABLE IF EXISTS `alertes`;
CREATE TABLE `alertes` (
  `id` int(11) NOT NULL,
  `stock_id` int(11) NOT NULL,
  `type` enum('low_stock','out_of_stock') NOT NULL,
  `message` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Structure de la table `stocks`
--

DROP TABLE IF EXISTS `stocks`;
CREATE TABLE `stocks` (
  `id` int(11) NOT NULL,
  `nom_article` varchar(150) NOT NULL,
  `categorie` varchar(100) DEFAULT NULL,
  `code_barres` varchar(64) DEFAULT NULL,
  `prix_achat` decimal(10,2) DEFAULT NULL,
  `quantite` int(11) NOT NULL,
  `seuil` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `seuil_alerte` int(11) DEFAULT 10,
  `prix_vente` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `stocks`
--

INSERT INTO `stocks` (`id`, `nom_article`, `categorie`, `quantite`, `seuil`, `created_at`, `updated_at`, `seuil_alerte`, `prix_vente`) VALUES
(1, 'Stylos bleus', 'Fournitures', 120, 50, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10, 0.00),
(2, 'Cahiers A4', 'Papeterie', 40, 60, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10, 0.00),
(3, 'Marqueurs effaçables', 'Fournitures', 15, 30, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10, 0.00),
(4, 'Feuilles A3', 'Papeterie', 6, 10, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10, 0.00),
(5, 'Cartouches impression', 'Informatique', 3, 5, '2025-08-20 12:50:10', '2025-08-20 13:49:48', 10, 0.00),
(6, 'Calculatrices scientifiques', 'Matériel scolaire', 25, 5, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00),
(7, 'Règles 30cm', 'Matériel scolaire', 45, 10, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00),
(8, 'Livres de mathématiques', 'Manuels', 80, 15, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00),
(9, 'Chaises scolaires', 'Mobilier', 120, 20, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00),
(10, 'Tableaux blancs', 'Matériel enseignant', 8, 3, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00),
(11, 'Projecteurs', 'Informatique', 5, 2, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00),
(12, 'Ordinateurs portables', 'Informatique', 12, 3, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00),
(13, 'Cahiers travaux pratiques', 'Papeterie', 200, 40, '2025-08-20 13:49:48', '2025-08-20 13:49:48', 10, 0.00);

-- --------------------------------------------------------

--
-- Structure de la table `users`
--

DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','gestionnaire','caissier','directeur','utilisateur') NOT NULL DEFAULT 'utilisateur',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `role`, `created_at`) VALUES
(1, 'admin', 'admin@scolaria.local', '$2y$10$4IWV/38Ka0c/FgDNnoCYSu25WxwaDycCMdr1J8PVc8Gf/xsahvmRu', 'admin', '2025-08-21 09:55:06'),
(2, 'gestionnaire', 'gestionnaire@scolaria.local', '$2y$10$cMNWtGk4gDqCjt6Buodrf.kqHFi3bfNupK6Fx3VHfDS3mC6GtUDuO', 'gestionnaire', '2025-08-21 09:55:06'),
(3, 'caissier', 'caissier@scolaria.local', '$2y$10$bd7dN.BF6mgWmVWj1TiJyOYPAuRhlgnpeOaEyduKsug1MEsYEhQDy', 'caissier', '2025-08-21 10:14:29'),
(8, 'directeur', 'directeur@scolaria.local', '$2y$10$4IWV/38Ka0c/FgDNnoCYSu25WxwaDycCMdr1J8PVc8Gf/xsahvmRu', 'directeur', '2025-08-21 14:32:08');

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_budgets_comparaison`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_budgets_comparaison` (
`id` int(11)
,`mois` int(11)
,`annee` int(11)
,`montant_prevu` decimal(10,2)
,`categorie_nom` varchar(100)
,`categorie_couleur` varchar(7)
,`montant_reel` decimal(32,2)
,`difference` decimal(33,2)
,`statut` varchar(11)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_clients_stats`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_clients_stats` (
`id` int(11)
,`first_name` varchar(100)
,`last_name` varchar(100)
,`phone` varchar(20)
,`email` varchar(150)
,`client_type` enum('parent','eleve','acheteur_regulier','autre')
,`total_purchases` bigint(21)
,`total_spent` decimal(32,2)
,`avg_purchase` decimal(14,6)
,`last_purchase_date` timestamp
,`first_purchase_date` timestamp
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_client_purchase_history`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_client_purchase_history` (
`sale_id` int(11)
,`client_id` int(11)
,`client_name` varchar(201)
,`phone` varchar(20)
,`product_name` varchar(150)
,`quantity` int(11)
,`unit_price` decimal(10,2)
,`total_amount` decimal(20,2)
,`sale_date` timestamp
,`payment_method` binary(0)
,`notes` binary(0)
);

-- --------------------------------------------------------

--
-- Doublure de structure pour la vue `v_depenses_rapport`
-- (Voir ci-dessous la vue réelle)
--
CREATE TABLE `v_depenses_rapport` (
`id` int(11)
,`description` varchar(255)
,`montant` decimal(10,2)
,`date` date
,`facture_numero` varchar(50)
,`fournisseur` varchar(100)
,`categorie_nom` varchar(100)
,`categorie_couleur` varchar(7)
,`annee` int(4)
,`mois` int(2)
,`periode` varchar(7)
);

-- --------------------------------------------------------

--
-- Structure de la vue `v_budgets_comparaison`
--
DROP TABLE IF EXISTS `v_budgets_comparaison`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_budgets_comparaison`  AS SELECT `b`.`id` AS `id`, `b`.`mois` AS `mois`, `b`.`annee` AS `annee`, `b`.`montant_prevu` AS `montant_prevu`, `c`.`nom` AS `categorie_nom`, `c`.`couleur` AS `categorie_couleur`, coalesce(sum(`d`.`montant`),0) AS `montant_reel`, `b`.`montant_prevu`- coalesce(sum(`d`.`montant`),0) AS `difference`, CASE WHEN coalesce(sum(`d`.`montant`),0) > `b`.`montant_prevu` THEN 'depassement' WHEN coalesce(sum(`d`.`montant`),0) > `b`.`montant_prevu` * 0.8 THEN 'attention' ELSE 'normal' END AS `statut` FROM ((`budgets` `b` left join `categories` `c` on(`b`.`categorie_id` = `c`.`id`)) left join `depenses` `d` on(`d`.`categorie_id` = `b`.`categorie_id` and month(`d`.`date`) = `b`.`mois` and year(`d`.`date`) = `b`.`annee`)) GROUP BY `b`.`id`, `b`.`mois`, `b`.`annee`, `b`.`montant_prevu`, `c`.`nom`, `c`.`couleur` ORDER BY `b`.`annee` DESC, `b`.`mois` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_clients_stats`
--
DROP TABLE IF EXISTS `v_clients_stats`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_clients_stats`  AS SELECT `c`.`id` AS `id`, `c`.`first_name` AS `first_name`, `c`.`last_name` AS `last_name`, `c`.`phone` AS `phone`, `c`.`email` AS `email`, `c`.`client_type` AS `client_type`, count(`sa`.`id`) AS `total_purchases`, coalesce(sum(`sa`.`total`),0) AS `total_spent`, coalesce(avg(`sa`.`total`),0) AS `avg_purchase`, max(`sa`.`created_at`) AS `last_purchase_date`, min(`sa`.`created_at`) AS `first_purchase_date` FROM (`clients` `c` left join `sales` `sa` on(`c`.`id` = `sa`.`client_id`)) GROUP BY `c`.`id`, `c`.`first_name`, `c`.`last_name`, `c`.`phone`, `c`.`email`, `c`.`client_type` ORDER BY coalesce(sum(`sa`.`total`),0) DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_client_purchase_history`
--
DROP TABLE IF EXISTS `v_client_purchase_history`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_client_purchase_history`  AS SELECT `sa`.`id` AS `sale_id`, `sa`.`client_id` AS `client_id`, concat(`c`.`first_name`,' ',`c`.`last_name`) AS `client_name`, `c`.`phone` AS `phone`, `st`.`nom_article` AS `product_name`, `si`.`quantity` AS `quantity`, `si`.`price` AS `unit_price`, `si`.`quantity`* `si`.`price` AS `total_amount`, `sa`.`created_at` AS `sale_date`, `t`.`payment_method` AS `payment_method`, `t`.`reference` AS `notes` FROM ((((
  `sales` `sa`
  join `clients` `c` on(`sa`.`client_id` = `c`.`id`))
  join `sales_items` `si` on(`si`.`sale_id` = `sa`.`id`))
  join `stocks` `st` on(`st`.`id` = `si`.`product_id`))
  left join `transactions` `t` on(`t`.`sale_id` = `sa`.`id`)
) ORDER BY `sa`.`created_at` DESC ;

-- --------------------------------------------------------

--
-- Structure de la vue `v_depenses_rapport`
--
DROP TABLE IF EXISTS `v_depenses_rapport`;

CREATE ALGORITHM=UNDEFINED DEFINER=`root`@`localhost` SQL SECURITY DEFINER VIEW `v_depenses_rapport`  AS SELECT `d`.`id` AS `id`, `d`.`description` AS `description`, `d`.`montant` AS `montant`, `d`.`date` AS `date`, `d`.`facture_numero` AS `facture_numero`, `d`.`fournisseur` AS `fournisseur`, `c`.`nom` AS `categorie_nom`, `c`.`couleur` AS `categorie_couleur`, year(`d`.`date`) AS `annee`, month(`d`.`date`) AS `mois`, date_format(`d`.`date`,'%Y-%m') AS `periode` FROM (`depenses` `d` left join `categories` `c` on(`d`.`categorie_id` = `c`.`id`)) ORDER BY `d`.`date` DESC ;

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
-- Index pour la table `clients`
--
ALTER TABLE `clients`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `phone` (`phone`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_name` (`first_name`,`last_name`),
  ADD KEY `idx_phone` (`phone`),
  ADD KEY `idx_email` (`email`),
  ADD KEY `idx_type` (`client_type`);

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
  ADD KEY `idx_montant` (`montant`),
  ADD KEY `idx_date_depenses` (`date`),
  ADD KEY `idx_categorie_depenses` (`categorie_id`),
  ADD KEY `idx_montant_depenses` (`montant`),
  ADD KEY `idx_depenses_categorie_id` (`categorie_id`);

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
-- Index pour la table `sales`
--
ALTER TABLE `sales`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `sales_items`
--
ALTER TABLE `sales_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Index pour la table `stocks`
--
ALTER TABLE `stocks`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_stocks_seuil` (`quantite`,`seuil_alerte`),
  ADD KEY `idx_code_barres` (`code_barres`);

--
-- Index pour la table `transactions`
--

ALTER TABLE `transactions`
  ADD PRIMARY KEY (`id`),
  ADD KEY `sale_id` (`sale_id`),
  ADD KEY `idx_payment_method` (`payment_method`);

--
-- Index pour la table `alertes`
--

ALTER TABLE `alertes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `stock_id` (`stock_id`),
  ADD KEY `idx_type` (`type`);

--
-- Index pour la table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_users_username` (`username`),
  ADD UNIQUE KEY `uniq_users_email` (`email`);

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
-- AUTO_INCREMENT pour la table `clients`
--
ALTER TABLE `clients`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT pour la table `commandes`
--
ALTER TABLE `commandes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- AUTO_INCREMENT pour la table `sales`
--
ALTER TABLE `sales`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `sales_items`
--
ALTER TABLE `sales_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT pour la table `transactions`
--

ALTER TABLE `transactions`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `alertes`
--

ALTER TABLE `alertes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT pour la table `stocks`
--
ALTER TABLE `stocks`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT pour la table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `budgets`
--
ALTER TABLE `budgets`
  ADD CONSTRAINT `budgets_ibfk_1` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL;

--
-- Contraintes pour la table `commandes`
--
ALTER TABLE `commandes`
  ADD CONSTRAINT `commandes_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `commandes_ibfk_2` FOREIGN KEY (`fournisseur_id`) REFERENCES `fournisseurs` (`id`);

--
-- Contraintes pour la table `depenses`
--
ALTER TABLE `depenses`
  ADD CONSTRAINT `fk_depenses_categories` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Contraintes pour la table `mouvements`
--
ALTER TABLE `mouvements`
  ADD CONSTRAINT `mouvements_ibfk_1` FOREIGN KEY (`article_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `sales_items`
--
ALTER TABLE `sales_items`
  ADD CONSTRAINT `sales_items_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `sales_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `stocks` (`id`);
--
-- Contraintes pour la table `transactions`
--

ALTER TABLE `transactions`
  ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`sale_id`) REFERENCES `sales` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `alertes`
--

ALTER TABLE `alertes`
  ADD CONSTRAINT `alertes_ibfk_1` FOREIGN KEY (`stock_id`) REFERENCES `stocks` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
