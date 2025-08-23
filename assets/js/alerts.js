/**
 * JavaScript pour le module Alertes & Réapprovisionnement - Scolaria Team589
 * Gestion des interactions utilisateur et validations
 */

class AlertsManager {
    constructor() {
        this.init();
    }

    init() {
        this.bindEvents();
        this.startAutoRefresh();
    }

    // Gestion des onglets
    showSection(sectionName) {
        // Masquer toutes les sections
        const sections = document.querySelectorAll('.alerts-section');
        sections.forEach(section => section.classList.remove('active'));
        
        // Désactiver tous les onglets
        const tabs = document.querySelectorAll('.nav-tab');
        tabs.forEach(tab => tab.classList.remove('active'));
        
        // Afficher la section sélectionnée
        const targetSection = document.getElementById(sectionName + '-section');
        if (targetSection) {
            targetSection.classList.add('active');
        }
        
        // Activer l'onglet correspondant
        const activeTab = document.querySelector(`[onclick="showSection('${sectionName}')"]`);
        if (activeTab) {
            activeTab.classList.add('active');
        }
    }

    // Gestion des modales
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';
            
            // Focus sur le premier champ du formulaire
            const firstInput = modal.querySelector('input:not([type="hidden"]), select, textarea');
            if (firstInput) {
                setTimeout(() => firstInput.focus(), 100);
            }
        }
    }

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('show');
            document.body.style.overflow = 'auto';
            
            // Reset du formulaire
            const form = modal.querySelector('form');
            if (form) {
                this.resetForm(form);
            }
        }
    }

    resetForm(form) {
        form.reset();
        
        // Masquer les messages d'erreur
        const errors = form.querySelectorAll('.error-message');
        errors.forEach(error => error.style.display = 'none');
        
        // Retirer les classes d'erreur
        const inputs = form.querySelectorAll('.form-control.error');
        inputs.forEach(input => input.classList.remove('error'));
        
        // Réactiver les champs désactivés
        const disabledInputs = form.querySelectorAll('input:disabled, select:disabled');
        disabledInputs.forEach(input => input.disabled = false);
    }

    // Gestion des événements
    bindEvents() {
        // Fermer les modales en cliquant à l'extérieur
        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                document.body.style.overflow = 'auto';
            }
        });

        // Fermer les modales avec la touche Escape
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const openModal = document.querySelector('.modal.show');
                if (openModal) {
                    openModal.classList.remove('show');
                    document.body.style.overflow = 'auto';
                }
            }
        });

        // Validation en temps réel
        this.bindValidationEvents();
    }

    bindValidationEvents() {
        // Validation quantité commande
        const quantityInput = document.getElementById('order_quantity');
        if (quantityInput) {
            quantityInput.addEventListener('input', () => {
                this.validateQuantity(quantityInput);
            });
        }

        // Validation seuil
        const thresholdInput = document.getElementById('new_threshold');
        if (thresholdInput) {
            thresholdInput.addEventListener('input', () => {
                this.validateThreshold(thresholdInput);
            });
        }

        // Validation email
        const emailInput = document.getElementById('supplier_email');
        if (emailInput) {
            emailInput.addEventListener('input', () => {
                this.validateEmail(emailInput);
            });
        }

        // Validation prix
        const priceInput = document.getElementById('order_price');
        if (priceInput) {
            priceInput.addEventListener('input', () => {
                this.validatePrice(priceInput);
            });
        }
    }

    // Validations individuelles
    validateQuantity(input) {
        const value = parseInt(input.value);
        const errorElement = document.getElementById('quantity_error');
        
        if (!value || value <= 0) {
            this.showFieldError(input, errorElement, 'La quantité doit être supérieure à 0');
            return false;
        } else {
            this.hideFieldError(input, errorElement);
            return true;
        }
    }

    validateThreshold(input) {
        const value = parseInt(input.value);
        const errorElement = document.getElementById('threshold_error');
        
        if (value < 0) {
            this.showFieldError(input, errorElement, 'Le seuil doit être supérieur ou égal à 0');
            return false;
        } else {
            this.hideFieldError(input, errorElement);
            return true;
        }
    }

    validateEmail(input) {
        const value = input.value.trim();
        const errorElement = document.getElementById('email_error');
        
        if (value && !this.isValidEmail(value)) {
            this.showFieldError(input, errorElement, 'Format d\'email invalide');
            return false;
        } else {
            this.hideFieldError(input, errorElement);
            return true;
        }
    }

    validatePrice(input) {
        const value = parseFloat(input.value);
        
        if (value < 0) {
            input.classList.add('error');
            return false;
        } else {
            input.classList.remove('error');
            return true;
        }
    }

    showFieldError(input, errorElement, message) {
        input.classList.add('error');
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.style.display = 'block';
        }
    }

    hideFieldError(input, errorElement) {
        input.classList.remove('error');
        if (errorElement) {
            errorElement.style.display = 'none';
        }
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Validations de formulaires complets
    validateOrderForm() {
        let isValid = true;
        
        const quantity = document.getElementById('order_quantity');
        const supplier = document.getElementById('order_supplier');
        const article = document.getElementById('order_article_select');
        const price = document.getElementById('order_price');
        
        // Validation article
        if (!article.value) {
            article.classList.add('error');
            isValid = false;
        } else {
            article.classList.remove('error');
        }
        
        // Validation fournisseur
        if (!supplier.value) {
            supplier.classList.add('error');
            isValid = false;
        } else {
            supplier.classList.remove('error');
        }
        
        // Validation quantité
        if (!this.validateQuantity(quantity)) {
            isValid = false;
        }
        
        // Validation prix
        if (!this.validatePrice(price)) {
            isValid = false;
        }
        
        return isValid;
    }

    validateThresholdForm() {
        const threshold = document.getElementById('new_threshold');
        return this.validateThreshold(threshold);
    }

    validateSupplierForm() {
        let isValid = true;
        
        const nom = document.getElementById('supplier_nom');
        const email = document.getElementById('supplier_email');
        
        // Validation nom (requis)
        if (!nom.value.trim()) {
            nom.classList.add('error');
            isValid = false;
        } else {
            nom.classList.remove('error');
        }
        
        // Validation email (optionnel mais doit être valide si rempli)
        if (!this.validateEmail(email)) {
            isValid = false;
        }
        
        return isValid;
    }

    // Gestion des commandes
    openOrderModal(articleId = null, articleName = null) {
        const articleSelect = document.getElementById('order_article_select');
        const articleIdInput = document.getElementById('order_article_id');
        
        if (articleId && articleSelect) {
            articleIdInput.value = articleId;
            articleSelect.value = articleId;
            articleSelect.disabled = true;
            
            // Suggestion de quantité basée sur le seuil
            const selectedOption = articleSelect.querySelector(`option[value="${articleId}"]`);
            if (selectedOption) {
                const stock = parseInt(selectedOption.dataset.stock) || 0;
                const seuil = parseInt(selectedOption.dataset.seuil) || 10;
                const suggestedQuantity = Math.max(seuil * 2 - stock, seuil);
                
                const quantityInput = document.getElementById('order_quantity');
                if (quantityInput) {
                    quantityInput.value = suggestedQuantity;
                }
            }
        } else if (articleSelect) {
            articleSelect.disabled = false;
        }
        
        this.openModal('orderModal');
    }

    openThresholdModal(articleId, currentThreshold, articleName) {
        document.getElementById('threshold_article_id').value = articleId;
        document.getElementById('new_threshold').value = currentThreshold;
        document.getElementById('threshold_article_name').textContent = articleName;
        this.openModal('thresholdModal');
    }

    // Gestion des fournisseurs
    openSupplierModal() {
        document.getElementById('supplier_modal_title').innerHTML = '<i class="fas fa-plus"></i> Nouveau Fournisseur';
        document.getElementById('supplier_action').value = 'add_supplier';
        document.getElementById('supplier_submit_text').textContent = 'Ajouter';
        this.openModal('supplierModal');
    }

    editSupplier(supplier) {
        document.getElementById('supplier_modal_title').innerHTML = '<i class="fas fa-edit"></i> Modifier Fournisseur';
        document.getElementById('supplier_action').value = 'update_supplier';
        document.getElementById('supplier_id').value = supplier.id;
        document.getElementById('supplier_nom').value = supplier.nom;
        document.getElementById('supplier_contact').value = supplier.contact || '';
        document.getElementById('supplier_email').value = supplier.email || '';
        document.getElementById('supplier_telephone').value = supplier.telephone || '';
        document.getElementById('supplier_adresse').value = supplier.adresse || '';
        document.getElementById('supplier_submit_text').textContent = 'Modifier';
        this.openModal('supplierModal');
    }

    deleteSupplier(id, name) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le fournisseur "${name}" ?\n\nCette action est irréversible.`)) {
            this.submitForm({
                action: 'delete_supplier',
                supplier_id: id
            });
        }
    }

    // Mise à jour du statut des commandes
    updateOrderStatus(orderId, newStatus) {
        if (newStatus) {
            this.submitForm({
                action: 'update_order_status',
                order_id: orderId,
                new_status: newStatus
            });
        }
    }

    // Utilitaire pour soumettre des formulaires
    submitForm(data) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.style.display = 'none';
        
        for (const [key, value] of Object.entries(data)) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            form.appendChild(input);
        }
        
        document.body.appendChild(form);
        form.submit();
    }

    // Auto-refresh des alertes
    startAutoRefresh() {
        // Recharger la page toutes les 5 minutes pour mettre à jour les alertes
        setTimeout(() => {
            if (confirm('Actualiser les données pour voir les dernières alertes ?')) {
                window.location.reload();
            } else {
                this.startAutoRefresh(); // Relancer le timer
            }
        }, 300000); // 5 minutes
    }

    // Notifications
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            ${message}
        `;
        
        // Insérer au début du container
        const container = document.querySelector('.alerts-container');
        if (container) {
            container.insertBefore(notification, container.firstChild);
            
            // Supprimer après 5 secondes
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
    }

    // Calcul automatique du montant total
    calculateOrderTotal() {
        const quantity = document.getElementById('order_quantity');
        const price = document.getElementById('order_price');
        const totalElement = document.getElementById('order_total');
        
        if (quantity && price && totalElement) {
            const total = (parseFloat(quantity.value) || 0) * (parseFloat(price.value) || 0);
            totalElement.textContent = total.toFixed(2) + '$';
        }
    }
}

// Initialisation globale
let alertsManager;

document.addEventListener('DOMContentLoaded', function() {
    alertsManager = new AlertsManager();
    
    // Rendre les fonctions disponibles globalement pour les onclick
    window.showSection = (section) => alertsManager.showSection(section);
    window.openModal = (modal) => alertsManager.openModal(modal);
    window.closeModal = (modal) => alertsManager.closeModal(modal);
    window.openOrderModal = (id, name) => alertsManager.openOrderModal(id, name);
    window.openThresholdModal = (id, threshold, name) => alertsManager.openThresholdModal(id, threshold, name);
    window.openSupplierModal = () => alertsManager.openSupplierModal();
    window.editSupplier = (supplier) => alertsManager.editSupplier(supplier);
    window.deleteSupplier = (id, name) => alertsManager.deleteSupplier(id, name);
    window.updateOrderStatus = (id, status) => alertsManager.updateOrderStatus(id, status);
    window.validateOrderForm = () => alertsManager.validateOrderForm();
    window.validateThresholdForm = () => alertsManager.validateThresholdForm();
    window.validateSupplierForm = () => alertsManager.validateSupplierForm();
});

// Export pour utilisation en module (optionnel)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AlertsManager;
}