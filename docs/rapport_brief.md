# Scolaria — Brief complet pour rédacteur (rapport de soutenance)

Ce document fournit une description claire et structurée du projet Scolaria pour aider à la rédaction d’un rapport de soutenance. Il couvre la vision, l’architecture, les modules, les flux critiques (dont l’authentification et le POS), la base de données, la sécurité, l’exploitation et les annexes (diagrammes).

## 1. Vision et objectifs
- **But du projet**: Scolaria est une application de gestion de point de vente et de stocks, avec suivi des finances, alertes de seuil, rapports et notifications.
- **Public cible**: Caissiers, gestionnaires, administrateurs et direction.
- **Objectifs principaux**:
  - Accélérer la vente au comptoir (POS) et la facturation.
  - Suivre les stocks, déclencher des alertes et gérer le réapprovisionnement.
  - Consolider les finances (dépenses, transactions, bénéfice net) et produire des rapports.
  - Notifier en temps réel les événements importants (stocks, système).

## 2. Périmètre fonctionnel (modules)
- **Authentification & rôles**: Connexion, déconnexion, inscription, contrôle d’accès par rôles (admin, gestionnaire, caissier, directeur).
- **Point de vente (POS)**: Création de ventes, calcul des totaux/remises, génération de facture.
- **Stocks & alertes**: Seuils d’alerte, détection du low/out-of-stock, réapprovisionnement fournisseur.
- **Finances**: Dépenses, transactions liées aux ventes, indicateurs de performance.
- **Rapports**: Exports (PDF), tableaux de bord et statistiques consolidées.
- **Notifications**: Centre de notifications, lecture/lecture-toutes, vérifications périodiques (cron).
- **Profil utilisateur**: Mise à jour profil et avatar.

## 3. Architecture technique
- **Pile technologique**:
  - Backend: PHP (pages procédurales structurées), PDO/MySQL.
  - Frontend: HTML/CSS (design system local), JavaScript Vanilla.
  - Librairies: Bootstrap, Font Awesome, Chart.js, Mermaid CLI (docs), Pandoc (docs).
- **Organisation du code**:
  - Pages principales: racine du projet (`*.php`), ex. `dashboard.php`, `pos.php`, `alerts.php`, `finances.php`, `reports.php`.
  - Configuration/DB: `config/config.php`, `config/db.php` (classe `Database`), `config/auth.php` (helpers auth).
  - Modules réutilisables: `includes/notification_system.php`, `includes/get_notifications.php`, `includes/stock_monitor.php`.
  - Composants UI: `components/` (ex. `stats-card.php`, `data-table.php`).
  - Assets: `assets/css/`, `assets/js/` (scripts par module: `auth.js`, `stocks.js`, `profile.js`, etc.).
  - Diagrammes et docs: `docs/diagrams/`.
  - Données: `uploads/` (avatars), `logs/` (journaux), `sql/` (schéma).

## 4. Contrôle d’accès et authentification
- **Fichiers clés**: `login.php`, `logout.php`, `register.php`, `config/auth.php`.
- **Connexion**:
  - `login.php` vérifie l’utilisateur (`SELECT ... FROM users WHERE username = ?`) et compare le hash via `password_verify`.
  - En cas de succès: `session_regenerate_id(true)`, `$_SESSION[user_id, username, role]`, option « remember-me » (durée cookie étendue), redirection selon rôle.
- **Rôles et guards**: `config/auth.php` expose `require_login()` et `require_roles([...])`.
  - Les pages protégées vérifient la session; en cas d’échec, redirection vers `login.php`.
  - Si rôle insuffisant: redirection vers `access_denied.php`.
- **Déconnexion**: `logout.php` vide la session, supprime le cookie et redirige vers `login.php`.
- **Inscription**: `register.php` crée un compte avec `password_hash`, validations et unicité `username`/`email`.

Référence diagramme: `docs/diagrams/authentication.mmd` → `authentication.png`.

## 5. Point de vente (POS)
- **Fichier**: `pos.php` (+ `assets/js/main.js`/`stocks.js` pour l’UI) gère le flux de checkout.
- **Processus**:
  - Préparation panier (articles, quantités, client, remises, mode de paiement).
  - Soumission AJAX: le backend ouvre une transaction, verrouille les stocks (SELECT FOR UPDATE), met à jour `stocks`, insère `sales`, `sales_items`, `transactions`.
  - Calcul du total et du profit, génération lien facture (`invoice.php`).
- **Alertes de stock**: lors du checkout, si seuil atteint, création d’alertes.

Référence diagramme: `docs/diagrams/sequence-pos-checkout.mmd` → `sequence-pos-checkout.png`.

## 6. Stocks, alertes et réapprovisionnement
- **Fichier**: `alerts.php` (gestion avancée des alertes, création de commandes), `includes/stock_monitor.php` (surveillance), `cron_check_stock_alerts.php` (vérification périodique).
- **Seuils**: chaque article possède un seuil déclenchant une alerte « low_stock »/« out_of_stock ».
- **Réapprovisionnement**:
  - Un gestionnaire crée une commande fournisseur depuis une alerte.
  - À la réception, les quantités `stocks` sont mises à jour, les alertes sont résolues/ajustées.

Référence diagramme: `docs/diagrams/sequence-supplier-reorder.mmd` → `sequence-supplier-reorder.png`.

## 7. Finances et rapports
- **Finances**: `finances.php`, `admin_depenses.php` pour la gestion des dépenses; les ventes alimentent `transactions`.
- **Tableau de bord**: `dashboard*.php` affichent statistiques, cartes, graphiques (Chart.js) et exposent des endpoints JSON.
- **Rapports**: `reports.php`, `reports_export_pdf.php` (exports PDF/CSV selon implémentation). Les métriques clés: chiffre d’affaires, profit, dépenses, bénéfice net.

## 8. Notifications
- **Fichiers**: `includes/notification_system.php` (création/format), `includes/get_notifications.php`, `includes/mark_notification_read.php`, `includes/mark_all_notifications_read.php`.
- **UI**: `assets/js/header-notifications.js`, styles `assets/css/header-notifications.css`.
- **Fonctionnement**:
  - Back-end crée des notifications (ex. stock bas, erreurs système).
  - Front-end récupère périodiquement, affiche badge et liste, permet de marquer comme lues.

## 9. Base de données
- **Schéma source**: `sql/scolaria.sql` (base MySQL).
- **Tables principales (indicatif)**:
  - `users`: comptes (username, email, password hash, role, status, avatar_path...).
  - `stocks`: articles, quantités, seuil_alerte, prix (vente/achat).
  - `sales` / `sales_items`: ventes (en-tête) et lignes; lien avec `clients` si présent.
  - `transactions`: enregistrements financiers liés aux ventes.
  - `alertes`: événements de seuil; éventuellement `fournisseurs`, `orders` pour réapprovisionnement.
- **Intégrité**:
  - Transactions lors du checkout; verrous de lignes pour cohérence stock.
  - Index sur clés de recherche (username, article_id...).

## 10. Sécurité et qualité
- **Sessions**: cookies `httponly`, `samesite=Lax`, `secure` si HTTPS; `session_regenerate_id(true)` à la connexion.
- **Mots de passe**: `password_hash`/`password_verify` (algo par défaut).
- **Validation**: validations serveur pour formulaires (login/register/admin utilisateurs).
- **Journaux**: `logs/php_errors.log`, `logs/access.log`, `logs/stock_alerts.log`, `logs/auto_increment_reset.log`.
- **Accès**: guards `require_login`/`require_roles`, page `access_denied.php`.
- **Fichiers upload**: avatars dans `uploads/profiles`, contrôles de type/taille.

## 11. Déploiement et exécution
- **Prérequis**:
  - PHP 8+ recommandé, extensions PDO/MySQL.
  - MySQL/MariaDB.
  - Serveur web (Apache/Nginx) configuré.
- **Installation**:
  1) Importer `sql/scolaria.sql` (schéma et données de base si incluses).
  2) Configurer `config/config.php` (BASE_URL, APP_ENV) et `config/db.php` (connexion DB).
  3) Vérifier droits d’écriture sur `logs/` et `uploads/`.
  4) Démarrer l’application via le serveur web.
- **Comptes**: créer un premier admin via `register.php` (puis rebasculer l’accès), ou via `users_admin.php`.
- **Tâches planifiées**: activer `cron_check_stock_alerts.php` pour surveiller en continu les seuils.

## 12. Génération des diagrammes et documentation
- **Dossier**: `docs/diagrams/`.
- **Mermaid → PNG**:
  - `mmdc -i docs/diagrams/use-cases.mmd -o docs/diagrams/use-cases.png`
  - `mmdc -i docs/diagrams/sequence-pos-checkout.mmd -o docs/diagrams/sequence-pos-checkout.png`
  - `mmdc -i docs/diagrams/sequence-supplier-reorder.mmd -o docs/diagrams/sequence-supplier-reorder.png`
  - `mmdc -i docs/diagrams/classes.mmd -o docs/diagrams/classes.png`
  - `mmdc -i docs/diagrams/authentication.mmd -o docs/diagrams/authentication.png`
- **Document Markdown → Word**:
  - `pandoc docs/diagrams/diagrams.md -o docs/diagrams/diagrams.docx`
- **Références**: `docs/diagrams/README.md` explique les prérequis (Graphviz, mermaid-cli, pandoc).

## 13. Itinéraires utilisateurs (résumé)
- **Caissier**: se connecte → POS → scan articles → applique remise → valide → imprimer facture.
- **Gestionnaire**: consulte alertes → crée commande fournisseur → réceptionne → ajuste seuils.
- **Admin**: gère utilisateurs/dépenses → surveille transactions → exporte rapports.
- **Directeur**: consulte tableaux de bord/rapports, reçoit notifications clés.

## 14. Points forts et limites
- **Forces**: flux POS transactionnel robuste, alertes de stock intégrées, séparation claire des rôles, documentation avec diagrammes.
- **Limites**: pages PHP procédurales (pas de framework), API/endpoint limités au périmètre, notifications basiques.
- **Améliorations futures**: API REST, tests automatisés, 2FA, gestion de rôles plus fine, refonte MVC ou microservices.

## 15. Annexes — Cartes vers le code
- **Authentification**: `login.php`, `logout.php`, `register.php`, `config/auth.php`.
- **POS**: `pos.php`, `invoice.php`.
- **Stocks/Alertes**: `alerts.php`, `includes/stock_monitor.php`, `cron_check_stock_alerts.php`.
- **Finances/Rapports**: `finances.php`, `admin_depenses.php`, `reports.php`, `reports_export_pdf.php`.
- **Notifications**: `includes/notification_system.php`, `includes/get_notifications.php`, `assets/js/header-notifications.js`.
- **UI/Composants**: `components/`, `assets/css/`, `assets/js/`.
- **Logs & Uploads**: `logs/`, `uploads/`.

---

Ce brief est conçu pour être remis tel quel à un rédacteur. Il peut s’appuyer sur les diagrammes générés pour illustrer le rapport final et utiliser les chemins de fichiers pour citer précisément les implémentations.



