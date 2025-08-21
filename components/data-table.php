<?php
/**
 * Composant Tableau de Données
 * Composant réutilisable pour afficher des tableaux avec fonctionnalités avancées
 * Team589
 */

/**
 * Rend un tableau de données complet
 * @param array $config Configuration du tableau
 */
function renderDataTable($config) {
    $title = htmlspecialchars($config['title'] ?? '');
    $subtitle = htmlspecialchars($config['subtitle'] ?? '');
    $id = htmlspecialchars($config['id'] ?? 'dataTable');
    $data = $config['data'] ?? [];
    $columns = $config['columns'] ?? [];
    $actions = $config['actions'] ?? [];
    $search = $config['search'] ?? false;
    $export = $config['export'] ?? false;
    $pagination = $config['pagination'] ?? false;
    $itemsPerPage = $config['itemsPerPage'] ?? 10;
    
    echo '<div class="table-container" id="' . $id . 'Container">';
    
    // Header du tableau
    if ($title || $search || $export) {
        echo '<div class="card-header">';
        if ($title) {
            echo '<div>';
            echo '<h3 class="card-title">' . $title . '</h3>';
            if ($subtitle) {
                echo '<p class="card-subtitle">' . $subtitle . '</p>';
            }
            echo '</div>';
        }
        
        if ($search || $export) {
            echo '<div class="table-controls" style="display: flex; gap: var(--spacing-md); align-items: center;">';
            
            if ($search) {
                echo '<div class="table-search" style="position: relative;">';
                echo '<input type="text" id="' . $id . 'Search" placeholder="Rechercher..." ';
                echo 'style="padding: var(--spacing-sm) var(--spacing-md) var(--spacing-sm) 2.5rem; border: 1px solid var(--border-color); border-radius: var(--radius-md); font-size: var(--font-size-sm);">';
                echo '<i class="fas fa-search" style="position: absolute; left: var(--spacing-sm); top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>';
                echo '</div>';
            }
            
            if ($export) {
                echo '<div class="table-export">';
                echo '<button class="btn btn-outline btn-sm" onclick="exportTable(\'' . $id . '\')">';
                echo '<i class="fas fa-download"></i> Exporter';
                echo '</button>';
                echo '</div>';
            }
            
            echo '</div>';
        }
        echo '</div>';
    }
    
    // Tableau
    echo '<div class="table-responsive">';
    echo '<table class="table" id="' . $id . '">';
    
    // En-têtes
    echo '<thead>';
    echo '<tr>';
    foreach ($columns as $column) {
        $label = htmlspecialchars($column['label'] ?? '');
        $sortable = $column['sortable'] ?? false;
        $class = htmlspecialchars($column['class'] ?? '');
        
        echo '<th class="' . $class . '"';
        if ($sortable) {
            echo ' style="cursor: pointer;" onclick="sortTable(\'' . $id . '\', \'' . $column['key'] . '\')"';
        }
        echo '>';
        echo $label;
        if ($sortable) {
            echo ' <i class="fas fa-sort sort-icon" style="opacity: 0.5; margin-left: var(--spacing-xs);"></i>';
        }
        echo '</th>';
    }
    
    if (!empty($actions)) {
        echo '<th class="text-center">Actions</th>';
    }
    
    echo '</tr>';
    echo '</thead>';
    
    // Corps du tableau
    echo '<tbody>';
    if (empty($data)) {
        $colspan = count($columns) + (empty($actions) ? 0 : 1);
        echo '<tr><td colspan="' . $colspan . '" class="text-center" style="padding: var(--spacing-xl); color: var(--text-muted);">';
        echo '<i class="fas fa-inbox" style="font-size: var(--font-size-2xl); margin-bottom: var(--spacing-md);"></i><br>';
        echo 'Aucune donnée disponible';
        echo '</td></tr>';
    } else {
        foreach ($data as $index => $row) {
            echo '<tr>';
            foreach ($columns as $column) {
                $key = $column['key'];
                $type = $column['type'] ?? 'text';
                $class = htmlspecialchars($column['class'] ?? '');
                $value = $row[$key] ?? '';
                
                echo '<td class="' . $class . '">';
                echo renderTableCell($value, $type, $column, $row);
                echo '</td>';
            }
            
            // Actions
            if (!empty($actions)) {
                echo '<td class="text-center">';
                echo '<div class="table-actions" style="display: flex; gap: var(--spacing-xs); justify-content: center;">';
                foreach ($actions as $action) {
                    $icon = htmlspecialchars($action['icon'] ?? 'fas fa-eye');
                    $class = htmlspecialchars($action['class'] ?? 'btn-outline');
                    $title = htmlspecialchars($action['title'] ?? '');
                    $url = $action['url'] ?? '#';
                    
                    // Remplacer les placeholders dans l'URL
                    $url = str_replace('{id}', $row['id'] ?? '', $url);
                    foreach ($row as $k => $v) {
                        $url = str_replace('{' . $k . '}', $v, $url);
                    }
                    
                    echo '<a href="' . htmlspecialchars($url) . '" class="btn btn-sm ' . $class . '" title="' . $title . '">';
                    echo '<i class="' . $icon . '"></i>';
                    echo '</a>';
                }
                echo '</div>';
                echo '</td>';
            }
            
            echo '</tr>';
        }
    }
    echo '</tbody>';
    
    echo '</table>';
    echo '</div>';
    
    // Pagination
    if ($pagination && !empty($data)) {
        $totalItems = count($data);
        $totalPages = ceil($totalItems / $itemsPerPage);
        
        if ($totalPages > 1) {
            echo '<div class="table-pagination" style="padding: var(--spacing-lg); border-top: 1px solid var(--border-color); display: flex; justify-content: between; align-items: center;">';
            echo '<div class="pagination-info" style="color: var(--text-muted); font-size: var(--font-size-sm);">';
            echo 'Affichage de 1 à ' . min($itemsPerPage, $totalItems) . ' sur ' . $totalItems . ' éléments';
            echo '</div>';
            echo '<div class="pagination-controls" style="display: flex; gap: var(--spacing-xs);">';
            
            for ($i = 1; $i <= min(5, $totalPages); $i++) {
                $active = $i === 1 ? 'btn-primary' : 'btn-outline';
                echo '<button class="btn btn-sm ' . $active . '" onclick="goToPage(\'' . $id . '\', ' . $i . ')">' . $i . '</button>';
            }
            
            if ($totalPages > 5) {
                echo '<span style="padding: 0 var(--spacing-sm);">...</span>';
                echo '<button class="btn btn-sm btn-outline" onclick="goToPage(\'' . $id . '\', ' . $totalPages . ')">' . $totalPages . '</button>';
            }
            
            echo '</div>';
            echo '</div>';
        }
    }
    
    echo '</div>';
    
    // JavaScript pour les fonctionnalités
    echo '<script>';
    echo 'document.addEventListener("DOMContentLoaded", function() {';
    echo 'initDataTable("' . $id . '", ' . json_encode($config) . ');';
    echo '});';
    echo '</script>';
}

/**
 * Rend le contenu d'une cellule selon son type
 * @param mixed $value Valeur à afficher
 * @param string $type Type de cellule
 * @param array $column Configuration de la colonne
 * @param array $row Données de la ligne complète
 * @return string HTML de la cellule
 */
function renderTableCell($value, $type, $column, $row) {
    switch ($type) {
        case 'text':
            return htmlspecialchars($value);
            
        case 'number':
            return number_format($value);
            
        case 'currency':
            return number_format($value, 2) . ' €';
            
        case 'percentage':
            return number_format($value, 1) . '%';
            
        case 'date':
            return date('d/m/Y', strtotime($value));
            
        case 'datetime':
            return date('d/m/Y H:i', strtotime($value));
            
        case 'badge':
            $badgeClass = $column['badgeClass'] ?? [];
            $class = $badgeClass[$value] ?? 'primary';
            return '<span class="badge badge-' . $class . '">' . htmlspecialchars($value) . '</span>';
            
        case 'boolean':
            $icon = $value ? 'fa-check text-success' : 'fa-times text-danger';
            return '<i class="fas ' . $icon . '"></i>';
            
        case 'link':
            $url = $column['linkUrl'] ?? '#';
            $url = str_replace('{id}', $row['id'] ?? '', $url);
            return '<a href="' . htmlspecialchars($url) . '" class="table-link">' . htmlspecialchars($value) . '</a>';
            
        case 'image':
            $alt = htmlspecialchars($value);
            return '<img src="' . htmlspecialchars($value) . '" alt="' . $alt . '" style="width: 40px; height: 40px; border-radius: var(--radius-md); object-fit: cover;">';
            
        case 'progress':
            $percentage = min(100, max(0, $value));
            return '<div class="progress-bar" style="background-color: var(--border-light); border-radius: var(--radius-full); height: 8px; width: 100px;">
                        <div class="progress-fill" style="background-color: var(--primary-color); height: 100%; width: ' . $percentage . '%; border-radius: var(--radius-full);"></div>
                    </div>';
            
        case 'status':
            $colors = [
                'active' => 'success',
                'inactive' => 'danger',
                'pending' => 'warning',
                'completed' => 'success',
                'cancelled' => 'danger'
            ];
            $color = $colors[strtolower($value)] ?? 'primary';
            return '<span class="badge badge-' . $color . '">' . htmlspecialchars($value) . '</span>';
            
        case 'actions':
            // Actions personnalisées dans la cellule
            $actions = $column['actions'] ?? [];
            $html = '<div class="cell-actions" style="display: flex; gap: var(--spacing-xs);">';
            foreach ($actions as $action) {
                $icon = htmlspecialchars($action['icon'] ?? 'fas fa-eye');
                $class = htmlspecialchars($action['class'] ?? 'btn-outline');
                $title = htmlspecialchars($action['title'] ?? '');
                $onclick = str_replace('{id}', $row['id'] ?? '', $action['onclick'] ?? '');
                
                $html .= '<button class="btn btn-sm ' . $class . '" title="' . $title . '" onclick="' . $onclick . '">';
                $html .= '<i class="' . $icon . '"></i>';
                $html .= '</button>';
            }
            $html .= '</div>';
            return $html;
            
        default:
            return htmlspecialchars($value);
    }
}

/**
 * Rend un tableau simple
 * @param array $data Données du tableau
 * @param array $headers En-têtes du tableau
 * @param string $class Classes CSS additionnelles
 */
function renderSimpleTable($data, $headers = [], $class = '') {
    if (empty($data)) return;
    
    echo '<div class="table-container">';
    echo '<table class="table ' . htmlspecialchars($class) . '">';
    
    // En-têtes
    if (!empty($headers)) {
        echo '<thead><tr>';
        foreach ($headers as $header) {
            echo '<th>' . htmlspecialchars($header) . '</th>';
        }
        echo '</tr></thead>';
    }
    
    // Données
    echo '<tbody>';
    foreach ($data as $row) {
        echo '<tr>';
        if (is_array($row)) {
            foreach ($row as $cell) {
                echo '<td>' . htmlspecialchars($cell) . '</td>';
            }
        } else {
            echo '<td>' . htmlspecialchars($row) . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody>';
    
    echo '</table>';
    echo '</div>';
}

/**
 * JavaScript pour les fonctionnalités des tableaux
 */
?>
<script>
// Fonctions globales pour les tableaux
function initDataTable(tableId, config) {
    const table = document.getElementById(tableId);
    const searchInput = document.getElementById(tableId + 'Search');
    
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            filterTable(tableId, this.value);
        });
    }
}

function filterTable(tableId, searchTerm) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        const matches = text.includes(searchTerm.toLowerCase());
        row.style.display = matches ? '' : 'none';
    });
}

function sortTable(tableId, column) {
    const table = document.getElementById(tableId);
    const tbody = table.querySelector('tbody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const headerIndex = Array.from(table.querySelectorAll('th')).findIndex(th => 
        th.onclick && th.onclick.toString().includes(column)
    );
    
    if (headerIndex === -1) return;
    
    const isAscending = !table.dataset.sortAsc || table.dataset.sortAsc === 'false';
    table.dataset.sortAsc = isAscending;
    
    rows.sort((a, b) => {
        const aVal = a.cells[headerIndex].textContent.trim();
        const bVal = b.cells[headerIndex].textContent.trim();
        
        // Détection du type de données
        const aNum = parseFloat(aVal.replace(/[^\d.-]/g, ''));
        const bNum = parseFloat(bVal.replace(/[^\d.-]/g, ''));
        
        if (!isNaN(aNum) && !isNaN(bNum)) {
            return isAscending ? aNum - bNum : bNum - aNum;
        } else {
            return isAscending ? aVal.localeCompare(bVal) : bVal.localeCompare(aVal);
        }
    });
    
    // Réorganiser les lignes
    rows.forEach(row => tbody.appendChild(row));
    
    // Mettre à jour les icônes de tri
    table.querySelectorAll('.sort-icon').forEach(icon => {
        icon.className = 'fas fa-sort sort-icon';
        icon.style.opacity = '0.5';
    });
    
    const currentIcon = table.querySelectorAll('th')[headerIndex].querySelector('.sort-icon');
    if (currentIcon) {
        currentIcon.className = isAscending ? 'fas fa-sort-up sort-icon' : 'fas fa-sort-down sort-icon';
        currentIcon.style.opacity = '1';
    }
}

function exportTable(tableId) {
    const table = document.getElementById(tableId);
    const rows = table.querySelectorAll('tr');
    let csv = [];
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('th, td');
        const rowData = Array.from(cells).map(cell => {
            return '"' + cell.textContent.trim().replace(/"/g, '""') + '"';
        });
        csv.push(rowData.join(','));
    });
    
    const csvContent = csv.join('\n');
    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', tableId + '_export.csv');
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

function goToPage(tableId, page) {
    // Implémentation de la pagination
    console.log('Go to page', page, 'for table', tableId);
}
</script>