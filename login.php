<?php
/**
 * SCOLARIA - Page de Connexion Moderne
 * Interface de connexion avec le nouveau design system
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Configuration de la page
$currentPage = 'login';
$pageTitle = 'Connexion - Scolaria';
$showSidebar = false;
$additionalCSS = ['assets/css/auth.css'];
$additionalJS = ['assets/js/auth.js'];
$bodyClass = 'login-page';

// Traitement du formulaire de connexion
$error = '';
$success = '';

// Cookies de session sécurisés (alignés avec login.php)
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Redirection si déjà connecté
if (!empty($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if ($username === '' || $password === '') {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare('SELECT id, username, password, role FROM users WHERE username = :username LIMIT 1');
            $stmt->bindValue(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user && is_string($user['password']) && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['username'] = (string) $user['username'];
                $_SESSION['role'] = (string) $user['role'];

                if ($remember) {
                    // Étendre la durée du cookie de session (30 jours)
                    setcookie(session_name(), session_id(), time() + (30 * 24 * 60 * 60), '/', '', isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on', true);
                }

                header('Location: ' . BASE_URL . 'dashboard.php');
                exit;
            }

            $error = 'Identifiants invalides. Veuillez réessayer.';
        } catch (Throwable $e) {
            if (defined('APP_ENV') && APP_ENV === 'dev') {
                error_log('Login error: ' . $e->getMessage());
            }
            $error = 'Une erreur est survenue. Merci de réessayer plus tard.';
        }
    }
}

// Début du contenu HTML
ob_start();
?>

<div class="auth-wrapper">
    <div class="auth-card">
            <!-- Logo et titre -->
            <div class="auth-header">
                <div class="auth-logo">
                    <i class="fas fa-graduation-cap"></i>
                    SCOLARIA
                </div>
                <h2 class="auth-title">Connexion</h2>
                <p class="auth-subtitle">Accédez à votre espace de gestion scolaire</p>
            </div>
            
            <!-- Messages d'erreur/succès -->
            <?php if ($error): ?>
                <div class="alert alert-error animate-slide-in">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success animate-slide-in">
                    <i class="fas fa-check-circle"></i>
                    <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>
            
            <!-- Formulaire de connexion -->
            <form class="auth-form" method="POST" action="" id="loginForm">
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i>
                        Nom d'utilisateur
                    </label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        class="form-control" 
                        placeholder="Entrez votre nom d'utilisateur"
                        value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                        required
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group password-field">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i>
                        Mot de passe
                    </label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        class="form-control" 
                        placeholder="Entrez votre mot de passe"
                        required
                        autocomplete="current-password"
                    >
                    <button type="button" class="password-toggle" onclick="togglePassword()">
                        <i class="fas fa-eye" id="passwordIcon"></i>
                    </button>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember" id="remember">
                        <span class="checkmark"></span>
                        Se souvenir de moi
                    </label>
                </div>
                
                <button type="submit" class="btn btn-primary btn-auth">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            
            <!-- Liens utiles -->
            <div class="auth-links">
                <a href="forgot-password.php" class="auth-link">
                    <i class="fas fa-question-circle"></i>
                    Mot de passe oublié ?
                </a>
                <a href="register.php" class="auth-link">
                    <i class="fas fa-user-plus"></i>
                    Créer un compte
                </a>
            </div>
            
            <!-- Informations de démonstration -->
            <div class="demo-credentials">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    Comptes de démonstration
                </h4>
                <p><strong>Administrateur :</strong> <code>admin</code> / <code>admin123</code></p>
                <p><strong>Gestionnaire :</strong> <code>manager</code> / <code>manager123</code></p>
                <p><strong>Utilisateur :</strong> <code>user</code> / <code>user123</code></p>
            </div>
                                  
            <!-- Fonctionnalités -->
            <div class="auth-features">
                <h4>Fonctionnalités de Scolaria</h4>
                <div class="feature-list">
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Gestion des stocks
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Alertes automatiques
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Suivi financier
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Commandes fournisseurs
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Rapports financiers
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Mode sombre
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
$content = ob_get_clean();

// Inclure le layout d'authentification
include __DIR__ . '/layout/auth.php';
?>
