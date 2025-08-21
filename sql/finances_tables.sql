-- Script SQL pour le module Gestion Financière - Scolaria Team589
-- Tables : categories, depenses, budgets

-- Table des catégories de dépenses
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    couleur VARCHAR(7) DEFAULT '#3B82F6',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des dépenses
CREATE TABLE IF NOT EXISTS depenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date DATE NOT NULL,
    categorie_id INT,
    facture_numero VARCHAR(50),
    fournisseur VARCHAR(100),
    notes TEXT,
    created_by VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_date (date),
    INDEX idx_categorie (categorie_id),
    INDEX idx_montant (montant)
);

-- Table des budgets mensuels
CREATE TABLE IF NOT EXISTS budgets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mois INT NOT NULL CHECK (mois BETWEEN 1 AND 12),
    annee INT NOT NULL,
    montant_prevu DECIMAL(10,2) NOT NULL,
    categorie_id INT NULL,
    notes TEXT,
    created_by VARCHAR(50) DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categorie_id) REFERENCES categories(id) ON DELETE SET NULL,
    UNIQUE KEY unique_budget (mois, annee, categorie_id),
    INDEX idx_periode (mois, annee)
);

-- Insertion des catégories par défaut
INSERT INTO categories (nom, description, couleur) VALUES
('Fournitures', 'Matériel scolaire, papeterie, consommables', '#3B82F6'),
('Maintenance', 'Réparations, entretien des équipements', '#EF4444'),
('Investissement', 'Achat d\'équipements, mobilier, informatique', '#10B981'),
('Personnel', 'Salaires, charges sociales, formations', '#8B5CF6'),
('Utilities', 'Électricité, eau, internet, téléphone', '#F59E0B'),
('Transport', 'Déplacements, carburant, transport scolaire', '#06B6D4'),
('Divers', 'Autres dépenses non catégorisées', '#6B7280')
ON DUPLICATE KEY UPDATE 
    description = VALUES(description),
    couleur = VALUES(couleur);

-- Insertion de données de test
INSERT INTO depenses (description, montant, date, categorie_id, facture_numero, fournisseur, notes) VALUES
('Achat de cahiers et stylos', 245.50, '2025-01-15', 1, 'FAC-2025-001', 'Papeterie Martin', 'Commande pour les classes de CP'),
('Réparation photocopieur', 180.00, '2025-01-18', 2, 'REP-2025-003', 'TechnoService', 'Remplacement tambour'),
('Ordinateurs portables (x5)', 2500.00, '2025-01-20', 3, 'INV-2025-012', 'InfoPlus', 'Pour la salle informatique'),
('Facture électricité janvier', 320.75, '2025-01-25', 5, 'EDF-2025-01', 'EDF', 'Consommation janvier 2025'),
('Formation premiers secours', 150.00, '2025-01-28', 4, 'FORM-2025-002', 'SecuriFormation', 'Formation obligatoire personnel'),
('Carburant bus scolaire', 95.30, '2025-01-30', 6, 'TOTAL-2025-015', 'Station Total', 'Plein mensuel'),
('Produits d\'entretien', 78.90, '2025-02-02', 7, 'NET-2025-004', 'CleanPro', 'Détergents et désinfectants')
ON DUPLICATE KEY UPDATE 
    description = VALUES(description);

-- Insertion de budgets de test
INSERT INTO budgets (mois, annee, montant_prevu, categorie_id, notes) VALUES
(1, 2025, 1000.00, 1, 'Budget fournitures janvier 2025'),
(1, 2025, 500.00, 2, 'Budget maintenance janvier 2025'),
(1, 2025, 3000.00, 3, 'Budget investissement janvier 2025'),
(2, 2025, 1200.00, 1, 'Budget fournitures février 2025'),
(2, 2025, 400.00, 2, 'Budget maintenance février 2025')
ON DUPLICATE KEY UPDATE 
    montant_prevu = VALUES(montant_prevu),
    notes = VALUES(notes);

-- Vue pour les rapports financiers
CREATE OR REPLACE VIEW v_depenses_rapport AS
SELECT 
    d.id,
    d.description,
    d.montant,
    d.date,
    d.facture_numero,
    d.fournisseur,
    c.nom as categorie_nom,
    c.couleur as categorie_couleur,
    YEAR(d.date) as annee,
    MONTH(d.date) as mois,
    DATE_FORMAT(d.date, '%Y-%m') as periode
FROM depenses d
LEFT JOIN categories c ON d.categorie_id = c.id
ORDER BY d.date DESC;

-- Vue pour les budgets avec comparaison
CREATE OR REPLACE VIEW v_budgets_comparaison AS
SELECT 
    b.id,
    b.mois,
    b.annee,
    b.montant_prevu,
    c.nom as categorie_nom,
    c.couleur as categorie_couleur,
    COALESCE(SUM(d.montant), 0) as montant_reel,
    (b.montant_prevu - COALESCE(SUM(d.montant), 0)) as difference,
    CASE 
        WHEN COALESCE(SUM(d.montant), 0) > b.montant_prevu THEN 'depassement'
        WHEN COALESCE(SUM(d.montant), 0) > (b.montant_prevu * 0.8) THEN 'attention'
        ELSE 'normal'
    END as statut
FROM budgets b
LEFT JOIN categories c ON b.categorie_id = c.id
LEFT JOIN depenses d ON d.categorie_id = b.categorie_id 
    AND MONTH(d.date) = b.mois 
    AND YEAR(d.date) = b.annee
GROUP BY b.id, b.mois, b.annee, b.montant_prevu, c.nom, c.couleur
ORDER BY b.annee DESC, b.mois DESC;