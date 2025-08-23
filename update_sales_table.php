<?php
/**
 * Script de mise à jour automatique de la table sales
 * Scolaria - Team589
 */

echo "<h1>Mise à jour automatique de la table sales - Scolaria</h1>";

// Configuration de la base de données
require_once 'config/config.php';
require_once 'config/db.php';

try {
    $pdo = Database::getConnection();
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
    
    echo "<h2>1. Vérification de la structure actuelle</h2>";
    
    // Vérifier la structure actuelle
    $stmt = $pdo->query("DESCRIBE `sales`");
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
    
    $has_auto_increment = false;
    $has_primary_key = false;
    
    foreach ($columns as $column) {
        $is_auto_increment = strpos($column['Extra'], 'auto_increment') !== false;
        $is_primary_key = $column['Key'] === 'PRI';
        
        if ($is_auto_increment) $has_auto_increment = true;
        if ($is_primary_key) $has_primary_key = true;
        
        $color = ($is_auto_increment || $is_primary_key) ? 'green' : 'black';
        
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
    
    echo "<h2>2. État actuel de la table</h2>";
    echo "<ul>";
    echo "<li><strong>AUTO_INCREMENT :</strong> " . ($has_auto_increment ? "✅ Présent" : "❌ Manquant") . "</li>";
    echo "<li><strong>PRIMARY KEY :</strong> " . ($has_primary_key ? "✅ Présent" : "❌ Manquant") . "</li>";
    echo "</ul>";
    
    if ($has_auto_increment && $has_primary_key) {
        echo "<p style='color: green; font-weight: bold;'>✅ La table sales est déjà correctement configurée !</p>";
        echo "<p>Aucune mise à jour nécessaire.</p>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ La table sales nécessite des corrections.</p>";
        
        echo "<h2>3. Application des corrections</h2>";
        
        try {
            // CORRECTION : D'abord ajouter PRIMARY KEY, puis AUTO_INCREMENT
            
            // 1. Ajouter PRIMARY KEY si manquant
            if (!$has_primary_key) {
                echo "<p>🔄 Ajout de PRIMARY KEY sur le champ id...</p>";
                $pdo->exec("ALTER TABLE `sales` ADD PRIMARY KEY (`id`)");
                echo "<p style='color: green;'>✅ PRIMARY KEY ajouté avec succès</p>";
                
                // Mettre à jour le statut
                $has_primary_key = true;
            }
            
            // 2. Maintenant ajouter AUTO_INCREMENT
            if (!$has_auto_increment) {
                echo "<p>🔄 Ajout de AUTO_INCREMENT au champ id...</p>";
                $pdo->exec("ALTER TABLE `sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT");
                echo "<p style='color: green;'>✅ AUTO_INCREMENT ajouté avec succès</p>";
                
                // Mettre à jour le statut
                $has_auto_increment = true;
            }
            
            echo "<h2>4. Vérification de la correction</h2>";
            
            // Vérifier la structure finale
            $stmt = $pdo->query("DESCRIBE `sales`");
            $columns_final = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Structure finale de la table sales :</strong></p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>Champ</th>";
            echo "<th>Type</th>";
            echo "<th>Null</th>";
            echo "<th>Clé</th>";
            echo "<th>Défaut</th>";
            echo "<th>Extra</th>";
            echo "</tr>";
            
            foreach ($columns_final as $column) {
                $color = ($column['Key'] === 'PRI' || strpos($column['Extra'], 'auto_increment') !== false) ? 'green' : 'black';
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
            
            echo "<h2>5. Test de la correction</h2>";
            
            // Test d'insertion
            echo "<p>🧪 Test d'insertion d'une vente...</p>";
            $stmt = $pdo->prepare("INSERT INTO `sales` (`client_id`, `total`) VALUES (?, ?)");
            $stmt->execute([1, 25.50]);
            
            $sale_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Vente insérée avec succès ! ID généré : $sale_id</p>";
            
            // Vérifier l'insertion
            $stmt = $pdo->prepare("SELECT * FROM `sales` WHERE `id` = ?");
            $stmt->execute([$sale_id]);
            $sale = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Détails de la vente test :</strong></p>";
            echo "<ul>";
            echo "<li><strong>ID :</strong> {$sale['id']}</li>";
            echo "<li><strong>Client ID :</strong> {$sale['client_id']}</li>";
            echo "<li><strong>Total :</strong> {$sale['total']} $</li>";
            echo "<li><strong>Date :</strong> {$sale['created_at']}</li>";
            echo "</ul>";
            
            // Nettoyer la vente de test
            echo "<p>🧹 Suppression de la vente de test...</p>";
            $pdo->exec("DELETE FROM `sales` WHERE `id` = $sale_id");
            echo "<p style='color: green;'>✅ Vente de test supprimée</p>";
            
            echo "<h2>6. Résumé</h2>";
            echo "<p style='color: green; font-weight: bold;'>🎉 Mise à jour terminée avec succès !</p>";
            echo "<p>La table sales est maintenant correctement configurée avec :</p>";
            echo "<ul>";
            echo "<li>✅ AUTO_INCREMENT sur le champ id</li>";
            echo "<li>✅ PRIMARY KEY sur le champ id</li>";
            echo "<li>✅ Possibilité de vendre des articles sans erreur</li>";
            echo "</ul>";
            
            echo "<p><strong>🎯 Problème résolu :</strong> L'erreur 'Field id doesn\'t have a default value' ne devrait plus apparaître !</p>";
            
        } catch (PDOException $e) {
            echo "<p style='color: red; font-weight: bold;'>❌ Erreur lors de la mise à jour :</p>";
            echo "<p style='color: red;'>{$e->getMessage()}</p>";
            
            echo "<h3>🔧 Solution manuelle</h3>";
            echo "<p>Si l'erreur persiste, exécutez manuellement ces commandes SQL dans l'ordre :</p>";
            echo "<ol>";
            echo "<li><code>ALTER TABLE `sales` ADD PRIMARY KEY (`id`);</code></li>";
            echo "<li><code>ALTER TABLE `sales` MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;</code></li>";
            echo "</ol>";
        }
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Erreur de connexion à la base de données :</p>";
    echo "<p style='color: red;'>{$e->getMessage()}</p>";
    echo "<p><strong>Vérifiez que :</strong></p>";
    echo "<ul>";
    echo "<li>MySQL est démarré</li>";
    echo "<li>La base de données 'scolaria' existe</li>";
    echo "<li>Les paramètres de connexion sont corrects</li>";
    echo "</ul>";
}

echo "<hr>";
echo "<p><strong>Fichiers disponibles :</strong></p>";
echo "<ul>";
echo "<li><a href='update_sales_table.sql'>📄 Script SQL manuel</a></li>";
echo "<li><a href='test_sales_fix.php'>🔍 Vérifier la correction</a></li>";
echo "<li><a href='clean_database_test.php'>🧹 Vérifier le nettoyage</a></li>";
echo "</ul>";
?>
