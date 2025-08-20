<?php
/**
 * Script de test de connexion - Module Gestion des Stocks Scolaria
 * Team589 - Vérification de l'installation et de la configuration
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Test de Configuration - Scolaria Stocks</h1>";
echo "<p><strong>Team589</strong> - Module de gestion de logistique scolaire</p>";
echo "<hr>";

// Test 1: Vérification de PHP
echo "<h2>✅ Test PHP</h2>";
echo "<p>Version PHP : " . PHP_VERSION . "</p>";
echo "<p>Extensions requises :</p>";
echo "<ul>";
echo "<li>PDO : " . (extension_loaded('pdo') ? '✅ Activée' : '❌ Manquante') . "</li>";
echo "<li>PDO MySQL : " . (extension_loaded('pdo_mysql') ? '✅ Activée' : '❌ Manquante') . "</li>";
echo "<li>Session : " . (extension_loaded('session') ? '✅ Activée' : '❌ Manquante') . "</li>";
echo "</ul>";

// Test 2: Vérification des fichiers
echo "<h2>📁 Test des Fichiers</h2>";
$required_files = [
    'stocks.php' => 'Fichier principal',
    'stocks.css' => 'Feuille de styles',
    'database.sql' => 'Script SQL',
    'config.php' => 'Configuration'
];

echo "<ul>";
foreach ($required_files as $file => $description) {
    $exists = file_exists($file);
    echo "<li>$description ($file) : " . ($exists ? '✅ Présent' : '❌ Manquant') . "</li>";
}
echo "</ul>";

// Test 3: Vérification de la base de données
echo "<h2>🗄️ Test de la Base de Données</h2>";

try {
    // Configuration de la base de données
    $host = "localhost";
    $db_name = "scolaria";
    $username = "root";
    $password = "";
    
    echo "<p>Configuration :</p>";
    echo "<ul>";
    echo "<li>Hôte : $host</li>";
    echo "<li>Base de données : $db_name</li>";
    echo "<li>Utilisateur : $username</li>";
    echo "</ul>";
    
    // Test de connexion
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<p>✅ <strong>Connexion à la base de données réussie !</strong></p>";
    
    // Test des tables
    echo "<h3>Vérification des tables :</h3>";
    $tables = ['stocks', 'mouvements'];
    
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<p>✅ Table '$table' : " . count($columns) . " colonnes</p>";
            
            // Compter les enregistrements
            $count_stmt = $pdo->query("SELECT COUNT(*) as total FROM $table");
            $count = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            echo "<p>&nbsp;&nbsp;&nbsp;📊 $count enregistrement(s)</p>";
            
        } catch (PDOException $e) {
            echo "<p>❌ Table '$table' : Erreur - " . $e->getMessage() . "</p>";
        }
    }
    
    // Test des données d'exemple
    echo "<h3>Données d'exemple :</h3>";
    try {
        $stmt = $pdo->query("SELECT nom_article, categorie, quantite, seuil FROM stocks LIMIT 5");
        $stocks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($stocks) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>Article</th><th>Catégorie</th><th>Quantité</th><th>Seuil</th>";
            echo "</tr>";
            
            foreach ($stocks as $stock) {
                $low_stock = $stock['quantite'] <= $stock['seuil'];
                $row_style = $low_stock ? 'background-color: #ffe6e6;' : '';
                echo "<tr style='$row_style'>";
                echo "<td>" . htmlspecialchars($stock['nom_article']) . "</td>";
                echo "<td>" . htmlspecialchars($stock['categorie']) . "</td>";
                echo "<td>" . $stock['quantite'] . ($low_stock ? ' ⚠️' : '') . "</td>";
                echo "<td>" . $stock['seuil'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>⚠️ Aucune donnée trouvée. Exécutez le script database.sql pour insérer les données d'exemple.</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p>❌ Erreur lors de la récupération des données : " . $e->getMessage() . "</p>";
    }
    
} catch (PDOException $e) {
    echo "<p>❌ <strong>Erreur de connexion à la base de données :</strong></p>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p><strong>Solutions possibles :</strong></p>";
    echo "<ul>";
    echo "<li>Vérifiez que MySQL est démarré dans XAMPP</li>";
    echo "<li>Vérifiez que la base de données '$db_name' existe</li>";
    echo "<li>Exécutez le script database.sql dans phpMyAdmin</li>";
    echo "<li>Vérifiez les identifiants de connexion</li>";
    echo "</ul>";
}

// Test 4: Vérification des permissions
echo "<h2>🔒 Test des Permissions</h2>";
echo "<ul>";
echo "<li>Lecture des fichiers : " . (is_readable('stocks.php') ? '✅ OK' : '❌ Problème') . "</li>";
echo "<li>Écriture dans le répertoire : " . (is_writable('.') ? '✅ OK' : '❌ Problème') . "</li>";
echo "</ul>";

// Test 5: Configuration du serveur web
echo "<h2>🌐 Configuration Serveur</h2>";
echo "<ul>";
echo "<li>Document Root : " . $_SERVER['DOCUMENT_ROOT'] . "</li>";
echo "<li>Script actuel : " . $_SERVER['SCRIPT_NAME'] . "</li>";
echo "<li>URL d'accès : http://" . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']) . "/stocks.php</li>";
echo "</ul>";

// Résumé final
echo "<hr>";
echo "<h2>📋 Résumé</h2>";

$all_ok = extension_loaded('pdo') && 
          extension_loaded('pdo_mysql') && 
          file_exists('stocks.php') && 
          file_exists('stocks.css');

if ($all_ok) {
    echo "<p style='color: green; font-size: 18px; font-weight: bold;'>✅ Installation OK - Vous pouvez utiliser l'application !</p>";
    echo "<p><a href='stocks.php' style='background: #2563eb; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Accéder à l'application</a></p>";
} else {
    echo "<p style='color: red; font-size: 18px; font-weight: bold;'>❌ Installation incomplète - Corrigez les erreurs ci-dessus</p>";
}

echo "<hr>";
echo "<p><em>Test effectué le " . date('d/m/Y à H:i:s') . "</em></p>";
echo "<p><strong>Team589</strong> - Module Scolaria Gestion des Stocks v1.0</p>";
?>

<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    line-height: 1.6;
    max-width: 1000px;
    margin: 0 auto;
    padding: 20px;
    background-color: #f9fafb;
}

h1, h2, h3 {
    color: #2563eb;
}

h1 {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    color: white;
    padding: 20px;
    border-radius: 8px;
    text-align: center;
    margin-bottom: 10px;
}

ul {
    background: white;
    padding: 15px 30px;
    border-radius: 5px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

table {
    background: white;
    border-radius: 5px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

p {
    margin: 10px 0;
}

hr {
    border: none;
    height: 2px;
    background: linear-gradient(to right, #2563eb, transparent);
    margin: 30px 0;
}
</style>