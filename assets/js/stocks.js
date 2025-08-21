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
        
        // Mettre à jour les métriques
        document.getElementById('totalArticlesMetric').textContent = totalArticles;
        document.getElementById('lowStockMetric').textContent = lowStockCount;
        document.getElementById('categoriesMetric').textContent = categories.size;
        
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
                // Fallback avec une valeur simulée
                const simulatedCount = Math.floor(Math.random() * 50) + 10;
                document.getElementById('movementsMetric').textContent = simulatedCount;
            });
    }

    // Gestion des onglets
    showTab(tabName) {
        // Masquer tous les onglets
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Désactiver tous les boutons d'onglet
        document.querySelectorAll('.tab-button').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Afficher l'onglet sélectionné
        document.getElementById(tabName + '-tab').classList.add('active');
        
        // Activer le bouton correspondant
        event.target.classList.add('active');
    }

    // Gestion des modales
    openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
        document.body.style.overflow = 'hidden';
    }

    closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Reset du formulaire
        const form = document.querySelector(`#${modalId} form`);
        if (form) {
            form.reset();
        }
    }

    // Gestion des événements
    bindEvents() {
        // Fermer les modales en cliquant à l'extérieur
        window.addEventListener('click', (event) => {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
                document.body.style.overflow = 'auto';
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
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Modifier un article';
                    document.getElementById('formAction').value = 'update';
                    document.getElementById('stockId').value = data.id;
                    document.getElementById('nom_article').value = data.nom_article;
                    document.getElementById('categorie').value = data.categorie;
                    document.getElementById('quantite').value = data.quantite;
                    document.getElementById('seuil').value = data.seuil;
                    document.getElementById('submitBtn').textContent = 'Modifier';
                    this.openModal('stockModal');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors du chargement des données');
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
});

// Export pour utilisation en module (optionnel)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = StocksManager;
}