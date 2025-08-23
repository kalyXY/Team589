-- Script SQL pour réinitialiser l'auto-increment d'une table
-- Scolaria - Team589
-- 
-- Utilisation :
-- 1. Remplacez 'nom_de_la_table' par le nom de votre table
-- 2. Exécutez ce script dans phpMyAdmin ou votre client MySQL

-- Exemple pour la table 'stocks'
-- Remplacez 'stocks' par le nom de votre table

-- Étape 1 : Vérifier l'état actuel
SELECT 
    TABLE_NAME,
    AUTO_INCREMENT,
    TABLE_ROWS
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'scolaria' 
AND TABLE_NAME = 'stocks';

-- Étape 2 : Obtenir le plus grand ID actuel
SELECT MAX(id) as max_id FROM stocks;

-- Étape 3 : Réinitialiser l'auto-increment (si la table est vide)
-- Si la table est vide, l'auto-increment sera mis à 1
ALTER TABLE stocks AUTO_INCREMENT = 1;

-- Étape 4 : Si la table contient des données et que vous voulez réorganiser les IDs
-- ATTENTION : Cette opération modifie les IDs existants !

-- Créer une table temporaire avec les nouveaux IDs
CREATE TEMPORARY TABLE temp_stocks AS 
SELECT *, (@rank := @rank + 1) as new_id 
FROM stocks 
ORDER BY id;

-- Initialiser le compteur
SET @rank = 0;

-- Mettre à jour les IDs dans la table temporaire
UPDATE temp_stocks SET id = new_id;

-- Supprimer les anciennes données
DELETE FROM stocks;

-- Insérer les données avec les nouveaux IDs
INSERT INTO stocks 
SELECT id, nom, description, quantite, seuil, prix_achat, prix_vente, fournisseur, categorie_id, created_at, updated_at
FROM temp_stocks;

-- Réinitialiser l'auto-increment
ALTER TABLE stocks AUTO_INCREMENT = (SELECT COUNT(*) + 1 FROM stocks);

-- Supprimer la table temporaire
DROP TEMPORARY TABLE temp_stocks;

-- Étape 5 : Vérifier le résultat
SELECT 
    TABLE_NAME,
    AUTO_INCREMENT,
    TABLE_ROWS
FROM information_schema.TABLES 
WHERE TABLE_SCHEMA = 'scolaria' 
AND TABLE_NAME = 'stocks';

-- Afficher les IDs pour vérification
SELECT id, nom FROM stocks ORDER BY id;
