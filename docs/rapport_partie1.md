# SOMMAIRE
XXXXXXXXXXXx

# DEDICACE
XXXXXXXXXXXx

# REMERCIEMENTS
XXXXXXXXXXXx

# RESUME
Ce projet tutoré présente Scolaria, une application web de gestion de point de vente et des stocks, intégrant la gestion des dépenses, des rapports et un système d’alertes et de notifications. L’objectif est de démontrer la conception et la mise en œuvre d’un système transactionnel fiable pour la vente, la surveillance des stocks et l’aide à la décision.

Méthodologie: analyse des besoins, modélisation (diagrammes Mermaid), conception technique (PHP/MySQL, JS, Bootstrap), implémentation incrémentale, tests sur des scénarios réels (vente, réapprovisionnement, rapports), et documentation.

Résultats: une application fonctionnelle avec authentification par rôles, un flux POS transactionnel (calcul des totaux et profit, facture), alertes automatiques de seuil, mécanisme de réapprovisionnement, gestion des dépenses et tableaux de bord. Les diagrammes fournis documentent les cas d’usage, la séquence POS, l’authentification et les classes.

Implications: sur le plan managérial, Scolaria améliore la visibilité sur les stocks et les finances, favorise la réactivité (alertes) et standardise les processus de vente et de réapprovisionnement. Conceptuellement, le projet illustre l’application de transactions et de verrous au niveau BD pour garantir la cohérence des stocks.

# ABSTRACT
This tutor project presents Scolaria, a web application for point-of-sale and inventory management, including expenses, reporting, alerts and notifications. The objective is to demonstrate the design and implementation of a reliable transactional system for sales, stock monitoring and decision support.

Methodology: requirements analysis, modeling (Mermaid diagrams), technical design (PHP/MySQL, JS, Bootstrap), incremental implementation, scenario-based testing (sales, replenishment, reporting), and documentation.

Results: a working application with role-based authentication, transactional POS flow (totals and profit, invoice), automatic threshold alerts, replenishment mechanism, expense management and dashboards. The provided diagrams document use cases, POS sequence, authentication and classes.

Implications: managerially, Scolaria improves visibility into inventory and finances, enhances responsiveness (alerts) and standardizes sales and replenishment processes. Conceptually, it shows the application of database transactions and row locks to guarantee stock consistency.

# SIGLES ET ABREVIATIONS
- POS: Point of Sale (Point de vente)
- BD: Base de Données
- UI: Interface Utilisateur
- HTTP: HyperText Transfer Protocol
- JSON: JavaScript Object Notation
- PDF: Portable Document Format

# 0. INTRODUCTION GENERALE
## 0.1. Introduction
XXXXXXXXXXXx

## 0.2. Contexte
Le commerce de détail et la gestion d’inventaire exigent des outils permettant de maîtriser la chaîne « achat-stock-vente ». Les petites et moyennes structures manquent souvent de solutions simples mais robustes combinant vente, suivi des seuils, finances et notifications en un seul outil.

## 0.3. Problématique
Comment concevoir et implémenter une application web intégrée permettant:
- la vente sécurisée et traçable;
- la surveillance des niveaux de stock avec alertes automatiques;
- le réapprovisionnement fournisseur;
- la consolidation des données financières et l’édition de rapports,
le tout avec un contrôle d’accès par rôles?

## 0.4. Objectifs
- Objectif global: Concevoir et réaliser une application intégrée de gestion de point de vente et des stocks avec support décisionnel.
- Objectifs spécifiques:
  - Implémenter un POS transactionnel et la génération de factures.
  - Surveiller les stocks et déclencher des alertes de seuil.
  - Faciliter le réapprovisionnement auprès des fournisseurs.
  - Gérer les dépenses et synthétiser les indicateurs clés.
  - Mettre en place l’authentification et les autorisations par rôles.

## 0.5. Résultats attendus à la fin du projet
- Application web opérationnelle couvrant vente, stocks, finances, notifications.
- Diagrammes de modélisation (cas d’usage, séquences, classes, authentification).
- Jeu de rapports et exports; journaux applicatifs pour traçabilité.

## 0.6. Diffusion et valorisation des résultats issus du projet mené
- Démonstration de l’application auprès des parties prenantes.
- Documentation et guide d’utilisation; diffusion académique (rapport, soutenance).
- Réutilisation pédagogique des diagrammes et du code comme base d’étude.

## 0.7. Choix et intérêt du thème choisi
- Scientifique: met en pratique transactions BD, verrous, contrôles d’accès, et modélisation.
- Pratique: apporte un outil simple pour fluidifier la vente, sécuriser les stocks et éclairer les décisions.

## 0.8. Délimitation du travail (Spatiale et temporaire)
- Spatiale: périmètre applicatif interne (vente au comptoir, back-office gestion).
- Temporelle: réalisation incrémentale sur la période du projet; données de test.

## 0.9. Subdivision du travail
Le document est structuré en quatre chapitres: définitions et contexte; revue de la littérature et méthodes; méthodologie et résultats; discussion et recommandations.

## 0.10. Difficultés rencontrées
A compléter après la présentation des résultats (ex.: contraintes matérielles, qualité des données, itérations de conception, délais).

# Chapitre I : DEFINITION DES THEMES & CONCEPTS DE L’ETUDE
## INTRODUCTION
Ce chapitre fixe les notions et le cadre conceptuel utilisés, et situe l’étude dans son environnement applicatif.

## SECTION 1 : DEFINITION DES TERMES & CONCEPTS
- Gestion de stock: ensemble des méthodes visant à maintenir des niveaux de stock optimaux (seuils, réapprovisionnement, rotation).
- Point de vente (POS): interface et processus de vente au comptoir intégrant panier, calculs et paiement.
- Transaction BD: unité atomique d’opérations (BEGIN/COMMIT/ROLLBACK) garantissant la cohérence des données.
- Alerte de seuil: notification déclenchée lorsque la quantité d’un article passe sous un seuil défini.
- Authentification/Autorisation: vérification de l’identité de l’utilisateur et contrôle de l’accès par rôle.
Références générales: manuels de bases de données (transactions), bonnes pratiques de sécurité web (OWASP), documentation PHP (sessions, password_hash).

## SECTION 2 : LE CONTEXTE DE L’ETUDE
Scolaria cible un environnement de vente au détail où la fiabilité des stocks et la rapidité de la vente sont essentielles. Le besoin principal est d’éviter les ruptures, de fiabiliser les totaux de caisse, et de produire des rapports simples pour le pilotage.

## CONCLUSION
Ce cadre conceptuel et contextuel prépare la revue de la littérature et la présentation des approches méthodologiques retenues pour Scolaria.



