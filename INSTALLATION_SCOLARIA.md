# 🚀 Installation du Module Stocks sur Base Scolaria Existante

## 📋 Prérequis

- ✅ Base de données `scolaria` existante
- ✅ XAMPP avec Apache et MySQL démarrés
- ✅ Accès à phpMyAdmin
- ✅ Les tables `users`, `stocks`, et `depenses` déjà présentes

## 🔧 Étapes d'Installation

### 1. **Sauvegarde de Sécurité**
```sql
-- Dans phpMyAdmin, exporter votre base 'scolaria' actuelle
-- Aller dans 'Exporter' > 'Méthode rapide' > 'SQL'
```

### 2. **Migration de la Base de Données**

**Option A : Migration Automatique (Recommandée)**
```sql
-- Exécuter le fichier migration_scolaria.sql dans phpMyAdmin
-- Ce script adapte votre base existante sans perdre de données
```

**Option B : Migration Manuelle**
```sql
-- 1. Ajouter la colonne updated_at à la table stocks
ALTER TABLE `stocks` 
ADD COLUMN `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 2. Créer la table mouvements
CREATE TABLE `mouvements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT,
    `utilisateur` VARCHAR(100),
    `date_mouvement` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`article_id`) REFERENCES `stocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 3. **Déploiement des Fichiers**

```bash
# Copier les fichiers dans votre dossier web
htdocs/
├── scolaria-stocks/
│   ├── stocks.php          # Application principale
│   ├── stocks.css          # Styles professionnels
│   ├── config.php          # Configuration
│   └── test_connection.php # Test de connexion
```

### 4. **Configuration**

Les fichiers sont déjà configurés pour utiliser la base `scolaria` :

```php
// Dans stocks.php et config.php
private $db_name = "scolaria";  // ✅ Déjà configuré
```

### 5. **Test de l'Installation**

1. **Tester la connexion :**
   ```
   http://localhost/scolaria-stocks/test_connection.php
   ```

2. **Accéder à l'application :**
   ```
   http://localhost/scolaria-stocks/stocks.php
   ```

## 📊 Vos Données Actuelles

Le module reconnaîtra automatiquement vos articles existants :

| Article | Catégorie | Quantité | Seuil | Statut |
|---------|-----------|----------|-------|--------|
| Stylos bleus | Fournitures | 120 | 50 | ✅ OK |
| Cahiers A4 | Papeterie | 40 | 60 | ⚠️ STOCK FAIBLE |
| Marqueurs effaçables | Fournitures | 15 | 30 | ⚠️ STOCK FAIBLE |
| Feuilles A3 | Papeterie | 6 | 10 | ⚠️ STOCK FAIBLE |
| Cartouches impression | Informatique | 3 | 5 | ⚠️ STOCK FAIBLE |

## 🎯 Fonctionnalités Disponibles

### ✅ Gestion Complète
- **Visualisation** de tous vos articles existants
- **Ajout** de nouveaux articles
- **Modification** des quantités et seuils
- **Suppression** sécurisée avec confirmation
- **Recherche** en temps réel
- **Filtres** par catégorie et stock faible

### 📈 Dashboard Professionnel
- **Métriques en temps réel** : Total articles, stocks faibles, catégories
- **Alertes visuelles** pour les stocks critiques
- **Historique complet** des mouvements
- **Interface responsive** pour mobile et desktop

### 🔍 Alertes Automatiques
Vos articles avec stock faible seront automatiquement signalés :
- 🔴 **Cahiers A4** : 40/60 (stock faible)
- 🔴 **Marqueurs effaçables** : 15/30 (stock critique)
- 🔴 **Feuilles A3** : 6/10 (stock très faible)
- 🔴 **Cartouches** : 3/5 (stock critique)

## 🛠️ Personnalisation

### Modifier les Seuils d'Alerte
```sql
-- Exemple : Augmenter le seuil des cahiers A4
UPDATE stocks SET seuil = 80 WHERE nom_article = 'Cahiers A4';
```

### Ajouter des Catégories
Les catégories sont dynamiques. Ajoutez simplement un article avec une nouvelle catégorie.

## 🚨 Dépannage

### Problème : "Table mouvements doesn't exist"
```sql
-- Exécuter dans phpMyAdmin :
CREATE TABLE `mouvements` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT,
    `action` VARCHAR(50) NOT NULL,
    `details` TEXT,
    `utilisateur` VARCHAR(100),
    `date_mouvement` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`article_id`) REFERENCES `stocks`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Problème : Erreur de connexion
1. Vérifier que MySQL est démarré dans XAMPP
2. Vérifier que la base `scolaria` existe
3. Tester avec `test_connection.php`

### Problème : Styles non appliqués
1. Vérifier que `stocks.css` est dans le même dossier
2. Vider le cache du navigateur (Ctrl+F5)

## 📞 Support

En cas de problème :

1. **Vérifier les logs** : Console du navigateur (F12)
2. **Tester la connexion** : `test_connection.php`
3. **Vérifier les permissions** : Dossier accessible en lecture/écriture

## 🎉 Résultat Final

Une fois installé, vous aurez :

- ✅ **Interface professionnelle** avec votre design corporate
- ✅ **Gestion complète** de vos stocks existants
- ✅ **Alertes automatiques** pour les stocks faibles
- ✅ **Historique détaillé** de tous les mouvements
- ✅ **Recherche avancée** et filtres
- ✅ **Responsive design** pour tous les appareils

**Votre base de données existante reste intacte** - le module s'adapte à vos données actuelles ! 🚀