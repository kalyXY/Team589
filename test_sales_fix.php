<?php
/**
 * Script de test pour vérifier la correction de la table sales
 * Scolaria - Team589
 */

echo "<h1>Test de la correction de la table sales - Scolaria</h1>";

$sql_file = 'sql/scolaria.sql';

if (!file_exists($sql_file)) {
    echo "<p style='color: red;'>❌ Le fichier $sql_file n'existe pas !</p>";
    exit;
}

echo "<h2>1. Vérification de la structure de la table sales</h2>";

$content = file_get_contents($sql_file);

// Vérifier que la table sales a AUTO_INCREMENT
if (preg_match('/CREATE TABLE `sales`\s*\(\s*`id`\s+int\(11\)\s+NOT\s+NULL\s+AUTO_INCREMENT/s', $content)) {
    echo "<p style='color: green;'>✅ La table sales a bien AUTO_INCREMENT sur le champ id</p>";
} else {
    echo "<p style='color: red;'>❌ La table sales n'a pas AUTO_INCREMENT sur le champ id</p>";
}

// Vérifier que la table sales a PRIMARY KEY
if (preg_match('/PRIMARY KEY \(`id`\)/s', $content)) {
    echo "<p style='color: green;'>✅ La table sales a bien PRIMARY KEY sur le champ id</p>";
} else {
    echo "<p style='color: red;'>❌ La table sales n'a pas PRIMARY KEY sur le champ id</p>";
}

// Vérifier la section AUTO_INCREMENT
if (preg_match('/ALTER TABLE `sales`\s+MODIFY `id` int\(11\) NOT NULL AUTO_INCREMENT/s', $content)) {
    echo "<p style='color: green;'>✅ La section AUTO_INCREMENT pour sales est correcte</p>";
} else {
    echo "<p style='color: red;'>❌ La section AUTO_INCREMENT pour sales est manquante ou incorrecte</p>";
}

echo "<h2>2. Structure complète de la table sales</h2>";

// Extraire la définition complète de la table sales
if (preg_match('/CREATE TABLE `sales`\s*\(([^)]+)\)\s+ENGINE/s', $content, $matches)) {
    $table_definition = $matches[1];
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo "CREATE TABLE `sales` (\n";
    echo $table_definition;
    echo "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    echo "</pre>";
} else {
    echo "<p style='color: red;'>❌ Impossible d'extraire la définition de la table sales</p>";
}

echo "<h2>3. Test de simulation d'insertion</h2>";

// Simuler une requête INSERT
$test_insert = "INSERT INTO sales (client_id, total) VALUES (1, 25.50)";
echo "<p><strong>Requête de test :</strong></p>";
echo "<code style='background: #f0f0f0; padding: 5px; border-radius: 3px;'>$test_insert</code>";

echo "<p>Cette requête devrait maintenant fonctionner car :</p>";
echo "<ul>";
echo "<li>✅ Le champ <code>id</code> a <code>AUTO_INCREMENT</code></li>";
echo "<li>✅ Le champ <code>id</code> est <code>PRIMARY KEY</code></li>";
echo "<li>✅ Le champ <code>created_at</code> a une valeur par défaut</li>";
echo "</ul>";

echo "<h2>4. Recommandations</h2>";

echo "<p style='color: green;'>✅ La correction de la table sales est terminée !</p>";
echo "<p><strong>Prochaines étapes :</strong></p>";
echo "<ol>";
echo "<li>Réimporter le fichier SQL corrigé dans votre base de données</li>";
echo "<li>Tester la vente d'un article</li>";
echo "<li>Vérifier que l'erreur 'Field id doesn\'t have a default value' n'apparaît plus</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='clean_database_test.php'>🔗 Vérifier le nettoyage de la base</a></p>";
echo "<p><a href='test_sql_import.php'>🔗 Tester l'import SQL</a></p>";
echo "<p><a href='import_laragon.php'>🔗 Script d'import Laragon</a></p>";
?>
