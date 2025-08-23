/**
 * JavaScript pour la page de profil - Scolaria
 * Gestion des interactions et validations
 */

document.addEventListener('DOMContentLoaded', function() {
    'use strict';

    // Éléments DOM
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const currentPassword = document.getElementById('current_password');
    const avatarInput = document.getElementById('avatar');
    const avatarPreview = document.querySelector('.avatar-container img, .avatar-placeholder');
    const profileForm = document.querySelector('form[action="update_profile"]');
    const passwordForm = document.querySelector('form[action="update_password"]');
    const avatarForm = document.querySelector('form[action="update_avatar"]');

    // Initialisation
    initPasswordValidation();
    initAvatarPreview();
    initFormValidation();
    initTooltips();
    initAnimations();

    /**
     * Validation en temps réel des mots de passe
     */
    function initPasswordValidation() {
        if (!newPassword || !confirmPassword) return;

        // Validation de la force du mot de passe
        newPassword.addEventListener('input', function() {
            const strength = checkPasswordStrength(this.value);
            updatePasswordStrengthIndicator(strength);
        });

        // Validation de la correspondance des mots de passe
        function validatePasswordMatch() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Les mots de passe ne correspondent pas');
                confirmPassword.classList.add('is-invalid');
            } else {
                confirmPassword.setCustomValidity('');
                confirmPassword.classList.remove('is-invalid');
                confirmPassword.classList.add('is-valid');
            }
        }

        newPassword.addEventListener('input', validatePasswordMatch);
        confirmPassword.addEventListener('input', validatePasswordMatch);

        // Validation de la longueur minimale
        newPassword.addEventListener('input', function() {
            if (this.value.length < 6) {
                this.setCustomValidity('Le mot de passe doit contenir au moins 6 caractères');
                this.classList.add('is-invalid');
            } else {
                this.setCustomValidity('');
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            }
        });
    }

    /**
     * Vérifier la force du mot de passe
     */
    function checkPasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        
        if (score <= 2) return 'weak';
        if (score <= 3) return 'medium';
        return 'strong';
    }

    /**
     * Mettre à jour l'indicateur de force du mot de passe
     */
    function updatePasswordStrengthIndicator(strength) {
        let strengthBar = document.querySelector('.password-strength-bar');
        
        if (!strengthBar) {
            // Créer l'indicateur s'il n'existe pas
            const strengthContainer = document.createElement('div');
            strengthContainer.className = 'password-strength mt-2';
            strengthContainer.innerHTML = '<div class="password-strength-bar"></div>';
            newPassword.parentNode.appendChild(strengthContainer);
            strengthBar = strengthContainer.querySelector('.password-strength-bar');
        }

        // Mettre à jour la classe et la largeur
        strengthBar.className = 'password-strength-bar password-strength-' + strength;
        
        // Mettre à jour le texte d'aide
        let helpText = newPassword.parentNode.querySelector('.form-text');
        if (!helpText) {
            helpText = document.createElement('div');
            helpText.className = 'form-text';
            newPassword.parentNode.appendChild(helpText);
        }
        
        const strengthTexts = {
            weak: 'Mot de passe faible - Ajoutez des lettres majuscules, chiffres et caractères spéciaux',
            medium: 'Mot de passe moyen - Ajoutez des caractères spéciaux pour plus de sécurité',
            strong: 'Mot de passe fort - Excellent niveau de sécurité'
        };
        
        helpText.textContent = strengthTexts[strength];
        helpText.className = 'form-text text-' + (strength === 'weak' ? 'danger' : strength === 'medium' ? 'warning' : 'success');
    }

    /**
     * Prévisualisation de l'avatar
     */
    function initAvatarPreview() {
        if (!avatarInput || !avatarPreview) return;

        avatarInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                // Validation du fichier
                if (!validateImageFile(file)) {
                    this.value = '';
                    return;
                }

                // Prévisualisation
                const reader = new FileReader();
                reader.onload = function(e) {
                    if (avatarPreview.tagName === 'IMG') {
                        avatarPreview.src = e.target.result;
                    } else {
                        // Remplacer l'icône par l'image
                        const newImg = document.createElement('img');
                        newImg.src = e.target.result;
                        newImg.alt = 'Avatar';
                        newImg.className = 'img-fluid rounded-circle';
                        newImg.style = 'width: 150px; height: 150px; object-fit: cover;';
                        avatarPreview.parentNode.replaceChild(newImg, avatarPreview);
                        avatarPreview = newImg;
                    }
                    
                    // Ajouter un bouton de suppression
                    addRemoveAvatarButton();
                };
                reader.readAsDataURL(file);
            }
        });
    }

    /**
     * Valider le fichier image
     */
    function validateImageFile(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        const maxSize = 5 * 1024 * 1024; // 5MB
        
        if (!allowedTypes.includes(file.type)) {
            showNotification('Type de fichier non autorisé. Utilisez JPG, PNG ou GIF.', 'error');
            return false;
        }
        
        if (file.size > maxSize) {
            showNotification('Fichier trop volumineux. Maximum 5MB.', 'error');
            return false;
        }
        
        return true;
    }

    /**
     * Ajouter un bouton de suppression d'avatar
     */
    function addRemoveAvatarButton() {
        const avatarContainer = document.querySelector('.avatar-container');
        if (!avatarContainer) return;

        // Supprimer l'ancien bouton s'il existe
        const oldButton = avatarContainer.querySelector('.remove-avatar-btn');
        if (oldButton) oldButton.remove();

        const removeBtn = document.createElement('button');
        removeBtn.type = 'button';
        removeBtn.className = 'btn btn-sm btn-danger remove-avatar-btn position-absolute';
        removeBtn.style = 'top: -10px; right: -10px; border-radius: 50%; width: 30px; height: 30px;';
        removeBtn.innerHTML = '<i class="fas fa-times"></i>';
        removeBtn.title = 'Supprimer l\'avatar';
        
        removeBtn.addEventListener('click', function() {
            if (confirm('Voulez-vous vraiment supprimer votre avatar ?')) {
                avatarInput.value = '';
                location.reload(); // Recharger pour afficher l'icône par défaut
            }
        });
        
        avatarContainer.style.position = 'relative';
        avatarContainer.appendChild(removeBtn);
    }

    /**
     * Validation des formulaires
     */
    function initFormValidation() {
        // Validation du profil
        if (profileForm) {
            profileForm.addEventListener('submit', function(e) {
                if (!validateProfileForm()) {
                    e.preventDefault();
                }
            });
        }

        // Validation du mot de passe
        if (passwordForm) {
            passwordForm.addEventListener('submit', function(e) {
                if (!validatePasswordForm()) {
                    e.preventDefault();
                }
            });
        }

        // Validation de l'avatar
        if (avatarForm) {
            avatarForm.addEventListener('submit', function(e) {
                if (!validateAvatarForm()) {
                    e.preventDefault();
                }
            });
        }
    }

    /**
     * Valider le formulaire de profil
     */
    function validateProfileForm() {
        const fullName = document.getElementById('full_name').value.trim();
        const email = document.getElementById('email').value.trim();
        
        if (fullName.length < 2) {
            showNotification('Le nom complet doit contenir au moins 2 caractères.', 'error');
            return false;
        }
        
        if (!isValidEmail(email)) {
            showNotification('Veuillez saisir un email valide.', 'error');
            return false;
        }
        
        return true;
    }

    /**
     * Valider le formulaire de mot de passe
     */
    function validatePasswordForm() {
        const currentPass = currentPassword.value;
        const newPass = newPassword.value;
        const confirmPass = confirmPassword.value;
        
        if (currentPass.length === 0) {
            showNotification('Veuillez saisir votre mot de passe actuel.', 'error');
            return false;
        }
        
        if (newPass.length < 6) {
            showNotification('Le nouveau mot de passe doit contenir au moins 6 caractères.', 'error');
            return false;
        }
        
        if (newPass !== confirmPass) {
            showNotification('Les nouveaux mots de passe ne correspondent pas.', 'error');
            return false;
        }
        
        return true;
    }

    /**
     * Valider le formulaire d'avatar
     */
    function validateAvatarForm() {
        if (!avatarInput.files || avatarInput.files.length === 0) {
            showNotification('Veuillez sélectionner une image.', 'error');
            return false;
        }
        
        return validateImageFile(avatarInput.files[0]);
    }

    /**
     * Valider un email
     */
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    /**
     * Initialiser les tooltips
     */
    function initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.classList.add('custom-tooltip');
        });
    }

    /**
     * Initialiser les animations
     */
    function initAnimations() {
        // Animation des cartes au scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver(function(entries) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        const cards = document.querySelectorAll('.card');
        cards.forEach(card => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(30px)';
            card.style.transition = 'all 0.6s ease-out';
            observer.observe(card);
        });
    }

    /**
     * Afficher une notification
     */
    function showNotification(message, type = 'info') {
        // Créer le conteneur de messages s'il n'existe pas
        let messageContainer = document.querySelector('.message-container');
        if (!messageContainer) {
            messageContainer = document.createElement('div');
            messageContainer.className = 'message-container';
            document.body.appendChild(messageContainer);
        }

        // Créer le message
        const messageElement = document.createElement('div');
        messageElement.className = `message ${type}`;
        messageElement.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                <span>${message}</span>
                <button type="button" class="btn-close ms-auto" onclick="this.parentElement.parentElement.remove()"></button>
            </div>
        `;

        // Ajouter le message
        messageContainer.appendChild(messageElement);

        // Auto-suppression après 5 secondes
        setTimeout(() => {
            if (messageElement.parentNode) {
                messageElement.remove();
            }
        }, 5000);
    }

    /**
     * Gestion des erreurs AJAX
     */
    function handleAjaxError(xhr, status, error) {
        console.error('Erreur AJAX:', status, error);
        showNotification('Une erreur est survenue. Veuillez réessayer.', 'error');
    }

    /**
     * Gestion des succès AJAX
     */
    function handleAjaxSuccess(response) {
        if (response.success) {
            showNotification(response.message || 'Opération réussie !', 'success');
        } else {
            showNotification(response.message || 'Une erreur est survenue.', 'error');
        }
    }

    // Gestion des erreurs globales
    window.addEventListener('error', function(e) {
        console.error('Erreur JavaScript:', e.error);
        showNotification('Une erreur JavaScript est survenue.', 'error');
    });

    // Gestion des erreurs de promesses non gérées
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Promesse rejetée:', e.reason);
        showNotification('Une erreur asynchrone est survenue.', 'error');
    });
});

/**
 * Fonctions utilitaires globales
 */

// Fonction pour formater les dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Fonction pour valider les numéros de téléphone
function validatePhone(phone) {
    const phoneRegex = /^[\+]?[0-9\s\-\(\)]{8,}$/;
    return phoneRegex.test(phone);
}

// Fonction pour nettoyer les chaînes
function sanitizeString(str) {
    return str.replace(/[<>]/g, '').trim();
}

// Fonction pour générer un ID unique
function generateUniqueId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2);
}
