<?php
/**
 * Script de test pour vérifier les alertes du dashboard
 * Scolaria - Team589
 */

echo "<h1>Test des alertes du dashboard - Scolaria</h1>";

// Configuration de la base de données
require_once 'config/config.php';
require_once 'config/db.php';

try {
    $pdo = Database::getConnection();
    echo "<p style='color: green;'>✅ Connexion à la base de données réussie</p>";
    
    echo "<h2>1. Test des alertes de stock</h2>";
    
    // Vérifier les articles avec stock faible
    $sql_stock = "SELECT id, nom_article, categorie, quantite, seuil, seuil_alerte, updated_at 
                 FROM stocks 
                 WHERE quantite <= seuil_alerte 
                 ORDER BY quantite ASC, nom_article ASC 
                 LIMIT 10";
    
    try {
        $stmt_stock = $pdo->query($sql_stock);
        $stock_alerts = $stmt_stock->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($stock_alerts)) {
            echo "<p style='color: orange;'>⚠️ <strong>" . count($stock_alerts) . " alertes de stock détectées</strong></p>";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>Article</th>";
            echo "<th>Catégorie</th>";
            echo "<th>Quantité</th>";
            echo "<th>Seuil</th>";
            echo "<th>Seuil d'alerte</th>";
            echo "<th>Statut</th>";
            echo "<th>Dernière mise à jour</th>";
            echo "</tr>";
            
            foreach ($stock_alerts as $alert) {
                $quantite = (int)$alert['quantite'];
                $seuil = (int)$alert['seuil'];
                $seuil_alerte = (int)$alert['seuil_alerte'];
                
                if ($quantite <= 0) {
                    $status = 'Rupture de stock';
                    $color = 'red';
                } elseif ($quantite <= $seuil_alerte) {
                    $status = 'Stock critique';
                    $color = 'red';
                } else {
                    $status = 'Stock faible';
                    $color = 'orange';
                }
                
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($alert['nom_article']) . "</strong></td>";
                echo "<td>" . htmlspecialchars($alert['categorie'] ?? 'N/A') . "</td>";
                echo "<td style='color: $color; font-weight: bold;'>" . $alert['quantite'] . "</td>";
                echo "<td>" . $alert['seuil'] . "</td>";
                echo "<td>" . $alert['seuil_alerte'] . "</td>";
                echo "<td style='color: $color;'>" . $status . "</td>";
                echo "<td>" . $alert['updated_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: green;'>✅ Aucune alerte de stock active</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de la récupération des alertes de stock : {$e->getMessage()}</p>";
    }
    
    echo "<h2>2. Test des commandes en attente</h2>";
    
    // Vérifier les commandes en attente
    $sql_commandes = "SELECT c.id, c.quantite, c.date_commande, c.date_livraison_prevue, 
                            s.nom_article, f.nom as fournisseur_nom
                     FROM commandes c
                     JOIN stocks s ON c.article_id = s.id
                     JOIN fournisseurs f ON c.fournisseur_id = f.id
                     WHERE c.statut = 'en attente'
                     ORDER BY c.date_commande ASC
                     LIMIT 10";
    
    try {
        $stmt_commandes = $pdo->query($sql_commandes);
        $commandes_en_attente = $stmt_commandes->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($commandes_en_attente)) {
            echo "<p style='color: blue;'>ℹ️ <strong>" . count($commandes_en_attente) . " commandes en attente détectées</strong></p>";
            
            echo "<table border='1' style='border-collapse: collapse; width: 100%; margin-bottom: 20px;'>";
            echo "<tr style='background: #f0f0f0;'>";
            echo "<th>Article</th>";
            echo "<th>Quantité</th>";
            echo "<th>Fournisseur</th>";
            echo "<th>Date commande</th>";
            echo "<th>Livraison prévue</th>";
            echo "<th>Délai</th>";
            echo "</tr>";
            
            foreach ($commandes_en_attente as $commande) {
                $date_commande = new DateTime($commande['date_commande']);
                $date_livraison = $commande['date_livraison_prevue'] ? new DateTime($commande['date_livraison_prevue']) : null;
                
                $now = new DateTime();
                $delai = $now->diff($date_commande);
                
                if ($delai->days > 7) {
                    $delai_text = "En retard de " . ($delai->days - 7) . " jour(s)";
                    $color = 'red';
                } elseif ($delai->days > 3) {
                    $delai_text = "En attente depuis " . $delai->days . " jour(s)";
                    $color = 'orange';
                } else {
                    $delai_text = "Commande récente";
                    $color = 'green';
                }
                
                echo "<tr>";
                echo "<td><strong>" . htmlspecialchars($commande['nom_article']) . "</strong></td>";
                echo "<td>" . $commande['quantite'] . "</td>";
                echo "<td>" . htmlspecialchars($commande['fournisseur_nom']) . "</td>";
                echo "<td>" . $date_commande->format('d/m/Y H:i') . "</td>";
                echo "<td>" . ($date_livraison ? $date_livraison->format('d/m/Y') : 'Non définie') . "</td>";
                echo "<td style='color: $color;'>" . $delai_text . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: green;'>✅ Aucune commande en attente</p>";
        }
        
    } catch (PDOException $e) {
        echo "<p style='color: red;'>❌ Erreur lors de la récupération des commandes : {$e->getMessage()}</p>";
    }
    
    echo "<h2>3. Test de la logique des alertes</h2>";
    
    // Tester la logique de classification des alertes
    echo "<h3>Classification des niveaux d'alerte :</h3>";
    echo "<ul>";
    echo "<li><strong>Rupture de stock</strong> : Quantité = 0 (alerte rouge)</li>";
    echo "<li><strong>Stock critique</strong> : Quantité ≤ seuil d'alerte (alerte rouge)</li>";
    echo "<li><strong>Stock faible</strong> : Quantité ≤ seuil normal (alerte orange)</li>";
    echo "</ul>";
    
    echo "<h3>Classification des délais de commande :</h3>";
    echo "<ul>";
    echo "<li><strong>Commande en retard</strong> : > 7 jours (alerte rouge)</li>";
    echo "<li><strong>Commande en attente</strong> : 3-7 jours (alerte orange)</li>";
    echo "<li><strong>Commande récente</strong> : < 3 jours (alerte verte)</li>";
    echo "</ul>";
    
    echo "<h2>4. Résumé</h2>";
    
    $total_alertes = count($stock_alerts ?? []) + count($commandes_en_attente ?? []);
    
    if ($total_alertes > 0) {
        echo "<p style='color: orange; font-weight: bold;'>⚠️ <strong>$total_alertes alertes actives</strong> nécessitent votre attention</p>";
        echo "<p><strong>Actions recommandées :</strong></p>";
        echo "<ol>";
        echo "<li>Vérifier les stocks critiques et commander en urgence</li>";
        echo "<li>Suivre les commandes en retard</li>";
        echo "<li>Mettre à jour les seuils d'alerte si nécessaire</li>";
        echo "</ol>";
    } else {
        echo "<p style='color: green; font-weight: bold;'>🎉 <strong>Aucune alerte active</strong> - Tous vos stocks sont dans les normes !</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Erreur de connexion à la base de données :</p>";
    echo "<p style='color: red;'>{$e->getMessage()}</p>";
}

echo "<hr>";
echo "<p><strong>Fichiers disponibles :</strong></p>";
echo "<ul>";
echo "<li><a href='dashboard.php'>📊 Dashboard principal</a></li>";
echo "<li><a href='stocks.php'>📦 Gestion des stocks</a></li>";
echo "<li><a href='commandes.php'>🛒 Commandes fournisseurs</a></li>";
echo "<li><a href='alerts.php'>🚨 Gestion des alertes</a></li>";
echo "</ul>";
?>
