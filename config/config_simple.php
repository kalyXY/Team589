<?php
/**
 * Configuration simplifiée pour Scolaria
 * Version de base pour résoudre les problèmes de connexion
 */

// Configuration de base de données - XAMPP par défaut
define('DB_HOST', 'localhost');
define('DB_NAME', 'scolaria');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Configuration minimale de l'application
define('APP_NAME', 'Scolaria');
define('APP_VERSION', '1.0');
define('BASE_URL', '/scolaria/');

// Mode debug pour identifier les problèmes
define('APP_ENV', 'dev');
define('DEBUG_MODE', true);

// Fonction de test de connexion
function testDatabaseConnection() {
    try {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        throw new Exception("Connexion échouée: " . $e->getMessage());
    }
}

// Test automatique si appelé directement
if (basename($_SERVER['PHP_SELF']) === 'config_simple.php') {
    echo "<h1>Test Configuration Simplifiée</h1>";
    try {
        $pdo = testDatabaseConnection();
        echo "<p style='color: green;'>✅ Connexion réussie !</p>";
        
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        echo "<p>Tables trouvées: " . implode(', ', $tables) . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erreur: " . $e->getMessage() . "</p>";
        echo "<p>Vérifiez que :</p>";
        echo "<ul>";
        echo "<li>XAMPP est démarré</li>";
        echo "<li>MySQL est actif</li>";
        echo "<li>La base 'scolaria' existe</li>";
        echo "</ul>";
    }
}
?>