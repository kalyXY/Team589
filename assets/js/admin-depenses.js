/**
 * JavaScript pour la gestion des dépenses - Administrateur
 * Scolaria - Team589
 */

// Variables globales
let currentPage = 1;
let itemsPerPage = 20;
let totalItems = 0;
let depenses = [];

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    console.log('Initialisation de la page des dépenses...');
    
    // Vérifier que tous les éléments nécessaires sont présents
    const requiredElements = [
        'searchInput', 'categoryFilter', 'monthFilter', 'yearFilter',
        'depenseForm', 'confirmDelete', 'depenseModal', 'deleteModal'
    ];
    
    const missingElements = requiredElements.filter(id => !document.getElementById(id));
    if (missingElements.length > 0) {
        console.error('Éléments manquants:', missingElements);
        showError('Erreur: certains éléments de la page sont manquants');
        return;
    }
    
    console.log('Tous les éléments sont présents, chargement des données...');
    
    loadDepenses();
    loadStats();
    
    // Event listeners
    document.getElementById('searchInput').addEventListener('input', debounce(loadDepenses, 300));
    document.getElementById('categoryFilter').addEventListener('change', loadDepenses);
    document.getElementById('monthFilter').addEventListener('change', loadDepenses);
    document.getElementById('yearFilter').addEventListener('change', loadDepenses);
    
    // Form submission
    document.getElementById('depenseForm').addEventListener('submit', handleFormSubmit);
    
    // Delete confirmation
    document.getElementById('confirmDelete').addEventListener('click', function() {
        console.log('Bouton de confirmation cliqué');
        // Cette fonction sera définie dynamiquement lors de la suppression
    });
    
    console.log('Initialisation terminée');
});

// Charger les dépenses
async function loadDepenses() {
    try {
        const search = document.getElementById('searchInput').value;
        const category = document.getElementById('categoryFilter').value;
        const month = document.getElementById('monthFilter').value;
        const year = document.getElementById('yearFilter').value;
        
        const params = new URLSearchParams({
            action: 'list',
            page: currentPage,
            limit: itemsPerPage,
            search: search,
            category: category,
            month: month,
            year: year
        });
        
        const response = await fetch(`admin_depenses.php?${params}`);
        const data = await response.json();
        
        if (data.success) {
            depenses = data.depenses;
            totalItems = data.total;
            renderTable();
            renderPagination();
        } else {
            showError(data.message || 'Erreur lors du chargement');
        }
    } catch (error) {
        console.error('Erreur:', error);
        showError('Erreur de connexion');
    }
}

// Charger les statistiques
async function loadStats() {
    try {
        const response = await fetch('admin_depenses.php?action=stats');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('totalDepenses').textContent = formatCurrency(data.stats.total);
            document.getElementById('depensesMois').textContent = formatCurrency(data.stats.mois);
            document.getElementById('depensesAnnee').textContent = formatCurrency(data.stats.annee);
            document.getElementById('nombreDepenses').textContent = data.stats.count;
        }
    } catch (error) {
        console.error('Erreur stats:', error);
    }
}

// Rendre le tableau
function renderTable() {
    const tbody = document.getElementById('depensesTableBody');
    const tableInfo = document.getElementById('tableInfo');
    tbody.innerHTML = '';
    
    // Mettre à jour les informations du tableau
    if (totalItems > 0) {
        const start = (currentPage - 1) * itemsPerPage + 1;
        const end = Math.min(currentPage * itemsPerPage, totalItems);
        tableInfo.textContent = `Affichage ${start}-${end} sur ${totalItems} dépenses`;
    } else {
        tableInfo.textContent = 'Aucune dépense trouvée';
    }
    
    if (depenses.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="9" class="text-center py-4">
                    <div class="empty-state">
                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Aucune dépense trouvée</h5>
                        <p class="text-muted">Essayez de modifier vos filtres ou ajoutez une nouvelle dépense.</p>
                    </div>
                </td>
            </tr>
        `;
        return;
    }
    
    depenses.forEach(depense => {
        const row = document.createElement('tr');
        
        // Créer les boutons d'action avec des event listeners
        const editBtn = document.createElement('button');
        editBtn.className = 'btn btn-sm btn-primary me-1';
        editBtn.innerHTML = '<i class="fas fa-edit"></i>';
        editBtn.title = 'Modifier cette dépense';
        editBtn.onclick = () => editDepense(depense.id);
        
        const deleteBtn = document.createElement('button');
        deleteBtn.className = 'btn btn-sm btn-danger';
        deleteBtn.innerHTML = '<i class="fas fa-trash"></i>';
        deleteBtn.title = 'Supprimer cette dépense';
        deleteBtn.onclick = () => deleteDepense(depense.id, depense.description, depense.montant);
        
        // Créer un badge pour la catégorie
        const categoryBadge = depense.categorie_nom ? 
            `<span class="badge bg-info">${escapeHtml(depense.categorie_nom)}</span>` : 
            '<span class="text-muted">-</span>';
        
        // Formater le montant avec une couleur
        const amountClass = parseFloat(depense.montant) > 1000 ? 'text-danger' : 
                           parseFloat(depense.montant) > 500 ? 'text-warning' : 'text-success';
        
        row.innerHTML = `
            <td><span class="badge bg-secondary">#${depense.id}</span></td>
            <td><i class="fas fa-calendar text-primary"></i> ${formatDate(depense.date)}</td>
            <td><strong>${escapeHtml(depense.description)}</strong></td>
            <td class="text-end ${amountClass}"><strong>${formatCurrency(depense.montant)}</strong></td>
            <td>${categoryBadge}</td>
            <td>${depense.fournisseur ? `<i class="fas fa-building text-info"></i> ${escapeHtml(depense.fournisseur)}` : '<span class="text-muted">-</span>'}</td>
            <td>${depense.facture_numero ? `<i class="fas fa-receipt text-success"></i> ${escapeHtml(depense.facture_numero)}` : '<span class="text-muted">-</span>'}</td>
            <td><i class="fas fa-user text-primary"></i> ${escapeHtml(depense.created_by || 'Système')}</td>
            <td class="text-center"></td>
        `;
        
        // Ajouter les boutons dans la dernière cellule
        const actionCell = row.querySelector('td:last-child');
        actionCell.appendChild(editBtn);
        actionCell.appendChild(deleteBtn);
        
        tbody.appendChild(row);
    });
}

// Rendre la pagination
function renderPagination() {
    const pagination = document.getElementById('pagination');
    const totalPages = Math.ceil(totalItems / itemsPerPage);
    
    if (totalPages <= 1) {
        pagination.innerHTML = '';
        return;
    }
    
    let html = '';
    
    // Bouton précédent
    if (currentPage > 1) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPage - 1})">Précédent</a></li>`;
    }
    
    // Pages
    for (let i = 1; i <= totalPages; i++) {
        if (i === currentPage) {
            html += `<li class="page-item active"><span class="page-link">$i</span></li>`;
        } else {
            html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage($i)">$i</a></li>`;
        }
    }
    
    // Bouton suivant
    if (currentPage < totalPages) {
        html += `<li class="page-item"><a class="page-link" href="#" onclick="goToPage(${currentPage + 1})">Suivant</a></li>`;
    }
    
    pagination.innerHTML = html;
}

// Aller à une page
function goToPage(page) {
    currentPage = page;
    loadDepenses();
}

// Ouvrir le modal d'ajout
function openAddModal() {
    document.getElementById('depenseModalLabel').textContent = 'Nouvelle Dépense';
    document.getElementById('depenseForm').reset();
    document.getElementById('depenseId').value = '';
    document.getElementById('date').value = new Date().toISOString().split('T')[0];
    
    // Utiliser la méthode Bootstrap native ou une alternative
    const modalElement = document.getElementById('depenseModal');
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        // Fallback si Bootstrap n'est pas disponible
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// Éditer une dépense
async function editDepense(id) {
    try {
        console.log('Édition de la dépense ID:', id);
        const response = await fetch(`admin_depenses.php?action=get&id=${id}`);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        const data = await response.json();
        console.log('Données reçues:', data);
        
        if (data.success) {
            const depense = data.depense;
            
            // Mettre à jour le titre du modal
            document.getElementById('depenseModalLabel').textContent = 'Modifier la Dépense';
            
            // Remplir le formulaire avec les données existantes
            document.getElementById('depenseId').value = depense.id;
            document.getElementById('description').value = depense.description || '';
            document.getElementById('montant').value = depense.montant || '';
            document.getElementById('date').value = depense.date || '';
            document.getElementById('categorie').value = depense.categorie_nom || '';
            document.getElementById('fournisseur').value = depense.fournisseur || '';
            document.getElementById('facture_numero').value = depense.facture_numero || '';
            document.getElementById('notes').value = depense.notes || '';
            
            // Ouvrir le modal
            const modalElement = document.getElementById('depenseModal');
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            } else {
                // Fallback si Bootstrap n'est pas disponible
                modalElement.style.display = 'block';
                modalElement.classList.add('show');
                document.body.classList.add('modal-open');
            }
        } else {
            showError(data.message || 'Erreur lors du chargement de la dépense');
        }
    } catch (error) {
        console.error('Erreur lors de l\'édition:', error);
        showError('Erreur de connexion lors du chargement de la dépense');
    }
}

// Gérer la soumission du formulaire
async function handleFormSubmit(event) {
    event.preventDefault();
    
    console.log('Soumission du formulaire...');
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    // Validation des champs requis
    if (!data.description || !data.montant || !data.date) {
        showError('Veuillez remplir tous les champs obligatoires');
        return;
    }
    
    // Validation du montant
    if (isNaN(data.montant) || parseFloat(data.montant) <= 0) {
        showError('Le montant doit être un nombre positif');
        return;
    }
    
    console.log('Données à envoyer:', data);
    
    try {
        // Créer l'URL avec l'action en paramètre GET
        const url = 'admin_depenses.php?action=save';
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(data)
        });
        
        console.log('Réponse reçue:', response);
        console.log('Status:', response.status);
        console.log('Headers:', response.headers);
        
        // Lire le contenu de la réponse pour déboguer
        const responseText = await response.text();
        console.log('Contenu de la réponse:', responseText);
        
        // Essayer de parser le JSON
        let result;
        try {
            result = JSON.parse(responseText);
            console.log('Résultat parsé:', result);
        } catch (parseError) {
            console.error('Erreur de parsing JSON:', parseError);
            console.error('Réponse brute:', responseText);
            showError('Réponse invalide du serveur');
            return;
        }
        
        if (result.success) {
            showSuccess(result.message || 'Dépense enregistrée avec succès');
            
            // Fermer le modal
            closeModal('depenseModal');
            
            // Recharger les données
            loadDepenses();
            loadStats();
        } else {
            showError(result.message || 'Erreur lors de l\'enregistrement');
        }
    } catch (error) {
        console.error('Erreur lors de la sauvegarde:', error);
        showError('Erreur de connexion lors de la sauvegarde');
    }
}

// Supprimer une dépense
function deleteDepense(id, description, montant) {
    console.log('Suppression de la dépense ID:', id, 'Description:', description, 'Montant:', montant);
    
    // Mettre à jour le modal de confirmation
    document.getElementById('deleteDescription').textContent = description || 'N/A';
    document.getElementById('deleteMontant').textContent = formatCurrency(montant || 0);
    
    // Définir la fonction de suppression
    document.getElementById('confirmDelete').onclick = () => performDelete(id);
    
    // Ouvrir le modal de confirmation
    const modalElement = document.getElementById('deleteModal');
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = new bootstrap.Modal(modalElement);
        modal.show();
    } else {
        // Fallback si Bootstrap n'est pas disponible
        modalElement.style.display = 'block';
        modalElement.classList.add('show');
        document.body.classList.add('modal-open');
    }
}

// Confirmer la suppression
async function performDelete(id) {
    try {
        console.log('Exécution de la suppression pour l\'ID:', id);
        
        // Créer l'URL avec l'action en paramètre GET
        const url = 'admin_depenses.php?action=delete';
        
        console.log('URL de la requête:', url);
        console.log('Méthode: POST');
        console.log('Données envoyées:', { id: id });
        
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ id: id })
        });
        
        console.log('Réponse reçue:', response);
        console.log('Status:', response.status);
        console.log('Headers:', response.headers);
        
        // Lire le contenu de la réponse pour déboguer
        const responseText = await response.text();
        console.log('Contenu de la réponse:', responseText);
        
        // Essayer de parser le JSON
        let result;
        try {
            result = JSON.parse(responseText);
            console.log('Résultat parsé:', result);
        } catch (parseError) {
            console.error('Erreur de parsing JSON:', parseError);
            console.error('Réponse brute:', responseText);
            showError('Réponse invalide du serveur: ' + responseText);
            return;
        }
        
        if (result.success) {
            showSuccess(result.message || 'Dépense supprimée avec succès');
            
            // Fermer le modal
            closeModal('deleteModal');
            
            // Recharger les données
            loadDepenses();
            loadStats();
        } else {
            showError(result.message || 'Erreur lors de la suppression');
        }
    } catch (error) {
        console.error('Erreur lors de la suppression:', error);
        showError('Erreur de connexion lors de la suppression: ' + error.message);
    }
}

// Fermer un modal
function closeModal(modalId) {
    console.log('Fermeture du modal:', modalId);
    
    const modalElement = document.getElementById(modalId);
    if (!modalElement) {
        console.error('Modal non trouvé:', modalId);
        return;
    }
    
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const modal = bootstrap.Modal.getInstance(modalElement);
        if (modal) {
            modal.hide();
        } else {
            // Si pas d'instance, en créer une nouvelle et la cacher
            const newModal = new bootstrap.Modal(modalElement);
            newModal.hide();
        }
    } else {
        // Fallback si Bootstrap n'est pas disponible
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        document.body.classList.remove('modal-open');
    }
}

// Réinitialiser les filtres
function resetFilters() {
    document.getElementById('searchInput').value = '';
    document.getElementById('categoryFilter').value = '';
    document.getElementById('monthFilter').value = '';
    document.getElementById('yearFilter').value = '';
    
    currentPage = 1;
    loadDepenses();
}

// Exporter les données
function exportData() {
    const search = document.getElementById('searchInput').value;
    const category = document.getElementById('categoryFilter').value;
    const month = document.getElementById('monthFilter').value;
    const year = document.getElementById('yearFilter').value;
    
    const params = new URLSearchParams({
        action: 'export',
        search: search,
        category: category,
        month: month,
        year: year
    });
    
    window.open(`admin_depenses.php?${params}`, '_blank');
}

// Utilitaires
function formatCurrency(amount) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('fr-FR');
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showSuccess(message) {
    if (typeof showNotification === 'function') {
        showNotification(message, 'success');
    } else {
        alert(message);
    }
}

function showError(message) {
    if (typeof showNotification === 'function') {
        showNotification(message, 'error');
    } else {
        alert(message);
    }
}

// Gestion des modals avec Bootstrap ou fallback
document.addEventListener('DOMContentLoaded', function() {
    // Fermer les modals en cliquant sur le bouton de fermeture
    document.querySelectorAll('.btn-close, .btn-secondary').forEach(button => {
        button.addEventListener('click', function() {
            const modal = this.closest('.modal');
            if (modal) {
                closeModal(modal.id);
            }
        });
    });
    
    // Fermer les modals en cliquant à l'extérieur
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal(this.id);
            }
        });
    });
    
    // Fermer les modals avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModal = document.querySelector('.modal.show');
            if (openModal) {
                closeModal(openModal.id);
            }
        }
    });
    
    // Ajouter des event listeners pour les boutons de fermeture des modals
    document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(button => {
        button.addEventListener('click', function() {
            const modalId = this.getAttribute('data-bs-target') || this.closest('.modal').id;
            if (modalId) {
                closeModal(modalId.replace('#', ''));
            }
        });
    });
});
