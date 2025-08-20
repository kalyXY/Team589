/**
 * SCOLARIA - JavaScript Principal
 * Gestion des interactions UI, dark mode, sidebar, notifications, etc.
 */

// ========================================
// CONFIGURATION GLOBALE
// ========================================

const SCOLARIA = {
    config: {
        sidebarStorageKey: 'scolaria_sidebar_collapsed',
        themeStorageKey: 'scolaria_theme',
        animationDuration: 300,
        debounceDelay: 300
    },
    
    elements: {
        sidebar: null,
        sidebarToggle: null,
        themeToggle: null,
        mobileOverlay: null,
        userDropdown: null,
        notificationsDropdown: null
    },
    
    state: {
        sidebarCollapsed: false,
        currentTheme: 'light',
        isMobile: false
    }
};

// ========================================
// INITIALISATION
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    initializeElements();
    initializeSidebar();
    initializeTheme();
    initializeResponsive();
    initializeNotifications();
    initializeUserMenu();
    initializeTooltips();
    initializeAnimations();
    initializeTableFeatures();
    
    console.log('🎓 Scolaria UI initialized successfully!');
});

// ========================================
// GESTION DES ÉLÉMENTS DOM
// ========================================

function initializeElements() {
    SCOLARIA.elements = {
        sidebar: document.getElementById('sidebar'),
        sidebarToggle: document.getElementById('sidebarToggle'),
        themeToggle: document.getElementById('themeToggle'),
        themeIcon: document.getElementById('themeIcon'),
        themeText: document.getElementById('themeText'),
        mobileOverlay: document.getElementById('mobileOverlay'),
        userDropdown: document.getElementById('userDropdown'),
        notificationsDropdown: document.getElementById('notificationsDropdown')
    };
}

// ========================================
// GESTION DE LA SIDEBAR
// ========================================

function initializeSidebar() {
    const { sidebar, sidebarToggle } = SCOLARIA.elements;
    
    if (!sidebar || !sidebarToggle) return;
    
    // Charger l'état sauvegardé
    const savedState = localStorage.getItem(SCOLARIA.config.sidebarStorageKey);
    if (savedState === 'true') {
        toggleSidebar(true);
    }
    
    // Event listener pour le toggle
    sidebarToggle.addEventListener('click', () => toggleSidebar());
    
    // Fermer la sidebar sur mobile quand on clique sur un lien
    const navLinks = sidebar.querySelectorAll('.nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (SCOLARIA.state.isMobile) {
                closeSidebar();
            }
        });
    });
}

function toggleSidebar(force = null) {
    const { sidebar } = SCOLARIA.elements;
    
    if (force !== null) {
        SCOLARIA.state.sidebarCollapsed = force;
    } else {
        SCOLARIA.state.sidebarCollapsed = !SCOLARIA.state.sidebarCollapsed;
    }
    
    if (SCOLARIA.state.isMobile) {
        // Sur mobile, on affiche/cache la sidebar
        sidebar.classList.toggle('active', !SCOLARIA.state.sidebarCollapsed);
        document.body.classList.toggle('sidebar-open', !SCOLARIA.state.sidebarCollapsed);
        toggleMobileOverlay(!SCOLARIA.state.sidebarCollapsed);
    } else {
        // Sur desktop, on collapse la sidebar
        sidebar.classList.toggle('collapsed', SCOLARIA.state.sidebarCollapsed);
    }
    
    // Sauvegarder l'état
    localStorage.setItem(SCOLARIA.config.sidebarStorageKey, SCOLARIA.state.sidebarCollapsed);
    
    // Animation du contenu
    animateContentResize();
}

function closeSidebar() {
    if (SCOLARIA.state.isMobile) {
        toggleSidebar(true);
    }
}

function toggleMobileOverlay(show) {
    const { mobileOverlay } = SCOLARIA.elements;
    if (mobileOverlay) {
        mobileOverlay.style.display = show ? 'block' : 'none';
    }
}

// ========================================
// GESTION DU THÈME (DARK MODE)
// ========================================

function initializeTheme() {
    const { themeToggle } = SCOLARIA.elements;
    
    if (!themeToggle) return;
    
    // Charger le thème sauvegardé ou détecter la préférence système
    const savedTheme = localStorage.getItem(SCOLARIA.config.themeStorageKey);
    const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    
    SCOLARIA.state.currentTheme = savedTheme || (systemPrefersDark ? 'dark' : 'light');
    
    applyTheme(SCOLARIA.state.currentTheme);
    
    // Event listener pour le toggle
    themeToggle.addEventListener('click', toggleTheme);
    
    // Écouter les changements de préférence système
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
        if (!localStorage.getItem(SCOLARIA.config.themeStorageKey)) {
            applyTheme(e.matches ? 'dark' : 'light');
        }
    });
}

function toggleTheme() {
    const newTheme = SCOLARIA.state.currentTheme === 'light' ? 'dark' : 'light';
    applyTheme(newTheme);
    localStorage.setItem(SCOLARIA.config.themeStorageKey, newTheme);
}

function applyTheme(theme) {
    const { themeIcon, themeText } = SCOLARIA.elements;
    
    SCOLARIA.state.currentTheme = theme;
    document.documentElement.setAttribute('data-theme', theme);
    
    // Mettre à jour l'icône et le texte du toggle
    if (themeIcon && themeText) {
        if (theme === 'dark') {
            themeIcon.className = 'fas fa-sun';
            themeText.textContent = 'Clair';
        } else {
            themeIcon.className = 'fas fa-moon';
            themeText.textContent = 'Sombre';
        }
    }
    
    // Animation de transition
    document.body.style.transition = 'background-color 0.3s ease, color 0.3s ease';
    setTimeout(() => {
        document.body.style.transition = '';
    }, 300);
}

// ========================================
// RESPONSIVE DESIGN
// ========================================

function initializeResponsive() {
    checkMobileState();
    
    window.addEventListener('resize', debounce(() => {
        checkMobileState();
        handleResize();
    }, SCOLARIA.config.debounceDelay));
}

function checkMobileState() {
    const wasMobile = SCOLARIA.state.isMobile;
    SCOLARIA.state.isMobile = window.innerWidth <= 768;
    
    if (wasMobile !== SCOLARIA.state.isMobile) {
        handleMobileStateChange();
    }
}

function handleMobileStateChange() {
    const { sidebar } = SCOLARIA.elements;
    
    if (SCOLARIA.state.isMobile) {
        // Passage en mobile
        sidebar.classList.remove('collapsed');
        sidebar.classList.remove('active');
        toggleMobileOverlay(false);
        document.body.classList.remove('sidebar-open');
    } else {
        // Passage en desktop
        sidebar.classList.remove('active');
        toggleMobileOverlay(false);
        document.body.classList.remove('sidebar-open');
        
        // Restaurer l'état collapsed si nécessaire
        const savedState = localStorage.getItem(SCOLARIA.config.sidebarStorageKey);
        if (savedState === 'true') {
            sidebar.classList.add('collapsed');
        }
    }
}

function handleResize() {
    // Recalculer les positions des dropdowns
    closeAllDropdowns();
}

// ========================================
// GESTION DES NOTIFICATIONS
// ========================================

function initializeNotifications() {
    // Auto-masquer les messages flash
    const flashMessages = document.querySelectorAll('.alert');
    flashMessages.forEach(alert => {
        setTimeout(() => {
            hideAlert(alert);
        }, 5000);
    });
}

function toggleNotifications() {
    const dropdown = SCOLARIA.elements.notificationsDropdown;
    if (dropdown) {
        const isVisible = dropdown.style.display === 'block';
        closeAllDropdowns();
        if (!isVisible) {
            showDropdown(dropdown);
        }
    }
}

function showNotification(message, type = 'info', duration = 5000) {
    const notification = createNotificationElement(message, type);
    document.body.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => notification.classList.add('show'), 10);
    
    // Auto-suppression
    setTimeout(() => hideAlert(notification), duration);
    
    return notification;
}

function createNotificationElement(message, type) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast animate-slide-in`;
    
    const icon = getAlertIcon(type);
    notification.innerHTML = `
        <i class="fas fa-${icon}"></i>
        <span>${message}</span>
        <button class="alert-close" onclick="hideAlert(this.parentElement)">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    return notification;
}

function getAlertIcon(type) {
    const icons = {
        success: 'check-circle',
        error: 'exclamation-triangle',
        warning: 'exclamation-triangle',
        info: 'info-circle'
    };
    return icons[type] || 'info-circle';
}

function hideAlert(alertElement) {
    if (alertElement) {
        alertElement.style.opacity = '0';
        alertElement.style.transform = 'translateY(-20px)';
        setTimeout(() => {
            if (alertElement.parentNode) {
                alertElement.parentNode.removeChild(alertElement);
            }
        }, 300);
    }
}

// ========================================
// GESTION DU MENU UTILISATEUR
// ========================================

function initializeUserMenu() {
    // Fermer les dropdowns quand on clique ailleurs
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.user-menu') && !e.target.closest('.notifications')) {
            closeAllDropdowns();
        }
    });
}

function toggleUserMenu() {
    const dropdown = SCOLARIA.elements.userDropdown;
    if (dropdown) {
        const isVisible = dropdown.style.display === 'block';
        closeAllDropdowns();
        if (!isVisible) {
            showDropdown(dropdown);
        }
    }
}

function showDropdown(dropdown) {
    dropdown.style.display = 'block';
    dropdown.classList.add('animate-fade-in');
}

function closeAllDropdowns() {
    const dropdowns = [
        SCOLARIA.elements.userDropdown,
        SCOLARIA.elements.notificationsDropdown
    ];
    
    dropdowns.forEach(dropdown => {
        if (dropdown) {
            dropdown.style.display = 'none';
            dropdown.classList.remove('animate-fade-in');
        }
    });
}

// ========================================
// GESTION DES TOOLTIPS
// ========================================

function initializeTooltips() {
    const tooltipElements = document.querySelectorAll('[data-tooltip]');
    
    tooltipElements.forEach(element => {
        element.addEventListener('mouseenter', showTooltip);
        element.addEventListener('mouseleave', hideTooltip);
    });
}

function showTooltip(e) {
    const text = e.target.getAttribute('data-tooltip');
    if (!text) return;
    
    const tooltip = document.createElement('div');
    tooltip.className = 'tooltip';
    tooltip.textContent = text;
    tooltip.id = 'active-tooltip';
    
    document.body.appendChild(tooltip);
    
    const rect = e.target.getBoundingClientRect();
    tooltip.style.left = rect.left + (rect.width / 2) - (tooltip.offsetWidth / 2) + 'px';
    tooltip.style.top = rect.top - tooltip.offsetHeight - 10 + 'px';
    
    setTimeout(() => tooltip.classList.add('show'), 10);
}

function hideTooltip() {
    const tooltip = document.getElementById('active-tooltip');
    if (tooltip) {
        tooltip.classList.remove('show');
        setTimeout(() => tooltip.remove(), 200);
    }
}

// ========================================
// ANIMATIONS
// ========================================

function initializeAnimations() {
    // Observer pour les animations au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-slide-in');
            }
        });
    }, observerOptions);
    
    // Observer les cartes et tableaux
    const animatedElements = document.querySelectorAll('.stats-card, .card, .table-container');
    animatedElements.forEach(el => observer.observe(el));
}

function animateContentResize() {
    const mainContent = document.querySelector('.main-content');
    if (mainContent) {
        mainContent.style.transition = 'margin-left 0.3s ease';
        setTimeout(() => {
            mainContent.style.transition = '';
        }, 300);
    }
}

// ========================================
// FONCTIONNALITÉS DES TABLEAUX
// ========================================

function initializeTableFeatures() {
    initializeTableSearch();
    initializeTableSorting();
}

function initializeTableSearch() {
    const searchInputs = document.querySelectorAll('[id$="Search"]');
    
    searchInputs.forEach(input => {
        const tableId = input.id.replace('Search', '');
        const table = document.getElementById(tableId);
        
        if (table) {
            input.addEventListener('input', debounce((e) => {
                filterTable(table, e.target.value);
            }, SCOLARIA.config.debounceDelay));
        }
    });
}

function filterTable(table, searchTerm) {
    const tbody = table.querySelector('tbody');
    const rows = tbody.querySelectorAll('tr');
    
    searchTerm = searchTerm.toLowerCase();
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const shouldShow = text.includes(searchTerm);
        row.style.display = shouldShow ? '' : 'none';
    });
    
    // Mettre à jour les couleurs alternées
    updateTableStripes(tbody);
}

function initializeTableSorting() {
    const sortableHeaders = document.querySelectorAll('th.sortable');
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const table = header.closest('table');
            const columnIndex = Array.from(header.parentNode.children).indexOf(header);
            const column = header.getAttribute('data-column');
            
            sortTable(table, columnIndex, column);
        });
    });
}

function sortTable(table, columnIndex, column) {
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const header = table.querySelector(`th[data-column="${column}"]`);
    
    // Déterminer l'ordre de tri
    const currentOrder = header.getAttribute('data-sort') || 'asc';
    const newOrder = currentOrder === 'asc' ? 'desc' : 'asc';
    
    // Réinitialiser tous les headers
    table.querySelectorAll('th.sortable').forEach(h => {
        h.removeAttribute('data-sort');
        h.querySelector('.sort-icon').className = 'fas fa-sort sort-icon';
    });
    
    // Mettre à jour le header actuel
    header.setAttribute('data-sort', newOrder);
    header.querySelector('.sort-icon').className = `fas fa-sort-${newOrder === 'asc' ? 'up' : 'down'} sort-icon`;
    
    // Trier les lignes
    rows.sort((a, b) => {
        const aValue = a.cells[columnIndex].textContent.trim();
        const bValue = b.cells[columnIndex].textContent.trim();
        
        // Détecter le type de données
        const aNum = parseFloat(aValue.replace(/[^0-9.-]/g, ''));
        const bNum = parseFloat(bValue.replace(/[^0-9.-]/g, ''));
        
        let comparison = 0;
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            // Comparaison numérique
            comparison = aNum - bNum;
        } else {
            // Comparaison alphabétique
            comparison = aValue.localeCompare(bValue);
        }
        
        return newOrder === 'asc' ? comparison : -comparison;
    });
    
    // Réorganiser le tableau
    rows.forEach(row => tbody.appendChild(row));
    
    // Mettre à jour les couleurs alternées
    updateTableStripes(tbody);
}

function updateTableStripes(tbody) {
    const visibleRows = Array.from(tbody.querySelectorAll('tr')).filter(row => 
        row.style.display !== 'none'
    );
    
    visibleRows.forEach((row, index) => {
        row.style.backgroundColor = index % 2 === 0 ? '' : 'rgba(30, 136, 229, 0.02)';
    });
}

// ========================================
// FONCTIONS D'EXPORT
// ========================================

function exportTable(tableId, format = 'csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    const data = extractTableData(table);
    
    switch (format) {
        case 'csv':
            exportToCSV(data, `${tableId}_export.csv`);
            break;
        case 'excel':
            exportToExcel(data, `${tableId}_export.xlsx`);
            break;
        default:
            exportToCSV(data, `${tableId}_export.csv`);
    }
}

function extractTableData(table) {
    const headers = Array.from(table.querySelectorAll('thead th')).map(th => th.textContent.trim());
    const rows = Array.from(table.querySelectorAll('tbody tr')).map(tr => 
        Array.from(tr.querySelectorAll('td')).map(td => td.textContent.trim())
    );
    
    return { headers, rows };
}

function exportToCSV(data, filename) {
    const csvContent = [
        data.headers.join(','),
        ...data.rows.map(row => row.map(cell => `"${cell.replace(/"/g, '""')}"`).join(','))
    ].join('\n');
    
    downloadFile(csvContent, filename, 'text/csv');
}

function downloadFile(content, filename, mimeType) {
    const blob = new Blob([content], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
    
    showNotification(`Fichier ${filename} téléchargé avec succès`, 'success');
}

// ========================================
// FONCTIONS DE PAGINATION
// ========================================

function changePage(page) {
    const currentUrl = new URL(window.location);
    currentUrl.searchParams.set('page', page);
    window.location.href = currentUrl.toString();
}

// ========================================
// UTILITAIRES
// ========================================

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

function formatNumber(number, decimals = 0) {
    return new Intl.NumberFormat('fr-FR', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(number);
}

function formatCurrency(amount, currency = 'EUR') {
    return new Intl.NumberFormat('fr-FR', {
        style: 'currency',
        currency: currency
    }).format(amount);
}

function formatDate(date, options = {}) {
    const defaultOptions = {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    };
    
    return new Intl.DateTimeFormat('fr-FR', { ...defaultOptions, ...options }).format(new Date(date));
}

// ========================================
// API HELPERS
// ========================================

async function apiRequest(url, options = {}) {
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    try {
        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API Request failed:', error);
        showNotification('Erreur de communication avec le serveur', 'error');
        throw error;
    }
}

// ========================================
// GESTION DES MODALES
// ========================================

function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.style.display = 'block';
        modal.classList.add('animate-fade-in');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('animate-fade-in');
        setTimeout(() => {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }, 200);
    }
}

// Fermer les modales avec Escape
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        const openModals = document.querySelectorAll('.modal[style*="display: block"]');
        openModals.forEach(modal => closeModal(modal.id));
    }
});

// ========================================
// VALIDATION DE FORMULAIRES
// ========================================

function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return false;
    
    const requiredFields = form.querySelectorAll('[required]');
    let isValid = true;
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'Ce champ est obligatoire');
            isValid = false;
        } else {
            clearFieldError(field);
        }
    });
    
    return isValid;
}

function showFieldError(field, message) {
    clearFieldError(field);
    
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'field-error';
    errorDiv.textContent = message;
    
    field.parentNode.appendChild(errorDiv);
}

function clearFieldError(field) {
    field.classList.remove('error');
    const existingError = field.parentNode.querySelector('.field-error');
    if (existingError) {
        existingError.remove();
    }
}

// ========================================
// RACCOURCIS CLAVIER
// ========================================

document.addEventListener('keydown', (e) => {
    // Ctrl/Cmd + K : Focus sur la recherche
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], input[placeholder*="recherch"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Ctrl/Cmd + B : Toggle sidebar
    if ((e.ctrlKey || e.metaKey) && e.key === 'b') {
        e.preventDefault();
        toggleSidebar();
    }
    
    // Ctrl/Cmd + D : Toggle dark mode
    if ((e.ctrlKey || e.metaKey) && e.key === 'd') {
        e.preventDefault();
        toggleTheme();
    }
});

// ========================================
// EXPORT DES FONCTIONS GLOBALES
// ========================================

// Rendre certaines fonctions disponibles globalement
window.SCOLARIA = SCOLARIA;
window.toggleSidebar = toggleSidebar;
window.closeSidebar = closeSidebar;
window.toggleTheme = toggleTheme;
window.toggleNotifications = toggleNotifications;
window.toggleUserMenu = toggleUserMenu;
window.showNotification = showNotification;
window.hideAlert = hideAlert;
window.exportTable = exportTable;
window.changePage = changePage;
window.openModal = openModal;
window.closeModal = closeModal;
window.validateForm = validateForm;

console.log('📱 Scolaria JavaScript loaded and ready!');
