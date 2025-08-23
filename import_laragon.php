<?php
/**
 * Script d'import de la base de données pour Laragon
 * Scolaria - Team589
 */

echo "<h1>Import de la base de données Scolaria pour Laragon</h1>";

// Configuration Laragon
$laragon_config = [
    'host' => 'localhost',
    'user' => 'root',
    'pass' => '', // Laragon utilise souvent un mot de passe vide
    'port' => 3306,
    'database' => 'scolaria'
];

// Vérifier si le fichier SQL existe
$sql_file = 'sql/scolaria.sql';
if (!file_exists($sql_file)) {
    echo "<p style='color: red;'>❌ Le fichier $sql_file n'existe pas !</p>";
    exit;
}

echo "<h2>1. Vérification de la connexion MySQL</h2>";

try {
    // Connexion à MySQL sans spécifier de base de données
    $dsn = "mysql:host={$laragon_config['host']};port={$laragon_config['port']};charset=utf8mb4";
    $pdo = new PDO($dsn, $laragon_config['user'], $laragon_config['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connexion MySQL réussie !</p>";
    
    // Vérifier si la base de données existe déjà
    $stmt = $pdo->query("SHOW DATABASES LIKE '{$laragon_config['database']}'");
    $exists = $stmt->rowCount() > 0;
    
    if ($exists) {
        echo "<p style='color: orange;'>⚠️ La base de données '{$laragon_config['database']}' existe déjà.</p>";
        echo "<p>Voulez-vous la supprimer et la recréer ?</p>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='recreate'>";
        echo "<button type='submit' style='background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Supprimer et recréer</button>";
        echo "</form>";
    } else {
        echo "<p style='color: green;'>✅ La base de données '{$laragon_config['database']}' n'existe pas encore.</p>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='create'>";
        echo "<button type='submit' style='background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;'>Créer la base de données</button>";
        echo "</form>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion MySQL : " . $e->getMessage() . "</p>";
    echo "<h3>Solutions possibles :</h3>";
    echo "<ol>";
    echo "<li><strong>Démarrer MySQL dans Laragon :</strong> Ouvrez Laragon et cliquez sur 'Start All'</li>";
    echo "<li><strong>Vérifier le port :</strong> Laragon utilise souvent le port 3306</li>";
    echo "<li><strong>Vérifier les identifiants :</strong> Laragon utilise souvent root sans mot de passe</li>";
    echo "<li><strong>Vérifier l'hôte :</strong> Assurez-vous que l'hôte est correct (localhost ou 127.0.0.1)</li>";
    echo "</ol>";
    exit;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'recreate') {
        echo "<h2>2. Création/Recréation de la base de données</h2>";
        
        try {
            if ($action === 'recreate') {
                // Supprimer la base existante
                $pdo->exec("DROP DATABASE IF EXISTS `{$laragon_config['database']}`");
                echo "<p style='color: orange;'>🗑️ Base de données supprimée.</p>";
            }
            
            // Créer la nouvelle base de données
            $pdo->exec("CREATE DATABASE `{$laragon_config['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "<p style='color: green;'>✅ Base de données '{$laragon_config['database']}' créée.</p>";
            
            // Sélectionner la base de données
            $pdo->exec("USE `{$laragon_config['database']}`");
            
            // Lire et exécuter le fichier SQL
            echo "<h2>3. Import des données</h2>";
            
            $sql_content = file_get_contents($sql_file);
            
            // Diviser le fichier SQL en requêtes individuelles
            $queries = explode(';', $sql_content);
            $success_count = 0;
            $error_count = 0;
            
            foreach ($queries as $query) {
                $query = trim($query);
                if (!empty($query) && !preg_match('/^(--|\/\*|SET|START|COMMIT)/', $query)) {
                    try {
                        $pdo->exec($query);
                        $success_count++;
                    } catch (PDOException $e) {
                        $error_count++;
                        echo "<p style='color: red;'>❌ Erreur dans la requête : " . $e->getMessage() . "</p>";
                    }
                }
            }
            
            echo "<p style='color: green;'>✅ Import terminé ! $success_count requêtes exécutées avec succès.</p>";
            if ($error_count > 0) {
                echo "<p style='color: orange;'>⚠️ $error_count erreurs rencontrées.</p>";
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
            
            echo "<h2>4. Test de connexion à la nouvelle base</h2>";
            
            // Tester la connexion à la nouvelle base
            $dsn_full = "mysql:host={$laragon_config['host']};dbname={$laragon_config['database']};charset=utf8mb4";
            if (defined('DB_PORT')) {
                $dsn_full .= ";port=" . DB_PORT;
            }
            
            $pdo_test = new PDO($dsn_full, $laragon_config['user'], $laragon_config['pass']);
            $pdo_test->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<p style='color: green;'>✅ Connexion à la base '{$laragon_config['database']}' réussie !</p>";
            
            // Tester une requête simple
            $stmt = $pdo_test->query("SELECT COUNT(*) as count FROM users");
            $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>👥 Nombre d'utilisateurs dans la base : $user_count</p>";
            
            echo "<h2>5. Configuration terminée !</h2>";
            echo "<p style='color: green; font-weight: bold;'>🎉 Votre base de données Scolaria est maintenant prête pour Laragon !</p>";
            
            echo "<h3>Liens utiles :</h3>";
            echo "<ul>";
            echo "<li><a href='http://localhost/scolaria/' target='_blank'>🔗 Application Scolaria</a></li>";
            echo "<li><a href='http://localhost/phpmyadmin/' target='_blank'>🔗 phpMyAdmin (si installé)</a></li>";
            echo "<li><a href='http://localhost/' target='_blank'>🔗 Page d'accueil Laragon</a></li>";
            echo "</ul>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erreur lors de l'import : " . $e->getMessage() . "</p>";
        }
    }
}

echo "<hr>";
echo "<h2>Instructions pour Laragon</h2>";
echo "<ol>";
echo "<li><strong>Démarrer Laragon :</strong> Ouvrez Laragon et cliquez sur 'Start All'</li>";
echo "<li><strong>Vérifier MySQL :</strong> Assurez-vous que MySQL est démarré (icône verte)</li>";
echo "<li><strong>Vérifier Apache :</strong> Assurez-vous qu'Apache est démarré (icône verte)</li>";
echo "<li><strong>Importer la base :</strong> Utilisez ce script ou importez manuellement via phpMyAdmin</li>";
echo "</ol>";

echo "<h2>Configuration Laragon par défaut</h2>";
echo "<ul>";
echo "<li><strong>Hôte :</strong> localhost</li>";
echo "<li><strong>Port :</strong> 3306</li>";
echo "<li><strong>Utilisateur :</strong> root</li>";
echo "<li><strong>Mot de passe :</strong> (vide)</li>";
echo "<li><strong>Base de données :</strong> scolaria</li>";
echo "</ul>";
?>
