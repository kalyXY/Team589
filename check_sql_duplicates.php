<?php
/**
 * Script de vérification des doublons dans le fichier SQL
 * Scolaria - Team589
 */

echo "<h1>Vérification des doublons dans le fichier SQL - Scolaria</h1>";

$sql_file = 'sql/scolaria.sql';

if (!file_exists($sql_file)) {
    echo "<p style='color: red;'>❌ Le fichier $sql_file n'existe pas !</p>";
    exit;
}

echo "<h2>1. Analyse du fichier SQL</h2>";

$content = file_get_contents($sql_file);
$lines = explode("\n", $content);

// Rechercher les tables
$tables = [];
$table_lines = [];
$duplicates = [];

echo "<h3>Tables trouvées :</h3>";
echo "<ul>";

foreach ($lines as $line_number => $line) {
    if (preg_match('/CREATE TABLE `([^`]+)`/', $line, $matches)) {
        $table_name = $matches[1];
        $tables[] = $table_name;
        $table_lines[$table_name][] = $line_number + 1;
        
        if (count($table_lines[$table_name]) > 1) {
            $duplicates[$table_name] = $table_lines[$table_name];
        }
        
        echo "<li><strong>$table_name</strong> - Ligne " . ($line_number + 1);
        if (count($table_lines[$table_name]) > 1) {
            echo " <span style='color: red;'>⚠️ DOUBLON</span>";
        }
        echo "</li>";
    }
}

echo "</ul>";

// Rechercher les vues
$views = [];
$view_lines = [];

echo "<h3>Vues trouvées :</h3>";
echo "<ul>";

foreach ($lines as $line_number => $line) {
    if (preg_match('/CREATE.*VIEW `([^`]+)`/', $line, $matches)) {
        $view_name = $matches[1];
        $views[] = $view_name;
        $view_lines[$view_name][] = $line_number + 1;
        
        echo "<li><strong>$view_name</strong> - Ligne " . ($line_number + 1) . "</li>";
    }
}

echo "</ul>";

// Rechercher les index dupliqués
$index_sections = [];
$index_duplicates = [];

echo "<h3>Sections d'index trouvées :</h3>";
echo "<ul>";

foreach ($lines as $line_number => $line) {
    if (preg_match('/Index pour la table `([^`]+)`/', $line, $matches)) {
        $table_name = $matches[1];
        $index_sections[$table_name][] = $line_number + 1;
        
        echo "<li><strong>Index pour $table_name</strong> - Ligne " . ($line_number + 1);
        if (count($index_sections[$table_name]) > 1) {
            echo " <span style='color: red;'>⚠️ DOUBLON</span>";
            $index_duplicates[$table_name] = $index_sections[$table_name];
        }
        echo "</li>";
    }
}

echo "</ul>";

// Afficher les résultats
echo "<h2>2. Résumé des doublons</h2>";

if (empty($duplicates) && empty($index_duplicates)) {
    echo "<p style='color: green;'>✅ Aucun doublon trouvé ! Le fichier SQL est propre.</p>";
} else {
    echo "<p style='color: red;'>❌ Doublons détectés :</p>";
    
    if (!empty($duplicates)) {
        echo "<h3>Tables dupliquées :</h3>";
        echo "<ul>";
        foreach ($duplicates as $table => $lines) {
            echo "<li><strong>$table</strong> - Lignes : " . implode(', ', $lines) . "</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($index_duplicates)) {
        echo "<h3>Index dupliqués :</h3>";
        echo "<ul>";
        foreach ($index_duplicates as $table => $lines) {
            echo "<li><strong>Index pour $table</strong> - Lignes : " . implode(', ', $lines) . "</li>";
        }
        echo "</ul>";
    }
}

// Statistiques
echo "<h2>3. Statistiques</h2>";
echo "<ul>";
echo "<li><strong>Nombre total de tables :</strong> " . count($tables) . "</li>";
echo "<li><strong>Nombre total de vues :</strong> " . count($views) . "</li>";
echo "<li><strong>Tables uniques :</strong> " . count(array_unique($tables)) . "</li>";
echo "<li><strong>Vues uniques :</strong> " . count(array_unique($views)) . "</li>";
echo "<li><strong>Doublons de tables :</strong> " . count($duplicates) . "</li>";
echo "<li><strong>Doublons d'index :</strong> " . count($index_duplicates) . "</li>";
echo "</ul>";

// Liste des tables uniques
echo "<h2>4. Liste des tables uniques</h2>";
$unique_tables = array_unique($tables);
sort($unique_tables);

echo "<ul>";
foreach ($unique_tables as $table) {
    echo "<li>$table</li>";
}
echo "</ul>";

// Liste des vues uniques
echo "<h2>5. Liste des vues uniques</h2>";
$unique_views = array_unique($views);
sort($unique_views);

echo "<ul>";
foreach ($unique_views as $view) {
    echo "<li>$view</li>";
}
echo "</ul>";

echo "<hr>";
echo "<p><a href='test_sql_import.php'>🔗 Tester l'import SQL</a></p>";
echo "<p><a href='import_laragon.php'>🔗 Script d'import Laragon</a></p>";
?>
