# 🏦 Module Gestion Financière - Scolaria

## Vue d'ensemble

Le module **Gestion Financière** de Scolaria permet un suivi complet des dépenses, budgets et rapports financiers pour les établissements scolaires. Il offre une interface moderne avec des fonctionnalités avancées d'analyse et d'export.

## 🚀 Fonctionnalités Principales

### 📊 Suivi des Dépenses
- **Ajout/Modification/Suppression** de dépenses avec validation
- **Catégorisation** automatique avec couleurs personnalisées
- **Filtrage avancé** par date, catégorie, fournisseur
- **Recherche textuelle** dans les descriptions
- **Gestion des factures** avec numéros et fournisseurs

### 📈 Rapports et Analyses
- **Graphiques dynamiques** (Chart.js) :
  - Évolution mensuelle des dépenses
  - Répartition par catégorie (camembert)
- **Indicateurs clés** :
  - Total mensuel et annuel
  - Catégorie la plus coûteuse
  - Nombre de dépenses
  - Alertes budgétaires

### 💰 Gestion Budgétaire
- **Budgets mensuels** par catégorie ou globaux
- **Comparaison automatique** budget prévu vs réel
- **Alertes visuelles** :
  - 🟢 Normal (< 80% du budget)
  - 🟡 Attention (80-100% du budget)
  - 🔴 Dépassement (> 100% du budget)

### 📂 Catégorisation
- **7 catégories prédéfinies** :
  - Fournitures (bleu)
  - Maintenance (rouge)
  - Investissement (vert)
  - Personnel (violet)
  - Utilities (orange)
  - Transport (cyan)
  - Divers (gris)
- **Création de nouvelles catégories** avec couleurs personnalisées

### 📤 Exports
- **Export PDF** avec mise en page professionnelle
- **Export Excel/CSV** pour analyses externes
- **Filtrage des exports** selon les critères sélectionnés

## 🛠️ Installation

### 1. Base de Données
```bash
# Exécuter le script d'initialisation
http://localhost/scolaria/init_finances.php
```

### 2. Fichiers Requis
- `finances.php` - Page principale du module
- `assets/css/finances.css` - Styles spécifiques
- `sql/finances_tables.sql` - Structure de base de données

### 3. Dépendances
- **Chart.js** (CDN) - Pour les graphiques
- **TCPDF** (optionnel) - Pour l'export PDF avancé
- **PHP PDO** - Accès base de données

## 📋 Structure de Base de Données

### Table `categories`
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- nom (VARCHAR(100), NOT NULL, UNIQUE)
- description (TEXT)
- couleur (VARCHAR(7), DEFAULT '#3B82F6')
- created_at, updated_at (TIMESTAMP)
```

### Table `depenses`
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- description (VARCHAR(255), NOT NULL)
- montant (DECIMAL(10,2), NOT NULL)
- date (DATE, NOT NULL)
- categorie_id (INT, FK vers categories)
- facture_numero (VARCHAR(50))
- fournisseur (VARCHAR(100))
- notes (TEXT)
- created_by (VARCHAR(50))
- created_at, updated_at (TIMESTAMP)
```

### Table `budgets`
```sql
- id (INT, AUTO_INCREMENT, PRIMARY KEY)
- mois (INT, 1-12, NOT NULL)
- annee (INT, NOT NULL)
- montant_prevu (DECIMAL(10,2), NOT NULL)
- categorie_id (INT, FK vers categories, NULL pour budget global)
- notes (TEXT)
- created_by (VARCHAR(50))
- created_at, updated_at (TIMESTAMP)
```

### Vues Créées
- `v_depenses_rapport` - Dépenses avec informations de catégorie
- `v_budgets_comparaison` - Budgets avec comparaison réel vs prévu

## 🎨 Interface Utilisateur

### Navigation par Onglets
1. **📊 Dépenses** - Gestion des dépenses
2. **📈 Rapports** - Graphiques et analyses
3. **💰 Budget** - Gestion budgétaire
4. **📂 Catégories** - Gestion des catégories
5. **📤 Export** - Exportation des données

### Indicateurs Visuels
- **Cartes colorées** pour les métriques clés
- **Badges de catégories** avec couleurs personnalisées
- **Alertes budgétaires** avec codes couleur
- **Graphiques interactifs** Chart.js

### Responsive Design
- **Mobile-first** avec breakpoints adaptatifs
- **Tableaux responsives** avec scroll horizontal
- **Modales optimisées** pour tous les écrans

## 🔧 Utilisation

### Ajouter une Dépense
1. Aller dans l'onglet "Dépenses"
2. Remplir le formulaire (description, montant, date obligatoires)
3. Sélectionner une catégorie (optionnel)
4. Ajouter facture et fournisseur (optionnel)
5. Cliquer "Enregistrer"

### Créer un Budget
1. Aller dans l'onglet "Budget"
2. Sélectionner mois et année
3. Définir le montant prévu
4. Choisir une catégorie (optionnel pour budget global)
5. Ajouter des notes (optionnel)
6. Cliquer "Enregistrer Budget"

### Consulter les Rapports
1. Aller dans l'onglet "Rapports"
2. Les graphiques se chargent automatiquement
3. **Graphique linéaire** : évolution mensuelle
4. **Graphique camembert** : répartition par catégorie

### Exporter les Données
1. Aller dans l'onglet "Export"
2. Définir les filtres (dates, catégorie, recherche)
3. Choisir le format (PDF ou Excel/CSV)
4. Le téléchargement démarre automatiquement

## 🎯 Fonctionnalités Avancées

### Filtrage Intelligent
- **Filtres combinables** : date + catégorie + recherche
- **Recherche textuelle** dans description et fournisseur
- **Persistance des filtres** lors de la navigation

### Validation des Données
- **Montants positifs** obligatoires
- **Dates valides** avec contrôles
- **Descriptions non vides** requises
- **Validation JavaScript** temps réel

### Sécurité
- **Requêtes préparées** PDO pour éviter les injections SQL
- **Échappement HTML** pour l'affichage
- **Validation serveur** de toutes les entrées
- **Sessions sécurisées** pour l'authentification

## 📊 Métriques et KPI

### Indicateurs Calculés
- **Total mensuel** : somme des dépenses du mois courant
- **Total annuel** : somme des dépenses de l'année
- **Catégorie principale** : catégorie avec le plus de dépenses
- **Alertes budget** : nombre de dépassements détectés

### Analyses Disponibles
- **Tendance mensuelle** : évolution des dépenses sur 12 mois
- **Répartition catégorielle** : pourcentage par catégorie
- **Comparaison budgétaire** : prévu vs réalisé
- **Top fournisseurs** : classement par montant

## 🔄 Maintenance

### Sauvegarde Recommandée
```sql
-- Sauvegarde des données financières
mysqldump -u user -p scolaria categories depenses budgets > finances_backup.sql
```

### Nettoyage Périodique
- **Archivage** des dépenses anciennes (> 2 ans)
- **Suppression** des budgets obsolètes
- **Optimisation** des index de performance

### Monitoring
- **Surveillance** des dépassements budgétaires
- **Alertes automatiques** par email (à implémenter)
- **Rapports mensuels** automatisés

## 🚀 Extensions Possibles

### Fonctionnalités Futures
- **Prévisions automatiques** basées sur l'historique
- **Intégration comptable** avec logiciels externes
- **Workflow d'approbation** pour les grosses dépenses
- **Notifications email** pour les alertes budgétaires
- **API REST** pour intégrations tierces

### Améliorations UX
- **Drag & drop** pour l'upload de factures
- **Reconnaissance OCR** des factures scannées
- **Tableaux de bord** personnalisables
- **Thèmes** et personnalisation avancée

## 📞 Support

### Dépannage Courant
1. **Graphiques ne s'affichent pas** : Vérifier Chart.js CDN
2. **Export PDF échoue** : Installer TCPDF
3. **Données manquantes** : Exécuter `init_finances.php`
4. **Erreurs de base** : Vérifier les permissions MySQL

### Contact
- **Équipe** : Scolaria Team589
- **Documentation** : README_FINANCES.md
- **Version** : 1.0.0
- **Dernière MAJ** : Janvier 2025

---

*Module développé avec ❤️ pour la gestion financière des établissements scolaires*