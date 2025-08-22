<?php
/**
 * Script pour créer les tables manquantes dans la base de données Scolaria
 * Résout l'erreur "Table 'scolaria.sales' doesn't exist"
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

try {
    $pdo = Database::getConnection();
    
    echo "<h2>Création des tables manquantes</h2>\n";
    echo "<pre>\n";
    
    // 1. Table sales
    echo "1. Création de la table sales...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sales` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `client_id` int(11) DEFAULT NULL,
        `total` decimal(10,2) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table sales créée\n";
    
    // 2. Table sales_items
    echo "\n2. Création de la table sales_items...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `sales_items` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sale_id` int(11) NOT NULL,
        `product_id` int(11) NOT NULL,
        `quantity` int(11) NOT NULL,
        `price` decimal(10,2) NOT NULL,
        PRIMARY KEY (`id`),
        KEY `sale_id` (`sale_id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table sales_items créée\n";
    
    // 3. Table transactions
    echo "\n3. Création de la table transactions...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `transactions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sale_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `payment_method` enum('cash','mobile_money','card','transfer') NOT NULL,
        `reference` varchar(100) DEFAULT NULL,
        `paid_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `sale_id` (`sale_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table transactions créée\n";
    
    // 4. Table alertes
    echo "\n4. Création de la table alertes...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `alertes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `stock_id` int(11) NOT NULL,
        `type` enum('low_stock','out_of_stock') NOT NULL,
        `message` varchar(255) NOT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`),
        KEY `stock_id` (`stock_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "✓ Table alertes créée\n";
    
    // 5. Vérification des tables existantes
    echo "\n5. Vérification des tables...\n";
    $tables = ['sales', 'sales_items', 'transactions', 'alertes'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "✓ Table $table existe\n";
        } else {
            echo "❌ Table $table manquante\n";
        }
    }
    
    // 6. Ajout de données de test pour sales si la table est vide
    echo "\n6. Ajout de données de test...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sales");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "Ajout de données de test dans sales...\n";
        $pdo->exec("INSERT INTO `sales` (`client_id`, `total`, `created_at`) VALUES
            (1, 12.50, '2025-01-15 08:30:00'),
            (1, 7.50, '2025-01-15 08:35:00'),
            (1, 12.50, '2025-01-15 08:30:00'),
            (1, 7.50, '2025-01-15 08:35:00')");
        echo "✓ Données de test ajoutées\n";
    } else {
        echo "✓ Données déjà présentes dans sales ($count enregistrements)\n";
    }
    
    // 7. Ajout de données de test pour sales_items si la table est vide
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM sales_items");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count == 0) {
        echo "Ajout de données de test dans sales_items...\n";
        $pdo->exec("INSERT INTO `sales_items` (`sale_id`, `product_id`, `quantity`, `price`) VALUES
            (2, 1, 10, 0.75)");
        echo "✓ Données de test ajoutées\n";
    } else {
        echo "✓ Données déjà présentes dans sales_items ($count enregistrements)\n";
    }
    
    echo "\n✅ Toutes les tables manquantes ont été créées avec succès!\n";
    echo "Le dashboard caissier devrait maintenant fonctionner correctement.\n";
    
} catch (Exception $e) {
    echo "\n❌ Erreur lors de la création des tables: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='dashboard_caissier.php'>← Retour au dashboard caissier</a></p>\n";
echo "<p><a href='index.php'>← Retour à l'accueil</a></p>\n";
?>
