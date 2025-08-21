<?php
/**
 * Script de test de connexion à la base de données
 * Diagnostic complet pour identifier les problèmes de connexion
 * Scolaria Team589
 */

echo "<h1>🔍 Test de Connexion Base de Données</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    .test-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #007bff; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 5px; }
    .test-success { background: #d4edda; border: 1px solid #c3e6cb; }
    .test-error { background: #f8d7da; border: 1px solid #f5c6cb; }
    .test-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
    .test-info { background: #d1ecf1; border: 1px solid #bee5eb; }
    pre { background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

// 1. Test des extensions PHP
echo "<div class='test-section'>";
echo "<h2>🔧 Extensions PHP</h2>";

$extensions = ['pdo', 'pdo_mysql', 'mysqli'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "<div class='test-result test-success'>✅ Extension '$ext' chargée</div>";
    } else {
        echo "<div class='test-result test-error'>❌ Extension '$ext' manquante</div>";
    }
}
echo "</div>";

// 2. Test de la configuration
echo "<div class='test-section'>";
echo "<h2>⚙️ Configuration</h2>";

try {
    require_once __DIR__ . '/config/config.php';
    echo "<div class='test-result test-success'>✅ Fichier config.php chargé</div>";
    
    $config_vars = ['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS', 'DB_CHARSET'];
    foreach ($config_vars as $var) {
        if (defined($var)) {
            $value = constant($var);
            if ($var === 'DB_PASS') {
                $display_value = empty($value) ? '(vide)' : '(défini)';
            } else {
                $display_value = $value;
            }
            echo "<div class='test-result test-info'>ℹ️ $var = $display_value</div>";
        } else {
            echo "<div class='test-result test-error'>❌ $var non défini</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-error'>❌ Erreur config: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 3. Test de connexion MySQL direct
echo "<div class='test-section'>";
echo "<h2>🗄️ Test Connexion MySQL</h2>";

try {
    // Test avec mysqli d'abord
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS);
    
    if ($mysqli->connect_error) {
        echo "<div class='test-result test-error'>❌ Connexion MySQL échouée: " . $mysqli->connect_error . "</div>";
    } else {
        echo "<div class='test-result test-success'>✅ Connexion MySQL réussie</div>";
        echo "<div class='test-result test-info'>ℹ️ Version MySQL: " . $mysqli->server_info . "</div>";
        
        // Test de sélection de la base
        if ($mysqli->select_db(DB_NAME)) {
            echo "<div class='test-result test-success'>✅ Base de données '" . DB_NAME . "' accessible</div>";
            
            // Lister les tables
            $result = $mysqli->query("SHOW TABLES");
            if ($result) {
                $tables = [];
                while ($row = $result->fetch_array()) {
                    $tables[] = $row[0];
                }
                echo "<div class='test-result test-info'>ℹ️ Tables trouvées: " . implode(', ', $tables) . "</div>";
                
                // Vérifier les tables importantes
                $important_tables = ['categories', 'depenses', 'budgets', 'users'];
                foreach ($important_tables as $table) {
                    if (in_array($table, $tables)) {
                        echo "<div class='test-result test-success'>✅ Table '$table' existe</div>";
                    } else {
                        echo "<div class='test-result test-warning'>⚠️ Table '$table' manquante</div>";
                    }
                }
            }
        } else {
            echo "<div class='test-result test-error'>❌ Base de données '" . DB_NAME . "' inaccessible: " . $mysqli->error . "</div>";
        }
        
        $mysqli->close();
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-error'>❌ Erreur MySQL: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 4. Test de connexion PDO
echo "<div class='test-section'>";
echo "<h2>🔌 Test Connexion PDO</h2>";

try {
    $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    echo "<div class='test-result test-info'>ℹ️ DSN sans base: $dsn</div>";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    // Test connexion sans base d'abord
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    echo "<div class='test-result test-success'>✅ Connexion PDO (sans base) réussie</div>";
    
    // Test avec la base
    $dsn_with_db = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
    echo "<div class='test-result test-info'>ℹ️ DSN avec base: $dsn_with_db</div>";
    
    $pdo_with_db = new PDO($dsn_with_db, DB_USER, DB_PASS, $options);
    echo "<div class='test-result test-success'>✅ Connexion PDO (avec base) réussie</div>";
    
    // Test d'une requête simple
    $stmt = $pdo_with_db->query("SELECT 1 as test");
    $result = $stmt->fetch();
    if ($result && $result['test'] == 1) {
        echo "<div class='test-result test-success'>✅ Requête de test réussie</div>";
    }
    
} catch (PDOException $e) {
    echo "<div class='test-result test-error'>❌ Erreur PDO: " . $e->getMessage() . "</div>";
    echo "<div class='test-result test-info'>ℹ️ Code erreur: " . $e->getCode() . "</div>";
}
echo "</div>";

// 5. Test de la classe Database
echo "<div class='test-section'>";
echo "<h2>🏗️ Test Classe Database</h2>";

try {
    require_once __DIR__ . '/config/db.php';
    echo "<div class='test-result test-success'>✅ Fichier db.php chargé</div>";
    
    $connection = Database::getConnection();
    echo "<div class='test-result test-success'>✅ Database::getConnection() réussie</div>";
    
    // Test d'une requête
    $stmt = $connection->query("SELECT COUNT(*) as count FROM categories");
    $result = $stmt->fetch();
    echo "<div class='test-result test-success'>✅ Requête sur categories: " . $result['count'] . " enregistrements</div>";
    
} catch (Exception $e) {
    echo "<div class='test-result test-error'>❌ Erreur classe Database: " . $e->getMessage() . "</div>";
}
echo "</div>";

// 6. Informations système
echo "<div class='test-section'>";
echo "<h2>💻 Informations Système</h2>";

echo "<div class='test-result test-info'>ℹ️ PHP Version: " . PHP_VERSION . "</div>";
echo "<div class='test-result test-info'>ℹ️ OS: " . PHP_OS . "</div>";
echo "<div class='test-result test-info'>ℹ️ SAPI: " . php_sapi_name() . "</div>";

// Vérifier XAMPP
if (strpos(__DIR__, 'xampp') !== false) {
    echo "<div class='test-result test-info'>ℹ️ XAMPP détecté</div>";
    
    // Vérifier si MySQL est démarré
    $mysql_running = false;
    if (function_exists('exec')) {
        exec('tasklist /FI "IMAGENAME eq mysqld.exe" 2>NUL', $output);
        if (count($output) > 1) {
            $mysql_running = true;
        }
    }
    
    if ($mysql_running) {
        echo "<div class='test-result test-success'>✅ Service MySQL semble démarré</div>";
    } else {
        echo "<div class='test-result test-warning'>⚠️ Vérifiez que MySQL est démarré dans XAMPP</div>";
    }
}
echo "</div>";

// 7. Solutions recommandées
echo "<div class='test-section'>";
echo "<h2>🔧 Solutions Recommandées</h2>";

echo "<div class='test-result test-info'>";
echo "<h3>Si la connexion échoue :</h3>";
echo "<ol>";
echo "<li><strong>Vérifiez XAMPP :</strong> MySQL doit être démarré</li>";
echo "<li><strong>Vérifiez la base :</strong> La base 'scolaria' doit exister</li>";
echo "<li><strong>Vérifiez les credentials :</strong> root / (vide) par défaut</li>";
echo "<li><strong>Vérifiez le port :</strong> 3306 par défaut</li>";
echo "<li><strong>Redémarrez XAMPP :</strong> Parfois nécessaire</li>";
echo "</ol>";
echo "</div>";

echo "<div class='test-result test-warning'>";
echo "<h3>Commandes utiles :</h3>";
echo "<ul>";
echo "<li>Créer la base : <code>CREATE DATABASE scolaria;</code></li>";
echo "<li>Vérifier les bases : <code>SHOW DATABASES;</code></li>";
echo "<li>Vérifier l'utilisateur : <code>SELECT USER();</code></li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-size: 0.9rem;'>";
echo "Test effectué le " . date('d/m/Y à H:i:s') . " - Scolaria Team589";
echo "</p>";
?>