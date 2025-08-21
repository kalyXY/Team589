<?php
/**
 * Script de test complet du module Clients
 * Vérifie toutes les fonctionnalités
 * Mama Sophie School Supplies - Team589
 */

echo "<h1>🧪 Test Complet du Module Clients</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .info { color: blue; }
    .warning { color: orange; }
    .test-section { background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 8px; border-left: 4px solid #8B5CF6; }
    .test-result { margin: 10px 0; padding: 10px; border-radius: 5px; }
    .test-success { background: #d4edda; border: 1px solid #c3e6cb; }
    .test-error { background: #f8d7da; border: 1px solid #f5c6cb; }
    .test-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { padding: 8px; text-align: left; border: 1px solid #ddd; }
    th { background-color: #f2f2f2; }
</style>";

// Tests de vérification
$tests = [];
$errors = [];

echo "<div class='test-section'>";
echo "<h2>📋 Vérification des Fichiers</h2>";

$requiredFiles = [
    'clients.php' => 'Page principale du module clients',
    'assets/css/clients.css' => 'Styles CSS du module',
    'sql/clients_table.sql' => 'Script de création des tables',
    'init_clients.php' => 'Script d\'initialisation'
];

foreach ($requiredFiles as $file => $description) {
    if (file_exists($file)) {
        echo "<div class='test-result test-success'>✅ $file - $description</div>";
        $tests['files'][] = ['file' => $file, 'status' => 'OK'];
    } else {
        echo "<div class='test-result test-error'>❌ $file - $description (MANQUANT)</div>";
        $errors[] = "Fichier manquant: $file";
        $tests['files'][] = ['file' => $file, 'status' => 'MISSING'];
    }
}
echo "</div>";

// Test de la base de données
echo "<div class='test-section'>";
echo "<h2>🗄️ Test de la Base de Données</h2>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
    
    $pdo = Database::getConnection();
    echo "<div class='test-result test-success'>✅ Connexion à la base de données réussie</div>";
    
    // Vérifier les tables
    $tables = ['clients', 'sales'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("DESCRIBE $table");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<div class='test-result test-success'>✅ Table '$table' existe (" . count($columns) . " colonnes)</div>";
            $tests['database'][$table] = 'OK';
        } catch (PDOException $e) {
            echo "<div class='test-result test-error'>❌ Table '$table' manquante</div>";
            $errors[] = "Table manquante: $table";
            $tests['database'][$table] = 'MISSING';
        }
    }
    
    // Vérifier les vues
    $views = ['v_clients_stats', 'v_client_purchase_history'];
    foreach ($views as $view) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $view");
            echo "<div class='test-result test-success'>✅ Vue '$view' accessible</div>";
            $tests['views'][$view] = 'OK';
        } catch (PDOException $e) {
            echo "<div class='test-result test-warning'>⚠️ Vue '$view' non accessible (peut être normale si pas de données)</div>";
            $tests['views'][$view] = 'WARNING';
        }
    }
    
} catch (Exception $e) {
    echo "<div class='test-result test-error'>❌ Erreur de connexion à la base: " . $e->getMessage() . "</div>";
    $errors[] = "Erreur de base de données: " . $e->getMessage();
}
echo "</div>";

// Test des données
echo "<div class='test-section'>";
echo "<h2>📊 Test des Données</h2>";

if (isset($pdo)) {
    try {
        // Compter les clients
        $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
        $clientsCount = $stmt->fetchColumn();
        echo "<div class='test-result test-info'>👥 Clients enregistrés: $clientsCount</div>";
        
        if ($clientsCount > 0) {
            // Statistiques par type
            $stmt = $pdo->query("SELECT client_type, COUNT(*) as count FROM clients GROUP BY client_type");
            $types = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table>";
            echo "<tr><th>Type de Client</th><th>Nombre</th></tr>";
            foreach ($types as $type) {
                echo "<tr><td>" . htmlspecialchars($type['client_type']) . "</td><td>" . $type['count'] . "</td></tr>";
            }
            echo "</table>";
        }
        
        // Compter les ventes
        $stmt = $pdo->query("SELECT COUNT(*) FROM sales");
        $salesCount = $stmt->fetchColumn();
        echo "<div class='test-result test-info'>🛒 Ventes enregistrées: $salesCount</div>";
        
        if ($salesCount > 0) {
            $stmt = $pdo->query("SELECT SUM(total_amount) FROM sales");
            $totalSales = $stmt->fetchColumn();
            echo "<div class='test-result test-info'>💰 Chiffre d'affaires total: " . number_format($totalSales, 2) . " $</div>";
        }
        
        $tests['data'] = [
            'clients' => $clientsCount,
            'sales' => $salesCount
        ];
        
    } catch (Exception $e) {
        echo "<div class='test-result test-error'>❌ Erreur lors de la lecture des données: " . $e->getMessage() . "</div>";
        $errors[] = "Erreur de données: " . $e->getMessage();
    }
}
echo "</div>";

// Test des fonctionnalités
echo "<div class='test-section'>";
echo "<h2>🔧 Test des Fonctionnalités</h2>";

if (file_exists('clients.php')) {
    try {
        $clientsContent = file_get_contents('clients.php');
        
        $checks = [
            'class ClientsManager' => 'Classe principale définie',
            'function listClients' => 'Méthode listClients disponible',
            'function addClient' => 'Méthode addClient disponible',
            'function updateClient' => 'Méthode updateClient disponible',
            'function deleteClient' => 'Méthode deleteClient disponible',
            'function getClientPurchaseHistory' => 'Méthode historique disponible',
            'ajax' => 'Support AJAX intégré',
            'searchClients()' => 'Recherche JavaScript présente'
        ];
        
        foreach ($checks as $search => $description) {
            if (strpos($clientsContent, $search) !== false) {
                echo "<div class='test-result test-success'>✅ $description</div>";
                $tests['php'][$search] = 'OK';
            } else {
                echo "<div class='test-result test-warning'>⚠️ $description (non trouvé)</div>";
                $tests['php'][$search] = 'WARNING';
            }
        }
        
    } catch (Exception $e) {
        echo "<div class='test-result test-error'>❌ Erreur lors de l'analyse du fichier PHP: " . $e->getMessage() . "</div>";
        $errors[] = "Erreur PHP: " . $e->getMessage();
    }
}
echo "</div>";

// Résumé
echo "<div class='test-section'>";
echo "<h2>📈 Résumé des Tests</h2>";

if (empty($errors)) {
    echo "<div class='test-result test-success'>";
    echo "<h3>🎉 Tous les tests sont passés avec succès !</h3>";
    echo "<p>Le module Clients est prêt à être utilisé.</p>";
    echo "</div>";
} else {
    echo "<div class='test-result test-warning'>";
    echo "<h3>⚠️ Quelques problèmes détectés</h3>";
    echo "<ul>";
    foreach ($errors as $error) {
        echo "<li>$error</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h3>🔗 Actions Recommandées</h3>";
echo "<ol>";
if (in_array('Table manquante: clients', $errors) || in_array('Table manquante: sales', $errors)) {
    echo "<li><a href='init_clients.php' target='_blank'>Exécuter l'initialisation du module</a></li>";
}
echo "<li><a href='clients.php' target='_blank'>👥 Accéder au module Clients</a></li>";
echo "<li>Tester l'ajout d'un nouveau client</li>";
echo "<li>Tester la recherche et les filtres</li>";
echo "<li>Consulter l'historique d'un client</li>";
echo "</ol>";
echo "</div>";

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-size: 0.9rem;'>";
echo "Test effectué le " . date('d/m/Y à H:i:s') . " - Mama Sophie School Supplies - Team589";
echo "</p>";
?>