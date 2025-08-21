<?php
/**
 * Script d'initialisation du module Clients
 * Crée les tables et insère les données de test
 * Scolaria - Mama Sophie School Supplies - Team589
 */

echo "<h1>👥 Initialisation du Module Clients - Mama Sophie School Supplies</h1>";
echo "<style>body { font-family: Arial, sans-serif; margin: 20px; } .success { color: green; } .error { color: red; } .info { color: blue; } .warning { color: orange; }</style>";

try {
    require_once __DIR__ . '/config/config.php';
    require_once __DIR__ . '/config/db.php';
    
    $pdo = Database::getConnection();
    
    echo "<h2>📋 Création des Tables</h2>";
    
    // Lire et exécuter le script SQL
    $sqlFile = __DIR__ . '/sql/clients_table.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Diviser le script en requêtes individuelles
        $queries = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($queries as $query) {
            if (!empty($query) && !preg_match('/^--/', $query)) {
                try {
                    $pdo->exec($query);
                    
                    // Identifier le type de requête pour l'affichage
                    if (preg_match('/^CREATE TABLE.*?(\w+)/i', $query, $matches)) {
                        echo "<p class='success'>✅ Table '{$matches[1]}' créée avec succès</p>";
                    } elseif (preg_match('/^INSERT INTO (\w+)/i', $query, $matches)) {
                        echo "<p class='info'>📝 Données insérées dans '{$matches[1]}'</p>";
                    } elseif (preg_match('/^CREATE.*?VIEW (\w+)/i', $query, $matches)) {
                        echo "<p class='success'>👁️ Vue '{$matches[1]}' créée avec succès</p>";
                    }
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'already exists') !== false) {
                        echo "<p class='warning'>⚠️ Élément déjà existant (ignoré)</p>";
                    } else {
                        echo "<p class='error'>❌ Erreur: " . $e->getMessage() . "</p>";
                    }
                }
            }
        }
        
        echo "<p class='success'>✅ Script SQL exécuté avec succès</p>";
    } else {
        echo "<p class='error'>❌ Fichier SQL non trouvé: $sqlFile</p>";
    }
    
    echo "<h2>📊 Vérification des Données</h2>";
    
    // Vérifier les clients
    $stmt = $pdo->query("SELECT COUNT(*) FROM clients");
    $clientsCount = $stmt->fetchColumn();
    echo "<p class='info'>👥 Clients créés: $clientsCount</p>";
    
    // Vérifier les ventes
    $stmt = $pdo->query("SELECT COUNT(*) FROM sales");
    $salesCount = $stmt->fetchColumn();
    echo "<p class='info'>🛒 Ventes de test: $salesCount</p>";
    
    echo "<h2>🎯 Données de Test Créées</h2>";
    
    // Afficher les clients
    echo "<h3>👥 Clients</h3>";
    $stmt = $pdo->query("SELECT first_name, last_name, phone, email, client_type FROM clients ORDER BY first_name");
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Nom</th><th>Téléphone</th><th>Email</th><th>Type</th></tr>";
    foreach ($clients as $client) {
        echo "<tr>";
        echo "<td><strong>" . htmlspecialchars($client['first_name'] . ' ' . $client['last_name']) . "</strong></td>";
        echo "<td>" . htmlspecialchars($client['phone'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($client['email'] ?? '') . "</td>";
        echo "<td>" . htmlspecialchars($client['client_type']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Afficher les statistiques
    echo "<h3>📊 Statistiques</h3>";
    $stmt = $pdo->query("
        SELECT 
            client_type,
            COUNT(*) as count,
            COALESCE(SUM(s.total_amount), 0) as total_sales
        FROM clients c
        LEFT JOIN sales s ON c.id = s.client_id
        GROUP BY client_type
        ORDER BY count DESC
    ");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
    echo "<tr><th>Type de Client</th><th>Nombre</th><th>Chiffre d'Affaires</th></tr>";
    $totalClients = 0;
    $totalRevenue = 0;
    foreach ($stats as $stat) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($stat['client_type']) . "</td>";
        echo "<td style='text-align: center;'>" . $stat['count'] . "</td>";
        echo "<td style='text-align: right;'>" . number_format($stat['total_sales'], 2) . " $</td>";
        echo "</tr>";
        $totalClients += $stat['count'];
        $totalRevenue += $stat['total_sales'];
    }
    echo "<tr style='font-weight: bold; background-color: #f0f0f0;'>";
    echo "<td>TOTAL</td>";
    echo "<td style='text-align: center;'>$totalClients</td>";
    echo "<td style='text-align: right;'>" . number_format($totalRevenue, 2) . " $</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<div style='background: #e3f2fd; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #1976d2; margin-top: 0;'>🎉 Initialisation Terminée !</h3>";
    echo "<p>Le module Clients a été initialisé avec succès. Vous pouvez maintenant :</p>";
    echo "<ul>";
    echo "<li><a href='clients.php' target='_blank'>👥 Accéder au module Clients</a></li>";
    echo "<li>Ajouter de nouveaux clients</li>";
    echo "<li>Consulter l'historique des achats</li>";
    echo "<li>Gérer les différents types de clients</li>";
    echo "<li>Exporter les données clients</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #fff3e0; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #f57c00; margin-top: 0;'>📋 Fonctionnalités Disponibles</h3>";
    echo "<ul>";
    echo "<li><strong>CRUD Complet :</strong> Ajouter, modifier, supprimer des clients</li>";
    echo "<li><strong>Recherche Avancée :</strong> Par nom, téléphone, email en temps réel</li>";
    echo "<li><strong>Filtrage :</strong> Par type de client (parent, élève, acheteur régulier)</li>";
    echo "<li><strong>Historique des Achats :</strong> Suivi complet des transactions</li>";
    echo "<li><strong>Statistiques :</strong> Cartes avec métriques clés</li>";
    echo "<li><strong>Export CSV :</strong> Exportation des données clients</li>";
    echo "<li><strong>Interface Responsive :</strong> Adaptation mobile/desktop</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<div style='background: #f3e5f5; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
    echo "<h3 style='color: #7b1fa2; margin-top: 0;'>🔧 Configuration Technique</h3>";
    echo "<ul>";
    echo "<li><strong>Tables créées :</strong> clients, sales (si nécessaire)</li>";
    echo "<li><strong>Vues créées :</strong> v_clients_stats, v_client_purchase_history</li>";
    echo "<li><strong>Index optimisés :</strong> Sur les noms, téléphone, email, type</li>";
    echo "<li><strong>Contraintes :</strong> Unicité téléphone/email, clés étrangères</li>";
    echo "<li><strong>Données de test :</strong> 10 clients, 13 ventes</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Erreur lors de l'initialisation: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p style='text-align: center; color: #666; font-size: 0.9rem;'>";
echo "Initialisation effectuée le " . date('d/m/Y à H:i:s') . " - Mama Sophie School Supplies - Team589";
echo "</p>";
?>