-- Table utilisateurs pour Scolaria

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','caissier','gestionnaire','directeur') NOT NULL DEFAULT 'caissier',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Compte administrateur par défaut (mot de passe: admin123)
-- Remplacer le hash si nécessaire
INSERT INTO users (username, password, role)
VALUES ('admin', '$2y$10$4IWV/38Ka0c/FgDNnoCYSu25WxwaDycCMdr1J8PVc8Gf/xsahvmRu', 'admin')
ON DUPLICATE KEY UPDATE username = username;

-- Compte directeur par défaut (mot de passe: admin123)
INSERT INTO users (username, password, role)
VALUES ('directeur', '$2y$10$4IWV/38Ka0c/FgDNnoCYSu25WxwaDycCMdr1J8PVc8Gf/xsahvmRu', 'directeur')
ON DUPLICATE KEY UPDATE username = username;
