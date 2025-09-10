# Diagramme de Classe UML - Scolaria

## Vue d'ensemble

Ce répertoire contient le diagramme de classe UML complet de l'application **Scolaria**, représentant l'architecture et la modélisation complète du système de gestion des stocks et finances scolaires.

## Fichiers

### 📄 Fichiers source
- `class-diagram-complete.mmd` - Diagramme source en format Mermaid
- `class-diagram-documentation.md` - Documentation complète du diagramme
- `generate-class-diagram.ps1` - Script de génération automatique

### 🖼️ Fichiers générés
- `class-diagram-complete.png` - Diagramme en format image (généré)
- `class-diagram-complete.svg` - Diagramme vectoriel (optionnel)
- `class-diagram-complete.pdf` - Diagramme PDF (optionnel)

## Génération du Diagramme

### Prérequis

1. **Node.js et npm** installés
2. **Mermaid CLI** installé globalement :
   ```bash
   npm install -g @mermaid-js/mermaid-cli
   ```

### Génération automatique

#### Méthode 1 : Script PowerShell (Recommandé)
```powershell
# Depuis le répertoire docs/diagrams/
.\generate-class-diagram.ps1

# Avec options
.\generate-class-diagram.ps1 -Format svg -OpenAfter
.\generate-class-diagram.ps1 -Format pdf
```

#### Méthode 2 : Commande directe
```bash
# PNG (par défaut)
mmdc -i class-diagram-complete.mmd -o class-diagram-complete.png -t default --width 2400 --height 1800 --scale 2

# SVG vectoriel
mmdc -i class-diagram-complete.mmd -o class-diagram-complete.svg -t default --svg

# PDF
mmdc -i class-diagram-complete.mmd -o class-diagram-complete.pdf -t default --pdf
```

## Structure du Diagramme

### 🏗️ Architecture

Le diagramme représente une architecture en couches :

1. **Entités de Domaine** (bleu)
   - User, Stock, Client, Sale, Transaction
   - Fournisseur, Commande, Alerte, Mouvement
   - Notification, Categorie, Depense, Budget

2. **Couche Service** (vert)
   - StockManager, FinancesManager, AlertsManager
   - NotificationSystem, StockMonitor, AuthManager

3. **Couche Contrôleur** (orange)
   - BaseController, HomeController

4. **Couche Infrastructure** (gris)
   - Database

5. **Composants de Vue** (violet)
   - StatsCard, DataTable

### 🔗 Relations Principales

| Relation | Type | Cardinalité | Description |
|----------|------|-------------|-------------|
| User → Notification | Association | 1:N | Un utilisateur reçoit plusieurs notifications |
| Stock → SaleItem | Association | 1:N | Un article peut être vendu plusieurs fois |
| Client → Sale | Association | 1:N | Un client peut effectuer plusieurs ventes |
| Sale → SaleItem | Composition | 1:N | Une vente contient plusieurs items |
| Sale → Transaction | Composition | 1:N | Une vente peut avoir plusieurs paiements |
| Fournisseur → Commande | Association | 1:N | Un fournisseur reçoit plusieurs commandes |

### 📊 Données Métier Clés

#### Types d'utilisateurs (Role)
- `admin` : Accès complet
- `gestionnaire` : Gestion stocks et finances
- `caissier` : Ventes uniquement
- `directeur` : Consultation rapports

#### Types de clients
- `parent` : Parent d'élève
- `eleve` : Élève de l'école
- `acheteur_regulier` : Acheteur fréquent
- `autre` : Autre type

#### Modes de paiement
- `cash` : Espèces
- `mobile_money` : Paiement mobile
- `card` : Carte bancaire
- `transfer` : Virement

## Modélisation UML

### Notations utilisées

- **📦 Classes** : Rectangles avec nom, attributs et méthodes
- **🔗 Relations** : Lignes avec cardinalités
- **💎 Composition** : Losange plein (dépendance forte)
- **◇ Agrégation** : Losange vide (dépendance faible)
- **→ Association** : Ligne simple avec flèche
- **- - → Dépendance** : Ligne pointillée
- **→ Héritage** : Ligne avec triangle

### Visibilité des attributs et méthodes

- `+` **public** : Accessible depuis l'extérieur
- `-` **private** : Accessible uniquement dans la classe
- `#` **protected** : Accessible dans la classe et ses sous-classes
- `~` **package** : Accessible dans le même package

### Cardinalités

- `1` : Exactement un
- `0..1` : Zéro ou un
- `1..*` : Un ou plusieurs
- `0..*` ou `*` : Zéro ou plusieurs
- `1..n` : Entre 1 et n

## Règles de Gestion

### 🏪 Gestion des Stocks
- **Seuil d'alerte** : Alerte automatique quand `quantite ≤ seuil_alerte`
- **Rupture de stock** : Alerte critique quand `quantite = 0`
- **Calcul de profit** : `(prix_vente - prix_achat) × quantité`
- **Traçabilité** : Tous les mouvements sont enregistrés

### 💰 Gestion des Ventes
- **Vérification stock** : Impossible de vendre plus que disponible
- **Calcul automatique** : Total calculé en temps réel
- **Remises** : Support des remises en pourcentage ou montant fixe
- **Paiements multiples** : Une vente peut être payée en plusieurs fois

### 👥 Gestion des Utilisateurs
- **Authentification** : Login/mot de passe avec hachage bcrypt
- **Autorisation** : Contrôle d'accès basé sur les rôles
- **Sessions sécurisées** : Configuration hardened
- **Gestion de profil** : Avatar, informations personnelles

### 🔔 Système de Notifications
- **Notifications par rôle** : Envoi automatique selon le rôle
- **Types prioritaires** : Erreur > Avertissement > Info > Succès
- **Nettoyage automatique** : Suppression des notifications anciennes
- **Temps réel** : Mise à jour en direct via AJAX

## Patterns de Conception

### 🔧 Patterns Utilisés

1. **Singleton** - `Database`
   - Une seule instance de connexion DB

2. **Factory** - `NotificationSystem`
   - Création de différents types de notifications

3. **Observer** - `StockMonitor`
   - Surveillance des changements de stock

4. **Strategy** - `FinancesManager`
   - Différentes stratégies d'export (PDF, CSV)

5. **MVC** - Architecture globale
   - Séparation Modèle-Vue-Contrôleur

### 🎯 Avantages de l'Architecture

- **Séparation des responsabilités** : Chaque classe a un rôle précis
- **Facilité de maintenance** : Modifications localisées
- **Extensibilité** : Ajout facile de nouvelles fonctionnalités
- **Testabilité** : Tests unitaires simplifiés
- **Réutilisabilité** : Composants modulaires

## Consultation du Diagramme

### 📱 Visualisation en ligne
Le fichier `.mmd` peut être visualisé directement sur :
- [Mermaid Live Editor](https://mermaid.live/)
- GitHub (support natif)
- GitLab (support natif)

### 🖥️ Outils recommandés
- **VS Code** avec extension Mermaid Preview
- **IntelliJ IDEA** avec plugin Mermaid
- **Draw.io** (import possible)
- **Lucidchart** (import possible)

## Maintenance

### ✏️ Modification du diagramme

1. Éditer le fichier `class-diagram-complete.mmd`
2. Régénérer l'image : `.\generate-class-diagram.ps1`
3. Mettre à jour la documentation si nécessaire
4. Valider visuellement le résultat

### 📋 Bonnes pratiques

- **Cohérence** : Respecter les conventions de nommage
- **Lisibilité** : Éviter les croisements de lignes
- **Simplicité** : Ne pas surcharger le diagramme
- **Documentation** : Tenir à jour la documentation associée

### 🔄 Versioning

- Utiliser des commits séparés pour le source (.mmd) et les images générées
- Taguer les versions importantes du diagramme
- Maintenir un changelog des modifications importantes

## Support

### 🐛 Problèmes de génération

1. **Vérifier Node.js** : `node --version`
2. **Vérifier mermaid-cli** : `mmdc --version`
3. **Réinstaller si nécessaire** : `npm install -g @mermaid-js/mermaid-cli`
4. **Vérifier la syntaxe** : Utiliser Mermaid Live Editor

### 📞 Contact

Pour toute question sur le diagramme ou l'architecture :
- **Équipe** : Team589
- **Projet** : Scolaria
- **Documentation** : `class-diagram-documentation.md`

---

*Diagramme généré automatiquement pour l'application Scolaria - Système de gestion des stocks et finances scolaires*
