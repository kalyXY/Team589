# Module Gestion des Stocks - Scolaria (Team589)

## Description
Module de gestion de logistique scolaire permettant de gérer les articles, leur quantité, les seuils d'alerte et l'historique des mouvements.

## Fonctionnalités

### ✅ Gestion des Articles
- **Liste complète** : Affichage de tous les articles avec ID, nom, catégorie, quantité, seuil et date d'ajout
- **Ajout d'articles** : Formulaire modal pour ajouter de nouveaux articles
- **Modification d'articles** : Édition des informations existantes
- **Suppression d'articles** : Suppression avec confirmation sécurisée

### 🔍 Recherche et Filtres
- **Recherche en temps réel** : Par nom ou catégorie d'article
- **Filtre par catégorie** : Sélection d'une catégorie spécifique
- **Filtre stock faible** : Affichage des articles en dessous du seuil

### 📊 Historique des Mouvements
- **Traçabilité complète** : Enregistrement de toutes les actions (ajout, modification, suppression)
- **Détails des modifications** : Information sur les changements effectués
- **Horodatage** : Date et heure de chaque action
- **Identification utilisateur** : Suivi des actions par utilisateur

### 🎨 Interface Moderne
- **Design responsive** : Compatible mobile et desktop
- **Modales interactives** : Formulaires en popup
- **Alertes visuelles** : Indication des stocks faibles
- **Navigation par onglets** : Organisation claire des fonctionnalités

## Installation

### Prérequis
- **XAMPP** (Apache + MySQL + PHP)
- **Navigateur web moderne**

### Étapes d'installation

1. **Démarrer XAMPP**
   ```
   - Lancer XAMPP Control Panel
   - Démarrer Apache et MySQL
   ```

2. **Créer la base de données**
   ```
   - Ouvrir phpMyAdmin (http://localhost/phpmyadmin)
   - Exécuter le script database.sql
   ```

3. **Déployer les fichiers**
   ```
   - Copier tous les fichiers dans le dossier htdocs/scolaria-stocks/
   - Ou dans un sous-dossier de votre choix
   ```

4. **Configuration**
   - Vérifier les paramètres de connexion dans stocks.php (lignes 19-22)
   - Adapter si nécessaire selon votre configuration MySQL

## Structure des Fichiers

```
📁 scolaria-stocks/
├── 📄 stocks.php          # Fichier principal (Frontend + Backend)
├── 🎨 stocks.css          # Styles CSS responsive
├── 🗄️ database.sql        # Script de création des tables
└── 📖 README.md           # Documentation
```

## Structure de la Base de Données

### Table `stocks`
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- nom_article (VARCHAR(150), NOT NULL)
- categorie (VARCHAR(100))
- quantite (INT, NOT NULL)
- seuil (INT, NOT NULL)
- created_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
- updated_at (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP ON UPDATE)
```

### Table `mouvements`
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- article_id (INT, FOREIGN KEY)
- action (VARCHAR(50), NOT NULL) -- ajout, modification, suppression
- details (TEXT)
- utilisateur (VARCHAR(100))
- date_mouvement (TIMESTAMP, DEFAULT CURRENT_TIMESTAMP)
```

## Utilisation

### Accès à l'application
```
http://localhost/scolaria-stocks/stocks.php
```

### Fonctionnalités principales

#### 1. Ajouter un Article
- Cliquer sur "Ajouter un article"
- Remplir le formulaire modal
- Valider avec le bouton "Ajouter"

#### 2. Modifier un Article
- Cliquer sur l'icône "crayon" dans la ligne de l'article
- Modifier les informations dans le formulaire pré-rempli
- Valider avec "Modifier"

#### 3. Supprimer un Article
- Cliquer sur l'icône "poubelle"
- Confirmer la suppression dans la popup

#### 4. Rechercher et Filtrer
- **Recherche** : Taper dans la barre de recherche
- **Filtre catégorie** : Sélectionner dans le menu déroulant
- **Stock faible** : Cocher la case correspondante

#### 5. Consulter l'Historique
- Cliquer sur l'onglet "Historique"
- Visualiser tous les mouvements avec détails

## Sécurité

### Mesures implémentées
- **Requêtes préparées** : Protection contre les injections SQL
- **Validation des données** : Contrôle côté client et serveur
- **Échappement HTML** : Prévention des attaques XSS
- **Sessions PHP** : Gestion sécurisée des sessions

### Recommandations
- Modifier les identifiants de base de données par défaut
- Configurer un utilisateur MySQL dédié avec privilèges limités
- Implémenter un système d'authentification pour la production

## Données de Test

Le script SQL inclut des données fictives :
- **8 articles** de différentes catégories
- **5 mouvements** dans l'historique
- Articles avec stocks normaux et faibles pour tester les alertes

## Personnalisation

### Modification des couleurs
Éditer les variables CSS dans `stocks.css` (lignes 6-18) :
```css
:root {
    --primary-color: #2563eb;    /* Couleur principale */
    --success-color: #10b981;    /* Couleur succès */
    --warning-color: #f59e0b;    /* Couleur avertissement */
    --danger-color: #ef4444;     /* Couleur danger */
}
```

### Ajout de fonctionnalités
- Modifier la classe `StockManager` dans `stocks.php`
- Ajouter de nouveaux endpoints AJAX
- Étendre l'interface utilisateur selon les besoins

## Support Technique

### Problèmes courants

1. **Erreur de connexion à la base de données**
   - Vérifier que MySQL est démarré
   - Contrôler les paramètres de connexion
   - S'assurer que la base de données existe

2. **Styles non appliqués**
   - Vérifier que `stocks.css` est dans le même dossier
   - Contrôler les permissions de fichiers

3. **JavaScript non fonctionnel**
   - Vérifier la connexion internet (jQuery CDN)
   - Consulter la console du navigateur pour les erreurs

### Logs et Debug
- Activer l'affichage des erreurs PHP si nécessaire
- Consulter les logs Apache/MySQL pour diagnostiquer les problèmes

## Évolutions Possibles

### Fonctionnalités avancées
- **Système d'authentification** : Connexion utilisateur
- **Gestion des rôles** : Permissions différenciées
- **Export de données** : PDF, Excel, CSV
- **Notifications** : Alertes par email pour stock faible
- **API REST** : Interface pour applications mobiles
- **Tableau de bord** : Statistiques et graphiques
- **Gestion des fournisseurs** : Informations sur les fournisseurs
- **Commandes automatiques** : Génération automatique de commandes

### Améliorations techniques
- **Cache** : Optimisation des performances
- **Pagination** : Gestion de grandes quantités de données
- **Recherche avancée** : Filtres multiples et tri
- **Backup automatique** : Sauvegarde programmée
- **Multi-langues** : Support international

---

## Auteur
**Team589** - Module développé pour l'application Scolaria de gestion de logistique scolaire.

## Version
**1.0** - Version initiale complète avec toutes les fonctionnalités demandées.