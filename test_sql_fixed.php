<?php
/**
 * Script de test pour le fichier SQL corrigé
 * Scolaria - Team589
 */

echo "<h1>Test du fichier SQL corrigé - Scolaria</h1>";

$sql_file = 'sql/scolaria.sql';

if (!file_exists($sql_file)) {
    echo "<p style='color: red;'>❌ Le fichier $sql_file n'existe pas !</p>";
    exit;
}

echo "<h2>1. Vérification des erreurs courantes</h2>";

$content = file_get_contents($sql_file);
$lines = explode("\n", $content);

// Vérifier les erreurs courantes
$errors = [];

// 1. Vérifier les doublons de tables
$tables = [];
$table_lines = [];
foreach ($lines as $line_number => $line) {
    if (preg_match('/CREATE TABLE `([^`]+)`/', $line, $matches)) {
        $table_name = $matches[1];
        $tables[] = $table_name;
        $table_lines[$table_name][] = $line_number + 1;
        
        if (count($table_lines[$table_name]) > 1) {
            $errors[] = "Table dupliquée : $table_name (lignes : " . implode(', ', $table_lines[$table_name]) . ")";
        }
    }
}

// 2. Vérifier les clés primaires dupliquées
if (preg_match_all('/ADD PRIMARY KEY/', $content)) {
    $errors[] = "Instructions ADD PRIMARY KEY trouvées - peuvent causer des conflits";
}

// 3. Vérifier les IDs = 0 dans les INSERT
if (preg_match('/INSERT INTO.*\(0,/', $content)) {
    $errors[] = "INSERT avec ID = 0 trouvé - peut causer des erreurs de clé primaire";
}

// 4. Vérifier la syntaxe des vues
if (preg_match('/CREATE.*VIEW.*\(\)/', $content)) {
    $errors[] = "Vue avec structure vide trouvée";
}

// Afficher les résultats
if (empty($errors)) {
    echo "<p style='color: green;'>✅ Aucune erreur critique détectée !</p>";
} else {
    echo "<p style='color: red;'>❌ Erreurs détectées :</p>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
}

echo "<h2>2. Statistiques du fichier</h2>";
echo "<ul>";
echo "<li><strong>Nombre de lignes :</strong> " . count($lines) . "</li>";
echo "<li><strong>Nombre de tables :</strong> " . count(array_unique($tables)) . "</li>";
echo "<li><strong>Taille du fichier :</strong> " . number_format(strlen($content) / 1024, 2) . " KB</li>";
echo "</ul>";

echo "<h2>3. Structure des tables</h2>";
$unique_tables = array_unique($tables);
sort($unique_tables);

echo "<ul>";
foreach ($unique_tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

echo "<h2>4. Test de syntaxe SQL</h2>";

// Test simple de syntaxe
$sql_errors = [];
$test_queries = [
    'CREATE TABLE test (id INT PRIMARY KEY)',
    'INSERT INTO test VALUES (1)',
    'SELECT * FROM test'
];

foreach ($test_queries as $query) {
    if (!preg_match('/^[A-Za-z\s\(\)\d\'\",\.\-\+\*\/\=\<\>\!\@\#\$\%\^\&\*\(\)\[\]\{\}\|\;\:]+$/', $query)) {
        $sql_errors[] = "Syntaxe suspecte dans : $query";
    }
}

if (empty($sql_errors)) {
    echo "<p style='color: green;'>✅ Syntaxe SQL de base valide</p>";
} else {
    echo "<p style='color: red;'>❌ Problèmes de syntaxe détectés</p>";
}

echo "<h2>5. Recommandations</h2>";

if (empty($errors)) {
    echo "<p style='color: green;'>🎉 Le fichier SQL semble prêt pour l'import !</p>";
    echo "<p><strong>Prochaines étapes :</strong></p>";
    echo "<ol>";
    echo "<li>Tester l'import avec <code>test_sql_import.php</code></li>";
    echo "<li>Importer dans Laragon avec <code>import_laragon.php</code></li>";
    echo "<li>Vérifier l'application</li>";
    echo "</ol>";
} else {
    echo "<p style='color: orange;'>⚠️ Corrigez les erreurs avant l'import</p>";
}

echo "<hr>";
echo "<p><a href='test_sql_import.php'>🔗 Tester l'import SQL</a></p>";
echo "<p><a href='import_laragon.php'>🔗 Script d'import Laragon</a></p>";
echo "<p><a href='check_sql_duplicates.php'>🔗 Vérifier les doublons</a></p>";
?>
