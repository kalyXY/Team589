/**
 * Système de notifications toast global pour l'application Scolaria
 * Gestion des messages d'erreur/succès avec animations et auto-disparition
 */

// Gestion automatique des notifications toast existantes
document.addEventListener('DOMContentLoaded', function() {
    const existingToasts = document.querySelectorAll('.toast.show');
    existingToasts.forEach(toast => {
        setupToastAutoDismiss(toast);
    });
});

/**
 * Configure l'auto-disparition d'un toast
 * @param {HTMLElement} toast - L'élément toast à configurer
 */
function setupToastAutoDismiss(toast) {
    if (!toast) return;
    
    // Auto-dismiss après 5 secondes
    setTimeout(() => {
        dismissToast(toast);
    }, 5000);
    
    // Gestion du bouton de fermeture
    const closeBtn = toast.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            dismissToast(toast);
        });
    }
}

/**
 * Ferme un toast avec animation
 * @param {HTMLElement} toast - L'élément toast à fermer
 */
function dismissToast(toast) {
    if (toast && !toast.classList.contains('fade-out')) {
        toast.classList.add('fade-out');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }
}

/**
 * Affiche une notification toast personnalisée
 * @param {string} message - Le message à afficher
 * @param {string} type - Le type de notification ('success', 'danger', 'warning', 'info')
 * @param {number} duration - Durée d'affichage en millisecondes (défaut: 5000)
 */
function showNotification(message, type = 'success', duration = 5000) {
    const container = document.querySelector('.toast-container') || createToastContainer();
    
    const toast = document.createElement('div');
    toast.className = `toast text-bg-${type} show`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    const icon = getIconForType(type);
    
    toast.innerHTML = `
        <div class="toast-body d-flex align-items-center">
            <i class="fas fa-${icon} me-2"></i>
            ${message}
        </div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    `;
    
    container.appendChild(toast);
    
    // Auto-dismiss après la durée spécifiée
    setTimeout(() => {
        dismissToast(toast);
    }, duration);
    
    // Gestion du bouton de fermeture
    const closeBtn = toast.querySelector('.btn-close');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            dismissToast(toast);
        });
    }
    
    return toast;
}

/**
 * Retourne l'icône appropriée selon le type de notification
 * @param {string} type - Le type de notification
 * @returns {string} Le nom de l'icône FontAwesome
 */
function getIconForType(type) {
    const icons = {
        'success': 'check-circle',
        'danger': 'exclamation-triangle',
        'warning': 'exclamation-circle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

/**
 * Crée un conteneur de notifications s'il n'existe pas
 * @returns {HTMLElement} Le conteneur créé
 */
function createToastContainer() {
    const container = document.createElement('div');
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '1080';
    document.body.appendChild(container);
    return container;
}

/**
 * Affiche une notification de succès
 * @param {string} message - Le message à afficher
 * @param {number} duration - Durée d'affichage (défaut: 5000)
 */
function showSuccess(message, duration = 5000) {
    return showNotification(message, 'success', duration);
}

/**
 * Affiche une notification d'erreur
 * @param {string} message - Le message à afficher
 * @param {number} duration - Durée d'affichage (défaut: 7000)
 */
function showError(message, duration = 7000) {
    return showNotification(message, 'danger', duration);
}

/**
 * Affiche une notification d'avertissement
 * @param {string} message - Le message à afficher
 * @param {number} duration - Durée d'affichage (défaut: 6000)
 */
function showWarning(message, duration = 6000) {
    return showNotification(message, 'warning', duration);
}

/**
 * Affiche une notification d'information
 * @param {string} message - Le message à afficher
 * @param {number} duration - Durée d'affichage (défaut: 5000)
 */
function showInfo(message, duration = 5000) {
    return showNotification(message, 'info', duration);
}

/**
 * Remplace les alert() par des notifications toast
 * Surcharge de la fonction alert native
 */
(function() {
    const originalAlert = window.alert;
    window.alert = function(message, type = 'info') {
        showNotification(message, type === 'error' ? 'danger' : 'info');
    };
})();

// Export pour utilisation dans d'autres modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        showNotification,
        showSuccess,
        showError,
        showWarning,
        showInfo,
        dismissToast
    };
}
