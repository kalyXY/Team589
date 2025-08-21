<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/auth.php';

ensure_session_started();

$currentPage = 'access_denied';
$pageTitle = 'Accès refusé';
$showSidebar = false;
$additionalCSS = ['assets/css/auth.css'];
$bodyClass = 'login-page';

ob_start();
?>

<div class="auth-wrapper">
	<div class="auth-card" style="max-width:560px;">
		<div class="auth-header">
			<div class="auth-logo"><i class="fas fa-shield-alt"></i> SCOLARIA</div>
			<h2 class="auth-title">Accès refusé</h2>
			<p class="auth-subtitle">Vous n'avez pas les droits nécessaires pour accéder à cette page.</p>
		</div>
		<div class="alert alert-error">
			<i class="fas fa-lock"></i> Si vous pensez qu'il s'agit d'une erreur, contactez un administrateur.
		</div>
		<div style="margin-top: 16px; display:flex; gap:8px;">
			<a class="btn btn-outline" href="dashboard.php"><i class="fas fa-home"></i> Retour au tableau de bord</a>
			<a class="btn btn-primary" href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
		</div>
	</div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/auth.php';
?>


