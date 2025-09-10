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
$kpi = $kpiStmt->fetch() ?: ['total' => 4, 'orders' => 4];

$clientsCount = (int)($pdo->query('SELECT COUNT(*) FROM clients')->fetchColumn() ?: 0);

$stocksStmt = $pdo->query('SELECT nom_article, quantite, seuil_alerte FROM stocks ORDER BY nom_article ASC LIMIT 10');
$stocks = $stocksStmt->fetchAll() ?: [];

$salesByWeek = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%x-W%v') AS wk, SUM(total) AS t FROM sales WHERE created_at BETWEEN :s AND :e GROUP BY YEARWEEK(created_at, 3) ORDER BY MIN(created_at)");
$salesByWeek->execute([':s' => $monthStart, ':e' => $monthEnd]);
$byWeek = $salesByWeek->fetchAll() ?: [];

// Préparer les données du graphique côté PHP (compatibilité PHP < 7.4)
$weekLabels = [];
$weekValues = [];
foreach ($byWeek as $row) {
    $weekLabels[] = isset($row['wk']) ? (string)$row['wk'] : '';
    $weekValues[] = isset($row['t']) ? (float)$row['t'] : 0.0;
}

$recentStmt = $pdo->prepare('SELECT sa.id, sa.total, sa.created_at, COALESCE(CONCAT(c.last_name, " ", c.first_name), "Client par défaut") AS client_name, (
    SELECT t.payment_method FROM transactions t WHERE t.sale_id = sa.id ORDER BY t.paid_at ASC LIMIT 1
) AS payment_method
FROM sales sa
LEFT JOIN clients c ON c.id = sa.client_id
WHERE sa.created_at BETWEEN :s AND :e
ORDER BY sa.created_at DESC
LIMIT 10');
$recentStmt->execute([':s' => $monthStart, ':e' => $monthEnd]);
$recentSales = $recentStmt->fetchAll() ?: [];

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
			                <div class="client-stat-value"><?php echo number_format((float)$kpi['total'], 2); ?> $</div>
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
				<div style="position: relative; width: 100%; min-height: 280px;">
					<canvas id="chartWeek" role="img" aria-label="Graphique des ventes par semaine" style="width:100%; height:100%; display:block;"></canvas>
				</div>
			</div>
		</div>
		<div class="card">
			<div class="card-header"><h3 class="card-title">Dernières ventes (mois)</h3></div>
			<div class="card-body">
				<div class="table-responsive">
					<table class="table">
						<thead><tr><th>Ticket</th><th>Client</th><th>Paiement</th><th>Total</th><th>Date</th><th></th></tr></thead>
						<tbody>
							<?php foreach ($recentSales as $r): ?>
							<tr>
								<td>#<?php echo (int)$r['id']; ?></td>
								<td><?php echo htmlspecialchars($r['client_name'] ?? ''); ?></td>
								<td><?php echo htmlspecialchars($r['payment_method'] ?? '-'); ?></td>
								                        <td><?php echo number_format((float)$r['total'], 2, ',', ' '); ?> $</td>
								<td><?php echo htmlspecialchars($r['created_at']); ?></td>
								<td><a class="btn btn-sm btn-outline" href="sale_detail.php?id=<?php echo (int)$r['id']; ?>"><i class="fas fa-eye"></i></a></td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
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
	// Préparer les données
	const labels = <?php echo json_encode($weekLabels, JSON_UNESCAPED_UNICODE); ?>;
	const values = <?php echo json_encode($weekValues); ?>;
	console.debug('Dashboard Directeur - labels:', labels);
	console.debug('Dashboard Directeur - values:', values);

	// Fallback si aucune donnée
	const safeLabels = (Array.isArray(labels) && labels.length > 0) ? labels : ['Aucune donnée'];
	const safeValues = (Array.isArray(values) && values.length > 0) ? values : [0];

	try {
		// Vérifier la présence du canvas
		const canvas = document.getElementById('chartWeek');
		if (!canvas) return;
		const ctx = canvas.getContext('2d');

		// Vérifier que Chart est disponible
		if (typeof Chart === 'undefined') {
			console.error('Chart.js non chargé');
			if (window.showNotification) window.showNotification('Chart.js non chargé', 'error');
			return;
		}

		// Couleurs et styles
		const primary = 'rgba(59,130,246,1)'; // bleu
		const primaryBg = 'rgba(59,130,246,0.15)';
		const accent = 'rgba(234,179,8,1)'; // amber pour moyenne mobile
		const gridColor = 'rgba(0,0,0,0.06)';
		const textColor = '#444';

		// Dégradé de fond pour la zone sous la courbe
		const gradient = ctx.createLinearGradient(0, 0, 0, canvas.parentElement?.clientHeight || 300);
		gradient.addColorStop(0, 'rgba(59,130,246,0.35)');
		gradient.addColorStop(1, 'rgba(59,130,246,0.02)');

		// Labels formatés (Sem 34)
		const formattedLabels = safeLabels.map(l => {
			if (typeof l === 'string' && l.includes('W')) {
				const parts = l.split('W');
				return `Sem ${parts[1]}`;
			}
			return l;
		});

		// Moyenne mobile (fenêtre 3)
		function movingAverage(values, windowSize = 3) {
			const result = [];
			for (let i = 0; i < values.length; i++) {
				const start = Math.max(0, i - windowSize + 1);
				const slice = values.slice(start, i + 1);
				const avg = slice.reduce((a, b) => a + (Number(b) || 0), 0) / slice.length;
				result.push(Number.isFinite(avg) ? Number(avg.toFixed(2)) : 0);
			}
			return result;
		}
		const avgValues = movingAverage(safeValues, 3);

		new Chart(ctx, {
			type: 'line',
			data: {
				labels: formattedLabels,
				datasets: [{
					label: 'Ventes ($)',
					data: safeValues,
					borderColor: primary,
					backgroundColor: gradient,
					borderWidth: 2,
					tension: 0.35,
					fill: true,
					pointRadius: 2.5,
					pointHoverRadius: 5,
					pointHitRadius: 10,
					pointStyle: 'circle'
				},
				{
					label: 'Moyenne mobile (3 sem.)',
					data: avgValues,
					borderColor: accent,
					backgroundColor: 'transparent',
					borderDash: [6, 6],
					borderWidth: 2,
					tension: 0.25,
					pointRadius: 0,
					fill: false
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				interaction: { mode: 'index', intersect: false },
				animation: { duration: 800, easing: 'easeOutQuart' },
				scales: {
					y: {
						beginAtZero: true,
						grid: { color: gridColor },
						ticks: {
							color: textColor,
							callback: (v) => `${new Intl.NumberFormat('fr-FR', { notation: 'compact', compactDisplay: 'short' }).format(v)} $`
						}
					},
					x: {
						grid: { display: false },
						ticks: { color: textColor }
					}
				},
				plugins: {
					legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } },
					title: {
						display: true,
						text: 'Ventes hebdomadaires (mois en cours)',
						font: { size: 14, weight: '600' },
						padding: { bottom: 10 }
					},
					tooltip: {
						callbacks: {
							label: (ctx) => {
								const val = ctx.parsed.y ?? 0;
								const formatted = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'USD', maximumFractionDigits: 2 }).format(val);
								return `${ctx.dataset.label}: ${formatted}`;
							}
						}
					}
				}
			}
		});
	} catch (err) {
		console.error('Erreur lors du rendu du graphique:', err);
		if (window.showNotification) window.showNotification('Erreur lors du rendu du graphique', 'error');
		// Afficher un message dans le canvas
		const canvas = document.getElementById('chartWeek');
		if (canvas) {
			const ctx2 = canvas.getContext('2d');
			ctx2.font = '14px sans-serif';
			ctx2.fillStyle = '#888';
			ctx2.textAlign = 'center';
			ctx2.fillText('Impossible d\'afficher le graphique', canvas.width / 2, canvas.height / 2);
		}
	}
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


