-- =====================================================
-- Script de mise à jour de la table sales
-- Scolaria - Team589
-- =====================================================

-- IMPORTANT : Exécutez ces commandes dans l'ordre exact !

-- 1. D'abord, ajouter PRIMARY KEY sur le champ id
ALTER TABLE `sales` ADD PRIMARY KEY (`id`);

-- 2. Ensuite, modifier la colonne pour ajouter AUTO_INCREMENT
ALTER TABLE `sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

-- 3. Vérifier que la table a bien la structure attendue
-- (Cette commande affichera la structure actuelle)
DESCRIBE `sales`;

-- 4. Vérifier les index existants
SHOW INDEX FROM `sales`;

-- 5. Si vous voulez réinitialiser l'AUTO_INCREMENT à 1
-- (Optionnel - seulement si vous voulez repartir de 1)
-- ALTER TABLE `sales` AUTO_INCREMENT = 1;

-- =====================================================
-- Test de la correction
-- =====================================================

-- Test d'insertion (devrait maintenant fonctionner)
INSERT INTO `sales` (`client_id`, `total`) VALUES (1, 25.50);

-- Vérifier que l'insertion a fonctionné
SELECT * FROM `sales` ORDER BY `id` DESC LIMIT 1;

-- =====================================================
-- Notes importantes
-- =====================================================
-- 
-- ✅ Ce script corrige le problème "Field 'id' doesn't have a default value"
-- ✅ Après exécution, vous pourrez vendre des articles normalement
-- ✅ Les nouvelles ventes auront automatiquement un ID unique
-- 
-- ⚠️  ATTENTION : 
-- - Faites une sauvegarde de votre base avant d'exécuter ce script
-- - Exécutez les commandes DANS L'ORDRE EXACT indiqué
-- - Testez ensuite l'insertion
-- - Si tout fonctionne, vous pouvez supprimer les lignes de test
--
-- 🔧 EXPLICATION DE L'ERREUR 1075 :
-- MySQL exige qu'une colonne AUTO_INCREMENT soit définie comme clé (PRIMARY ou UNIQUE)
-- Il faut donc d'abord ajouter la PRIMARY KEY, puis modifier la colonne
--
-- =====================================================
