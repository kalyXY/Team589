<?php
/**
 * Script de diagnostic de la base de données
 * Scolaria - Team589
 */

echo "<h1>Diagnostic de la Base de Données - Scolaria</h1>";

// Vérifier si les constantes sont définies
echo "<h2>1. Vérification de la configuration</h2>";
$config_checks = [
    'DB_HOST' => defined('DB_HOST') ? DB_HOST : 'Non défini',
    'DB_NAME' => defined('DB_NAME') ? DB_NAME : 'Non défini',
    'DB_USER' => defined('DB_USER') ? DB_USER : 'Non défini',
    'DB_PASS' => defined('DB_PASS') ? (DB_PASS === '' ? '(vide)' : '***') : 'Non défini',
    'DB_CHARSET' => defined('DB_CHARSET') ? DB_CHARSET : 'Non défini'
];

echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'>";
echo "<th>Paramètre</th>";
echo "<th>Valeur</th>";
echo "<th>Statut</th>";
echo "</tr>";

foreach ($config_checks as $param => $value) {
    $status = $value !== 'Non défini' ? "✅ OK" : "❌ Manquant";
    $color = $value !== 'Non défini' ? "green" : "red";
    echo "<tr>";
    echo "<td><strong>$param</strong></td>";
    echo "<td>$value</td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}
echo "</table>";

// Tester la connexion MySQL
echo "<h2>2. Test de connexion MySQL</h2>";

try {
    // Inclure la configuration si elle n'est pas déjà chargée
    if (!defined('DB_HOST')) {
        require_once 'config/config.php';
    }
    
    $dsn = 'mysql:host=' . DB_HOST . ';charset=' . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p style='color: green;'>✅ Connexion MySQL réussie !</p>";
    
    // Lister les bases de données
    $stmt = $pdo->query("SHOW DATABASES");
    $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<h3>Bases de données disponibles :</h3>";
    echo "<ul>";
    foreach ($databases as $db) {
        $status = $db === DB_NAME ? "✅ Base cible" : "";
        echo "<li>$db $status</li>";
    }
    echo "</ul>";
    
    // Vérifier si la base scolaria existe
    if (in_array(DB_NAME, $databases)) {
        echo "<p style='color: green;'>✅ La base de données '" . DB_NAME . "' existe</p>";
        
        // Tester la connexion à la base spécifique
        try {
            $dsn_full = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $pdo_full = new PDO($dsn_full, DB_USER, DB_PASS);
            $pdo_full->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            echo "<p style='color: green;'>✅ Connexion à la base '" . DB_NAME . "' réussie !</p>";
            
            // Lister les tables
            $stmt = $pdo_full->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<h3>Tables dans la base '" . DB_NAME . "' :</h3>";
            if (empty($tables)) {
                echo "<p style='color: orange;'>⚠️ Aucune table trouvée</p>";
            } else {
                echo "<ul>";
                foreach ($tables as $table) {
                    echo "<li>$table</li>";
                }
                echo "</ul>";
            }
            
        } catch (PDOException $e) {
            echo "<p style='color: red;'>❌ Erreur de connexion à la base '" . DB_NAME . "' : " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ La base de données '" . DB_NAME . "' n'existe pas</p>";
        echo "<p><strong>Solution :</strong> Importez le fichier <code>sql/scolaria.sql</code> dans phpMyAdmin</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Erreur de connexion MySQL : " . $e->getMessage() . "</p>";
    
    // Suggestions de résolution
    echo "<h3>Solutions possibles :</h3>";
    echo "<ol>";
    echo "<li><strong>Démarrer MySQL dans XAMPP :</strong> Ouvrez xampp-control.exe et cliquez sur 'Start' à côté de MySQL</li>";
    echo "<li><strong>Vérifier le port :</strong> Assurez-vous que MySQL écoute sur le port 3306</li>";
    echo "<li><strong>Vérifier les identifiants :</strong> Contrôlez DB_USER et DB_PASS dans config/config.php</li>";
    echo "<li><strong>Vérifier l'hôte :</strong> Assurez-vous que DB_HOST est correct (localhost ou 127.0.0.1)</li>";
    echo "</ol>";
}

// Vérifier les services Laragon
echo "<h2>3. Vérification des services Laragon</h2>";
echo "<p><strong>Instructions :</strong></p>";
echo "<ol>";
echo "<li>Ouvrez <strong>Laragon</strong></li>";
echo "<li>Cliquez sur <strong>'Start All'</strong> pour démarrer tous les services</li>";
echo "<li>Vérifiez que MySQL est démarré (icône verte)</li>";
echo "<li>Vérifiez qu'Apache est démarré (icône verte)</li>";
echo "<li>Si MySQL ne démarre pas, vérifiez les logs dans Laragon</li>";
echo "</ol>";

echo "<h2>4. Liens utiles</h2>";
echo "<p><a href='http://localhost/phpmyadmin' target='_blank'>🔗 phpMyAdmin</a></p>";
echo "<p><a href='http://localhost/scolaria/' target='_blank'>🔗 Application Scolaria</a></p>";

echo "<hr>";
echo "<p><strong>Note :</strong> Une fois MySQL démarré, rechargez cette page pour vérifier que tout fonctionne.</p>";
?>
