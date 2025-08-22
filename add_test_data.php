<?php
/**
 * Script pour ajouter des données de test si les tables sont vides
 * Scolaria - Team589
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

echo "<h1>📊 Ajout de données de test - Scolaria</h1>";

try {
    $pdo = Database::getConnection();
    echo "<div style='color: green;'>✅ Connexion à la base de données réussie</div>";
} catch (Exception $e) {
    echo "<div style='color: red;'>❌ Erreur de connexion: " . $e->getMessage() . "</div>";
    exit;
}

// Fonction pour vérifier si une table est vide
function isTableEmpty($pdo, $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
        return (int) $stmt->fetchColumn() === 0;
    } catch (Exception $e) {
        return false; // Table n'existe pas
    }
}

// 1. Ajouter des utilisateurs de test
echo "<h2>1. Utilisateurs</h2>";
if (isTableEmpty($pdo, 'users')) {
    try {
        $users = [
            ['username' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'role' => 'admin', 'full_name' => 'Administrateur Principal', 'email' => 'admin@scolaria.com', 'phone' => '+1234567890', 'status' => 'actif'],
            ['username' => 'directeur', 'password' => password_hash('directeur123', PASSWORD_DEFAULT), 'role' => 'directeur', 'full_name' => 'Directeur École', 'email' => 'directeur@scolaria.com', 'phone' => '+1234567891', 'status' => 'actif'],
            ['username' => 'caissier', 'password' => password_hash('caissier123', PASSWORD_DEFAULT), 'role' => 'caissier', 'full_name' => 'Caissier Principal', 'email' => 'caissier@scolaria.com', 'phone' => '+1234567892', 'status' => 'actif'],
            ['username' => 'gestionnaire', 'password' => password_hash('gestionnaire123', PASSWORD_DEFAULT), 'role' => 'gestionnaire', 'full_name' => 'Gestionnaire Stock', 'email' => 'gestionnaire@scolaria.com', 'phone' => '+1234567893', 'status' => 'actif']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($users as $user) {
            $stmt->execute([$user['username'], $user['password'], $user['role'], $user['full_name'], $user['email'], $user['phone'], $user['status']]);
        }
        
        echo "<div style='color: green;'>✅ 4 utilisateurs de test ajoutés</div>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur ajout utilisateurs: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: blue;'>ℹ️ Table users contient déjà des données</div>";
}

// 2. Ajouter des articles de test
echo "<h2>2. Articles de stock</h2>";
if (isTableEmpty($pdo, 'stocks')) {
    try {
        $articles = [
            ['nom_article' => 'Cahiers 96 pages', 'categorie' => 'Fournitures', 'quantite' => 150, 'seuil' => 20, 'prix_achat' => 2.50, 'prix_vente' => 3.50],
            ['nom_article' => 'Stylos bleus', 'categorie' => 'Fournitures', 'quantite' => 200, 'seuil' => 30, 'prix_achat' => 0.80, 'prix_vente' => 1.20],
            ['nom_article' => 'Règles 30cm', 'categorie' => 'Géométrie', 'quantite' => 75, 'seuil' => 15, 'prix_achat' => 1.20, 'prix_vente' => 1.80],
            ['nom_article' => 'Compas', 'categorie' => 'Géométrie', 'quantite' => 45, 'seuil' => 10, 'prix_achat' => 3.50, 'prix_vente' => 5.00],
            ['nom_article' => 'Calculatrices', 'categorie' => 'Électronique', 'quantite' => 25, 'seuil' => 5, 'prix_achat' => 15.00, 'prix_vente' => 22.00],
            ['nom_article' => 'Trousse scolaire', 'categorie' => 'Accessoires', 'quantite' => 60, 'seuil' => 12, 'prix_achat' => 4.50, 'prix_vente' => 6.50],
            ['nom_article' => 'Gommes', 'categorie' => 'Fournitures', 'quantite' => 8, 'seuil' => 20, 'prix_achat' => 0.30, 'prix_vente' => 0.50],
            ['nom_article' => 'Crayons HB', 'categorie' => 'Fournitures', 'quantite' => 5, 'seuil' => 25, 'prix_achat' => 0.25, 'prix_vente' => 0.40]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO stocks (nom_article, categorie, quantite, seuil, prix_achat, prix_vente, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        
        foreach ($articles as $article) {
            $stmt->execute([$article['nom_article'], $article['categorie'], $article['quantite'], $article['seuil'], $article['prix_achat'], $article['prix_vente']]);
        }
        
        echo "<div style='color: green;'>✅ 8 articles de test ajoutés (dont 2 en alerte)</div>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur ajout articles: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: blue;'>ℹ️ Table stocks contient déjà des données</div>";
}

// 3. Ajouter des clients de test
echo "<h2>3. Clients</h2>";
if (isTableEmpty($pdo, 'clients')) {
    try {
        $clients = [
            ['first_name' => 'Jean', 'last_name' => 'Dupont', 'phone' => '+1234567890', 'email' => 'jean.dupont@email.com'],
            ['first_name' => 'Marie', 'last_name' => 'Martin', 'phone' => '+1234567891', 'email' => 'marie.martin@email.com'],
            ['first_name' => 'Pierre', 'last_name' => 'Bernard', 'phone' => '+1234567892', 'email' => 'pierre.bernard@email.com'],
            ['first_name' => 'Sophie', 'last_name' => 'Petit', 'phone' => '+1234567893', 'email' => 'sophie.petit@email.com'],
            ['first_name' => 'Lucas', 'last_name' => 'Robert', 'phone' => '+1234567894', 'email' => 'lucas.robert@email.com']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO clients (first_name, last_name, phone, email, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($clients as $client) {
            $stmt->execute([$client['first_name'], $client['last_name'], $client['phone'], $client['email']]);
        }
        
        echo "<div style='color: green;'>✅ 5 clients de test ajoutés</div>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur ajout clients: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: blue;'>ℹ️ Table clients contient déjà des données</div>";
}

// 4. Ajouter des dépenses de test
echo "<h2>4. Dépenses</h2>";
if (isTableEmpty($pdo, 'depenses')) {
    try {
        $depenses = [
            ['description' => 'Achat fournitures scolaires', 'montant' => 1250.00, 'date' => date('Y-m-d', strtotime('-30 days')), 'fournisseur' => 'Fournitures Pro'],
            ['description' => 'Maintenance équipements', 'montant' => 450.00, 'date' => date('Y-m-d', strtotime('-15 days')), 'fournisseur' => 'Tech Services'],
            ['description' => 'Achat calculatrices', 'montant' => 800.00, 'date' => date('Y-m-d', strtotime('-7 days')), 'fournisseur' => 'Électronique Plus'],
            ['description' => 'Frais de transport', 'montant' => 120.00, 'date' => date('Y-m-d', strtotime('-3 days')), 'fournisseur' => 'Transport Express'],
            ['description' => 'Achat papeterie', 'montant' => 300.00, 'date' => date('Y-m-d'), 'fournisseur' => 'Papeterie Centrale']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO depenses (description, montant, date, fournisseur, created_at) VALUES (?, ?, ?, ?, NOW())");
        
        foreach ($depenses as $depense) {
            $stmt->execute([$depense['description'], $depense['montant'], $depense['date'], $depense['fournisseur']]);
        }
        
        echo "<div style='color: green;'>✅ 5 dépenses de test ajoutées</div>";
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur ajout dépenses: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: blue;'>ℹ️ Table depenses contient déjà des données</div>";
}

// 5. Ajouter des ventes de test
echo "<h2>5. Ventes</h2>";
if (isTableEmpty($pdo, 'sales')) {
    try {
        // Récupérer les IDs des clients et articles
        $clientIds = $pdo->query("SELECT id FROM clients LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        $articleIds = $pdo->query("SELECT id FROM stocks LIMIT 5")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($clientIds) || empty($articleIds)) {
            echo "<div style='color: orange;'>⚠️ Impossible d'ajouter des ventes: clients ou articles manquants</div>";
        } else {
            $ventes = [
                ['client_id' => $clientIds[0], 'total' => 15.50, 'created_at' => date('Y-m-d H:i:s', strtotime('-2 hours'))],
                ['client_id' => $clientIds[1], 'total' => 8.20, 'created_at' => date('Y-m-d H:i:s', strtotime('-1 hour'))],
                ['client_id' => $clientIds[2], 'total' => 22.00, 'created_at' => date('Y-m-d H:i:s', strtotime('-30 minutes'))],
                ['client_id' => $clientIds[3], 'total' => 12.80, 'created_at' => date('Y-m-d H:i:s', strtotime('-15 minutes'))],
                ['client_id' => $clientIds[4], 'total' => 18.50, 'created_at' => date('Y-m-d H:i:s')]
            ];
            
            $stmt = $pdo->prepare("INSERT INTO sales (client_id, total, created_at) VALUES (?, ?, ?)");
            
            foreach ($ventes as $vente) {
                $stmt->execute([$vente['client_id'], $vente['total'], $vente['created_at']]);
            }
            
            echo "<div style='color: green;'>✅ 5 ventes de test ajoutées</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur ajout ventes: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: blue;'>ℹ️ Table sales contient déjà des données</div>";
}

// 6. Ajouter des mouvements de test
echo "<h2>6. Mouvements de stock</h2>";
if (isTableEmpty($pdo, 'mouvements')) {
    try {
        $articleIds = $pdo->query("SELECT id FROM stocks LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($articleIds)) {
            echo "<div style='color: orange;'>⚠️ Impossible d'ajouter des mouvements: articles manquants</div>";
        } else {
            $mouvements = [
                ['article_id' => $articleIds[0], 'action' => 'ajout', 'details' => 'Réception commande initiale', 'utilisateur' => 'admin'],
                ['article_id' => $articleIds[1], 'action' => 'ajout', 'details' => 'Réception commande initiale', 'utilisateur' => 'admin'],
                ['article_id' => $articleIds[2], 'action' => 'ajout', 'details' => 'Réception commande initiale', 'utilisateur' => 'admin'],
                ['article_id' => $articleIds[0], 'action' => 'modification', 'details' => 'Ajustement stock après inventaire', 'utilisateur' => 'gestionnaire'],
                ['article_id' => $articleIds[1], 'action' => 'modification', 'details' => 'Correction quantité', 'utilisateur' => 'gestionnaire']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO mouvements (article_id, action, details, utilisateur, date_mouvement) VALUES (?, ?, ?, ?, NOW())");
            
            foreach ($mouvements as $mouvement) {
                $stmt->execute([$mouvement['article_id'], $mouvement['action'], $mouvement['details'], $mouvement['utilisateur']]);
            }
            
            echo "<div style='color: green;'>✅ 5 mouvements de test ajoutés</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color: red;'>❌ Erreur ajout mouvements: " . $e->getMessage() . "</div>";
    }
} else {
    echo "<div style='color: blue;'>ℹ️ Table mouvements contient déjà des données</div>";
}

echo "<h2>✅ Ajout de données terminé</h2>";
echo "<p><strong>Données ajoutées:</strong></p>";
echo "<ul>";
echo "<li>Utilisateurs: admin/admin123, directeur/directeur123, caissier/caissier123, gestionnaire/gestionnaire123</li>";
echo "<li>Articles de stock avec alertes (gommes et crayons en stock faible)</li>";
echo "<li>Clients de test</li>";
echo "<li>Dépenses récentes</li>";
echo "<li>Ventes du jour</li>";
echo "<li>Mouvements de stock</li>";
echo "</ul>";
echo "<p><strong>Prochaines étapes:</strong></p>";
echo "<ul>";
echo "<li>Connectez-vous avec l'un des comptes utilisateur</li>";
echo "<li>Vérifiez que les statistiques du dashboard affichent les vraies données</li>";
echo "<li>Testez les fonctionnalités principales</li>";
echo "</ul>";
?>
