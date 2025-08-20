<?php
/**
 * SCOLARIA - Page de Connexion Moderne
 * Interface de connexion avec le nouveau design system
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

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

                header('Location: ' . BASE_URL . 'dashboard-modern.php');
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
?>
<!DOCTYPE html>
<html lang="fr" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - Scolaria</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
    
    <!-- CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    
    <!-- Styles spécifiques à la page de connexion -->
    <style>
        .auth-wrapper {
            background: linear-gradient(135deg, #1E88E5 0%, #43A047 100%);
            position: relative;
            overflow: hidden;
        }
        
        .auth-wrapper::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(circle at 20% 80%, rgba(255, 255, 255, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 255, 255, 0.1) 0%, transparent 50%);
        }
        
        .auth-card {
            position: relative;
            z-index: 2;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .auth-features {
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-light);
        }
        
        .feature-list {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .feature-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        
        .feature-item i {
            color: var(--success-color);
            font-size: 1rem;
        }
        
        .demo-credentials {
            background: rgba(30, 136, 229, 0.1);
            border: 1px solid rgba(30, 136, 229, 0.2);
            border-radius: var(--border-radius-sm);
            padding: 1rem;
            margin-top: 1.5rem;
        }
        
        .demo-credentials h4 {
            margin: 0 0 0.5rem 0;
            font-size: 0.9rem;
            color: var(--primary-color);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .demo-credentials p {
            margin: 0.25rem 0;
            font-size: 0.8rem;
            color: var(--text-muted);
        }
        
        .demo-credentials code {
            background: rgba(0, 0, 0, 0.1);
            padding: 0.2rem 0.4rem;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            font-size: 0.8rem;
        }
        
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            padding: 0.25rem;
            transition: color var(--transition-fast);
        }
        
        .password-toggle:hover {
            color: var(--text-primary);
        }
        
        .form-group.password-field {
            position: relative;
        }
        
        .form-group.password-field input {
            padding-right: 3rem;
        }
        
        @media (max-width: 480px) {
            .feature-list {
                grid-template-columns: 1fr;
            }
            
            .auth-wrapper {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
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
                
                <button type="submit" class="btn btn-primary btn-lg w-full">
                    <i class="fas fa-sign-in-alt"></i>
                    Se connecter
                </button>
            </form>
            
            <!-- Liens utiles -->
            <div class="auth-links">
                <a href="/forgot-password.php" class="auth-link">
                    <i class="fas fa-question-circle"></i>
                    Mot de passe oublié ?
                </a>
                <a href="/register.php" class="auth-link">
                    <i class="fas fa-user-plus"></i>
                    Créer un compte
                </a>
            </div>
            
            <!-- Informations de démonstration -->
            <div class="demo-credentials">
                <h4>
                    <i class="fas fa-info-circle"></i>
                    Compte de démonstration
                </h4>
                <p><strong>Utilisateur :</strong> <code>admin</code></p>
                <p><strong>Mot de passe :</strong> <code>admin123</code></p>
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
                        Rapports détaillés
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Interface moderne
                    </div>
                    <div class="feature-item">
                        <i class="fas fa-check"></i>
                        Mode sombre
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="/assets/js/main.js"></script>
    <script>
        // Fonction pour basculer la visibilité du mot de passe
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const passwordIcon = document.getElementById('passwordIcon');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                passwordIcon.className = 'fas fa-eye-slash';
            } else {
                passwordInput.type = 'password';
                passwordIcon.className = 'fas fa-eye';
            }
        }
        
        // Validation du formulaire
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            
            if (!username || !password) {
                e.preventDefault();
                showNotification('Veuillez remplir tous les champs', 'error');
                return false;
            }
            
            if (password.length < 6) {
                e.preventDefault();
                showNotification('Le mot de passe doit contenir au moins 6 caractères', 'error');
                return false;
            }
            
            // Animation de chargement
            const submitButton = e.target.querySelector('button[type="submit"]');
            submitButton.classList.add('loading');
            submitButton.disabled = true;
        });
        
        // Auto-focus sur le premier champ
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('username').focus();
        });
        
        // Raccourci clavier pour la connexion
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                document.getElementById('loginForm').submit();
            }
        });
        
        // Animation d'entrée
        document.addEventListener('DOMContentLoaded', function() {
            const authCard = document.querySelector('.auth-card');
            authCard.classList.add('animate-slide-in');
        });
    </script>
    
    <!-- Styles supplémentaires pour les éléments de formulaire -->
    <style>
        .auth-subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }
        
        .form-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 500;
        }
        
        .form-label i {
            width: 16px;
            color: var(--primary-color);
        }
        
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .checkbox-label input[type="checkbox"] {
            width: auto;
            margin: 0;
        }
        
        .auth-links {
            display: flex;
            justify-content: space-between;
            margin-top: 1.5rem;
            gap: 1rem;
        }
        
        .auth-link {
            color: var(--primary-color);
            text-decoration: none;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all var(--transition-fast);
        }
        
        .auth-link:hover {
            text-decoration: underline;
            transform: translateY(-1px);
        }
        
        .btn.loading {
            position: relative;
            color: transparent;
        }
        
        .btn.loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @media (max-width: 480px) {
            .auth-links {
                flex-direction: column;
                text-align: center;
            }
        }
    </style>
</body>
</html>
