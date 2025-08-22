# Système de Notifications Automatiques - Scolaria

Ce document explique comment utiliser le nouveau système de notifications automatiques pour les alertes de stock et autres événements importants.

## 🚀 Fonctionnalités

### 1. Notifications Automatiques
- **Alertes de stock** : Ruptures de stock et stocks faibles
- **Mouvements de stock** : Ajouts, modifications, suppressions
- **Nouvelles ventes** : Notifications des transactions
- **Dépenses importantes** : Alertes pour les dépenses
- **Problèmes système** : Notifications d'erreurs critiques

### 2. Destinataires Automatiques
- **Admins** : Toutes les notifications importantes
- **Gestionnaires** : Alertes de stock et mouvements
- **Utilisateurs spécifiques** : Notifications personnalisées

### 3. Types de Notifications
- **🚨 Error** : Ruptures de stock, problèmes système
- **⚠️ Warning** : Stocks faibles, dépenses importantes
- **💰 Success** : Nouvelles ventes, actions réussies
- **📦 Info** : Mouvements de stock, informations générales

## 📁 Structure des Fichiers

```
├── includes/
│   ├── notification_system.php    # Système principal de notifications
│   └── stock_monitor.php          # Moniteur d'alertes de stock
├── cron_check_stock_alerts.php    # Script cron pour vérifications automatiques
├── test_notification_system.php   # Page de test du système
└── logs/                          # Dossier des logs (créé automatiquement)
    └── stock_alerts.log          # Log des vérifications d'alertes
```

## ⚙️ Installation

### 1. Vérifier les Dépendances
Assurez-vous que la table `notifications` existe. Si elle n'existe pas, elle sera créée automatiquement.

### 2. Configuration des Permissions
Le dossier `logs/` sera créé automatiquement avec les permissions appropriées.

### 3. Test du Système
Accédez à `test_notification_system.php` pour tester le système de notifications.

## 🔧 Utilisation

### Créer une Notification Manuelle

```php
require_once 'includes/notification_system.php';

// Notification pour les admins et gestionnaires
NotificationSystem::createAdminNotification(
    "Titre de la notification",
    "Message de la notification",
    "warning" // type: info, success, warning, error
);

// Notification pour un rôle spécifique
NotificationSystem::createNotificationForRole(
    "Titre",
    "Message",
    "warning",
    "admin" // rôle: admin, gestionnaire, caissier, etc.
);

// Notification pour un utilisateur spécifique
NotificationSystem::createNotification(
    $userId,
    "Titre",
    "Message",
    "info"
);
```

### Notifications Automatiques de Stock

```php
require_once 'includes/stock_monitor.php';

// Vérifier et notifier des alertes de stock
$alertsCount = StockMonitor::checkAndNotify();

// Vérifier un produit spécifique après modification
StockMonitor::checkProductAfterUpdate($productId);

// Notifier d'un mouvement de stock
StockMonitor::notifyStockMovement($productId, "Ajout", $quantity, $userName);
```

### Types de Notifications Disponibles

#### 1. Alertes de Stock
```php
// Rupture de stock
NotificationSystem::notifyStockOut($productId, $productName, $currentQuantity);

// Stock faible
NotificationSystem::notifyLowStock($productId, $productName, $currentQuantity, $threshold);
```

#### 2. Ventes et Transactions
```php
// Nouvelle vente
NotificationSystem::notifyNewSale($saleId, $total, $clientName);

// Mouvement de stock
NotificationSystem::notifyStockMovement($productId, $productName, $action, $quantity, $userName);
```

#### 3. Finances et Système
```php
// Dépense importante
NotificationSystem::notifyExpense($expenseId, $amount, $description);

// Problème système
NotificationSystem::notifySystemIssue($issue, $details);
```

## 📅 Automatisation

### Script Cron
Le script `cron_check_stock_alerts.php` peut être exécuté automatiquement pour vérifier les alertes :

```bash
# Vérifier toutes les heures
0 * * * * php /path/to/cron_check_stock_alerts.php

# Vérifier toutes les 30 minutes
*/30 * * * * php /path/to/cron_check_stock_alerts.php

# Vérifier tous les jours à 8h00
0 8 * * * php /path/to/cron_check_stock_alerts.php
```

### Exécution Manuelle
Vous pouvez aussi exécuter le script manuellement :

```bash
# En ligne de commande
php cron_check_stock_alerts.php

# Via le navigateur
http://votre-site.com/cron_check_stock_alerts.php
```

## 🎯 Intégration dans le Code Existant

### Dans les Scripts de Gestion de Stock

```php
// Après modification d'un produit
if ($stmt->execute([$quantity, $productId])) {
    // Vérifier si des alertes doivent être envoyées
    StockMonitor::checkProductAfterUpdate($productId);
    
    // Notifier du mouvement
    StockMonitor::notifyStockMovement($productId, "Modification", $quantity, $_SESSION['username']);
}
```

### Dans le Système de Ventes

```php
// Après enregistrement d'une vente
if ($saleId = $pdo->lastInsertId()) {
    // Notifier de la nouvelle vente
    NotificationSystem::notifyNewSale($saleId, $total, $clientName);
    
    // Vérifier les stocks après vente
    foreach ($items as $item) {
        StockMonitor::checkProductAfterUpdate($item['product_id']);
    }
}
```

### Dans la Gestion des Dépenses

```php
// Après enregistrement d'une dépense importante
if ($amount > 100) { // Seuil configurable
    NotificationSystem::notifyExpense($expenseId, $amount, $description);
}
```

## 🔍 Surveillance et Maintenance

### Logs
Le système génère des logs détaillés dans `logs/stock_alerts.log` :

```
[2025-08-22 17:30:00] === Début de la vérification des alertes de stock ===
[2025-08-22 17:30:01] ✅ Connexion à la base de données établie
[2025-08-22 17:30:02] 🔍 Vérification des alertes de stock...
[2025-08-22 17:30:03] 📊 État des stocks: 2 ruptures, 5 stocks faibles
[2025-08-22 17:30:04] 📢 Envoi des notifications d'alerte...
[2025-08-22 17:30:05] ✅ 7 notifications d'alerte envoyées
```

### Nettoyage Automatique
Les anciennes notifications (plus de 30 jours) sont automatiquement supprimées pour maintenir les performances.

### Limitation des Notifications
Le système limite le nombre de notifications envoyées par exécution pour éviter le spam (50 par défaut).

## 🧪 Tests

### Page de Test
Accédez à `test_notification_system.php` pour :

1. **Vérifier l'état des stocks** : Voir les ruptures et stocks faibles
2. **Tester les notifications** : Créer des notifications de test
3. **Vérifier les alertes** : Déclencher la vérification automatique
4. **Gérer les données** : Ajouter/supprimer des notifications

### Tests Automatiques
```php
// Test de notification
$result = NotificationSystem::createAdminNotification(
    "Test",
    "Message de test",
    "info"
);

// Test de vérification d'alertes
$alerts = StockMonitor::checkAndNotify();
```

## 📊 Personnalisation

### Modifier les Seuils
```php
// Dans stock_monitor.php
$maxNotificationsPerRun = 100; // Plus de notifications par exécution
```

### Ajouter de Nouveaux Types
```php
// Dans notification_system.php
public static function notifyCustomEvent($title, $message, $data = null) {
    return self::createAdminNotification($title, $message, 'info', $data);
}
```

### Modifier les Destinataires
```php
// Ajouter d'autres rôles
public static function createExtendedAdminNotification($title, $message, $type = 'warning', $data = null) {
    $notificationsCreated = 0;
    
    // Admins
    $notificationsCreated += self::createNotificationForRole($title, $message, $type, 'admin', $data);
    
    // Gestionnaires
    $notificationsCreated += self::createNotificationForRole($title, $message, $type, 'gestionnaire', $data);
    
    // Directeurs
    $notificationsCreated += self::createNotificationForRole($title, $message, $type, 'directeur', $data);
    
    return $notificationsCreated;
}
```

## 🚨 Dépannage

### Problèmes Courants

1. **Notifications non envoyées**
   - Vérifiez que la table `notifications` existe
   - Contrôlez les logs dans `logs/stock_alerts.log`
   - Vérifiez les permissions du dossier `logs/`

2. **Erreurs de base de données**
   - Vérifiez la connexion à la base de données
   - Contrôlez que les tables `users` et `stocks` existent
   - Vérifiez les permissions utilisateur

3. **Script cron ne fonctionne pas**
   - Vérifiez le chemin absolu dans le crontab
   - Contrôlez les permissions d'exécution
   - Testez manuellement le script

### Logs d'Erreur
Les erreurs sont enregistrées dans :
- `logs/stock_alerts.log` pour les vérifications automatiques
- `error_log` PHP pour les erreurs générales

## 🔮 Évolutions Futures

- **Notifications push** : Support des notifications navigateur
- **Emails** : Envoi automatique d'emails pour les alertes critiques
- **SMS** : Notifications par SMS pour les urgences
- **Webhooks** : Intégration avec des services externes
- **Préférences utilisateur** : Configuration des types de notifications par utilisateur

---

**Scolaria** - Système de gestion scolaire avec notifications intelligentes
*Développé avec ❤️ par Team589*
