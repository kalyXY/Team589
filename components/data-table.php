<?php
/**
 * SCOLARIA - Composant Tableau de Données
 * Tableau réutilisable avec tri, pagination et actions
 */

/**
 * Affiche un tableau de données moderne
 * 
 * @param array $config Configuration du tableau
 */
function renderDataTable($config) {
    $title = $config['title'] ?? '';
    $subtitle = $config['subtitle'] ?? '';
    $columns = $config['columns'] ?? [];
    $data = $config['data'] ?? [];
    $actions = $config['actions'] ?? [];
    $pagination = $config['pagination'] ?? false;
    $search = $config['search'] ?? false;
    $export = $config['export'] ?? false;
    $tableId = $config['id'] ?? 'dataTable';
    
    ?>
    <div class="table-container">
        <?php if ($title || $subtitle || $search || $export): ?>
            <div class="table-header">
                <div class="table-title-section">
                    <?php if ($title): ?>
                        <h3 class="table-title"><?= htmlspecialchars($title) ?></h3>
                    <?php endif; ?>
                    <?php if ($subtitle): ?>
                        <p class="table-subtitle"><?= htmlspecialchars($subtitle) ?></p>
                    <?php endif; ?>
                </div>
                
                <div class="table-controls">
                    <?php if ($search): ?>
                        <div class="table-search">
                            <div class="form-group">
                                <input type="text" 
                                       class="form-control" 
                                       placeholder="Rechercher..." 
                                       id="<?= $tableId ?>Search">
                                <i class="fas fa-search"></i>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($export): ?>
                        <div class="table-export">
                            <button class="btn btn-outline btn-sm" onclick="exportTable('<?= $tableId ?>')">
                                <i class="fas fa-download"></i>
                                Exporter
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="table-wrapper">
            <table class="table" id="<?= $tableId ?>">
                <thead>
                    <tr>
                        <?php foreach ($columns as $column): ?>
                            <th class="<?= $column['sortable'] ?? false ? 'sortable' : '' ?>"
                                <?= isset($column['width']) ? 'style="width: ' . $column['width'] . '"' : '' ?>
                                data-column="<?= $column['key'] ?? '' ?>">
                                <?= htmlspecialchars($column['label'] ?? '') ?>
                                <?php if ($column['sortable'] ?? false): ?>
                                    <i class="fas fa-sort sort-icon"></i>
                                <?php endif; ?>
                            </th>
                        <?php endforeach; ?>
                        <?php if (!empty($actions)): ?>
                            <th class="actions-column">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="<?= count($columns) + (!empty($actions) ? 1 : 0) ?>" class="text-center">
                                <div class="empty-state">
                                    <i class="fas fa-inbox"></i>
                                    <p>Aucune donnée disponible</p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $row): ?>
                            <tr>
                                <?php foreach ($columns as $column): ?>
                                    <td class="<?= $column['class'] ?? '' ?>">
                                        <?php 
                                        $value = $row[$column['key']] ?? '';
                                        
                                        // Formatage selon le type
                                        switch ($column['type'] ?? 'text') {
                                            case 'currency':
                                                echo '€' . number_format($value, 2, ',', ' ');
                                                break;
                                            case 'date':
                                                echo $value ? date('d/m/Y', strtotime($value)) : '';
                                                break;
                                            case 'datetime':
                                                echo $value ? date('d/m/Y H:i', strtotime($value)) : '';
                                                break;
                                            case 'badge':
                                                $badgeClass = $column['badgeClass'][$value] ?? 'primary';
                                                echo '<span class="badge badge-' . $badgeClass . '">' . htmlspecialchars($value) . '</span>';
                                                break;
                                            case 'boolean':
                                                $icon = $value ? 'check-circle text-success' : 'times-circle text-error';
                                                echo '<i class="fas fa-' . $icon . '"></i>';
                                                break;
                                            case 'progress':
                                                echo '<div class="progress-bar">
                                                        <div class="progress-fill" style="width: ' . $value . '%"></div>
                                                        <span class="progress-text">' . $value . '%</span>
                                                      </div>';
                                                break;
                                            case 'image':
                                                if ($value) {
                                                    echo '<img src="' . htmlspecialchars($value) . '" alt="" class="table-image">';
                                                }
                                                break;
                                            case 'link':
                                                if ($value && isset($column['linkUrl'])) {
                                                    $url = str_replace('{id}', $row['id'] ?? '', $column['linkUrl']);
                                                    echo '<a href="' . $url . '" class="table-link">' . htmlspecialchars($value) . '</a>';
                                                } else {
                                                    echo htmlspecialchars($value);
                                                }
                                                break;
                                            default:
                                                echo htmlspecialchars($value);
                                        }
                                        ?>
                                    </td>
                                <?php endforeach; ?>
                                
                                <?php if (!empty($actions)): ?>
                                    <td class="actions-column">
                                        <div class="table-actions">
                                            <?php foreach ($actions as $action): ?>
                                                <?php
                                                $url = str_replace('{id}', $row['id'] ?? '', $action['url'] ?? '#');
                                                $class = 'btn-action ' . ($action['class'] ?? '');
                                                $onclick = $action['onclick'] ?? '';
                                                if ($onclick) {
                                                    $onclick = str_replace('{id}', $row['id'] ?? '', $onclick);
                                                    $onclick = 'onclick="' . $onclick . '"';
                                                }
                                                ?>
                                                <a href="<?= $url ?>" 
                                                   class="<?= $class ?>" 
                                                   <?= $onclick ?>
                                                   title="<?= htmlspecialchars($action['title'] ?? '') ?>">
                                                    <i class="<?= $action['icon'] ?? 'fas fa-edit' ?>"></i>
                                                </a>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($pagination): ?>
            <div class="table-pagination">
                <div class="pagination-info">
                    Affichage de <?= $pagination['start'] ?? 1 ?> à <?= $pagination['end'] ?? count($data) ?> 
                    sur <?= $pagination['total'] ?? count($data) ?> résultats
                </div>
                
                <div class="pagination-controls">
                    <button class="btn btn-ghost btn-sm" 
                            <?= ($pagination['current'] ?? 1) <= 1 ? 'disabled' : '' ?>
                            onclick="changePage(<?= ($pagination['current'] ?? 1) - 1 ?>)">
                        <i class="fas fa-chevron-left"></i>
                        Précédent
                    </button>
                    
                    <div class="pagination-pages">
                        <?php 
                        $current = $pagination['current'] ?? 1;
                        $total = $pagination['pages'] ?? 1;
                        $start = max(1, $current - 2);
                        $end = min($total, $current + 2);
                        
                        for ($i = $start; $i <= $end; $i++): ?>
                            <button class="btn <?= $i === $current ? 'btn-primary' : 'btn-ghost' ?> btn-sm"
                                    onclick="changePage(<?= $i ?>)">
                                <?= $i ?>
                            </button>
                        <?php endfor; ?>
                    </div>
                    
                    <button class="btn btn-ghost btn-sm"
                            <?= ($pagination['current'] ?? 1) >= ($pagination['pages'] ?? 1) ? 'disabled' : '' ?>
                            onclick="changePage(<?= ($pagination['current'] ?? 1) + 1 ?>)">
                        Suivant
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
}

/**
 * Rendu d'un badge coloré
 */
function renderBadge($text, $type = 'primary') {
    $classes = [
        'primary' => 'badge-primary',
        'success' => 'badge-success',
        'warning' => 'badge-warning',
        'error' => 'badge-error',
        'info' => 'badge-info'
    ];
    
    $class = $classes[$type] ?? 'badge-primary';
    echo '<span class="badge ' . $class . '">' . htmlspecialchars($text) . '</span>';
}
?>

<!-- Styles complémentaires pour le tableau -->
<style>
.table-container {
    margin-bottom: 2rem;
}

.table-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    padding: 1.5rem;
    border-bottom: 1px solid var(--border-light);
}

.table-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--text-primary);
    margin-bottom: 0.25rem;
}

.table-subtitle {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.table-controls {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.table-search {
    position: relative;
}

.table-search .form-group {
    margin-bottom: 0;
    position: relative;
}

.table-search i {
    position: absolute;
    right: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-muted);
}

.sortable {
    cursor: pointer;
    user-select: none;
    position: relative;
}

.sortable:hover {
    background: var(--bg-tertiary);
}

.sort-icon {
    margin-left: 0.5rem;
    opacity: 0.5;
    transition: opacity var(--transition-fast);
}

.sortable:hover .sort-icon {
    opacity: 1;
}

.actions-column {
    width: 120px;
    text-align: center;
}

.empty-state {
    padding: 3rem 2rem;
    text-align: center;
    color: var(--text-muted);
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.badge {
    padding: 0.25rem 0.75rem;
    border-radius: 1rem;
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-primary {
    background: rgba(30, 136, 229, 0.1);
    color: var(--primary-color);
}

.badge-success {
    background: rgba(76, 175, 80, 0.1);
    color: var(--success-color);
}

.badge-warning {
    background: rgba(255, 167, 38, 0.1);
    color: var(--warning-color);
}

.badge-error {
    background: rgba(244, 67, 54, 0.1);
    color: var(--error-color);
}

.badge-info {
    background: rgba(33, 150, 243, 0.1);
    color: #2196F3;
}

.progress-bar {
    position: relative;
    background: var(--border-light);
    border-radius: 1rem;
    height: 1.5rem;
    overflow: hidden;
}

.progress-fill {
    background: var(--success-color);
    height: 100%;
    transition: width var(--transition-normal);
}

.progress-text {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 0.75rem;
    font-weight: 600;
    color: var(--text-primary);
}

.table-image {
    width: 40px;
    height: 40px;
    border-radius: var(--border-radius-sm);
    object-fit: cover;
}

.table-link {
    color: var(--primary-color);
    text-decoration: none;
    font-weight: 500;
}

.table-link:hover {
    text-decoration: underline;
}

.table-pagination {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--border-light);
}

.pagination-info {
    color: var(--text-muted);
    font-size: 0.9rem;
}

.pagination-controls {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.pagination-pages {
    display: flex;
    gap: 0.25rem;
}

@media (max-width: 768px) {
    .table-header {
        flex-direction: column;
        gap: 1rem;
    }
    
    .table-controls {
        width: 100%;
        justify-content: flex-end;
    }
    
    .table-pagination {
        flex-direction: column;
        gap: 1rem;
    }
    
    .pagination-controls {
        flex-wrap: wrap;
        justify-content: center;
    }
}
</style>
