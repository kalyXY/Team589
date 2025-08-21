-- Script SQL pour le module Clients - Scolaria (Mama Sophie School Supplies)
-- Table clients et données de test

-- Table des clients
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) UNIQUE,
    email VARCHAR(150) UNIQUE,
    address TEXT,
    client_type ENUM('parent', 'eleve', 'acheteur_regulier', 'autre') DEFAULT 'autre',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (first_name, last_name),
    INDEX idx_phone (phone),
    INDEX idx_email (email),
    INDEX idx_type (client_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- NOTE: la table sales est gérée par le module POS (sales + sales_items)
-- Ce script n'ajoute plus de table sales ici pour éviter les conflits de schéma.

-- Insertion de clients de test
INSERT INTO clients (first_name, last_name, phone, email, address, client_type, notes) VALUES
('Marie', 'Dubois', '+243 81 234 5678', 'marie.dubois@email.com', '123 Avenue Kasavubu, Kinshasa', 'parent', 'Mère de 2 enfants en primaire'),
('Jean', 'Mukendi', '+243 82 345 6789', 'jean.mukendi@email.com', '456 Boulevard Lumumba, Gombe', 'parent', 'Père de 3 enfants'),
('Sophie', 'Tshimanga', '+243 83 456 7890', 'sophie.tshimanga@email.com', '789 Rue de la Paix, Lemba', 'acheteur_regulier', 'Achète régulièrement des fournitures'),
('Pierre', 'Kabongo', '+243 84 567 8901', 'pierre.kabongo@email.com', '321 Avenue Mobutu, Kinshasa', 'parent', 'Parent délégué de classe'),
('Grace', 'Mbuyi', '+243 85 678 9012', 'grace.mbuyi@email.com', '654 Rue Victoire, Matete', 'parent', 'Mère célibataire, 1 enfant'),
('David', 'Nkomo', '+243 86 789 0123', 'david.nkomo@email.com', '987 Boulevard Triomphal, Ngaliema', 'acheteur_regulier', 'Professeur, achète pour sa classe'),
('Esther', 'Kalala', '+243 87 890 1234', 'esther.kalala@email.com', '147 Avenue Liberation, Bandalungwa', 'parent', 'Mère de jumeaux'),
('Joseph', 'Mwamba', '+243 88 901 2345', 'joseph.mwamba@email.com', '258 Rue Université, Lemba', 'autre', 'Directeur d\'école partenaire'),
('Chantal', 'Ilunga', '+243 89 012 3456', 'chantal.ilunga@email.com', '369 Avenue Kasa-Vubu, Barumbu', 'parent', 'Présidente association des parents'),
('Emmanuel', 'Kasongo', '+243 90 123 4567', 'emmanuel.kasongo@email.com', '741 Boulevard du 30 Juin, Gombe', 'acheteur_regulier', 'Grossiste en fournitures scolaires')
ON DUPLICATE KEY UPDATE 
    first_name = VALUES(first_name),
    last_name = VALUES(last_name);

-- Insertion d'exemples de ventes en se basant sur les tables POS (sales, sales_items) et stocks
-- Chaque insertion crée une vente et une ligne associée (si le produit existe dans stocks)
-- Exemples: vous pouvez commenter ceux qui ne correspondent pas à vos produits actuels

-- Vente 1: Cahiers 100 pages x5 @2.50 => 12.50
SET @pid := (SELECT id FROM stocks WHERE nom_article LIKE 'Cahiers%100%' LIMIT 1);
SET @total := 12.50;
INSERT INTO sales (client_id, total, created_at) VALUES (1, @total, '2025-01-15 10:30:00');
SET @sid := LAST_INSERT_ID();
INSERT INTO sales_items (sale_id, product_id, quantity, price)
SELECT @sid, @pid, 5, 2.50 WHERE @pid IS NOT NULL;

-- Vente 2: Stylos bleus x10 @0.75 => 7.50
SET @pid := (SELECT id FROM stocks WHERE nom_article LIKE 'Stylos bleus%' LIMIT 1);
SET @total := 7.50;
INSERT INTO sales (client_id, total, created_at) VALUES (1, @total, '2025-01-15 10:35:00');
SET @sid := LAST_INSERT_ID();
INSERT INTO sales_items (sale_id, product_id, quantity, price)
SELECT @sid, @pid, 10, 0.75 WHERE @pid IS NOT NULL;

-- Vue pour les statistiques clients
CREATE OR REPLACE VIEW v_clients_stats AS
SELECT 
    c.id,
    c.first_name,
    c.last_name,
    c.phone,
    c.email,
    c.client_type,
    COUNT(sa.id) as total_purchases,
    COALESCE(SUM(sa.total), 0) as total_spent,
    COALESCE(AVG(sa.total), 0) as avg_purchase,
    MAX(sa.created_at) as last_purchase_date,
    MIN(sa.created_at) as first_purchase_date
FROM clients c
LEFT JOIN sales sa ON c.id = sa.client_id
GROUP BY c.id, c.first_name, c.last_name, c.phone, c.email, c.client_type
ORDER BY total_spent DESC;

-- Vue pour l'historique détaillé des achats
CREATE OR REPLACE VIEW v_client_purchase_history AS
SELECT 
    sa.id as sale_id,
    sa.client_id,
    CONCAT(c.first_name, ' ', c.last_name) as client_name,
    c.phone,
    st.nom_article as product_name,
    si.quantity,
    si.price as unit_price,
    (si.quantity * si.price) as total_amount,
    sa.created_at as sale_date,
    NULL as payment_method,
    NULL as notes
FROM sales sa
JOIN clients c ON sa.client_id = c.id
JOIN sales_items si ON si.sale_id = sa.id
JOIN stocks st ON st.id = si.product_id
ORDER BY sa.created_at DESC;