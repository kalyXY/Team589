-- =====================================================
-- Script de mise à jour SÛR de la table sales
-- Scolaria - Team589
-- =====================================================

-- Ce script vérifie d'abord l'état de la table avant d'appliquer les modifications

-- ÉTAPE 1 : Vérifier la structure actuelle
DESCRIBE `sales`;

-- ÉTAPE 2 : Vérifier les index existants
SHOW INDEX FROM `sales`;

-- ÉTAPE 3 : Vérifier si PRIMARY KEY existe déjà
SELECT 
    COLUMN_NAME,
    COLUMN_KEY,
    EXTRA
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'sales' 
    AND COLUMN_NAME = 'id';

-- ÉTAPE 4 : Appliquer les corrections selon l'état actuel

-- Si pas de PRIMARY KEY, l'ajouter d'abord
-- (Décommentez la ligne suivante si nécessaire)
-- ALTER TABLE `sales` ADD PRIMARY KEY (`id`);

-- Si pas d'AUTO_INCREMENT, l'ajouter après
-- (Décommentez la ligne suivante si nécessaire)
-- ALTER TABLE `sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- ÉTAPE 5 : Vérifier le résultat final
DESCRIBE `sales`;

-- ÉTAPE 6 : Test d'insertion
-- (Décommentez les lignes suivantes pour tester)
-- INSERT INTO `sales` (`client_id`, `total`) VALUES (1, 25.50);
-- SELECT * FROM `sales` ORDER BY `id` DESC LIMIT 1;

-- =====================================================
-- Instructions d'utilisation
-- =====================================================
--
-- 1. Exécutez d'abord les commandes de vérification (ÉTAPES 1-3)
-- 2. Analysez le résultat pour voir ce qui manque
-- 3. Décommentez et exécutez les commandes ALTER TABLE nécessaires
-- 4. Vérifiez le résultat final (ÉTAPE 5)
-- 5. Testez l'insertion (ÉTAPE 6)
--
-- 🔧 RÈGLE IMPORTANTE :
-- MySQL exige qu'une colonne AUTO_INCREMENT soit définie comme clé
-- Il faut donc TOUJOURS avoir une PRIMARY KEY AVANT d'ajouter AUTO_INCREMENT
--
-- =====================================================
