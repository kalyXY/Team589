<?php
/**
 * Test pour vérifier l'état des auto-increments
 * Scolaria - Team589
 */

require_once 'config/config.php';
require_once 'config/db.php';

echo "<h1>Test des Auto-Increments - Scolaria</h1>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Liste des tables avec auto-increment
    $autoIncrementTables = [
        'categories',
        'notifications', 
        'alertes',
        'budgets',
        'clients',
        'commandes',
        'depenses',
        'fournisseurs',
        'login_history',
        'mouvements',
        'roles_custom',
        'sales',
        'sales_items',
        'stocks',
        'transactions',
        'users'
    ];
    
    echo "<h2>État actuel des tables</h2>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Table</th>";
    echo "<th>Lignes</th>";
    echo "<th>Auto-Increment</th>";
    echo "<th>ID Max</th>";
    echo "<th>État</th>";
    echo "<th>Action</th>";
    echo "</tr>";
    
    foreach ($autoIncrementTables as $table) {
        // Vérifier si la table existe
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            // Obtenir le nombre de lignes
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
            $stmt->execute();
            $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Obtenir l'auto-increment actuel
            $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE ?");
            $stmt->execute([$table]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            $autoIncrement = $status['Auto_increment'];
            
            // Obtenir le plus grand ID
            $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM `$table`");
            $stmt->execute();
            $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
            
            $hasGaps = $rowCount > 0 && $autoIncrement > ($maxId + 1);
            
            echo "<tr>";
            echo "<td><strong>$table</strong></td>";
            echo "<td>$rowCount</td>";
            echo "<td>$autoIncrement</td>";
            echo "<td>" . ($maxId ?: 0) . "</td>";
            
            if ($rowCount == 0) {
                echo "<td style='color: green;'>✅ Vide</td>";
                echo "<td>-</td>";
            } elseif ($hasGaps) {
                echo "<td style='color: orange;'>⚠️ Trous détectés</td>";
                echo "<td><a href='admin_reset_auto_increment.php' style='color: red;'>Réorganiser</a></td>";
            } else {
                echo "<td style='color: green;'>✅ OK</td>";
                echo "<td>-</td>";
            }
            
            echo "</tr>";
        }
    }
    
    echo "</table>";
    
    // Test d'ajout d'un élément
    echo "<h2>Test d'ajout d'élément</h2>";
    echo "<p>Pour tester l'auto-increment, vous pouvez :</p>";
    echo "<ol>";
    echo "<li>Aller sur la page <a href='stocks.php'>Stocks</a></li>";
    echo "<li>Ajouter un nouvel article</li>";
    echo "<li>Vérifier que l'ID est bien le suivant dans la séquence</li>";
    echo "</ol>";
    
    // Instructions
    echo "<h2>Instructions</h2>";
    echo "<p><strong>Si vous voyez des 'Trous détectés' :</strong></p>";
    echo "<ul>";
    echo "<li>Cliquez sur 'Réorganiser' pour corriger les séquences</li>";
    echo "<li>Ou utilisez le script SQL dans <code>sql/reset_auto_increment.sql</code></li>";
    echo "<li>Ou accédez à <a href='admin_reset_auto_increment.php'>Base de données</a> dans le menu</li>";
    echo "</ul>";
    
    echo "<p><strong>Note :</strong> Les 'trous' dans les séquences d'IDs sont normaux après suppression d'éléments. 
    Ils n'affectent pas le fonctionnement de l'application, mais peuvent être corrigés si nécessaire.</p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Erreur : " . $e->getMessage() . "</p>";
}
?>

<style>
body {
    font-family: Arial, sans-serif;
    margin: 20px;
    background: #f5f5f5;
}

h1, h2 {
    color: #333;
}

table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}

th, td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

th {
    background: #f8f9fa;
    font-weight: bold;
}

tr:hover {
    background: #f8f9fa;
}

a {
    color: #007bff;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}
</style>
