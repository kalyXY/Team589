<?php
/**
 * Page de profil utilisateur - Scolaria
 * Design professionnel et moderne
 */

session_start();

// Vérification de l'authentification
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Connexion à la base de données
$pdo = Database::getConnection();

// Récupération des informations de l'utilisateur
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: logout.php');
    exit;
}

// Gestion des formulaires
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'update_profile') {
            // Mise à jour du profil
            $full_name = trim($_POST['full_name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            
            // Validation
            if (empty($full_name)) {
                throw new Exception('Le nom complet est obligatoire');
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('Email invalide');
            }
            
            // Vérifier si l'email existe déjà (sauf pour l'utilisateur actuel)
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->rowCount() > 0) {
                throw new Exception('Cet email est déjà utilisé par un autre utilisateur');
            }
            
            // Mise à jour
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $user_id]);
            
            // Mettre à jour la session
            $_SESSION['full_name'] = $full_name;
            $_SESSION['email'] = $email;
            
            $message = 'Profil mis à jour avec succès !';
            $message_type = 'success';
            
            // Recharger les données utilisateur
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } elseif ($action === 'update_password') {
            // Mise à jour du mot de passe
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            // Validation
            if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                throw new Exception('Tous les champs sont obligatoires');
            }
            
            if ($new_password !== $confirm_password) {
                throw new Exception('Les nouveaux mots de passe ne correspondent pas');
            }
            
            if (strlen($new_password) < 6) {
                throw new Exception('Le nouveau mot de passe doit contenir au moins 6 caractères');
            }
            
            // Vérifier l'ancien mot de passe
            if (!password_verify($current_password, $user['password'])) {
                throw new Exception('Mot de passe actuel incorrect');
            }
            
            // Mettre à jour le mot de passe
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $message = 'Mot de passe mis à jour avec succès !';
            $message_type = 'success';
            
        } elseif ($action === 'update_avatar') {
            // Mise à jour de l'avatar
            if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['avatar'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                // Validation du fichier
                if (!in_array($file['type'], $allowed_types)) {
                    throw new Exception('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF');
                }
                
                if ($file['size'] > $max_size) {
                    throw new Exception('Fichier trop volumineux. Maximum 5MB');
                }
                
                // Créer le dossier uploads s'il n'existe pas
                $upload_dir = 'uploads/profiles/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Générer un nom de fichier unique
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $filename = 'u_' . time() . '_' . uniqid() . '.' . $extension;
                $filepath = $upload_dir . $filename;
                
                // Supprimer l'ancien avatar s'il existe
                if (!empty($user['avatar_path']) && file_exists($user['avatar_path'])) {
                    unlink($user['avatar_path']);
                }
                
                // Déplacer le nouveau fichier
                if (move_uploaded_file($file['tmp_name'], $filepath)) {
                    // Mettre à jour la base de données
                    $stmt = $pdo->prepare("UPDATE users SET avatar_path = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                    $stmt->execute([$filepath, $user_id]);
                    
                    // Mettre à jour la session et les données utilisateur
                    $_SESSION['avatar_path'] = $filepath;
                    $user['avatar_path'] = $filepath;
                    
                    $message = 'Avatar mis à jour avec succès !';
                    $message_type = 'success';
                } else {
                    throw new Exception('Erreur lors du téléchargement du fichier');
                }
            } else {
                throw new Exception('Aucun fichier sélectionné ou erreur de téléchargement');
            }
        }
        
    } catch (Exception $e) {
        $message = $e->getMessage();
        $message_type = 'error';
    }
}

// Page config
$currentPage = 'profile';
$pageTitle = 'Mon Profil';
$showSidebar = true;
$bodyClass = 'profile-page';
$additionalCSS = [
    'assets/css/profile.css'
];
$additionalJS = [
    'assets/js/profile.js'
];

// Préparer le contenu de la page
ob_start();
?>

<div class="profile-container">
    <!-- Header du profil -->
    <div class="profile-header">
        <div class="profile-cover">
            <div class="profile-avatar-container">
                <?php if (!empty($user['avatar_path']) && file_exists($user['avatar_path'])): ?>
                    <img src="<?= htmlspecialchars($user['avatar_path']) ?>" 
                         alt="Avatar" class="profile-avatar" id="profile-avatar">
                <?php else: ?>
                    <div class="profile-avatar-placeholder" id="profile-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                <?php endif; ?>
                
                <div class="avatar-upload-overlay">
                    <label for="avatar-upload" class="avatar-upload-btn">
                        <i class="fas fa-camera"></i>
                    </label>
                    <input type="file" id="avatar-upload" name="avatar" accept="image/*" style="display: none;">
                </div>
            </div>
            
            <div class="profile-info">
                <h1 class="profile-name"><?= htmlspecialchars($user['full_name']) ?></h1>
                <p class="profile-role">
                    <i class="fas fa-user-tag"></i>
                    <?= ucfirst(htmlspecialchars($user['role'])) ?>
                </p>
                <p class="profile-status">
                    <span class="status-badge status-<?= $user['status'] ?>">
                        <i class="fas fa-circle"></i>
                        <?= ucfirst(htmlspecialchars($user['status'])) ?>
                    </span>
                </p>
            </div>
        </div>
    </div>

    <!-- Messages d'alerte -->
    <?php if ($message): ?>
        <div class="alert-container">
            <div class="alert alert-<?= $message_type === 'success' ? 'success' : 'error' ?>">
                <div class="alert-icon">
                    <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-triangle' ?>"></i>
                </div>
                <div class="alert-content">
                    <p><?= htmlspecialchars($message) ?></p>
                </div>
                <button type="button" class="alert-close" onclick="this.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <!-- Contenu principal -->
    <div class="profile-content">
        <div class="profile-grid">
            <!-- Section Informations personnelles -->
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-user-edit"></i>
                    </div>
                    <div class="section-title">
                        <h3>Informations personnelles</h3>
                        <p>Gérez vos informations de base</p>
                    </div>
                </div>
                
                <div class="section-content">
                    <form method="POST" action="" class="profile-form">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="username">Nom d'utilisateur</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user input-icon"></i>
                                    <input type="text" id="username" value="<?= htmlspecialchars($user['username']) ?>" readonly>
                                </div>
                                <small class="form-help">Le nom d'utilisateur ne peut pas être modifié</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="role">Rôle</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-user-tag input-icon"></i>
                                    <input type="text" id="role" value="<?= ucfirst(htmlspecialchars($user['role'])) ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="full_name">Nom complet *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-id-card input-icon"></i>
                                <input type="text" id="full_name" name="full_name" 
                                       value="<?= htmlspecialchars($user['full_name']) ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input type="email" id="email" name="email" 
                                           value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Téléphone</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-phone input-icon"></i>
                                    <input type="tel" id="phone" name="phone" 
                                           value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="status">Statut</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-circle input-icon"></i>
                                    <input type="text" id="status" 
                                           value="<?= ucfirst(htmlspecialchars($user['status'])) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="created_at">Membre depuis</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-calendar input-icon"></i>
                                    <input type="text" id="created_at" 
                                           value="<?= date('d/m/Y', strtotime($user['created_at'])) ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i>
                                Mettre à jour le profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Section Sécurité -->
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div class="section-title">
                        <h3>Sécurité</h3>
                        <p>Protégez votre compte</p>
                    </div>
                </div>
                
                <div class="section-content">
                    <form method="POST" action="" class="profile-form">
                        <input type="hidden" name="action" value="update_password">
                        
                        <div class="form-group">
                            <label for="current_password">Mot de passe actuel *</label>
                            <div class="input-wrapper">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" id="current_password" name="current_password" required>
                                <button type="button" class="password-toggle" onclick="togglePassword('current_password')">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="new_password">Nouveau mot de passe *</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-key input-icon"></i>
                                    <input type="password" id="new_password" name="new_password" 
                                           minlength="6" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('new_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="password-strength" id="password-strength"></div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirmer le mot de passe *</label>
                                <div class="input-wrapper">
                                    <i class="fas fa-key input-icon"></i>
                                    <input type="password" id="confirm_password" name="confirm_password" required>
                                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-key"></i>
                                Changer le mot de passe
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Section Statistiques -->
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="section-title">
                        <h3>Statistiques</h3>
                        <p>Votre activité récente</p>
                    </div>
                </div>
                
                <div class="section-content">
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Dernière connexion</h4>
                                <p><?= isset($_SESSION['last_login']) ? date('d/m/Y H:i', strtotime($_SESSION['last_login'])) : 'N/A' ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Notifications</h4>
                                <p>
                                    <?php
                                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
                                    $stmt->execute([$user_id]);
                                    echo $stmt->fetchColumn();
                                    ?> non lues
                                </p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Membre depuis</h4>
                                <p><?= date('d/m/Y', strtotime($user['created_at'])) ?></p>
                            </div>
                        </div>
                        
                        <div class="stat-item">
                            <div class="stat-icon">
                                <i class="fas fa-user-check"></i>
                            </div>
                            <div class="stat-content">
                                <h4>Statut</h4>
                                <p class="status-badge status-<?= $user['status'] ?>">
                                    <?= ucfirst(htmlspecialchars($user['status'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Section Actions rapides -->
            <div class="profile-section">
                <div class="section-header">
                    <div class="section-icon">
                        <i class="fas fa-bolt"></i>
                    </div>
                    <div class="section-title">
                        <h3>Actions rapides</h3>
                        <p>Accès direct aux fonctionnalités</p>
                    </div>
                </div>
                
                <div class="section-content">
                    <div class="actions-grid">
                        <a href="dashboard.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-tachometer-alt"></i>
                            </div>
                            <div class="action-content">
                                <h4>Tableau de bord</h4>
                                <p>Voir les statistiques</p>
                            </div>
                            <div class="action-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <a href="settings.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-cog"></i>
                            </div>
                            <div class="action-content">
                                <h4>Paramètres</h4>
                                <p>Configurer l'application</p>
                            </div>
                            <div class="action-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <a href="notifications.php" class="action-card">
                            <div class="action-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="action-content">
                                <h4>Notifications</h4>
                                <p>Gérer les alertes</p>
                            </div>
                            <div class="action-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                        
                        <a href="logout.php" class="action-card action-danger">
                            <div class="action-icon">
                                <i class="fas fa-sign-out-alt"></i>
                            </div>
                            <div class="action-content">
                                <h4>Déconnexion</h4>
                                <p>Fermer la session</p>
                            </div>
                            <div class="action-arrow">
                                <i class="fas fa-chevron-right"></i>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire caché pour l'upload d'avatar -->
<form method="POST" action="" enctype="multipart/form-data" id="avatar-form" style="display: none;">
    <input type="hidden" name="action" value="update_avatar">
    <input type="file" name="avatar" id="avatar-input" accept="image/*">
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de l'upload d'avatar
    const avatarUpload = document.getElementById('avatar-upload');
    const avatarInput = document.getElementById('avatar-input');
    const avatarForm = document.getElementById('avatar-form');
    
    avatarUpload.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            // Validation du fichier
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            const maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!allowedTypes.includes(file.type)) {
                alert('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.');
                return;
            }
            
            if (file.size > maxSize) {
                alert('Fichier trop volumineux. Maximum 5MB.');
                return;
            }
            
            // Prévisualisation
            const reader = new FileReader();
            reader.onload = function(e) {
                const avatar = document.getElementById('profile-avatar');
                if (avatar.tagName === 'IMG') {
                    avatar.src = e.target.result;
                } else {
                    // Remplacer l'icône par l'image
                    const newImg = document.createElement('img');
                    newImg.src = e.target.result;
                    newImg.alt = 'Avatar';
                    newImg.className = 'profile-avatar';
                    newImg.id = 'profile-avatar';
                    avatar.parentNode.replaceChild(newImg, avatar);
                }
            };
            reader.readAsDataURL(file);
            
            // Soumettre le formulaire
            avatarInput.files = e.target.files;
            avatarForm.submit();
        }
    });
    
    // Validation du mot de passe
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const strengthIndicator = document.getElementById('password-strength');
    
    function validatePassword() {
        if (newPassword.value !== confirmPassword.value) {
            confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    function checkPasswordStrength(password) {
        let score = 0;
        let feedback = [];
        
        if (password.length >= 8) score++;
        else feedback.push('Au moins 8 caractères');
        
        if (/[a-z]/.test(password)) score++;
        else feedback.push('Lettres minuscules');
        
        if (/[A-Z]/.test(password)) score++;
        else feedback.push('Lettres majuscules');
        
        if (/[0-9]/.test(password)) score++;
        else feedback.push('Chiffres');
        
        if (/[^A-Za-z0-9]/.test(password)) score++;
        else feedback.push('Caractères spéciaux');
        
        let strength = 'faible';
        let color = '#dc3545';
        
        if (score >= 4) {
            strength = 'fort';
            color = '#28a745';
        } else if (score >= 3) {
            strength = 'moyen';
            color = '#ffc107';
        }
        
        strengthIndicator.innerHTML = `
            <div class="strength-bar">
                <div class="strength-fill" style="width: ${score * 20}%; background-color: ${color};"></div>
            </div>
            <small style="color: ${color};">Force: ${strength}</small>
            ${feedback.length > 0 ? `<small class="text-muted">Suggestion: ${feedback[0]}</small>` : ''}
        `;
    }
    
    newPassword.addEventListener('input', function() {
        validatePassword();
        checkPasswordStrength(this.value);
    });
    
    confirmPassword.addEventListener('input', validatePassword);
});

// Fonction pour basculer la visibilité du mot de passe
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    const icon = input.nextElementSibling.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        input.type = 'password';
        icon.className = 'fas fa-eye';
    }
}
</script>

<?php
// Récupérer le contenu et l'inclure dans le layout
$content = ob_get_clean();
include 'layout/base.php';
?>
