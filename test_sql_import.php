<?php
/**
 * Script de test pour l'import du fichier SQL corrigé
 * Scolaria - Team589
 */

echo "<h1>Test d'import du fichier SQL corrigé - Scolaria</h1>";

// Configuration Laragon
$config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '',
    'port' => 3306,
    'database' => 'scolaria_test'
];

// Vérifier si le fichier SQL existe
$sql_file = 'sql/scolaria.sql';
if (!file_exists($sql_file)) {
    echo "<p style='color: red;'>❌ Le fichier $sql_file n'existe pas !</p>";
    exit;
}

echo "<h2>1. Test de connexion MySQL</h2>";

try {
    // Connexion à MySQL
    $dsn = "mysql:host={$config['host']};port={$config['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['user'], $config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connexion MySQL réussie !</p>";
    
    // Supprimer la base de test si elle existe
    $pdo->exec("DROP DATABASE IF EXISTS `{$config['database']}`");
    echo "<p style='color: orange;'>🗑️ Base de test supprimée (si elle existait).</p>";
    
    // Créer la nouvelle base de test
    $pdo->exec("CREATE DATABASE `{$config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "<p style='color: green;'>✅ Base de test '{$config['database']}' créée.</p>";
    
    // Sélectionner la base de test
    $pdo->exec("USE `{$config['database']}`");
    
    // Lire et exécuter le fichier SQL
    echo "<h2>2. Import du fichier SQL</h2>";
    
    $sql_content = file_get_contents($sql_file);
    
    // Diviser le fichier SQL en requêtes individuelles
    $queries = explode(';', $sql_content);
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query) && !preg_match('/^(--|\/\*|SET|START|COMMIT)/', $query)) {
            try {
                $pdo->exec($query);
                $success_count++;
            } catch (PDOException $e) {
                $error_count++;
                $errors[] = [
                    'query' => substr($query, 0, 100) . '...',
                    'error' => $e->getMessage()
                ];
            }
        }
    }
    
    echo "<p style='color: green;'>✅ Import terminé ! $success_count requêtes exécutées avec succès.</p>";
    
    if ($error_count > 0) {
        echo "<p style='color: red;'>❌ $error_count erreurs rencontrées :</p>";
        echo "<ul>";
        foreach ($errors as $error) {
            echo "<li><strong>Requête :</strong> " . htmlspecialchars($error['query']) . "</li>";
            echo "<li><strong>Erreur :</strong> " . htmlspecialchars($error['error']) . "</li>";
            echo "<hr>";
        }
        echo "</ul>";
    } else {
        echo "<p style='color: green;'>🎉 Aucune erreur ! L'import s'est déroulé parfaitement.</p>";
    }
    
    // Vérifier les tables créées
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Tables créées :</h3>";
    echo "<ul>";
    foreach ($tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    
    // Tester quelques requêtes pour vérifier l'intégrité
    echo "<h2>3. Test d'intégrité des données</h2>";
    
    try {
        // Test 1: Compter les utilisateurs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>👥 Nombre d'utilisateurs : $user_count</p>";
        
        // Test 2: Compter les stocks
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM stocks");
        $stock_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>📦 Nombre d'articles en stock : $stock_count</p>";
        
        // Test 3: Compter les mouvements
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mouvements");
        $mouvement_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>📝 Nombre de mouvements : $mouvement_count</p>";
        
        // Test 4: Vérifier qu'il n'y a pas d'ID = 0
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM mouvements WHERE id = 0");
        $zero_id_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>🔍 Enregistrements avec ID = 0 dans mouvements : $zero_id_count</p>";
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM stocks WHERE id = 0");
        $zero_id_stocks = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>🔍 Enregistrements avec ID = 0 dans stocks : $zero_id_stocks</p>";
        
        if ($zero_id_count == 0 && $zero_id_stocks == 0) {
            echo "<p style='color: green;'>✅ Aucun enregistrement avec ID = 0 trouvé !</p>";
        } else {
            echo "<p style='color: red;'>❌ Des enregistrements avec ID = 0 existent encore !</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors des tests d'intégrité : " . $e->getMessage() . "</p>";
    }
    
    // Nettoyer la base de test
    $pdo->exec("DROP DATABASE IF EXISTS `{$config['database']}`");
    echo "<p style='color: orange;'>🗑️ Base de test nettoyée.</p>";
    
    echo "<h2>4. Résumé</h2>";
    if ($error_count == 0) {
        echo "<p style='color: green; font-weight: bold;'>🎉 Le fichier SQL est maintenant corrigé et prêt pour l'import !</p>";
        echo "<p>Vous pouvez maintenant utiliser le script <code>import_laragon.php</code> pour importer la base de données.</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Il y a encore des erreurs dans le fichier SQL.</p>";
        echo "<p>Vérifiez les erreurs listées ci-dessus et corrigez-les.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion MySQL : " . $e->getMessage() . "</p>";
    echo "<p><strong>Assurez-vous que MySQL est démarré dans Laragon.</strong></p>";
}

echo "<hr>";
echo "<p><a href='import_laragon.php'>🔗 Aller au script d'import Laragon</a></p>";
echo "<p><a href='check_database.php'>🔗 Aller au script de diagnostic</a></p>";
?>
