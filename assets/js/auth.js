/**
 * SCOLARIA - JavaScript d'Authentification
 * Fonctionnalités pour les pages de connexion et d'inscription
 */

class AuthManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupPasswordToggle();
        this.setupFormValidation();
        this.setupKeyboardShortcuts();
        this.setupAnimations();
        this.setupAutoFocus();
        this.setupRememberMe();
    }

    /**
     * Configuration du toggle de visibilité du mot de passe
     */
    setupPasswordToggle() {
        const toggleButtons = document.querySelectorAll('.password-toggle');
        
        toggleButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const passwordField = button.parentElement.querySelector('input[type="password"], input[type="text"]');
                const icon = button.querySelector('i');
                
                if (passwordField.type === 'password') {
                    passwordField.type = 'text';
                    icon.className = 'fas fa-eye-slash';
                    button.setAttribute('aria-label', 'Masquer le mot de passe');
                } else {
                    passwordField.type = 'password';
                    icon.className = 'fas fa-eye';
                    button.setAttribute('aria-label', 'Afficher le mot de passe');
                }
                
                // Animation du bouton
                button.style.transform = 'scale(0.95)';
                setTimeout(() => {
                    button.style.transform = 'scale(1)';
                }, 100);
            });
        });
    }

    /**
     * Validation du formulaire en temps réel
     */
    setupFormValidation() {
        const forms = document.querySelectorAll('.auth-form');
        
        forms.forEach(form => {
            const inputs = form.querySelectorAll('.form-control');
            
            // Validation en temps réel
            inputs.forEach(input => {
                input.addEventListener('blur', () => this.validateField(input));
                input.addEventListener('input', () => this.clearFieldError(input));
            });
            
            // Validation à la soumission
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                    return false;
                }
                
                this.showLoadingState(form);
            });
        });
    }

    /**
     * Valide un champ individuel
     */
    validateField(input) {
        const value = input.value.trim();
        const fieldName = input.name;
        let isValid = true;
        let errorMessage = '';

        // Supprime les erreurs précédentes
        this.clearFieldError(input);

        // Validation selon le type de champ
        switch (fieldName) {
            case 'username':
                if (!value) {
                    errorMessage = 'Le nom d\'utilisateur est requis';
                    isValid = false;
                } else if (value.length < 3) {
                    errorMessage = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
                    isValid = false;
                }
                break;
                
            case 'password':
                if (!value) {
                    errorMessage = 'Le mot de passe est requis';
                    isValid = false;
                } else if (value.length < 6) {
                    errorMessage = 'Le mot de passe doit contenir au moins 6 caractères';
                    isValid = false;
                }
                break;
                
            case 'email':
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!value) {
                    errorMessage = 'L\'email est requis';
                    isValid = false;
                } else if (!emailRegex.test(value)) {
                    errorMessage = 'Format d\'email invalide';
                    isValid = false;
                }
                break;
        }

        if (!isValid) {
            this.showFieldError(input, errorMessage);
        }

        return isValid;
    }

    /**
     * Valide tout le formulaire
     */
    validateForm(form) {
        const inputs = form.querySelectorAll('.form-control[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!this.validateField(input)) {
                isValid = false;
            }
        });

        return isValid;
    }

    /**
     * Affiche une erreur sur un champ
     */
    showFieldError(input, message) {
        input.classList.add('error');
        
        // Supprime l'erreur existante
        const existingError = input.parentElement.querySelector('.field-error');
        if (existingError) {
            existingError.remove();
        }
        
        // Ajoute la nouvelle erreur
        const errorElement = document.createElement('div');
        errorElement.className = 'field-error';
        errorElement.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${message}`;
        input.parentElement.appendChild(errorElement);
        
        // Animation
        errorElement.style.opacity = '0';
        errorElement.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            errorElement.style.opacity = '1';
            errorElement.style.transform = 'translateY(0)';
        }, 10);
    }

    /**
     * Supprime l'erreur d'un champ
     */
    clearFieldError(input) {
        input.classList.remove('error');
        const errorElement = input.parentElement.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }

    /**
     * Affiche l'état de chargement du formulaire
     */
    showLoadingState(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.classList.add('loading');
            submitButton.disabled = true;
            
            // Restaure l'état après 10 secondes (sécurité)
            setTimeout(() => {
                submitButton.classList.remove('loading');
                submitButton.disabled = false;
            }, 10000);
        }
    }

    /**
     * Configuration des raccourcis clavier
     */
    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + Enter pour soumettre
            if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                const form = document.querySelector('.auth-form');
                if (form) {
                    form.dispatchEvent(new Event('submit', { bubbles: true }));
                }
            }
            
            // Échap pour effacer les erreurs
            if (e.key === 'Escape') {
                this.clearAllErrors();
            }
        });
    }

    /**
     * Supprime toutes les erreurs du formulaire
     */
    clearAllErrors() {
        const errors = document.querySelectorAll('.field-error');
        const errorInputs = document.querySelectorAll('.form-control.error');
        
        errors.forEach(error => error.remove());
        errorInputs.forEach(input => input.classList.remove('error'));
    }

    /**
     * Configuration des animations
     */
    setupAnimations() {
        // Animation d'entrée de la carte
        const authCard = document.querySelector('.auth-card');
        if (authCard) {
            // Observer pour l'animation au scroll (si nécessaire)
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('animate-in');
                    }
                });
            }, { threshold: 0.1 });
            
            observer.observe(authCard);
        }

        // Animation des éléments de fonctionnalités
        const featureItems = document.querySelectorAll('.feature-item');
        featureItems.forEach((item, index) => {
            item.style.animationDelay = `${index * 0.1}s`;
            item.classList.add('animate-slide-in');
        });
    }

    /**
     * Auto-focus sur le premier champ
     */
    setupAutoFocus() {
        document.addEventListener('DOMContentLoaded', () => {
            const firstInput = document.querySelector('.auth-form .form-control');
            if (firstInput && !this.isMobile()) {
                setTimeout(() => {
                    firstInput.focus();
                }, 100);
            }
        });
    }

    /**
     * Gestion du "Se souvenir de moi"
     */
    setupRememberMe() {
        const rememberCheckbox = document.querySelector('input[name="remember"]');
        const usernameInput = document.querySelector('input[name="username"]');
        
        if (rememberCheckbox && usernameInput) {
            // Restaure le nom d'utilisateur si sauvegardé
            const savedUsername = localStorage.getItem('scolaria_remembered_username');
            if (savedUsername) {
                usernameInput.value = savedUsername;
                rememberCheckbox.checked = true;
            }
            
            // Sauvegarde/supprime le nom d'utilisateur
            rememberCheckbox.addEventListener('change', () => {
                if (rememberCheckbox.checked && usernameInput.value.trim()) {
                    localStorage.setItem('scolaria_remembered_username', usernameInput.value.trim());
                } else {
                    localStorage.removeItem('scolaria_remembered_username');
                }
            });
            
            // Met à jour la sauvegarde quand l'utilisateur tape
            usernameInput.addEventListener('input', () => {
                if (rememberCheckbox.checked) {
                    localStorage.setItem('scolaria_remembered_username', usernameInput.value.trim());
                }
            });
        }
    }

    /**
     * Détecte si l'utilisateur est sur mobile
     */
    isMobile() {
        return window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }

    /**
     * Affiche une notification
     */
    showNotification(message, type = 'info') {
        // Utilise la fonction globale si disponible
        if (typeof showNotification === 'function') {
            showNotification(message, type);
            return;
        }
        
        // Fallback simple
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;
        notification.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
        
        const form = document.querySelector('.auth-form');
        if (form) {
            form.insertBefore(notification, form.firstChild);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }
}

// Styles CSS pour les erreurs de validation
const validationStyles = `
.form-control.error {
    border-color: var(--error-color);
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
}

.field-error {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    color: var(--error-color);
    font-size: 0.8rem;
    margin-top: 0.5rem;
    transition: all 0.3s ease;
}

.field-error i {
    font-size: 0.9rem;
}

.animate-slide-in {
    animation: slideInUp 0.6s ease-out forwards;
}

.animate-in {
    animation: slideInUp 0.6s ease-out;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
`;

// Injection des styles
const styleSheet = document.createElement('style');
styleSheet.textContent = validationStyles;
document.head.appendChild(styleSheet);

// Initialisation
document.addEventListener('DOMContentLoaded', () => {
    new AuthManager();
});

// Export pour utilisation externe
window.AuthManager = AuthManager;