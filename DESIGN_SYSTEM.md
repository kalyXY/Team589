# 🎓 SCOLARIA - Design System Moderne

## Vue d'ensemble

Ce document décrit le nouveau design system moderne de **Scolaria**, une application de gestion logistique scolaire. Le système a été conçu pour offrir une expérience utilisateur moderne, professionnelle et accessible.

## 📋 Table des matières

- [Caractéristiques principales](#caractéristiques-principales)
- [Structure des fichiers](#structure-des-fichiers)
- [Système de couleurs](#système-de-couleurs)
- [Composants](#composants)
- [Layout et Navigation](#layout-et-navigation)
- [Responsivité](#responsivité)
- [Dark Mode](#dark-mode)
- [Utilisation](#utilisation)
- [Exemples](#exemples)

## ✨ Caractéristiques principales

- **Design moderne** : Interface épurée avec des éléments visuels contemporains
- **Système de thème** : Support du mode sombre/clair avec basculement automatique
- **Responsive design** : Optimisé pour desktop, tablette et mobile
- **Composants réutilisables** : Cartes, tableaux, formulaires modulaires
- **Animations fluides** : Transitions et micro-interactions soignées
- **Accessibilité** : Respect des standards WCAG
- **Performance** : CSS optimisé et JavaScript modulaire

## 📁 Structure des fichiers

```
/assets/
├── css/
│   ├── style.css           # CSS principal avec variables et layout
│   └── components.css      # Composants spécialisés et utilitaires
├── js/
│   └── main.js            # JavaScript principal avec toutes les fonctionnalités
└── images/
    └── favicon.ico

/layout/
└── base.php               # Template de base avec sidebar et header

/components/
├── stats-card.php         # Composant pour cartes de statistiques
└── data-table.php         # Composant pour tableaux de données

/
├── dashboard-modern.php   # Exemple de dashboard moderne
├── login-modern.php       # Page de connexion moderne
└── DESIGN_SYSTEM.md      # Cette documentation
```

## 🎨 Système de couleurs

### Palette principale

```css
/* Couleurs primaires */
--primary-color: #1E88E5     /* Bleu professionnel */
--secondary-color: #43A047   /* Vert succès */
--accent-color: #FF6B35      /* Orange accent */

/* Couleurs fonctionnelles */
--success-color: #66BB6A     /* Vert succès */
--warning-color: #FFA726     /* Orange avertissement */
--error-color: #EF5350       /* Rouge erreur */

/* Couleurs neutres */
--bg-primary: #F5F7FA        /* Fond principal */
--bg-secondary: #FFFFFF      /* Fond secondaire */
--text-primary: #333333      /* Texte principal */
--text-secondary: #666666    /* Texte secondaire */
```

### Mode sombre

Les couleurs s'adaptent automatiquement en mode sombre :

```css
[data-theme="dark"] {
    --bg-primary: #121212
    --bg-secondary: #1E1E1E
    --text-primary: #EEEEEE
    --text-secondary: #CCCCCC
}
```

## 🧩 Composants

### 1. Cartes de statistiques

Affichage de métriques importantes avec icônes et indicateurs de tendance.

```php
<?php
renderStatsCard([
    'title' => 'Articles en stock',
    'value' => 1250,
    'icon' => 'fas fa-boxes',
    'type' => 'primary',
    'change' => '+12%',
    'changeType' => 'positive',
    'link' => '/stocks.php'
]);
?>
```

**Types disponibles :** `primary`, `success`, `warning`, `error`

### 2. Tableaux de données

Tableaux avec tri, recherche, pagination et actions.

```php
<?php
renderDataTable([
    'title' => 'Liste des utilisateurs',
    'columns' => [
        ['key' => 'name', 'label' => 'Nom', 'sortable' => true],
        ['key' => 'email', 'label' => 'Email', 'type' => 'link'],
        ['key' => 'status', 'label' => 'Statut', 'type' => 'badge']
    ],
    'data' => $users,
    'search' => true,
    'export' => true
]);
?>
```

### 3. Système d'alertes

Messages contextuels avec icônes et couleurs appropriées.

```html
<div class="alert alert-success">
    <i class="fas fa-check-circle"></i>
    Opération réussie !
</div>
```

**Types :** `success`, `warning`, `error`, `info`

### 4. Boutons

Boutons avec différents styles et tailles.

```html
<button class="btn btn-primary">
    <i class="fas fa-plus"></i>
    Ajouter
</button>
```

**Variantes :** `btn-primary`, `btn-secondary`, `btn-outline`, `btn-ghost`
**Tailles :** `btn-sm`, `btn-lg`

## 🏗️ Layout et Navigation

### Sidebar

Navigation latérale fixe avec icônes FontAwesome :

- **Desktop** : Sidebar visible avec texte et icônes
- **Mobile** : Sidebar repliable avec overlay
- **État collapsed** : Affichage icônes uniquement

### Header

Barre supérieure avec :

- Toggle sidebar
- Titre de la page
- Toggle dark mode
- Notifications
- Menu utilisateur

### Structure HTML

```html
<div class="app-container">
    <aside class="sidebar">
        <!-- Navigation -->
    </aside>
    
    <main class="main-content">
        <header class="header">
            <!-- Barre supérieure -->
        </header>
        
        <div class="content">
            <!-- Contenu de la page -->
        </div>
    </main>
</div>
```

## 📱 Responsivité

### Points de rupture

- **Mobile** : ≤ 768px
- **Tablette** : 769px - 1024px  
- **Desktop** : ≥ 1025px

### Adaptations

- **Mobile** : Sidebar en overlay, cartes en colonne unique
- **Tablette** : Cartes en 2 colonnes, tableaux avec scroll horizontal
- **Desktop** : Layout complet avec sidebar fixe

## 🌙 Dark Mode

### Activation

Le dark mode peut être activé de plusieurs façons :

1. **Toggle manuel** : Bouton dans le header
2. **Préférence système** : Détection automatique
3. **Stockage local** : Mémorisation du choix utilisateur

### Implémentation

```javascript
// Basculer le thème
function toggleTheme() {
    const newTheme = currentTheme === 'light' ? 'dark' : 'light';
    document.documentElement.setAttribute('data-theme', newTheme);
    localStorage.setItem('scolaria_theme', newTheme);
}
```

## 🚀 Utilisation

### 1. Intégration de base

Inclure les fichiers CSS et JS dans votre page :

```html
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/components.css">
<script src="/assets/js/main.js"></script>
```

### 2. Utilisation du layout

Utiliser le template de base pour toutes les pages :

```php
<?php
$pageTitle = 'Mon titre';
$currentPage = 'dashboard';

ob_start();
?>
<!-- Contenu de votre page -->
<?php
$content = ob_get_clean();
include 'layout/base.php';
?>
```

### 3. Composants

Inclure et utiliser les composants :

```php
require_once 'components/stats-card.php';
require_once 'components/data-table.php';

// Utiliser les fonctions render*()
```

## 📖 Exemples

### Page de dashboard complète

Voir `dashboard-modern.php` pour un exemple complet incluant :

- Cartes de statistiques
- Graphiques Chart.js
- Tableaux interactifs
- Alertes
- Actions rapides

### Page de connexion

Voir `login-modern.php` pour :

- Formulaire moderne
- Validation JavaScript
- Design responsive
- Animations

### Notifications

```javascript
// Afficher une notification
showNotification('Opération réussie !', 'success', 5000);

// Types : success, error, warning, info
```

### Modales

```javascript
// Ouvrir/fermer une modale
openModal('myModal');
closeModal('myModal');
```

## 🎯 Bonnes pratiques

### CSS

- Utiliser les variables CSS pour la cohérence
- Respecter la hiérarchie des composants
- Optimiser pour les performances

### JavaScript

- Utiliser les fonctions utilitaires fournies
- Respecter les conventions de nommage
- Gérer les états de chargement

### PHP

- Séparer la logique de la présentation
- Utiliser les composants réutilisables
- Échapper les données utilisateur

### Accessibilité

- Utiliser les attributs ARIA appropriés
- Assurer un contraste suffisant
- Supporter la navigation clavier

## 🔧 Personnalisation

### Variables CSS

Modifier les variables dans `:root` pour personnaliser :

```css
:root {
    --primary-color: #your-color;
    --border-radius: 8px;
    --sidebar-width: 300px;
}
```

### Thèmes personnalisés

Créer un nouveau thème :

```css
[data-theme="custom"] {
    --primary-color: #custom-primary;
    --bg-primary: #custom-background;
}
```

## 🐛 Dépannage

### Problèmes courants

1. **Sidebar ne s'affiche pas** : Vérifier l'inclusion de `main.js`
2. **Dark mode ne fonctionne pas** : Vérifier les variables CSS
3. **Tableaux non responsives** : Ajouter la classe `table-container`

### Support

Pour toute question ou problème :

1. Consulter cette documentation
2. Vérifier les exemples fournis
3. Examiner la console du navigateur

## 📝 Changelog

### Version 1.0.0
- Design system complet
- Composants de base
- Dark mode
- Responsive design
- Documentation complète

---

**Développé avec ❤️ pour Scolaria (Team589)**
