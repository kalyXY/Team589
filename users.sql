-- SCOLARIA - Initial database schema and seed for users
-- Usage: import this file in phpMyAdmin or run via mysql CLI

CREATE DATABASE IF NOT EXISTS `scolaria` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `scolaria`;

-- Drop table if you need a clean slate (comment out in production)
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `username` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('admin','gestionnaire','enseignant') NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed users (passwords are bcrypt hashes of 'admin123' and 'gest123')
INSERT INTO `users` (`username`, `password`, `role`) VALUES
('admin', '$2y$10$4IWV/38Ka0c/FgDNnoCYSu25WxwaDycCMdr1J8PVc8Gf/xsahvmRu', 'admin'),
('gestionnaire', '$2y$10$cMNWtGk4gDqCjt6Buodrf.kqHFi3bfNupK6Fx3VHfDS3mC6GtUDuO', 'gestionnaire');

-- Demo tables for dashboard
DROP TABLE IF EXISTS `stocks`;
CREATE TABLE `stocks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nom_article` VARCHAR(150) NOT NULL,
  `categorie` VARCHAR(100),
  `quantite` INT NOT NULL,
  `seuil` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

DROP TABLE IF EXISTS `depenses`;
CREATE TABLE `depenses` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `description` VARCHAR(255),
  `montant` DECIMAL(10,2) NOT NULL,
  `date` DATE NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed demo data
INSERT INTO `stocks` (`nom_article`, `categorie`, `quantite`, `seuil`) VALUES
('Stylos bleus', 'Fournitures', 120, 50),
('Cahiers A4', 'Papeterie', 40, 60),
('Marqueurs effaçables', 'Fournitures', 15, 30),
('Feuilles A3', 'Papeterie', 6, 10),
('Cartouches impression', 'Informatique', 3, 5);

INSERT INTO `depenses` (`description`, `montant`, `date`) VALUES
('Achat fournitures', 230.50, DATE_SUB(CURDATE(), INTERVAL 5 MONTH)),
('Maintenance imprimantes', 120.00, DATE_SUB(CURDATE(), INTERVAL 4 MONTH)),
('Achat cahiers', 340.00, DATE_SUB(CURDATE(), INTERVAL 3 MONTH)),
('Réassort marqueurs', 95.20, DATE_SUB(CURDATE(), INTERVAL 2 MONTH)),
('Divers logistique', 180.00, DATE_SUB(CURDATE(), INTERVAL 1 MONTH)),
('Achat papier A4', 210.00, CURDATE());


