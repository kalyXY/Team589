/**
 * SCOLARIA - JavaScript Principal
 * Gestion des interactions communes, sidebar, dark mode, notifications
 * Team589
 */

class ScolariApp {
    constructor() {
        this.sidebar = null;
        this.sidebarToggle = null;
        this.themeToggle = null;
        this.currentTheme = localStorage.getItem('theme') || 'light';
        
        this.init();
    }

    init() {
        this.initElements();
        this.initTheme();
        this.initSidebar();
        this.initNotifications();
        this.bindEvents();
        this.initTooltips();
    }

    initElements() {
        this.sidebar = document.querySelector('.sidebar');
        this.sidebarToggle = document.querySelector('.sidebar-toggle');
        this.themeToggle = document.querySelector('.theme-toggle');
        this.mainContent = document.querySelector('.main-content');
    }

    initTheme() {
        // Appliquer le thème sauvegardé
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        
        // Mettre à jour l'icône du toggle
        if (this.themeToggle) {
            this.updateThemeIcon();
        }
    }

    initSidebar() {
        // Restaurer l'état de la sidebar
        const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
        if (sidebarCollapsed && this.sidebar) {
            this.sidebar.classList.add('collapsed');
        }

        // Marquer le lien actif
        this.setActiveNavLink();
    }

    initNotifications() {
        // Initialiser le système de notifications
        this.createNotificationContainer();
        
        // Vérifier les notifications au chargement
        this.checkNotifications();
        
        // Vérifier périodiquement
        setInterval(() => this.checkNotifications(), 30000); // 30 secondes
    }

    bindEvents() {
        // Toggle sidebar
        if (this.sidebarToggle) {
            this.sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Toggle theme
        if (this.themeToggle) {
            this.themeToggle.addEventListener('click', () => this.toggleTheme());
        }

        // Fermer sidebar sur mobile en cliquant à l'extérieur
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768) {
                if (!this.sidebar?.contains(e.target) && !this.sidebarToggle?.contains(e.target)) {
                    this.closeMobileSidebar();
                }
            }
        });

        // Responsive sidebar
        window.addEventListener('resize', () => this.handleResize());

        // Navigation avec clavier
        document.addEventListener('keydown', (e) => this.handleKeyboard(e));

        // Gestion des modales
        this.initModals();
    }

    toggleSidebar() {
        if (!this.sidebar) return;

        if (window.innerWidth <= 768) {
            // Mode mobile - toggle visibility
            this.sidebar.classList.toggle('mobile-open');
        } else {
            // Mode desktop - toggle collapsed
            this.sidebar.classList.toggle('collapsed');
            localStorage.setItem('sidebarCollapsed', this.sidebar.classList.contains('collapsed'));
        }
    }

    closeMobileSidebar() {
        if (this.sidebar) {
            this.sidebar.classList.remove('mobile-open');
        }
    }

    toggleTheme() {
        this.currentTheme = this.currentTheme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', this.currentTheme);
        localStorage.setItem('theme', this.currentTheme);
        this.updateThemeIcon();
        
        // Animation de transition
        document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    updateThemeIcon() {
        if (!this.themeToggle) return;
        
        const icon = this.themeToggle.querySelector('i');
        if (icon) {
            icon.className = this.currentTheme === 'light' ? 'fas fa-moon' : 'fas fa-sun';
        }
    }

    setActiveNavLink() {
        const currentPath = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            link.classList.remove('active');
            const href = link.getAttribute('href');
            if (href && currentPath.includes(href.replace('./', ''))) {
                link.classList.add('active');
            }
        });
    }

    handleResize() {
        if (window.innerWidth > 768) {
            // Desktop - fermer le menu mobile
            this.closeMobileSidebar();
        }
    }

    handleKeyboard(e) {
        // Échap pour fermer modales et menus
        if (e.key === 'Escape') {
            this.closeMobileSidebar();
            this.closeAllModals();
        }
        
        // Ctrl/Cmd + B pour toggle sidebar
        if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
            e.preventDefault();
            this.toggleSidebar();
        }
        
        // Ctrl/Cmd + D pour toggle dark mode
        if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
            e.preventDefault();
            this.toggleTheme();
        }
    }

    // Système de notifications
    createNotificationContainer() {
        if (!document.querySelector('.notifications-container')) {
            const container = document.createElement('div');
            container.className = 'notifications-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 3000;
                max-width: 400px;
                pointer-events: none;
            `;
            document.body.appendChild(container);
        }
    }

    showNotification(message, type = 'info', duration = 5000) {
        const container = document.querySelector('.notifications-container');
        if (!container) return;

        const notification = document.createElement('div');
        notification.className = `notification notification-${type} animate-slideInDown`;
        notification.style.cssText = `
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-left: 4px solid var(--${type === 'success' ? 'success' : type === 'warning' ? 'warning' : type === 'error' ? 'danger' : 'info'}-color);
            border-radius: var(--radius-md);
            padding: var(--spacing-md);
            margin-bottom: var(--spacing-sm);
            box-shadow: var(--shadow-lg);
            pointer-events: auto;
            cursor: pointer;
            transition: all var(--transition-fast);
        `;

        const icons = {
            success: 'fas fa-check-circle',
            warning: 'fas fa-exclamation-triangle',
            error: 'fas fa-times-circle',
            info: 'fas fa-info-circle'
        };

        notification.innerHTML = `
            <div style="display: flex; align-items: flex-start; gap: var(--spacing-sm);">
                <i class="${icons[type] || icons.info}" style="color: var(--${type === 'success' ? 'success' : type === 'warning' ? 'warning' : type === 'error' ? 'danger' : 'info'}-color); margin-top: 2px;"></i>
                <div style="flex: 1; color: var(--text-primary); font-size: var(--font-size-sm);">${message}</div>
                <button style="background: none; border: none; color: var(--text-muted); cursor: pointer; padding: 0; font-size: var(--font-size-lg);">&times;</button>
            </div>
        `;

        // Fermer au clic
        notification.addEventListener('click', () => this.removeNotification(notification));

        container.appendChild(notification);

        // Auto-remove
        if (duration > 0) {
            setTimeout(() => this.removeNotification(notification), duration);
        }

        return notification;
    }

    removeNotification(notification) {
        if (notification && notification.parentNode) {
            notification.style.transform = 'translateX(100%)';
            notification.style.opacity = '0';
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
            }, 300);
        }
    }

    checkNotifications() {
        // Vérifier les alertes de stock, nouvelles commandes, etc.
        // Cette fonction sera étendue selon les besoins spécifiques
        
        // Exemple : vérifier les stocks faibles
        if (typeof window.checkLowStock === 'function') {
            window.checkLowStock().then(count => {
                if (count > 0) {
                    this.updateNotificationBadge(count);
                }
            });
        }
    }

    updateNotificationBadge(count) {
        const badge = document.querySelector('.notifications-badge');
        if (badge) {
            badge.textContent = count;
            badge.style.display = count > 0 ? 'block' : 'none';
        }
    }

    // Gestion des modales
    initModals() {
        // Fermer modales en cliquant sur le backdrop
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal')) {
                this.closeModal(e.target);
            }
        });

        // Fermer modales avec le bouton close
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || e.target.closest('.modal-close')) {
                const modal = e.target.closest('.modal');
                if (modal) {
                    this.closeModal(modal);
                }
            }
        });
    }

    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Focus sur le premier élément focusable
            const focusable = modal.querySelector('input, select, textarea, button');
            if (focusable) {
                setTimeout(() => focusable.focus(), 100);
            }
        }
    }

    closeModal(modal) {
        if (typeof modal === 'string') {
            modal = document.getElementById(modal);
        }
        
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = '';
        }
    }

    closeAllModals() {
        const modals = document.querySelectorAll('.modal.show');
        modals.forEach(modal => this.closeModal(modal));
    }

    // Tooltips
    initTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            this.createTooltip(element);
        });
    }

    createTooltip(element) {
        const tooltipText = element.getAttribute('data-tooltip');
        if (!tooltipText) return;

        let tooltip = null;

        element.addEventListener('mouseenter', () => {
            tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.textContent = tooltipText;
            tooltip.style.cssText = `
                position: absolute;
                background: var(--text-dark);
                color: var(--text-white);
                padding: var(--spacing-xs) var(--spacing-sm);
                border-radius: var(--radius-sm);
                font-size: var(--font-size-xs);
                white-space: nowrap;
                z-index: 4000;
                pointer-events: none;
                opacity: 0;
                transition: opacity var(--transition-fast);
            `;

            document.body.appendChild(tooltip);

            // Position du tooltip
            const rect = element.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            
            tooltip.style.left = `${rect.left + (rect.width - tooltipRect.width) / 2}px`;
            tooltip.style.top = `${rect.top - tooltipRect.height - 8}px`;
            
            // Animation d'apparition
            setTimeout(() => {
                if (tooltip) tooltip.style.opacity = '1';
            }, 10);
        });

        element.addEventListener('mouseleave', () => {
            if (tooltip) {
                tooltip.style.opacity = '0';
                setTimeout(() => {
                    if (tooltip && tooltip.parentNode) {
                        tooltip.parentNode.removeChild(tooltip);
                    }
                }, 150);
            }
        });
    }

    // Utilitaires
    formatNumber(num) {
        return new Intl.NumberFormat('fr-FR').format(num);
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('fr-FR', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }

    formatDate(date) {
        return new Intl.DateTimeFormat('fr-FR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        }).format(new Date(date));
    }

    formatDateTime(date) {
        return new Intl.DateTimeFormat('fr-FR', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(new Date(date));
    }

    // Validation de formulaires
    validateForm(form) {
        const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
        let isValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                this.showFieldError(input, 'Ce champ est requis');
                isValid = false;
            } else {
                this.clearFieldError(input);
            }

            // Validation email
            if (input.type === 'email' && input.value) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(input.value)) {
                    this.showFieldError(input, 'Format d\'email invalide');
                    isValid = false;
                }
            }

            // Validation numérique
            if (input.type === 'number' && input.value) {
                const min = parseFloat(input.getAttribute('min'));
                const max = parseFloat(input.getAttribute('max'));
                const value = parseFloat(input.value);

                if (!isNaN(min) && value < min) {
                    this.showFieldError(input, `La valeur doit être supérieure à ${min}`);
                    isValid = false;
                }

                if (!isNaN(max) && value > max) {
                    this.showFieldError(input, `La valeur doit être inférieure à ${max}`);
                    isValid = false;
                }
            }
        });

        return isValid;
    }

    showFieldError(input, message) {
        this.clearFieldError(input);
        
        input.classList.add('error');
        input.style.borderColor = 'var(--danger-color)';
        
        const errorDiv = document.createElement('div');
        errorDiv.className = 'field-error';
        errorDiv.style.cssText = `
            color: var(--danger-color);
            font-size: var(--font-size-xs);
            margin-top: var(--spacing-xs);
        `;
        errorDiv.textContent = message;
        
        input.parentNode.appendChild(errorDiv);
    }

    clearFieldError(input) {
        input.classList.remove('error');
        input.style.borderColor = '';
        
        const errorDiv = input.parentNode.querySelector('.field-error');
        if (errorDiv) {
            errorDiv.remove();
        }
    }

    // API Helper
    async apiRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        const config = { ...defaultOptions, ...options };
        
        try {
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const contentType = response.headers.get('content-type');
            if (contentType && contentType.includes('application/json')) {
                return await response.json();
            } else {
                return await response.text();
            }
        } catch (error) {
            console.error('API Request failed:', error);
            this.showNotification('Erreur de connexion au serveur', 'error');
            throw error;
        }
    }
}

// Initialisation globale
let app;

document.addEventListener('DOMContentLoaded', () => {
    app = new ScolariApp();
    
    // Rendre l'app disponible globalement
    window.ScolariApp = app;
    
    // Fonctions globales pour compatibilité
    window.showNotification = (message, type, duration) => app.showNotification(message, type, duration);
    window.openModal = (modalId) => app.openModal(modalId);
    window.closeModal = (modal) => app.closeModal(modal);
    window.validateForm = (form) => app.validateForm(form);
});

// Export pour modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = ScolariApp;
}