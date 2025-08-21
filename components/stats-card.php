<?php
/**
 * Composant Carte de Statistiques
 * Composant réutilisable pour afficher des métriques
 * Team589
 */

/**
 * Rend une grille de cartes de statistiques
 * @param array $stats Tableau des statistiques
 */
function renderStatsGrid($stats) {
    if (empty($stats)) return;
    
    echo '<div class="stats-grid">';
    foreach ($stats as $stat) {
        renderStatCard($stat);
    }
    echo '</div>';
}

/**
 * Rend une carte de statistique individuelle
 * @param array $stat Configuration de la statistique
 */
function renderStatCard($stat) {
    $title = htmlspecialchars($stat['title'] ?? '');
    $value = htmlspecialchars($stat['value'] ?? '0');
    $icon = htmlspecialchars($stat['icon'] ?? 'fas fa-chart-line');
    $type = htmlspecialchars($stat['type'] ?? 'primary');
    $change = htmlspecialchars($stat['change'] ?? '');
    $changeType = htmlspecialchars($stat['changeType'] ?? 'neutral');
    $subtitle = htmlspecialchars($stat['subtitle'] ?? '');
    $link = htmlspecialchars($stat['link'] ?? '');
    $color = htmlspecialchars($stat['color'] ?? '');
    
    echo '<div class="stat-card" data-type="' . $type . '">';
    
    // Header avec titre et icône
    echo '<div class="stat-header">';
    echo '<div class="stat-title">' . $title . '</div>';
    echo '<div class="stat-icon ' . $type . '">';
    echo '<i class="' . $icon . '"></i>';
    echo '</div>';
    echo '</div>';
    
    // Valeur principale
    echo '<div class="stat-value"' . ($color ? ' style="color: ' . $color . '"' : '') . '>' . $value . '</div>';
    
    // Changement/évolution
    if ($change) {
        echo '<div class="stat-change ' . $changeType . '">';
        $changeIcon = $changeType === 'positive' ? 'fa-arrow-up' : ($changeType === 'negative' ? 'fa-arrow-down' : 'fa-minus');
        echo '<i class="fas ' . $changeIcon . '"></i>';
        echo '<span>' . $change . '</span>';
        echo '</div>';
    }
    
    // Sous-titre
    if ($subtitle) {
        echo '<div class="stat-subtitle">' . $subtitle . '</div>';
    }
    
    // Lien d'action
    if ($link) {
        echo '<div class="stat-action" style="margin-top: var(--spacing-md);">';
        echo '<a href="' . $link . '" class="btn btn-sm btn-outline">Voir détails</a>';
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Rend une carte de métrique simple
 * @param string $title Titre de la métrique
 * @param mixed $value Valeur à afficher
 * @param string $icon Classe d'icône FontAwesome
 * @param string $color Couleur de la métrique
 */
function renderSimpleMetric($title, $value, $icon = 'fas fa-chart-line', $color = 'var(--primary-color)') {
    echo '<div class="stat-card simple-metric">';
    echo '<div class="stat-icon" style="background-color: ' . $color . ';">';
    echo '<i class="' . htmlspecialchars($icon) . '"></i>';
    echo '</div>';
    echo '<div class="stat-content">';
    echo '<div class="stat-value" style="color: ' . $color . ';">' . htmlspecialchars($value) . '</div>';
    echo '<div class="stat-title">' . htmlspecialchars($title) . '</div>';
    echo '</div>';
    echo '</div>';
}

/**
 * Rend une carte de progression
 * @param string $title Titre
 * @param int $current Valeur actuelle
 * @param int $total Valeur totale
 * @param string $color Couleur de la barre
 */
function renderProgressCard($title, $current, $total, $color = 'var(--primary-color)') {
    $percentage = $total > 0 ? round(($current / $total) * 100) : 0;
    
    echo '<div class="stat-card progress-card">';
    echo '<div class="stat-header">';
    echo '<div class="stat-title">' . htmlspecialchars($title) . '</div>';
    echo '<div class="stat-percentage">' . $percentage . '%</div>';
    echo '</div>';
    
    echo '<div class="progress-bar" style="background-color: var(--border-light); border-radius: var(--radius-full); height: 8px; margin: var(--spacing-md) 0;">';
    echo '<div class="progress-fill" style="background-color: ' . $color . '; height: 100%; width: ' . $percentage . '%; border-radius: var(--radius-full); transition: width var(--transition-normal);"></div>';
    echo '</div>';
    
    echo '<div class="stat-subtitle">' . number_format($current) . ' sur ' . number_format($total) . '</div>';
    echo '</div>';
}

/**
 * Rend une carte de comparaison
 * @param string $title Titre
 * @param array $data Données de comparaison [['label' => '', 'value' => '', 'color' => '']]
 */
function renderComparisonCard($title, $data) {
    echo '<div class="stat-card comparison-card">';
    echo '<div class="stat-title" style="margin-bottom: var(--spacing-md);">' . htmlspecialchars($title) . '</div>';
    
    $total = array_sum(array_column($data, 'value'));
    
    foreach ($data as $item) {
        $label = htmlspecialchars($item['label'] ?? '');
        $value = $item['value'] ?? 0;
        $color = htmlspecialchars($item['color'] ?? 'var(--primary-color)');
        $percentage = $total > 0 ? round(($value / $total) * 100) : 0;
        
        echo '<div class="comparison-item" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--spacing-sm);">';
        echo '<div style="display: flex; align-items: center; gap: var(--spacing-sm);">';
        echo '<div style="width: 12px; height: 12px; background-color: ' . $color . '; border-radius: var(--radius-full);"></div>';
        echo '<span style="font-size: var(--font-size-sm);">' . $label . '</span>';
        echo '</div>';
        echo '<div style="font-weight: 600; color: var(--text-primary);">' . number_format($value) . ' (' . $percentage . '%)</div>';
        echo '</div>';
    }
    
    echo '</div>';
}

/**
 * Rend une carte de tendance
 * @param string $title Titre
 * @param mixed $value Valeur actuelle
 * @param array $trend Données de tendance [valeurs numériques]
 * @param string $color Couleur de la tendance
 */
function renderTrendCard($title, $value, $trend = [], $color = 'var(--primary-color)') {
    $trendDirection = 'neutral';
    $trendValue = '';
    
    if (count($trend) >= 2) {
        $last = end($trend);
        $previous = prev($trend);
        if ($last > $previous) {
            $trendDirection = 'positive';
            $trendValue = '+' . round((($last - $previous) / $previous) * 100, 1) . '%';
        } elseif ($last < $previous) {
            $trendDirection = 'negative';
            $trendValue = round((($last - $previous) / $previous) * 100, 1) . '%';
        }
    }
    
    echo '<div class="stat-card trend-card">';
    echo '<div class="stat-header">';
    echo '<div class="stat-title">' . htmlspecialchars($title) . '</div>';
    if ($trendValue) {
        $trendIcon = $trendDirection === 'positive' ? 'fa-arrow-up' : 'fa-arrow-down';
        $trendColor = $trendDirection === 'positive' ? 'var(--success-color)' : 'var(--danger-color)';
        echo '<div class="stat-trend" style="color: ' . $trendColor . '; font-size: var(--font-size-sm); font-weight: 600;">';
        echo '<i class="fas ' . $trendIcon . '"></i> ' . $trendValue;
        echo '</div>';
    }
    echo '</div>';
    
    echo '<div class="stat-value" style="color: ' . $color . ';">' . htmlspecialchars($value) . '</div>';
    
    // Mini graphique de tendance
    if (!empty($trend)) {
        echo '<div class="trend-chart" style="height: 40px; margin-top: var(--spacing-md);">';
        echo '<canvas id="trend-' . md5($title) . '" width="100" height="40"></canvas>';
        echo '</div>';
        
        // Script pour le mini graphique
        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function() {';
        echo 'const ctx = document.getElementById("trend-' . md5($title) . '");';
        echo 'if (ctx) {';
        echo 'new Chart(ctx, {';
        echo 'type: "line",';
        echo 'data: {';
        echo 'labels: ' . json_encode(array_keys($trend)) . ',';
        echo 'datasets: [{';
        echo 'data: ' . json_encode(array_values($trend)) . ',';
        echo 'borderColor: "' . $color . '",';
        echo 'backgroundColor: "transparent",';
        echo 'borderWidth: 2,';
        echo 'pointRadius: 0,';
        echo 'tension: 0.4';
        echo '}]';
        echo '},';
        echo 'options: {';
        echo 'responsive: true,';
        echo 'maintainAspectRatio: false,';
        echo 'plugins: { legend: { display: false } },';
        echo 'scales: {';
        echo 'x: { display: false },';
        echo 'y: { display: false }';
        echo '}';
        echo '}';
        echo '});';
        echo '}';
        echo '});';
        echo '</script>';
    }
    
    echo '</div>';
}

/**
 * Rend une carte d'alerte
 * @param string $title Titre de l'alerte
 * @param string $message Message de l'alerte
 * @param string $type Type d'alerte (success, warning, danger, info)
 * @param string $action Lien d'action optionnel
 */
function renderAlertCard($title, $message, $type = 'info', $action = '') {
    $icons = [
        'success' => 'fas fa-check-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'danger' => 'fas fa-times-circle',
        'info' => 'fas fa-info-circle'
    ];
    
    echo '<div class="stat-card alert-card alert-' . $type . '">';
    echo '<div class="stat-header">';
    echo '<div class="stat-icon ' . $type . '">';
    echo '<i class="' . ($icons[$type] ?? $icons['info']) . '"></i>';
    echo '</div>';
    echo '<div class="stat-title">' . htmlspecialchars($title) . '</div>';
    echo '</div>';
    
    echo '<div class="alert-message" style="color: var(--text-primary); margin: var(--spacing-md) 0;">';
    echo htmlspecialchars($message);
    echo '</div>';
    
    if ($action) {
        echo '<div class="alert-action">';
        echo '<a href="' . htmlspecialchars($action) . '" class="btn btn-sm btn-' . $type . '">Action</a>';
        echo '</div>';
    }
    
    echo '</div>';
}
?>