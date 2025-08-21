<?php
/**
 * Classe Database simplifiée pour résoudre les problèmes de connexion
 * Version de debug pour Scolaria
 */

class DatabaseSimple
{
    private static ?PDO $connection = null;
    
    public static function getConnection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }
        
        // Configuration par défaut XAMPP
        $host = 'localhost';
        $dbname = 'scolaria';
        $username = 'root';
        $password = '';
        $charset = 'utf8mb4';
        
        $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
        
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            self::$connection = new PDO($dsn, $username, $password, $options);
            return self::$connection;
        } catch (PDOException $e) {
            // Debug détaillé
            $error_details = [
                'Message' => $e->getMessage(),
                'Code' => $e->getCode(),
                'DSN' => $dsn,
                'Username' => $username,
                'Password' => empty($password) ? '(vide)' : '(défini)'
            ];
            
            error_log('Erreur connexion DB: ' . print_r($error_details, true));
            
            throw new RuntimeException(
                'Connexion base de données échouée. ' .
                'Vérifiez que XAMPP/MySQL est démarré et que la base "scolaria" existe. ' .
                'Erreur: ' . $e->getMessage()
            );
        }
    }
    
    public static function testConnection(): array
    {
        $results = [];
        
        try {
            // Test 1: Connexion sans base
            $dsn_no_db = "mysql:host=localhost;charset=utf8mb4";
            $pdo_no_db = new PDO($dsn_no_db, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            $results['connection_server'] = 'OK';
            
            // Test 2: Vérifier si la base existe
            $stmt = $pdo_no_db->query("SHOW DATABASES LIKE 'scolaria'");
            $db_exists = $stmt->rowCount() > 0;
            $results['database_exists'] = $db_exists ? 'OK' : 'MISSING';
            
            if (!$db_exists) {
                $results['create_database'] = "Exécutez: CREATE DATABASE scolaria;";
            }
            
            // Test 3: Connexion avec base
            if ($db_exists) {
                $pdo_with_db = self::getConnection();
                $results['connection_database'] = 'OK';
                
                // Test 4: Vérifier les tables
                $stmt = $pdo_with_db->query("SHOW TABLES");
                $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
                $results['tables'] = $tables;
                
                $required_tables = ['categories', 'depenses', 'budgets'];
                $missing_tables = array_diff($required_tables, $tables);
                $results['missing_tables'] = $missing_tables;
            }
            
        } catch (Exception $e) {
            $results['error'] = $e->getMessage();
        }
        
        return $results;
    }
}

// Test si appelé directement
if (basename($_SERVER['PHP_SELF']) === 'db_simple.php') {
    echo "<h1>Test Connexion Database Simple</h1>";
    echo "<style>body{font-family:Arial;margin:20px;} .ok{color:green;} .error{color:red;} .warning{color:orange;}</style>";
    
    $results = DatabaseSimple::testConnection();
    
    foreach ($results as $test => $result) {
        echo "<p><strong>$test:</strong> ";
        if ($result === 'OK') {
            echo "<span class='ok'>✅ $result</span>";
        } elseif (is_array($result)) {
            echo implode(', ', $result);
        } elseif (strpos($test, 'error') !== false) {
            echo "<span class='error'>❌ $result</span>";
        } elseif ($result === 'MISSING') {
            echo "<span class='warning'>⚠️ $result</span>";
        } else {
            echo $result;
        }
        echo "</p>";
    }
}
?>