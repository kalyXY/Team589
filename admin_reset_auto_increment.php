<?php
/**
 * Script d'administration pour réinitialiser les séquences d'auto-incrémentation
 * Scolaria - Team589
 */

// Inclure la configuration
require_once 'config/config.php';
require_once 'config/db.php';

// Vérifier les permissions d'administrateur
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';
$tables = [];

// Traitement de la réinitialisation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_auto_increment'])) {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Liste des tables avec auto-increment
        $autoIncrementTables = [
            'categories',
            'notifications', 
            'alertes',
            'budgets',
            'clients',
            'commandes',
            'depenses',
            'fournisseurs',
            'login_history',
            'mouvements',
            'roles_custom',
            'sales',
            'sales_items',
            'stocks',
            'transactions',
            'users'
        ];
        
        $resetCount = 0;
        
        foreach ($autoIncrementTables as $table) {
            // Vérifier si la table existe
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            
            if ($stmt->rowCount() > 0) {
                // Obtenir le nombre de lignes dans la table
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
                $stmt->execute();
                $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                
                if ($rowCount == 0) {
                    // Table vide, réinitialiser l'auto-increment à 1
                    $stmt = $pdo->prepare("ALTER TABLE `$table` AUTO_INCREMENT = 1");
                    $stmt->execute();
                    $resetCount++;
                } else {
                    // Table avec données, réorganiser les IDs
                    $stmt = $pdo->prepare("SET @rank = 0");
                    $stmt->execute();
                    
                    // Créer une table temporaire avec les nouveaux IDs
                    $stmt = $pdo->prepare("
                        CREATE TEMPORARY TABLE temp_$table AS 
                        SELECT *, (@rank := @rank + 1) as new_id 
                        FROM `$table` 
                        ORDER BY id
                    ");
                    $stmt->execute();
                    
                    // Mettre à jour les IDs dans la table temporaire
                    $stmt = $pdo->prepare("
                        UPDATE temp_$table 
                        SET id = new_id
                    ");
                    $stmt->execute();
                    
                    // Supprimer les anciennes données et insérer les nouvelles
                    $stmt = $pdo->prepare("DELETE FROM `$table`");
                    $stmt->execute();
                    
                    // Obtenir la structure de la table
                    $stmt = $pdo->prepare("DESCRIBE `$table`");
                    $stmt->execute();
                    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Construire la requête d'insertion
                    $columnList = implode(', ', array_map(function($col) { return "`$col`"; }, $columns));
                    $placeholders = implode(', ', array_fill(0, count($columns), '?'));
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO `$table` ($columnList) 
                        SELECT $columnList FROM temp_$table
                    ");
                    $stmt->execute();
                    
                    // Réinitialiser l'auto-increment
                    $stmt = $pdo->prepare("ALTER TABLE `$table` AUTO_INCREMENT = " . ($rowCount + 1));
                    $stmt->execute();
                    
                    // Supprimer la table temporaire
                    $stmt = $pdo->prepare("DROP TEMPORARY TABLE IF EXISTS temp_$table");
                    $stmt->execute();
                    
                    $resetCount++;
                }
            }
        }
        
        $message = "✅ Réinitialisation terminée ! $resetCount tables ont été réorganisées.";
        
    } catch (PDOException $e) {
        $error = "❌ Erreur lors de la réinitialisation : " . $e->getMessage();
    }
}

// Obtenir l'état actuel des tables
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $autoIncrementTables = [
        'categories',
        'notifications', 
        'alertes',
        'budgets',
        'clients',
        'commandes',
        'depenses',
        'fournisseurs',
        'login_history',
        'mouvements',
        'roles_custom',
        'sales',
        'sales_items',
        'stocks',
        'transactions',
        'users'
    ];
    
    foreach ($autoIncrementTables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        
        if ($stmt->rowCount() > 0) {
            // Obtenir le nombre de lignes
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM `$table`");
            $stmt->execute();
            $rowCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            // Obtenir l'auto-increment actuel
            $stmt = $pdo->prepare("SHOW TABLE STATUS LIKE ?");
            $stmt->execute([$table]);
            $status = $stmt->fetch(PDO::FETCH_ASSOC);
            $autoIncrement = $status['Auto_increment'];
            
            // Obtenir le plus grand ID
            $stmt = $pdo->prepare("SELECT MAX(id) as max_id FROM `$table`");
            $stmt->execute();
            $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
            
            $tables[] = [
                'name' => $table,
                'row_count' => $rowCount,
                'auto_increment' => $autoIncrement,
                'max_id' => $maxId ?: 0,
                'has_gaps' => $rowCount > 0 && $autoIncrement > ($maxId + 1)
            ];
        }
    }
    
} catch (PDOException $e) {
    $error = "❌ Erreur lors de la récupération des informations : " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation Auto-Increment - Scolaria</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5em;
            font-weight: 300;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1em;
        }
        
        .content {
            padding: 30px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }
        
        .warning-box h3 {
            margin-top: 0;
            color: #856404;
        }
        
        .warning-box ul {
            margin: 10px 0;
            padding-left: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .table-container {
            margin-top: 30px;
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        
        tr:hover {
            background: #f8f9fa;
        }
        
        .status-ok {
            color: #28a745;
            font-weight: 600;
        }
        
        .status-warning {
            color: #ffc107;
            font-weight: 600;
        }
        
        .status-danger {
            color: #dc3545;
            font-weight: 600;
        }
        
        .actions {
            margin-top: 30px;
            text-align: center;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-database"></i> Réinitialisation Auto-Increment</h1>
            <p>Gestion des séquences d'identifiants dans la base de données</p>
        </div>
        
        <div class="content">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="warning-box">
                <h3><i class="fas fa-exclamation-triangle"></i> Attention !</h3>
                <p>Cette opération va réorganiser tous les IDs des tables pour éliminer les "trous" dans la séquence. Cela peut avoir des conséquences importantes :</p>
                <ul>
                    <li>Les IDs existants seront modifiés</li>
                    <li>Si vous avez des références externes vers ces IDs, elles devront être mises à jour</li>
                    <li>Cette opération est irréversible</li>
                    <li>Il est recommandé de faire une sauvegarde avant de procéder</li>
                </ul>
            </div>
            
            <div class="table-container">
                <h3><i class="fas fa-table"></i> État actuel des tables</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Lignes</th>
                            <th>Auto-Increment</th>
                            <th>ID Max</th>
                            <th>État</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tables as $table): ?>
                            <tr>
                                <td><strong><?php echo $table['name']; ?></strong></td>
                                <td><?php echo $table['row_count']; ?></td>
                                <td><?php echo $table['auto_increment']; ?></td>
                                <td><?php echo $table['max_id']; ?></td>
                                <td>
                                    <?php if ($table['row_count'] == 0): ?>
                                        <span class="status-ok">✅ Vide</span>
                                    <?php elseif ($table['has_gaps']): ?>
                                        <span class="status-warning">⚠️ Trous détectés</span>
                                    <?php else: ?>
                                        <span class="status-ok">✅ OK</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <div class="actions">
                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir réorganiser tous les IDs ? Cette opération est irréversible !');">
                    <button type="submit" name="reset_auto_increment" class="btn btn-danger">
                        <i class="fas fa-sync-alt"></i> Réorganiser tous les IDs
                    </button>
                </form>
                
                <a href="dashboard.php" class="back-link">
                    <i class="fas fa-arrow-left"></i> Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</body>
</html>
