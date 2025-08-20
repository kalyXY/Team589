# Scolaria - Structure MVC minimale (PHP)

## Arborescence

```
config/
  config.php        # Constantes appli, placeholders DB
  db.php            # Connexion PDO sécurisée
controllers/
  BaseController.php
  HomeController.php
models/
  BaseModel.php
  ExampleModel.php
views/
  home.php          # Page d’accueil
assets/
  css/style.css
  js/app.js
index.php           # Routeur frontal
```

## Configuration base de données

Éditez `config/config.php` et remplacez les placeholders par vos paramètres MySQL :

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'scolaria');
define('DB_USER', 'root');
define('DB_PASS', 'password');
```

Le DSN utilise `utf8mb4`, `PDO::ATTR_EMULATE_PREPARES=false` pour prévenir les injections SQL.

Optionnel : si l’application est servie depuis un sous-dossier, définissez `BASE_URL` (ex : `'/scolaria/'`).

## Lancer en local (XAMPP/WAMP)

1. Copiez le dossier du projet dans votre répertoire web :
   - XAMPP : `C:\\xampp\\htdocs\\Scolaria`
   - WAMP : `C:\\wamp64\\www\\Scolaria`
2. Démarrez Apache et MySQL depuis le panneau XAMPP/WAMP.
3. Créez la base si nécessaire :
   - via phpMyAdmin : créez la DB `scolaria`
4. Mettez à jour `config/config.php` avec vos identifiants.
5. Ouvrez le navigateur :
   - XAMPP : `http://localhost/Scolaria/`
   - WAMP : `http://localhost/Scolaria/`

Vous devriez voir « Bienvenue sur Scolaria ».

## Sécurité minimale

- Connexion PDO avec erreurs en exceptions et requêtes préparées par défaut.
- Paramètres d’URL `c`/`a` filtrés (alphanum/underscore) et échappés.
- Sortie HTML échappée via `htmlspecialchars`.

## Modèle Waterfall (organisation)

- Spécification et conception: cette structure MVC de base.
- Implémentation: controllers/models/views séparés.
- Vérification: test manuel de la page d’accueil.
- Maintenance: ajoutez de nouveaux contrôleurs, modèles, vues selon les besoins.


