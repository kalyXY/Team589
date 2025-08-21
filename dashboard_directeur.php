<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

require_roles(['admin','directeur']);

$pdo = Database::getConnection();

$monthStart = (new DateTime('first day of this month 00:00:00'))->format('Y-m-d H:i:s');
$monthEnd = (new DateTime('last day of this month 23:59:59'))->format('Y-m-d H:i:s');

$kpiStmt = $pdo->prepare('SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS orders FROM sales WHERE created_at BETWEEN :s AND :e');
$kpiStmt->execute([':s' => $monthStart, ':e' => $monthEnd]);
$kpi = $kpiStmt->fetch() ?: ['total' => 0, 'orders' => 0];

$clientsCount = (int)($pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn() ?: 0);

$stocksStmt = $pdo->query('SELECT nom_article, quantite, seuil_alerte FROM stocks ORDER BY nom_article ASC LIMIT 10');
$stocks = $stocksStmt->fetchAll() ?: [];

$salesByWeek = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%x-W%v') AS wk, SUM(total) AS t FROM sales WHERE created_at BETWEEN :s AND :e GROUP BY YEARWEEK(created_at, 3) ORDER BY MIN(created_at)");
$salesByWeek->execute([':s' => $monthStart, ':e' => $monthEnd]);
$byWeek = $salesByWeek->fetchAll() ?: [];

$currentPage = 'dashboard';
$pageTitle = 'Dashboard Directeur';
$showSidebar = true;
$additionalCSS = ['assets/css/dashboard.css', 'assets/css/clients.css'];

ob_start();
?>

<div class="clients-page">
	<div class="clients-header">
		<h2 class="clients-title"><i class="fas fa-user-tie"></i> Bonjour, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></h2>
	</div>

	<div class="clients-stats">
		<div class="client-stat-card">
			<div class="client-stat-header"><h3 class="client-stat-title">Ventes du mois</h3><div class="client-stat-icon"><i class="fas fa-coins"></i></div></div>
			<div class="client-stat-value"><?php echo number_format((float)$kpi['total'], 2); ?> €</div>
			<div class="client-stat-subtitle">Chiffre d'affaires</div>
		</div>
		<div class="client-stat-card">
			<div class="client-stat-header"><h3 class="client-stat-title">Clients</h3><div class="client-stat-icon"><i class="fas fa-users"></i></div></div>
			<div class="client-stat-value"><?php echo (int)$clientsCount; ?></div>
			<div class="client-stat-subtitle">Total clients</div>
		</div>
	</div>

	<div class="charts-grid">
		<div class="card">
			<div class="card-header"><h3 class="card-title">Ventes par semaine</h3></div>
			<div class="card-body">
				<canvas id="chartWeek" height="100"></canvas>
			</div>
		</div>
		<div class="card">
			<div class="card-header"><h3 class="card-title">Stocks (aperçu)</h3></div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table">
						<thead><tr><th>Article</th><th>Quantité</th><th>Seuil</th></tr></thead>
						<tbody>
							<?php foreach ($stocks as $s): ?>
							<tr>
								<td><?php echo htmlspecialchars($s['nom_article']); ?></td>
								<td><?php echo (int)$s['quantite']; ?></td>
								<td><?php echo (int)($s['seuil_alerte'] ?? 0); ?></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
	const labels = <?php echo json_encode(array_column($byWeek, 'wk')); ?>;
	const data = <?php echo json_encode(array_map(static fn($r)=> (float)$r['t'], $byWeek)); ?>;
	const ctx = document.getElementById('chartWeek').getContext('2d');
	new Chart(ctx, { type: 'line', data: { labels, datasets: [{ label: 'Ventes (€)', data, borderColor: 'rgba(59,130,246,1)', backgroundColor: 'rgba(59,130,246,0.2)'}] }, options: { responsive: true } });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


