/**
 * JavaScript pour le module Gestion des Stocks - Scolaria Team589
 * Style moderne inspiré du module Alerts
 */

class StocksManager {
    constructor() {
        this.init();
    }

    init() {
        this.updateMetrics();
        this.updateMovementsMetric();
        this.bindEvents();
    }

    // Calcul et affichage des métriques
    updateMetrics() {
        const tableRows = document.querySelectorAll('#stocksTable tbody tr');
        const totalArticles = tableRows.length;
        let lowStockCount = 0;
        const categories = new Set();
        
        tableRows.forEach(row => {
            if (row.classList.contains('low-stock')) {
                lowStockCount++;
            }
            // Extraire la catégorie de la 3ème colonne
            const categoryCell = row.cells[2];
            if (categoryCell) {
                categories.add(categoryCell.textContent.trim());
            }
        });
        
        // Mettre à jour les métriques (si présentes dans le DOM)
        const totalEl = document.getElementById('totalArticlesMetric');
        if (totalEl) totalEl.textContent = totalArticles;
        const lowEl = document.getElementById('lowStockMetric');
        if (lowEl) lowEl.textContent = lowStockCount;
        const catEl = document.getElementById('categoriesMetric');
        if (catEl) catEl.textContent = categories.size;
        
        // Animation des nombres
        this.animateNumbers();
    }
    
    // Animation des nombres
    animateNumbers() {
        const numbers = document.querySelectorAll('.stat-number');
        numbers.forEach(element => {
            const finalValue = parseInt(element.textContent);
            let currentValue = 0;
            const increment = Math.ceil(finalValue / 20);
            
            const timer = setInterval(() => {
                currentValue += increment;
                if (currentValue >= finalValue) {
                    currentValue = finalValue;
                    clearInterval(timer);
                }
                element.textContent = currentValue;
            }, 50);
        });
    }
    
    // Calculer les mouvements
    updateMovementsMetric() {
        fetch('?ajax=count_movements')
            .then(response => response.json())
            .then(data => {
                document.getElementById('movementsMetric').textContent = data.count || 0;
            })
            .catch(() => {
                // Pas de valeur simulée en prod: afficher 0
                document.getElementById('movementsMetric').textContent = 0;
            });
    }

    // Gestion des onglets
    showTab(tabName) {
        console.log('showTab called with:', tabName);
        
        // Masquer tous les onglets
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.style.display = 'none';
            tab.classList.remove('active');
        });
        
        // Désactiver tous les boutons d'onglet
        document.querySelectorAll('.btn[onclick*="showTab"]').forEach(btn => {
            btn.style.borderBottom = '3px solid transparent';
            btn.classList.remove('active');
        });
        
        // Afficher l'onglet sélectionné
        const targetTab = document.getElementById(tabName + '-tab');
        if (targetTab) {
            targetTab.style.display = 'block';
            targetTab.classList.add('active');
        }
        
        // Activer le bouton correspondant
        const targetBtn = document.getElementById(tabName + 'Tab');
        if (targetBtn) {
            targetBtn.style.borderBottom = '3px solid var(--primary-color)';
            targetBtn.classList.add('active');
        }
        
        console.log('Tab switched to:', tabName);
    }

    // Gestion des modales
    openModal(modalId) {
        const el = document.getElementById(modalId);
        if (!el) return;
        el.classList.add('show');
        document.body.style.overflow = 'hidden';
    }

    closeModal(modalId) {
        const el = document.getElementById(modalId);
        if (!el) return;
        el.classList.remove('show');
        // Si aucune autre modale n'est ouverte, réactiver le scroll
        if (!document.querySelector('.modal.show')) {
            document.body.style.overflow = 'auto';
        }
        
        // Reset du formulaire
        const form = document.querySelector(`#${modalId} form`);
        if (form) {
            form.reset();
        }
    }

    closeAllModals() {
        document.querySelectorAll('.modal.show').forEach(m => m.classList.remove('show'));
        document.body.style.overflow = 'auto';
    }

    // Gestion des événements
    bindEvents() {
        // Fermer les modales en cliquant à l'extérieur
        window.addEventListener('click', (event) => {
            if (event.target.classList && event.target.classList.contains('modal')) {
                event.target.classList.remove('show');
                if (!document.querySelector('.modal.show')) {
                    document.body.style.overflow = 'auto';
                }
            }
        });
        
        // Fermer avec la touche Echap
        window.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                this.closeAllModals();
            }
        });

        // Recherche en temps réel
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                this.performSearch();
            });
        }

        // Filtre par catégorie
        const categoryFilter = document.getElementById('categoryFilter');
        if (categoryFilter) {
            categoryFilter.addEventListener('change', () => {
                this.performSearch();
            });
        }

        // Filtre stock faible
        const lowStockFilter = document.getElementById('lowStockFilter');
        if (lowStockFilter) {
            lowStockFilter.addEventListener('change', () => {
                this.performSearch();
            });
        }
    }

    // Recherche et filtrage
    performSearch() {
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categoryFilter').value;
        const lowStock = document.getElementById('lowStockFilter').checked;
        
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (category) params.append('category', category);
        if (lowStock) params.append('low_stock', '1');
        
        // Recharger la page avec les nouveaux paramètres
        window.location.search = params.toString();
    }

    // Gestion des articles
    openAddModal() {
        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-plus-circle"></i> Ajouter un article';
        document.getElementById('formAction').value = 'add';
        document.getElementById('submitBtn').textContent = 'Ajouter';
        document.getElementById('stockForm').reset();
        this.openModal('stockModal');
    }

    openEditModal(id) {
        fetch(`?ajax=get_stock&id=${id}`)
            .then(async (response) => {
                const status = response.status;
                const url = response.url;
                const text = await response.text();
                try {
                    const data = JSON.parse(text);
                    if (data) {
                        document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Modifier un article';
                        document.getElementById('formAction').value = 'update';
                        document.getElementById('stockId').value = data.id;
                        document.getElementById('nom_article').value = data.nom_article;
                        document.getElementById('categorie').value = data.categorie;
                        document.getElementById('quantite').value = data.quantite;
                        document.getElementById('seuil').value = data.seuil;
                        if (data.prix_achat) document.getElementById('prix_achat').value = data.prix_achat;
                        if (data.prix_vente) document.getElementById('prix_vente').value = data.prix_vente;
                        const submitBtn = document.getElementById('submitBtn');
                        submitBtn.textContent = 'Modifier';
                        submitBtn.className = 'btn btn-warning';
                        this.openModal('stockModal');
                    } else {
                        console.error('Aucun article trouvé pour id =', id);
                        if (typeof showError === 'function') { showError('Article introuvable.'); } else { console.error('Article introuvable.'); }
                    }
                } catch (err) {
                    console.error('Réponse non-JSON depuis', url, '(status', status + '):', text);
                    if (typeof showError === 'function') { showError('Erreur lors du chargement des données.'); }
                }
            })
            .catch(error => {
                console.error('Erreur réseau:', error);
                if (typeof showError === 'function') { showError('Erreur réseau lors du chargement des données'); }
            });
    }

    confirmDelete(id, name) {
        document.getElementById('deleteItemName').textContent = name;
        window.deleteId = id;
        this.openModal('deleteModal');
    }

    executeDelete() {
        if (window.deleteId) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="${window.deleteId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    closeDeleteModal() {
        this.closeModal('deleteModal');
        window.deleteId = null;
    }
}

// Initialisation
let stocksManager;

document.addEventListener('DOMContentLoaded', function() {
    stocksManager = new StocksManager();
    
    // Rendre les fonctions disponibles globalement pour les onclick
    window.showTab = (tab) => stocksManager.showTab(tab);
    window.openAddModal = () => stocksManager.openAddModal();
    window.openEditModal = (id) => stocksManager.openEditModal(id);
    window.confirmDelete = (id, name) => stocksManager.confirmDelete(id, name);
    window.executeDelete = () => stocksManager.executeDelete();
    window.closeModal = (modal) => stocksManager.closeModal(modal);
    window.closeDeleteModal = () => stocksManager.closeDeleteModal();
    
    // Ajouter des gestionnaires d'événements pour les boutons d'action
    setTimeout(() => {
        // Gestionnaires pour les boutons d'édition
        document.querySelectorAll('.action-btn[onclick*="openEditModal"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const onclick = this.getAttribute('onclick');
                if (onclick) {
                    eval(onclick);
                }
            });
        });
        
        // Gestionnaires pour les boutons de suppression
        document.querySelectorAll('.action-btn[onclick*="confirmDelete"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const onclick = this.getAttribute('onclick');
                if (onclick) {
                    eval(onclick);
                }
            });
        });
        
        // Gestionnaires pour les boutons d'onglets
        document.querySelectorAll('.btn[onclick*="showTab"]').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                const onclick = this.getAttribute('onclick');
                if (onclick) {
                    eval(onclick);
                }
            });
        });
    }, 100);
});

// Export pour utilisation en module (optionnel)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StocksManager;
}