<?php
/**
 * Script de test pour vérifier le nettoyage de la base de données
 * Scolaria - Team589
 */

echo "<h1>Vérification du nettoyage de la base de données - Scolaria</h1>";

$sql_file = 'sql/scolaria.sql';

if (!file_exists($sql_file)) {
    echo "<p style='color: red;'>❌ Le fichier $sql_file n'existe pas !</p>";
    exit;
}

echo "<h2>1. Analyse des données dans le fichier SQL</h2>";

$content = file_get_contents($sql_file);
$lines = explode("\n", $content);

// Rechercher tous les INSERT INTO
$inserts = [];
$table_data_status = [];

foreach ($lines as $line_number => $line) {
    if (preg_match('/INSERT INTO `([^`]+)`/', $line, $matches)) {
        $table_name = $matches[1];
        $inserts[] = [
            'table' => $table_name,
            'line' => $line_number + 1,
            'content' => trim($line)
        ];
        $table_data_status[$table_name] = 'HAS_DATA';
    }
}

// Rechercher les tables avec commentaire "Table vide"
foreach ($lines as $line_number => $line) {
    if (strpos($line, '-- Table vide - données supprimées pour démarrage propre') !== false) {
        // Trouver la table correspondante
        for ($i = $line_number; $i >= 0; $i--) {
            if (preg_match('/Déchargement des données de la table `([^`]+)`/', $lines[$i], $matches)) {
                $table_name = $matches[1];
                $table_data_status[$table_name] = 'EMPTY';
                break;
            }
        }
    }
}

// Afficher le statut de chaque table
echo "<h3>Statut des données par table :</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Table</th>";
echo "<th>Statut</th>";
echo "<th>Note</th>";
echo "</tr>";

$expected_empty_tables = [
    'categories', 'notifications', 'budgets', 'clients', 'commandes', 
    'depenses', 'fournisseurs', 'mouvements', 'sales', 'sales_items', 
    'school_settings', 'stocks', 'system_config'
];

$expected_with_data = ['users'];

foreach ($expected_empty_tables as $table) {
    $status = isset($table_data_status[$table]) ? $table_data_status[$table] : 'UNKNOWN';
    $color = $status === 'EMPTY' ? 'green' : 'red';
    $icon = $status === 'EMPTY' ? '✅' : '❌';
    $note = $status === 'EMPTY' ? 'Correctement vidée' : 'Contient encore des données';
    
    echo "<tr>";
    echo "<td><strong>$table</strong></td>";
    echo "<td style='color: $color;'>$icon $status</td>";
    echo "<td>$note</td>";
    echo "</tr>";
}

foreach ($expected_with_data as $table) {
    $status = isset($table_data_status[$table]) ? $table_data_status[$table] : 'UNKNOWN';
    $color = $status === 'HAS_DATA' ? 'green' : 'red';
    $icon = $status === 'HAS_DATA' ? '✅' : '❌';
    $note = $status === 'HAS_DATA' ? 'Données conservées (correct)' : 'Données supprimées (erreur)';
    
    echo "<tr>";
    echo "<td><strong>$table</strong></td>";
    echo "<td style='color: $color;'>$icon $status</td>";
    echo "<td>$note</td>";
    echo "</tr>";
}

echo "</table>";

// Résumé
echo "<h2>2. Résumé du nettoyage</h2>";

$empty_count = 0;
$data_count = 0;

foreach ($expected_empty_tables as $table) {
    if (isset($table_data_status[$table]) && $table_data_status[$table] === 'EMPTY') {
        $empty_count++;
    }
}

foreach ($expected_with_data as $table) {
    if (isset($table_data_status[$table]) && $table_data_status[$table] === 'HAS_DATA') {
        $data_count++;
    }
}

$total_expected_empty = count($expected_empty_tables);
$total_expected_data = count($expected_with_data);

echo "<ul>";
echo "<li><strong>Tables vidées :</strong> $empty_count / $total_expected_empty</li>";
echo "<li><strong>Tables avec données conservées :</strong> $data_count / $total_expected_data</li>";
echo "</ul>";

if ($empty_count === $total_expected_empty && $data_count === $total_expected_data) {
    echo "<p style='color: green; font-weight: bold;'>🎉 Nettoyage réussi ! Toutes les tables sont dans l'état attendu.</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ Problème détecté dans le nettoyage.</p>";
}

// Détails des INSERT restants
if (!empty($inserts)) {
    echo "<h2>3. INSERT INTO restants</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Table</th>";
    echo "<th>Ligne</th>";
    echo "<th>Statut</th>";
    echo "</tr>";
    
    foreach ($inserts as $insert) {
        $is_expected = in_array($insert['table'], $expected_with_data);
        $color = $is_expected ? 'green' : 'red';
        $status = $is_expected ? '✅ Attendu' : '❌ À supprimer';
        
        echo "<tr>";
        echo "<td><strong>{$insert['table']}</strong></td>";
        echo "<td>{$insert['line']}</td>";
        echo "<td style='color: $color;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h2>4. Recommandations</h2>";

if ($empty_count === $total_expected_empty && $data_count === $total_expected_data) {
    echo "<p style='color: green;'>✅ Le fichier SQL est prêt pour l'import avec une base de données propre !</p>";
    echo "<p><strong>Prochaines étapes :</strong></p>";
    echo "<ol>";
    echo "<li>Tester l'import avec <code>test_sql_import.php</code></li>";
    echo "<li>Importer dans Laragon avec <code>import_laragon.php</code></li>";
    echo "<li>Vérifier que seuls les utilisateurs sont présents</li>";
    echo "</ol>";
} else {
    echo "<p style='color: red;'>❌ Le nettoyage n'est pas complet. Vérifiez les tables marquées en rouge.</p>";
}

echo "<hr>";
echo "<p><strong>Note :</strong> Seule la table 'users' devrait contenir des données après l'import.</p>";
echo "<p><a href='test_sql_import.php'>🔗 Tester l'import SQL</a></p>";
echo "<p><a href='import_laragon.php'>🔗 Script d'import Laragon</a></p>";
?>
