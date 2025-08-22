/**
 * Gestion des notifications du header et de l'avatar utilisateur
 * Système de notifications en temps réel pour Scolaria
 */

class HeaderNotifications {
    constructor() {
        this.notificationsBtn = document.querySelector('.notifications-btn');
        this.notificationsBadge = document.querySelector('.notifications-badge');
        this.userMenu = document.querySelector('.user-menu');
        this.notificationsPanel = null;
        this.isPanelOpen = false;
        
        this.init();
    }
    
    init() {
        if (this.notificationsBtn) {
            this.notificationsBtn.addEventListener('click', () => this.toggleNotificationsPanel());
        }
        
        if (this.userMenu) {
            this.userMenu.addEventListener('click', () => this.toggleUserMenu());
        }
        
        // Fermer le panel si on clique ailleurs
        document.addEventListener('click', (e) => {
            if (!this.notificationsBtn?.contains(e.target) && !this.notificationsPanel?.contains(e.target)) {
                this.closeNotificationsPanel();
            }
        });
        
        // Mettre à jour les notifications au chargement
        this.updateNotificationsBadge();
        
        // Vérifier les nouvelles notifications toutes les 30 secondes
        setInterval(() => this.checkNewNotifications(), 30000);
    }
    
    async updateNotificationsBadge() {
        try {
            const response = await fetch('includes/get_notifications.php');
            const data = await response.json();
            
            if (data.success && this.notificationsBadge) {
                const count = data.unread_count || 0;
                
                if (count > 0) {
                    this.notificationsBadge.textContent = count > 99 ? '99+' : count;
                    this.notificationsBadge.style.display = 'block';
                    
                    // Animation de pulse pour attirer l'attention
                    if (count > 0) {
                        this.notificationsBtn.classList.add('has-notifications');
                    }
                } else {
                    this.notificationsBadge.style.display = 'none';
                    this.notificationsBtn.classList.remove('has-notifications');
                }
            }
        } catch (error) {
            console.error('Erreur lors de la mise à jour des notifications:', error);
        }
    }
    
    async checkNewNotifications() {
        await this.updateNotificationsBadge();
    }
    
    toggleNotificationsPanel() {
        if (this.isPanelOpen) {
            this.closeNotificationsPanel();
        } else {
            this.openNotificationsPanel();
        }
    }
    
    async openNotificationsPanel() {
        if (this.notificationsPanel) {
            this.closeNotificationsPanel();
        }
        
        try {
            const response = await fetch('includes/get_notifications.php');
            const data = await response.json();
            
            if (data.success) {
                this.createNotificationsPanel(data.notifications);
                this.isPanelOpen = true;
                
                // Animation d'ouverture
                this.notificationsPanel.style.transform = 'translateY(0)';
                this.notificationsPanel.style.opacity = '1';
            }
        } catch (error) {
            console.error('Erreur lors de l\'ouverture des notifications:', error);
            this.showError('Impossible de charger les notifications');
        }
    }
    
    closeNotificationsPanel() {
        if (this.notificationsPanel) {
            this.notificationsPanel.style.transform = 'translateY(-10px)';
            this.notificationsPanel.style.opacity = '0';
            
            setTimeout(() => {
                if (this.notificationsPanel && this.notificationsPanel.parentNode) {
                    this.notificationsPanel.parentNode.removeChild(this.notificationsPanel);
                }
                this.notificationsPanel = null;
                this.isPanelOpen = false;
            }, 200);
        }
    }
    
    createNotificationsPanel(notifications) {
        // Supprimer l'ancien panel s'il existe
        if (this.notificationsPanel) {
            this.notificationsPanel.remove();
        }
        
        this.notificationsPanel = document.createElement('div');
        this.notificationsPanel.className = 'notifications-panel';
        this.notificationsPanel.innerHTML = `
            <div class="notifications-header">
                <h3>Notifications</h3>
                <button class="btn-close" aria-label="Fermer">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="notifications-list">
                ${this.renderNotificationsList(notifications)}
            </div>
            <div class="notifications-footer">
                <button class="btn btn-sm btn-primary mark-all-read">Tout marquer comme lu</button>
                <a href="notifications.php" class="btn btn-sm btn-outline-primary">Voir tout</a>
            </div>
        `;
        
        // Positionner le panel
        const rect = this.notificationsBtn.getBoundingClientRect();
        this.notificationsPanel.style.position = 'absolute';
        this.notificationsPanel.style.top = (rect.bottom + 10) + 'px';
        this.notificationsPanel.style.right = '20px';
        this.notificationsPanel.style.zIndex = '1000';
        
        // Ajouter au DOM
        document.body.appendChild(this.notificationsPanel);
        
        // Événements
        const closeBtn = this.notificationsPanel.querySelector('.btn-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.closeNotificationsPanel());
        }
        
        const markAllReadBtn = this.notificationsPanel.querySelector('.mark-all-read');
        if (markAllReadBtn) {
            markAllReadBtn.addEventListener('click', () => this.markAllAsRead());
        }
        
        // Marquer comme lu au clic
        const notificationItems = this.notificationsPanel.querySelectorAll('.notification-item');
        notificationItems.forEach(item => {
            item.addEventListener('click', () => {
                const notificationId = item.dataset.id;
                if (notificationId) {
                    this.markAsRead(notificationId);
                }
            });
        });
    }
    
    renderNotificationsList(notifications) {
        if (!notifications || notifications.length === 0) {
            return '<div class="no-notifications">Aucune notification</div>';
        }
        
        return notifications.map(notification => `
            <div class="notification-item ${notification.is_read ? 'read' : 'unread'}" data-id="${notification.id}">
                <div class="notification-icon">
                    <i class="fas fa-${this.getNotificationIcon(notification.type)}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${this.escapeHtml(notification.title || notification.message)}</div>
                    <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                </div>
                ${!notification.is_read ? '<div class="notification-dot"></div>' : ''}
            </div>
        `).join('');
    }
    
    getNotificationIcon(type) {
        const icons = {
            'info': 'info-circle',
            'success': 'check-circle',
            'warning': 'exclamation-triangle',
            'error': 'exclamation-circle',
            'default': 'bell'
        };
        return icons[type] || icons.default;
    }
    
    formatTime(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'À l\'instant';
        if (diff < 3600000) return `Il y a ${Math.floor(diff / 60000)} min`;
        if (diff < 86400000) return `Il y a ${Math.floor(diff / 3600000)}h`;
        if (diff < 604800000) return `Il y a ${Math.floor(diff / 86400000)}j`;
        
        return date.toLocaleDateString('fr-FR');
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    async markAsRead(notificationId) {
        try {
            const response = await fetch('includes/mark_notification_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ notification_id: notificationId })
            });
            
            const data = await response.json();
            if (data.success) {
                // Mettre à jour l'interface
                this.updateNotificationsBadge();
                
                // Marquer visuellement comme lu
                const item = this.notificationsPanel?.querySelector(`[data-id="${notificationId}"]`);
                if (item) {
                    item.classList.remove('unread');
                    item.classList.add('read');
                    const dot = item.querySelector('.notification-dot');
                    if (dot) dot.remove();
                }
            }
        } catch (error) {
            console.error('Erreur lors du marquage comme lu:', error);
        }
    }
    
    async markAllAsRead() {
        try {
            const response = await fetch('includes/mark_all_notifications_read.php', {
                method: 'POST'
            });
            
            const data = await response.json();
            if (data.success) {
                // Mettre à jour l'interface
                this.updateNotificationsBadge();
                
                // Fermer le panel
                this.closeNotificationsPanel();
                
                // Afficher un message de succès
                if (typeof showNotification === 'function') {
                    showNotification('Toutes les notifications ont été marquées comme lues', 'success');
                }
            }
        } catch (error) {
            console.error('Erreur lors du marquage de toutes les notifications:', error);
        }
    }
    
    toggleUserMenu() {
        // Toggle du menu utilisateur (peut être étendu plus tard)
        console.log('Menu utilisateur cliqué');
    }
    
    showError(message) {
        if (typeof showNotification === 'function') {
            showNotification(message, 'error');
        } else {
            console.error(message);
        }
    }
}

// Initialisation au chargement de la page
document.addEventListener('DOMContentLoaded', () => {
    new HeaderNotifications();
});
