<?php
/**
 * SCOLARIA - Composant Carte de Statistiques
 * Carte réutilisable pour afficher des métriques importantes
 */

/**
 * Affiche une carte de statistiques
 * 
 * @param string $title Titre de la carte
 * @param string|int $value Valeur principale à afficher
 * @param string $icon Classe d'icône FontAwesome
 * @param string $type Type de carte (primary, success, warning, error)
 * @param string $change Changement en pourcentage (optionnel)
 * @param string $changeType Type de changement (positive, negative)
 * @param string $subtitle Sous-titre ou description (optionnel)
 * @param string $link Lien vers une page détaillée (optionnel)
 */
function renderStatsCard($config) {
    $title = $config['title'] ?? '';
    $value = $config['value'] ?? '0';
    $icon = $config['icon'] ?? 'fas fa-chart-bar';
    $type = $config['type'] ?? 'primary';
    $change = $config['change'] ?? null;
    $changeType = $config['changeType'] ?? 'positive';
    $subtitle = $config['subtitle'] ?? '';
    $link = $config['link'] ?? '';
    $color = $config['color'] ?? '';
    
    $cardClass = "stats-card";
    if ($type !== 'primary') {
        $cardClass .= " " . $type;
    }
    
    $wrapperStart = $link ? '<a href="' . htmlspecialchars($link) . '" class="card-link">' : '<div>';
    $wrapperEnd = $link ? '</a>' : '</div>';
    
    echo $wrapperStart;
    ?>
    <div class="<?= $cardClass ?>">
        <div class="stats-header">
            <div class="stats-icon" <?= $color ? 'style="background-color: ' . $color . '"' : '' ?>>
                <i class="<?= $icon ?>"></i>
            </div>
            <?php if ($change): ?>
                <div class="stats-change <?= $changeType ?>">
                    <i class="fas fa-<?= $changeType === 'positive' ? 'arrow-up' : 'arrow-down' ?>"></i>
                    <?= $change ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="stats-body">
            <div class="stats-value"><?= number_format($value) ?></div>
            <div class="stats-label"><?= htmlspecialchars($title) ?></div>
            <?php if ($subtitle): ?>
                <div class="stats-subtitle"><?= htmlspecialchars($subtitle) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <?php
    echo $wrapperEnd;
}

/**
 * Grille de cartes de statistiques
 */
function renderStatsGrid($cards) {
    echo '<div class="stats-grid">';
    foreach ($cards as $card) {
        renderStatsCard($card);
    }
    echo '</div>';
}
?>

<!-- Exemple d'utilisation -->
<?php if (false): // Code d'exemple, ne s'exécute pas ?>
<script>
// Exemple d'utilisation du composant stats-card

// Carte simple
renderStatsCard([
    'title' => 'Total des stocks',
    'value' => 1250,
    'icon' => 'fas fa-boxes',
    'type' => 'primary',
    'change' => '+12%',
    'changeType' => 'positive',
    'subtitle' => 'Articles en inventaire',
    'link' => '/stocks.php'
]);

// Grille de cartes
$dashboardStats = [
    [
        'title' => 'Articles en stock',
        'value' => 1250,
        'icon' => 'fas fa-boxes',
        'type' => 'primary',
        'change' => '+5.2%',
        'changeType' => 'positive',
        'link' => '/stocks.php'
    ],
    [
        'title' => 'Alertes actives',
        'value' => 23,
        'icon' => 'fas fa-exclamation-triangle',
        'type' => 'warning',
        'change' => '-15%',
        'changeType' => 'positive',
        'link' => '/alerts.php'
    ],
    [
        'title' => 'Dépenses du mois',
        'value' => '€15,420',
        'icon' => 'fas fa-euro-sign',
        'type' => 'success',
        'change' => '+8.1%',
        'changeType' => 'negative',
        'link' => '/finances.php'
    ],
    [
        'title' => 'Utilisateurs actifs',
        'value' => 45,
        'icon' => 'fas fa-users',
        'type' => 'primary',
        'subtitle' => 'Connectés cette semaine',
        'link' => '/users.php'
    ]
];

renderStatsGrid($dashboardStats);
</script>
<?php endif; ?>
