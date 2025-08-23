<?php
/**
 * Script de test pour vérifier la correction de users_admin.php
 * Scolaria - Team589
 */

echo "<h1>Test de la correction de users_admin.php - Scolaria</h1>";

// Configuration de la base de données
require_once 'config/config.php';
require_once 'config/db.php';

try {
    $pdo = Database::getConnection();
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
    
    echo "<h2>1. Vérification de la structure de la table users</h2>";
    
    // Vérifier la structure actuelle
    $stmt = $pdo->query("DESCRIBE `users`");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #f0f0f0;'>";
    echo "<th>Champ</th>";
    echo "<th>Type</th>";
    echo "<th>Null</th>";
    echo "<th>Clé</th>";
    echo "<th>Défaut</th>";
    echo "<th>Extra</th>";
    echo "</tr>";
    
    $required_columns = [
        'id', 'username', 'full_name', 'email', 'phone', 
        'password', 'role', 'status', 'avatar_path', 'created_at'
    ];
    
    $existing_columns = [];
    
    foreach ($columns as $column) {
        $existing_columns[] = $column['Field'];
        $color = in_array($column['Field'], $required_columns) ? 'green' : 'black';
        
        echo "<tr>";
        echo "<td style='color: $color;'><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>2. Vérification des colonnes requises</h2>";
    
    $missing_columns = array_diff($required_columns, $existing_columns);
    $present_columns = array_intersect($required_columns, $existing_columns);
    
    echo "<ul>";
    foreach ($present_columns as $column) {
        echo "<li style='color: green;'>✅ <strong>$column</strong> - Présent</li>";
    }
    
    foreach ($missing_columns as $column) {
        echo "<li style='color: red;'>❌ <strong>$column</strong> - Manquant</li>";
    }
    echo "</ul>";
    
    if (empty($missing_columns)) {
        echo "<p style='color: green; font-weight: bold;'>✅ Toutes les colonnes requises sont présentes !</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ Certaines colonnes sont manquantes.</p>";
    }
    
    echo "<h2>3. Test de la fonction columnExists</h2>";
    
    // Tester la fonction columnExists
    function columnExists($pdo, $table, $column) {
        try {
            $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?");
            $stmt->execute([$table, $column]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    echo "<p>Test de la fonction columnExists :</p>";
    echo "<ul>";
    foreach ($required_columns as $column) {
        $exists = columnExists($pdo, 'users', $column);
        $status = $exists ? '✅ Existe' : '❌ N\'existe pas';
        $color = $exists ? 'green' : 'red';
        echo "<li style='color: $color;'>$column : $status</li>";
    }
    echo "</ul>";
    
    echo "<h2>4. Test d'ajout de colonne manquante</h2>";
    
    // Tester l'ajout d'une colonne de test
    $test_column = 'test_column_' . time();
    if (!columnExists($pdo, 'users', $test_column)) {
        echo "<p>🧪 Test d'ajout de la colonne de test : $test_column</p>";
        try {
            $pdo->exec("ALTER TABLE users ADD COLUMN $test_column VARCHAR(50) NULL");
            echo "<p style='color: green;'>✅ Colonne de test ajoutée avec succès</p>";
            
            // Vérifier qu'elle existe maintenant
            if (columnExists($pdo, 'users', $test_column)) {
                echo "<p style='color: green;'>✅ Vérification : la colonne existe bien</p>";
            } else {
                echo "<p style='color: red;'>❌ Vérification échouée</p>";
            }
            
            // Supprimer la colonne de test
            $pdo->exec("ALTER TABLE users DROP COLUMN $test_column");
            echo "<p style='color: green;'>✅ Colonne de test supprimée</p>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erreur lors de l'ajout de la colonne de test : {$e->getMessage()}</p>";
        }
    }
    
    echo "<h2>5. Résumé</h2>";
    
    if (empty($missing_columns)) {
        echo "<p style='color: green; font-weight: bold;'>🎉 La table users est correctement configurée !</p>";
        echo "<p>users_admin.php devrait maintenant fonctionner sans erreur SQL.</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ Des colonnes sont manquantes dans la table users.</p>";
        echo "<p>Exécutez users_admin.php pour qu'il ajoute automatiquement les colonnes manquantes.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Erreur de connexion à la base de données :</p>";
    echo "<p style='color: red;'>{$e->getMessage()}</p>";
}

echo "<hr>";
echo "<p><strong>Fichiers disponibles :</strong></p>";
echo "<ul>";
echo "<li><a href='users_admin.php'>👥 Gestion des utilisateurs</a></li>";
echo "<li><a href='update_sales_table.php'>💰 Corriger la table sales</a></li>";
echo "<li><a href='clean_database_test.php'>🧹 Vérifier le nettoyage</a></li>";
echo "</ul>";
?>
