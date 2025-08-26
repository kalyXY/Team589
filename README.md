# Scolaria – Gestion des Stocks et Finances scolaires

Application PHP (monolith) pour la gestion des stocks, ventes (POS), alertes de réapprovisionnement, dépenses et indicateurs financiers. Pensée pour un déploiement local (Laragon/WAMP/XAMPP) avec MySQL/MariaDB.

## Fonctionnalités
- POS (Point of Sale) avec recherche, panier, remises, modes de paiement, impression de facture.
- Calcul d’intérêt (profit) par vente et agrégation du bénéfice net mensuel/annuel.
- Alertes de stock (faible/rupture), seuils configurables, réapprovisionnement fournisseurs et réception.
- Dépenses: CRUD, catégories, export PDF/CSV, rapports et budgets.
- Notifications système (en-tête) et lecture/« tout marquer comme lues ».
- Tableaux de bord par rôle (admin/gestionnaire/caissier/directeur).

## Pile technique
- PHP 8+, MySQL/MariaDB (PDO), HTML/CSS/JS (vanilla), Chart.js
- Session/ACL simples dans `config/auth.php`

## Structure du projet (extraits)
```
config/           # Configuration app et DB
controllers/      # BaseController + HomeController (rendu views)
layout/           # Layouts principaux (base et auth)
includes/         # Endpoints notifications & helpers
components/       # Composants UI (stats, tables)
assets/css|js/    # Styles et scripts (POS, notifications, profils, etc.)
views/            # Vues (ex. home)
sql/              # Schéma base de données (scolaria.sql)
docs/diagrams/    # Diagrammes Mermaid + PNG + script génération
```

## Prérequis
- PHP 8.0+
- MySQL/MariaDB (10.4+ conseillé)
- Serveur web local (Laragon/WAMP/XAMPP) ou PHP built-in server
- Optionnel (diagrammes): Node.js + `@mermaid-js/mermaid-cli`, Graphviz, Pandoc

## Installation & configuration
1) Cloner/copier le projet dans votre environnement web (ex. `C:/laragon/www/scolaria`).
2) Créer la base de données et importer le schéma:
   - Ouvrir `sql/scolaria.sql` dans phpMyAdmin ou via CLI, exécuter l’ensemble du script.
3) Configuration applicative:
   - Éditer `config/config.php` si besoin (hôte/port, base, options comme `ENABLE_CSRF_PROTECTION`).
   - Les identifiants DB par défaut sont adaptés à Laragon: `root` sans mot de passe.
4) Démarrer le serveur web (Laragon/WAMP/XAMPP) et naviguer vers `http://localhost/scolaria/`.

## Lancement rapide (serveur PHP intégré)
Depuis la racine du projet:
```
php -S localhost:8000
```
Puis ouvrir `http://localhost:8000/` (redirection vers `login.php`).

## Base de données – tables clés
- `stocks`: articles, prix_achat, prix_vente, quantite, seuil_alerte
- `sales`, `sales_items`: ventes et lignes; `transactions`: paiements
- `clients`, `users`
- `alertes`, `commandes`, `fournisseurs`, `mouvements`
- `depenses`, `categories`, `budgets`
- `notifications`

Le schéma complet est dans `sql/scolaria.sql` (avec vues d’exemple pour rapports).

## Modules principaux
- POS (`pos.php`)
  - Recherche produits AJAX, panier, remises (%/montant), validation de vente.
  - Décrémente le stock, crée alertes si seuil atteint, enregistre transactions.
  - Calcule le profit (total − somme des coûts d’achat) et le renvoie au front.
- Alertes & Réapprovisionnement (`alerts.php`)
  - Liste des articles en alerte, modification des seuils.
  - Commandes fournisseurs et mise à jour de stock à la réception (mouvements).
- Finances (`finances.php`)
  - Dépenses (CRUD), catégories, budgets, exports PDF/CSV.
  - Indicateurs: dépenses/recettes/profit ventes, bénéfice net (mois/année).
- Notifications (`includes/` + `assets/js/header-notifications.js`)
  - Récupération, marquage lu/tout lu, badge en-tête, toasts.

## Comptes utilisateurs
Le script SQL insère quelques utilisateurs par défaut (voir `sql/scolaria.sql`, table `users`).
Adaptez/ajoutez vos utilisateurs depuis l’interface dédiée si présente, ou directement en DB.

## Diagrams (docs/diagrams)
Sources Mermaid + PNG générables:
- `use-cases.mmd` → `use-cases.png`
- `sequence-pos-checkout.mmd` → `sequence-pos-checkout.png`
- `sequence-supplier-reorder.mmd` → `sequence-supplier-reorder.png`
- `classes.mmd` → `classes.png`

Génération automatique (Windows/PowerShell):
```
cd docs/diagrams
powershell -ExecutionPolicy Bypass -File .\generate-doc.ps1
```
Si Pandoc n’est pas installé, le script génère au moins les PNG; vous pouvez insérer les images dans un document Word manuellement.

Dépendances optionnelles:
```
npm i -g @mermaid-js/mermaid-cli
```
Installez Graphviz pour de meilleurs rendus, et Pandoc pour produire `diagrams.docx`.

## Sécurité & bonnes pratiques
- Sessions sécurisées (`httponly`, `samesite=Lax`), redirections d’accès, rôles.
- Activez `ENABLE_CSRF_PROTECTION` en production et implémentez les tokens sur les formulaires.
- Validez et nettoyez les entrées côté serveur; limitez les messages d’erreur en prod.
- Surveillez les logs dans `logs/` (erreurs, accès, stocks/auto-incrément).

## Dépannage
- Erreur DB: vérifier `config/config.php` et que la base `scolaria` existe avec tables importées.
- Notifications vides: la table `notifications` est créée à la volée si absente (voir `includes/notification_system.php`).
- Profit à 0: renseignez `prix_achat` dans `stocks`.
- Génération de diagrammes: exécuter les commandes depuis `docs/diagrams/`.

## Licence
Projet interne/éducatif Team589. Adaptez la licence selon vos besoins.
