# Guide de Démarrage Rapide - StarUML pour Scolaria

## 🚀 **Démarrage Immédiat**

### 1. **Ouvrir le Fichier**
- Lancer **StarUML**
- **Fichier** → **Ouvrir** → Sélectionner `Untitled.mdj`
- Le diagramme s'affiche avec la classe **User** déjà créée

### 2. **Ajouter les Classes Manquantes**

#### **Classe Stock**
```
Clic droit sur le diagramme → Add → Class
Nom: Stock
Attributs:
  -id : int
  -nom_article : string
  -categorie : string
  -code_barres : string
  -prix_achat : decimal
  -prix_vente : decimal
  -quantite : int
  -seuil : int
  -seuil_alerte : int
  -created_at : datetime
  -updated_at : datetime

Méthodes:
  +addQuantity(quantity) : void
  +removeQuantity(quantity) : bool
  +updatePrice(achat, vente) : void
  +isLowStock() : bool
  +isOutOfStock() : bool
  +calculateProfit(quantity) : decimal
  +updateThreshold(threshold) : void
  +getMovements() : Mouvement[]
```

#### **Classe Client**
```
Clic droit sur le diagramme → Add → Class
Nom: Client
Attributs:
  -id : int
  -first_name : string
  -last_name : string
  -phone : string
  -email : string
  -address : string
  -client_type : enum
  -notes : text
  -created_at : datetime
  -updated_at : datetime

Méthodes:
  +getFullName() : string
  +getSales() : Sale[]
  +getTotalSpent() : decimal
  +getLastPurchaseDate() : datetime
  +getPurchaseHistory() : array
```

#### **Classe Sale**
```
Clic droit sur le diagramme → Add → Class
Nom: Sale
Attributs:
  -id : int
  -client_id : int
  -total : decimal
  -created_at : datetime

Méthodes:
  +addItem(product, quantity, price) : void
  +removeItem(itemId) : void
  +calculateTotal() : decimal
  +applyDiscount(type, value) : void
  +getItems() : SaleItem[]
  +getTransactions() : Transaction[]
  +getProfit() : decimal
  +generateInvoice() : string
```

### 3. **Créer les Relations UML**

#### **Association User → Notification (1:N)**
```
Sélectionner l'outil Association
Clic sur User → Clic sur Notification
Double-clic sur la ligne → Cardinalité: 1 (côté User), * (côté Notification)
```

#### **Composition Sale → SaleItem (1:N)**
```
Sélectionner l'outil Composition (losange plein)
Clic sur Sale → Clic sur SaleItem
Double-clic sur la ligne → Cardinalité: 1 (côté Sale), * (côté SaleItem)
```

#### **Héritage HomeController → BaseController**
```
Sélectionner l'outil Generalization (triangle)
Clic sur HomeController → Clic sur BaseController
```

## 📋 **Liste Complète des Classes à Créer**

### **Entités de Domaine**
- [x] User ✅ (déjà créée)
- [ ] Stock
- [ ] Client
- [ ] Sale
- [ ] SaleItem
- [ ] Transaction
- [ ] Fournisseur
- [ ] Commande
- [ ] Alerte
- [ ] Mouvement
- [ ] Notification
- [ ] Categorie
- [ ] Depense
- [ ] Budget

### **Classes de Service**
- [ ] BaseController
- [ ] HomeController
- [ ] Database
- [ ] StockManager
- [ ] FinancesManager
- [ ] AlertsManager
- [ ] NotificationSystem
- [ ] StockMonitor
- [ ] AuthManager

### **Composants de Vue**
- [ ] StatsCard
- [ ] DataTable

## 🔗 **Relations UML à Créer**

### **Associations (1:N)**
```
User ||--o{ Notification
User ||--o{ Sale
User ||--o{ Commande
User ||--o{ Depense
User ||--o{ Budget

Stock ||--o{ SaleItem
Stock ||--o{ Alerte
Stock ||--o{ Mouvement
Stock ||--o{ Commande

Client ||--o{ Sale
Fournisseur ||--o{ Commande
Categorie ||--o{ Depense
Categorie ||--o{ Budget
```

### **Compositions (Dépendance Forte)**
```
Sale *-- SaleItem
Sale *-- Transaction
```

### **Héritage**
```
HomeController --|> BaseController
```

### **Dépendances (Utilisation)**
```
StockManager ..> Stock
StockManager ..> Database
FinancesManager ..> Depense
FinancesManager ..> Budget
FinancesManager ..> Database
AlertsManager ..> Stock
AlertsManager ..> Alerte
AlertsManager ..> Commande
AlertsManager ..> Database
NotificationSystem ..> Notification
NotificationSystem ..> User
StockMonitor ..> Stock
StockMonitor ..> NotificationSystem
AuthManager ..> User
StatsCard ..> Database
DataTable ..> Database
```

## 🎨 **Conseils de Présentation**

### **Organisation Spatiale**
- **Gauche** : Entités de domaine
- **Centre** : Classes de service
- **Droite** : Composants de vue
- **Haut** : Classes de base et interfaces

### **Couleurs et Styles**
- **Entités** : Bleu clair
- **Services** : Vert clair
- **Contrôleurs** : Orange clair
- **Composants** : Violet clair

### **Éviter les Croisements**
- Organiser les classes en cercles
- Utiliser des relations courbes si nécessaire
- Grouper les classes liées

## 📤 **Export et Partage**

### **Formats d'Export**
1. **PNG** : Pour la documentation
2. **SVG** : Pour le web (vectoriel)
3. **PDF** : Pour l'impression
4. **JPG** : Pour les présentations

### **Résolution Recommandée**
- **Documentation** : 300 DPI
- **Présentation** : 150 DPI
- **Web** : 72 DPI

## 🆘 **Résolution des Problèmes**

### **Problème : Classes qui se chevauchent**
**Solution** : Utiliser **Arrange** → **Auto Layout**

### **Problème : Relations difficiles à lire**
**Solution** : Utiliser des **relations courbes** et ajuster les positions

### **Problème : Diagramme trop grand**
**Solution** : Utiliser **View** → **Zoom** → **Fit to Window**

## ✅ **Checklist de Validation**

- [ ] Toutes les 25 classes sont créées
- [ ] Tous les attributs ont leur visibilité et type
- [ ] Toutes les méthodes ont leurs paramètres et type de retour
- [ ] Toutes les relations UML sont créées
- [ ] Toutes les cardinalités sont spécifiées
- [ ] Le diagramme est lisible et organisé
- [ ] Les couleurs et styles sont cohérents
- [ ] Le diagramme est exporté en haute qualité

## 🎯 **Objectif Final**

Créer un diagramme de classe UML **complet et professionnel** qui respecte :
- ✅ **100% des standards UML**
- ✅ **Tous les attributs et méthodes**
- ✅ **Toutes les relations et cardinalités**
- ✅ **L'architecture complète de Scolaria**

---

*Guide créé pour faciliter la création du diagramme UML complet dans StarUML*
