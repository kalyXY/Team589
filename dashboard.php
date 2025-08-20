<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Secure session cookie params (align with login.php)
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_start();

// API/JSON endpoints for charts and widgets (must be authenticated)
if (empty($_SESSION['user_id']) || empty($_SESSION['username']) || empty($_SESSION['role'])) {
	if (isset($_GET['action'])) {
		header('Content-Type: application/json; charset=utf-8', true, 401);
		echo json_encode(['error' => 'unauthorized']);
		exit;
	}
	header('Location: ' . BASE_URL . 'login.php');
	exit;
}

$username = (string) $_SESSION['username'];
$role = (string) $_SESSION['role'];

// Role capabilities
$canManageStocks = in_array($role, ['admin', 'gestionnaire'], true);
$canViewAlerts = in_array($role, ['admin', 'gestionnaire'], true);
$canViewCosts = in_array($role, ['admin'], true);

$pdo = Database::getConnection();

// Serve JSON for monthly expenses
if (isset($_GET['action']) && $_GET['action'] === 'expenses_json') {
	try {
		$sql = 'SELECT YEAR(`date`) AS y, MONTH(`date`) AS m, SUM(montant) AS total
				FROM depenses
				GROUP BY y, m
				ORDER BY y, m';
		$stmt = $pdo->query($sql);
		$rows = $stmt->fetchAll() ?: [];
		$labels = [];
		$data = [];
		$totalCumul = 0.0;
		foreach ($rows as $r) {
			$label = sprintf('%04d-%02d', (int) $r['y'], (int) $r['m']);
			$labels[] = $label;
			$val = (float) $r['total'];
			$data[] = $val;
			$totalCumul += $val;
		}
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([
			'labels' => $labels,
			'data' => $data,
			'total' => $totalCumul,
		]);
		exit;
	} catch (Throwable $e) {
		header('Content-Type: application/json; charset=utf-8', true, 500);
		echo json_encode(['error' => 'server_error']);
		exit;
	}
}

// Load dashboard data from DB
$totalArticlesCount = 0;
$underThreshold = [];
$topUsed = [];
$hasStocks = true;

try {
	// Total items = COUNT(*) per requirements
	$row = $pdo->query('SELECT COUNT(*) AS c FROM stocks')->fetch();
	$totalArticlesCount = (int) ($row['c'] ?? 0);

	// Under threshold list
	$sqlUnder = 'SELECT id, nom_article, categorie, quantite, seuil
				FROM stocks
				WHERE quantite <= seuil
				ORDER BY quantite ASC, nom_article ASC
				LIMIT 10';
	$underThreshold = $pdo->query($sqlUnder)->fetchAll() ?: [];

	// "Most used" proxy: lowest quantities remaining
	$sqlTop = 'SELECT id, nom_article, categorie, quantite, seuil
				FROM stocks
				ORDER BY quantite ASC, nom_article ASC
				LIMIT 5';
	$topUsed = $pdo->query($sqlTop)->fetchAll() ?: [];
} catch (Throwable $e) {
	$hasStocks = false;
	$totalArticlesCount = 0;
	$underThreshold = [];
	$topUsed = [];
	if (APP_ENV === 'dev') {
		error_log('Stocks query error: ' . $e->getMessage());
	}
}
$totalExpenses = 0.0;
$budgetAnnual = 30000.0;
try {
	$row = $pdo->query('SELECT SUM(montant) AS t FROM depenses')->fetch();
	$totalExpenses = (float) ($row['t'] ?? 0);
} catch (Throwable $e) {
	$totalExpenses = 0.0;
}
?>
<!doctype html>
<html lang="fr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Tableau de bord · Scolaria</title>
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" integrity="sha512-SnH5WK+bZxgPHs44uWIX+LLJAJ9/2PkfKcSILhK+Gm2B9Bw5cJ0Z4wNG0Ax7Anxr1j7rwhhtjfiV2KxA9i6VwQ==" crossorigin="anonymous" referrerpolicy="no-referrer" />
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/dashboard.css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
	<link rel="stylesheet" href="<?php echo BASE_URL; ?>stocks.css">
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
		<div class="container-fluid">
			<a class="navbar-brand d-flex align-items-center gap-2" href="#">
				<i class="fa-solid fa-school text-primary"></i>
				<strong>Scolaria</strong>
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarSupportedContent">
				<ul class="navbar-nav me-auto mb-2 mb-lg-0">
					<li class="nav-item">
						<a class="nav-link <?php echo $canManageStocks ? '' : 'disabled'; ?>" href="#" data-section="stocks">Gestion des stocks</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo $canViewAlerts ? '' : 'disabled'; ?>" href="#" data-section="alerts">Alertes</a>
					</li>
					<li class="nav-item">
						<a class="nav-link <?php echo $canViewCosts ? '' : 'disabled'; ?>" href="#" data-section="costs">Coûts</a>
					</li>
				</ul>
				<form class="d-none d-lg-flex" role="search">
					<input class="form-control me-2" type="search" placeholder="Rechercher un article..." aria-label="Search">
				</form>
				<div class="d-flex align-items-center ms-lg-3">
					<span class="navbar-text me-3"><i class="fa-regular fa-user me-1"></i><strong><?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?></strong> (<?php echo htmlspecialchars($role, ENT_QUOTES, 'UTF-8'); ?>)</span>
					<a class="btn btn-outline-primary" href="<?php echo BASE_URL; ?>logout.php">Déconnexion</a>
				</div>
			</div>
		</div>
	</nav>

	<main class="container my-4">
		<?php if (!empty($underThreshold)): ?>
			<div class="alert alert-warning d-flex align-items-center" role="alert">
				<i class="fa-solid fa-triangle-exclamation me-2"></i>
				<strong>Attention:</strong>&nbsp;réapprovisionnement nécessaire pour certains articles (<?php echo (int) count($underThreshold); ?>).
			</div>
		<?php endif; ?>

		<div class="row g-3">
			<div class="col-md-4">
				<div class="card text-bg-primary h-100">
					<div class="card-body">
						<h5 class="card-title">Articles en stock</h5>
						<p class="display-6 mb-0"><?php echo (int) $totalArticlesCount; ?></p>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card text-bg-warning h-100">
					<div class="card-body">
						<h5 class="card-title">Alertes de réapprovisionnement</h5>
						<p class="display-6 mb-0"><?php echo (int) count($underThreshold); ?></p>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title">Dépenses mensuelles</h5>
						<div class="chart-wrap">
							<canvas id="expensesChart" height="120"></canvas>
						</div>
						<p class="mt-2 mb-0"><strong>Total cumulé:</strong> <span id="expensesTotal">0</span> €</p>
					</div>
				</div>
			</div>
		</div>

		<div class="row g-3 mt-1">
			<div class="col-lg-6">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title">Articles les plus utilisés</h5>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead>
									<tr>
										<th>Article</th>
										<th>Catégorie</th>
										<th class="text-end">Quantité</th>
										<th class="text-end">Seuil</th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($topUsed)): ?>
										<tr><td colspan="4" class="text-muted">Aucune donnée</td></tr>
									<?php else: ?>
										<?php foreach ($topUsed as $r): ?>
											<tr>
												<td><?php echo htmlspecialchars((string) $r['nom_article'], ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars((string) ($r['categorie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="text-end fw-semibold"><?php echo (int) $r['quantite']; ?></td>
												<td class="text-end"><?php echo (int) $r['seuil']; ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
			<div class="col-lg-6">
				<div class="card h-100">
					<div class="card-body">
						<h5 class="card-title">Articles sous le seuil</h5>
						<div class="table-responsive">
							<table class="table table-sm align-middle mb-0">
								<thead>
									<tr>
										<th>Article</th>
										<th>Catégorie</th>
										<th class="text-end">Quantité</th>
										<th class="text-end">Seuil</th>
									</tr>
								</thead>
								<tbody>
									<?php if (empty($underThreshold)): ?>
										<tr><td colspan="4" class="text-muted">Aucun article sous le seuil</td></tr>
									<?php else: ?>
										<?php foreach ($underThreshold as $r): ?>
											<tr class="<?php echo ((int) $r['quantite'] <= (int) $r['seuil']) ? 'table-warning' : ''; ?>">
												<td><?php echo htmlspecialchars((string) $r['nom_article'], ENT_QUOTES, 'UTF-8'); ?></td>
												<td><?php echo htmlspecialchars((string) ($r['categorie'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></td>
												<td class="text-end fw-semibold"><?php echo (int) $r['quantite']; ?></td>
												<td class="text-end"><?php echo (int) $r['seuil']; ?></td>
											</tr>
										<?php endforeach; ?>
									<?php endif; ?>
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>

		<hr class="my-4">

		<div class="row">
			<div class="col-12">
				<div class="row g-3">
					<div class="col-12 col-md-6 col-lg-3">
						<a class="shortcut-card card h-100 text-decoration-none" href="#" data-section="stocks" <?php echo $canManageStocks ? '' : 'aria-disabled="true"'; ?>>
							<div class="card-body d-flex align-items-center gap-2">
								<i class="fa-solid fa-boxes-stacked fa-2xl text-primary"></i>
								<div>
									<h6 class="mb-1">Gestion des stocks</h6>
									<p class="mb-0 text-muted">Ajouter, éditer, mouvementer</p>
								</div>
							</div>
						</a>
					</div>
					<div class="col-12 col-md-6 col-lg-3">
						<a class="shortcut-card card h-100 text-decoration-none" href="#" data-section="alerts" <?php echo $canViewAlerts ? '' : 'aria-disabled="true"'; ?>>
							<div class="card-body d-flex align-items-center gap-2">
								<i class="fa-solid fa-triangle-exclamation fa-2xl text-warning"></i>
								<div>
									<h6 class="mb-1">Alertes & réapprovisionnement</h6>
									<p class="mb-0 text-muted">Surveiller les seuils</p>
								</div>
							</div>
						</a>
					</div>
					<div class="col-12 col-md-6 col-lg-3">
						<a class="shortcut-card card h-100 text-decoration-none" href="#" data-section="costs" <?php echo $canViewCosts ? '' : 'aria-disabled="true"'; ?>>
							<div class="card-body d-flex align-items-center gap-2">
								<i class="fa-solid fa-coins fa-2xl text-success"></i>
								<div>
									<h6 class="mb-1">Gestion financière</h6>
									<p class="mb-0 text-muted">Dépenses et budgets</p>
								</div>
							</div>
						</a>
					</div>
					<div class="col-12 col-md-6 col-lg-3">
						<a class="shortcut-card card h-100 text-decoration-none" href="#" data-section="resources">
							<div class="card-body d-flex align-items-center gap-2">
								<i class="fa-solid fa-folder-tree fa-2xl text-secondary"></i>
								<div>
									<h6 class="mb-1">Ressources centralisées</h6>
									<p class="mb-0 text-muted">Documents et modèles</p>
								</div>
							</div>
						</a>
					</div>
				</div>
				<p class="text-muted mt-2" id="sectionHint">
					<?php if ($role === 'enseignant'): ?>
						Accès lecture seule aux disponibilités.
					<?php elseif ($role === 'gestionnaire'): ?>
						Accès à la gestion des stocks et aux alertes.
					<?php else: ?>
						Accès complet.
					<?php endif; ?>
				</p>
			</div>
		</div>
	</main>

	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
	<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js" integrity="sha384-6dC1Cw/w3bzWr7h6nljpq2GzR9w9Jk+U5rI6UdUL8M+rkfdlpZJ8x0ESCrvJr1lK" crossorigin="anonymous"></script>
	<script>
	(function() {
		'use strict';
		// Minimal interactivity: show a toast-like hint when clicking restricted sections
		document.querySelectorAll('[data-section]').forEach(function(el) {
			el.addEventListener('click', function(e) {
				if (el.classList.contains('disabled') || el.hasAttribute('disabled') || el.getAttribute('aria-disabled') === 'true') {
					e.preventDefault();
					var hint = document.getElementById('sectionHint');
					hint.classList.remove('text-muted');
					hint.classList.add('text-danger');
					hint.textContent = 'Accès non autorisé pour votre rôle.';
					setTimeout(function(){
						hint.classList.remove('text-danger');
						hint.classList.add('text-muted');
					}, 1500);
				}
			});
		});

		// Load expenses chart data
		var ctx = document.getElementById('expensesChart');
		if (ctx && window.Chart) {
			fetch('dashboard.php?action=expenses_json')
				.then(function(r){ return r.json(); })
				.then(function(payload){
					if (!payload || !Array.isArray(payload.labels)) return;
					document.getElementById('expensesTotal').textContent = (payload.total || 0).toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
					new Chart(ctx, {
						type: 'bar',
						data: {
							labels: payload.labels,
							datasets: [{
								label: 'Dépenses mensuelles (€)',
								data: payload.data,
								backgroundColor: 'rgba(13,110,253,0.5)',
								borderColor: 'rgba(13,110,253,1)',
								borderWidth: 1
							}]
						},
						options: {
							responsive: true,
							maintainAspectRatio: false,
							scales: {
								y: { beginAtZero: true }
							}
						}
					});
				})
				.catch(function(){ /* ignore */ });
		}
	})();
	</script>
</body>
</html>


