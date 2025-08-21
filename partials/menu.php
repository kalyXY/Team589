<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/auth.php';
ensure_session_started();

$role = (string)($_SESSION['role'] ?? '');

function renderNavItem(string $href, string $icon, string $text, string $currentPage): void {
	$active = strpos($href, $currentPage) !== false ? 'active' : '';
	echo '<div class="nav-item">';
	echo '<a href="' . htmlspecialchars($href) . '" class="nav-link ' . $active . '">';
	echo '<div class="nav-icon"><i class="' . htmlspecialchars($icon) . '"></i></div>';
	echo '<div class="nav-text">' . htmlspecialchars($text) . '</div>';
	echo '</a>';
	echo '</div>';
}

// Section Principale
echo '<div class="nav-section">';
echo '<div class="nav-section-title">Principal</div>';
$dashboardHref = 'dashboard.php';
if ($role === 'caissier') { $dashboardHref = 'dashboard_caissier.php'; }
elseif ($role === 'directeur') { $dashboardHref = 'dashboard_directeur.php'; }
renderNavItem($dashboardHref, 'fas fa-tachometer-alt', 'Tableau de bord', $currentPage ?? '');
echo '</div>';

// Section selon rôle
if ($role === 'admin') {
	echo '<div class="nav-section">';
	echo '<div class="nav-section-title">Gestion</div>';
	renderNavItem('stocks.php', 'fas fa-boxes', 'Stocks', $currentPage ?? '');
	renderNavItem('alerts.php', 'fas fa-exclamation-triangle', 'Alertes & Réappro', $currentPage ?? '');
	renderNavItem('finances.php', 'fas fa-euro-sign', 'Finances', $currentPage ?? '');
	renderNavItem('clients.php', 'fas fa-user-friends', 'Clients', $currentPage ?? '');
	renderNavItem('users.php', 'fas fa-users-cog', 'Utilisateurs', $currentPage ?? '');
	echo '</div>';

	echo '<div class="nav-section">';
	echo '<div class="nav-section-title">Système</div>';
	renderNavItem('settings.php', 'fas fa-cog', 'Paramètres', $currentPage ?? '');
	renderNavItem('reports.php', 'fas fa-chart-bar', 'Rapports', $currentPage ?? '');
	echo '</div>';
}

if ($role === 'caissier') {
	echo '<div class="nav-section">';
	echo '<div class="nav-section-title">Caissier</div>';
	renderNavItem('pos.php', 'fas fa-cash-register', 'POS', $currentPage ?? '');
	renderNavItem('clients.php', 'fas fa-user-friends', 'Clients', $currentPage ?? '');
	echo '</div>';
}

if ($role === 'gestionnaire') {
	echo '<div class="nav-section">';
	echo '<div class="nav-section-title">Stocks</div>';
	renderNavItem('stocks.php', 'fas fa-boxes', 'Gestion des stocks', $currentPage ?? '');
	renderNavItem('alerts.php', 'fas fa-exclamation-triangle', 'Alertes & Réappro', $currentPage ?? '');
	renderNavItem('reports.php', 'fas fa-chart-bar', 'Rapports', $currentPage ?? '');
	echo '</div>';
}

if ($role === 'directeur') {
	echo '<div class="nav-section">';
	echo '<div class="nav-section-title">Directeur</div>';
	renderNavItem('reports.php', 'fas fa-chart-bar', 'Rapports', $currentPage ?? '');
	echo '</div>';
}

// Déconnexion
echo '<div class="nav-section">';
renderNavItem('logout.php', 'fas fa-sign-out-alt', 'Déconnexion', $currentPage ?? '');
echo '</div>';


