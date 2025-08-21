<?php
/**
 * Scolaria - Rapports de ventes
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Session + contrôle d'accès
session_set_cookie_params([
	'lifetime' => 0,
	'path' => '/',
	'domain' => '',
	'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
	'httponly' => true,
	'samesite' => 'Lax',
]);
session_start();

if (empty($_SESSION['user_id']) || empty($_SESSION['role'])) {
	header('Location: ' . BASE_URL . 'login.php');
	exit;
}

function getDateRange(): array {
	$end = isset($_GET['end']) ? trim((string)$_GET['end']) : '';
	$start = isset($_GET['start']) ? trim((string)$_GET['start']) : '';
	if ($start === '' || $end === '') {
		$endDate = new DateTime('today');
		$startDate = (new DateTime('today'))->modify('-29 days');
		return [$startDate->format('Y-m-d') . ' 00:00:00', $endDate->format('Y-m-d') . ' 23:59:59'];
	}
	return [$start . ' 00:00:00', $end . ' 23:59:59'];
}

function addCommonFilters(array &$conds, array &$params): void {
	[$startDt, $endDt] = getDateRange();
	$conds[] = 'sa.created_at BETWEEN :start AND :end';
	$params[':start'] = $startDt;
	$params[':end'] = $endDt;

	$productId = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
	$clientId = isset($_GET['client_id']) ? (int)$_GET['client_id'] : 0;
	if ($productId > 0) {
		$conds[] = 'EXISTS (SELECT 1 FROM sales_items si2 WHERE si2.sale_id = sa.id AND si2.product_id = :pid)';
		$params[':pid'] = $productId;
	}
	if ($clientId > 0) {
		$conds[] = 'sa.client_id = :cid';
		$params[':cid'] = $clientId;
	}
}

// AJAX endpoints
if (isset($_GET['ajax'])) {
	header('Content-Type: application/json; charset=utf-8');
	$pdo = Database::getConnection();

	try {
		$action = (string)$_GET['ajax'];
		if ($action === 'filters') {
			// Produits (stocks) + Clients (limités)
			$products = $pdo->query('SELECT id, nom_article AS name FROM stocks ORDER BY nom_article ASC LIMIT 500')->fetchAll() ?: [];
			$clients = $pdo->query('SELECT id, CONCAT(last_name, " ", first_name) AS name FROM clients ORDER BY last_name ASC, first_name ASC LIMIT 500')->fetchAll() ?: [];
			echo json_encode(['products' => $products, 'clients' => $clients]);
			exit;
		}

		if ($action === 'by_period') {
			$granularity = (string)($_GET['granularity'] ?? 'day');
			$groupExpr = 'DATE(sa.created_at)';
			$labelExpr = 'DATE_FORMAT(sa.created_at, "%Y-%m-%d")';
			if ($granularity === 'week') {
				$groupExpr = 'YEARWEEK(sa.created_at, 3)';
				$labelExpr = 'DATE_FORMAT(sa.created_at, "%x-W%v")';
			} elseif ($granularity === 'month') {
				$groupExpr = 'DATE_FORMAT(sa.created_at, "%Y-%m")';
				$labelExpr = 'DATE_FORMAT(sa.created_at, "%Y-%m")';
			}

			$conds = [];
			$params = [];
			addCommonFilters($conds, $params);
			$where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

			$sql = "SELECT {$labelExpr} AS label, {$groupExpr} AS grp, 
						SUM(sa.total) AS total_sales, COUNT(*) AS orders_count
					FROM sales sa
					{$where}
					GROUP BY grp, label
					ORDER BY MIN(sa.created_at) ASC";
			$stmt = $pdo->prepare($sql);
			foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
			$stmt->execute();
			$data = $stmt->fetchAll() ?: [];
			echo json_encode($data);
			exit;
		}

		if ($action === 'top_products') {
			$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
			$conds = [];
			$params = [];
			addCommonFilters($conds, $params);
			$where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

			$sql = "SELECT st.id AS product_id, st.nom_article AS product_name,
						SUM(si.quantity) AS quantity_sold,
						SUM(si.quantity * si.price) AS revenue
					FROM sales sa
					JOIN sales_items si ON si.sale_id = sa.id
					JOIN stocks st ON st.id = si.product_id
					{$where}
					GROUP BY st.id, st.nom_article
					ORDER BY quantity_sold DESC
					LIMIT {$limit}";
			$stmt = $pdo->prepare($sql);
			foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
			$stmt->execute();
			echo json_encode($stmt->fetchAll() ?: []);
			exit;
		}

		if ($action === 'top_clients') {
			$limit = max(1, min(50, (int)($_GET['limit'] ?? 10)));
			$conds = [];
			$params = [];
			addCommonFilters($conds, $params);
			$where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';

			$sql = "SELECT c.id AS client_id, CONCAT(c.last_name, ' ', c.first_name) AS client_name,
						COUNT(sa.id) AS orders_count,
						SUM(sa.total) AS total_spent
					FROM sales sa
					LEFT JOIN clients c ON c.id = sa.client_id
					{$where}
					GROUP BY c.id, c.last_name, c.first_name
					ORDER BY total_spent DESC
					LIMIT {$limit}";
			$stmt = $pdo->prepare($sql);
			foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
			$stmt->execute();
			echo json_encode($stmt->fetchAll() ?: []);
			exit;
		}

		if ($action === 'kpis') {
			$conds = [];
			$params = [];
			addCommonFilters($conds, $params);
			$where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';
			$sql = "SELECT COALESCE(SUM(sa.total),0) AS total_sales,
						COALESCE(AVG(sa.total),0) AS avg_ticket,
						COUNT(*) AS orders_count
					FROM sales sa {$where}";
			$stmt = $pdo->prepare($sql);
			foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
			$stmt->execute();
			echo json_encode($stmt->fetch() ?: ['total_sales' => 0, 'avg_ticket' => 0, 'orders_count' => 0]);
			exit;
		}

		if ($action === 'export_csv') {
			$type = (string)($_GET['type'] ?? 'by_period');
			$data = [];
			// Reutiliser endpoints ci-dessus
			$_GET['ajax'] = $type === 'top_products' ? 'top_products' : ($type === 'top_clients' ? 'top_clients' : 'by_period');
			ob_start();
			echo '';
			ob_end_clean();
			// Call internally
			// Fallback simple re-run logic to avoid recursion complexity: duplicate minimal logic
			if ($type === 'top_products') {
				$conds = [];$params = [];
				addCommonFilters($conds, $params); $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';
				$stmt = $pdo->prepare("SELECT st.nom_article AS Produit, SUM(si.quantity) AS Quantite, SUM(si.quantity*si.price) AS Chiffre FROM sales sa JOIN sales_items si ON si.sale_id=sa.id JOIN stocks st ON st.id=si.product_id {$where} GROUP BY st.id, st.nom_article ORDER BY Quantite DESC LIMIT 100");
				foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
				$stmt->execute(); $data = $stmt->fetchAll();
				headers_sent() || header('Content-Type: text/csv; charset=utf-8');
				headers_sent() || header('Content-Disposition: attachment; filename=top_products.csv');
				echo "Produit,Quantite,Chiffre\n";
				foreach ($data as $row) {
					echo '"' . str_replace('"', '""', (string)$row['Produit']) . '",' . (int)$row['Quantite'] . ',' . number_format((float)$row['Chiffre'], 2, '.', '') . "\n";
				}
				exit;
			}
			if ($type === 'top_clients') {
				$conds = [];$params = [];
				addCommonFilters($conds, $params); $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';
				$stmt = $pdo->prepare("SELECT CONCAT(c.last_name,' ',c.first_name) AS Client, COUNT(sa.id) AS Commandes, SUM(sa.total) AS Montant FROM sales sa LEFT JOIN clients c ON c.id=sa.client_id {$where} GROUP BY c.id, c.last_name, c.first_name ORDER BY Montant DESC LIMIT 100");
				foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
				$stmt->execute(); $data = $stmt->fetchAll();
				headers_sent() || header('Content-Type: text/csv; charset=utf-8');
				headers_sent() || header('Content-Disposition: attachment; filename=top_clients.csv');
				echo "Client,Commandes,Montant\n";
				foreach ($data as $row) {
					echo '"' . str_replace('"', '""', (string)$row['Client']) . '",' . (int)$row['Commandes'] . ',' . number_format((float)$row['Montant'], 2, '.', '') . "\n";
				}
				exit;
			}
			// by_period default (day granularity)
			$conds = [];$params = [];
			addCommonFilters($conds, $params); $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';
			$stmt = $pdo->prepare("SELECT DATE_FORMAT(sa.created_at, '%Y-%m-%d') AS Date, SUM(sa.total) AS Montant, COUNT(*) AS Commandes FROM sales sa {$where} GROUP BY DATE(sa.created_at) ORDER BY DATE(sa.created_at) ASC");
			foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
			$stmt->execute(); $data = $stmt->fetchAll();
			headers_sent() || header('Content-Type: text/csv; charset=utf-8');
			headers_sent() || header('Content-Disposition: attachment; filename=ventes_par_jour.csv');
			echo "Date,Montant,Commandes\n";
			foreach ($data as $row) {
				echo $row['Date'] . ',' . number_format((float)$row['Montant'], 2, '.', '') . ',' . (int)$row['Commandes'] . "\n";
			}
			exit;
		}

		echo json_encode(['error' => 'action inconnue']);
		exit;
	} catch (Throwable $e) {
		http_response_code(500);
		echo json_encode(['error' => 'server_error']);
		exit;
	}
}

// Page config
$currentPage = 'reports';
$pageTitle = 'Rapports de ventes';
$showSidebar = true;
$additionalCSS = ['assets/css/reports.css'];

// Contenu HTML
ob_start();
?>

<div class="reports-page">
	<div class="reports-header">
		<h1 class="reports-title"><i class="fas fa-chart-bar"></i> Rapports de ventes</h1>
		<div class="reports-filters">
			<div class="filter-group">
				<label>Période</label>
				<div class="filter-row">
					<input type="date" id="filterStart" class="form-control">
					<span class="sep">→</span>
					<input type="date" id="filterEnd" class="form-control">
				</div>
			</div>
			<div class="filter-group">
				<label>Produit</label>
				<select id="filterProduct" class="form-control"><option value="">Tous</option></select>
			</div>
			<div class="filter-group">
				<label>Client</label>
				<select id="filterClient" class="form-control"><option value="">Tous</option></select>
			</div>
			<div class="filter-actions">
				<button class="btn btn-primary" id="applyFilters"><i class="fas fa-sync"></i> Appliquer</button>
				<div class="exports">
					<button class="btn btn-outline" id="exportCsvPeriod" title="Exporter ventes par période (CSV)"><i class="fas fa-file-csv"></i></button>
					<button class="btn btn-outline" id="exportCsvProducts" title="Exporter top produits (CSV)"><i class="fas fa-download"></i></button>
					<a class="btn btn-outline" id="exportPdf" title="Exporter PDF" target="_blank"><i class="fas fa-file-pdf"></i></a>
				</div>
			</div>
		</div>
	</div>

	<div class="kpi-grid">
		<div class="kpi-card">
			<div class="kpi-label">Chiffre d'affaires</div>
			<div class="kpi-value" id="kpiTotal">0</div>
		</div>
		<div class="kpi-card">
			<div class="kpi-label">Commandes</div>
			<div class="kpi-value" id="kpiOrders">0</div>
		</div>
		<div class="kpi-card">
			<div class="kpi-label">Panier moyen</div>
			<div class="kpi-value" id="kpiAvg">0</div>
		</div>
	</div>

	<div class="charts-grid">
		<div class="card">
			<div class="card-header"><h3 class="card-title">Ventes par période</h3>
				<div class="tabs">
					<button class="tab-btn active" data-granularity="day">Jour</button>
					<button class="tab-btn" data-granularity="week">Semaine</button>
					<button class="tab-btn" data-granularity="month">Mois</button>
				</div>
			</div>
			<div class="card-body"><canvas id="salesByPeriodChart" height="100"></canvas></div>
		</div>
		<div class="card">
			<div class="card-header"><h3 class="card-title">Produits les plus vendus</h3></div>
			<div class="card-body">
				<canvas id="topProductsChart" height="100"></canvas>
				<div class="table-responsive mt-md">
					<table class="table" id="topProductsTable">
						<thead><tr><th>Produit</th><th>Quantité</th><th>Chiffre</th></tr></thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
		<div class="card">
			<div class="card-header"><h3 class="card-title">Meilleurs clients</h3></div>
			<div class="card-body">
				<canvas id="topClientsChart" height="100"></canvas>
				<div class="table-responsive mt-md">
					<table class="table" id="topClientsTable">
						<thead><tr><th>Client</th><th>Commandes</th><th>Montant</th></tr></thead>
						<tbody></tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>

<script>
let salesByPeriodChart, topProductsChart, topClientsChart;

function getFilters() {
	const start = document.getElementById('filterStart').value;
	const end = document.getElementById('filterEnd').value;
	const product = document.getElementById('filterProduct').value;
	const client = document.getElementById('filterClient').value;
	const params = new URLSearchParams();
	if (start) params.set('start', start);
	if (end) params.set('end', end);
	if (product) params.set('product_id', product);
	if (client) params.set('client_id', client);
	return params;
}

async function loadFilters() {
	const res = await fetch('reports.php?ajax=filters');
	const data = await res.json();
	const prod = document.getElementById('filterProduct');
	const cli = document.getElementById('filterClient');
	(data.products||[]).forEach(p => {
		const opt = document.createElement('option');
		opt.value = p.id; opt.textContent = p.name; prod.appendChild(opt);
	});
	(data.clients||[]).forEach(c => {
		const opt = document.createElement('option');
		opt.value = c.id; opt.textContent = c.name; cli.appendChild(opt);
	});
}

async function loadKpis() {
	const params = getFilters();
	const res = await fetch('reports.php?ajax=kpis&' + params.toString());
	const d = await res.json();
	document.getElementById('kpiTotal').textContent = (parseFloat(d.total_sales||0)).toFixed(2) + ' €';
	document.getElementById('kpiOrders').textContent = d.orders_count||0;
	document.getElementById('kpiAvg').textContent = (parseFloat(d.avg_ticket||0)).toFixed(2) + ' €';
}

async function loadByPeriod(granularity='day') {
	const params = getFilters(); params.set('granularity', granularity);
	const res = await fetch('reports.php?ajax=by_period&' + params.toString());
	const data = await res.json();
	const labels = data.map(r => r.label);
	const totals = data.map(r => parseFloat(r.total_sales||0));
	const orders = data.map(r => parseInt(r.orders_count||0));

	const ctx = document.getElementById('salesByPeriodChart').getContext('2d');
	if (salesByPeriodChart) salesByPeriodChart.destroy();
	salesByPeriodChart = new Chart(ctx, {
		type: 'bar',
		data: {
			labels,
			datasets: [
				{ label: 'Chiffre (€)', data: totals, backgroundColor: 'rgba(59,130,246,0.5)', borderColor: 'rgba(59,130,246,1)', borderWidth: 1 },
				{ label: 'Commandes', data: orders, type: 'line', yAxisID: 'y1', borderColor: 'rgba(16,185,129,1)', backgroundColor: 'rgba(16,185,129,0.2)' }
			]
		},
		options: {
			responsive: true,
			scales: {
				y: { beginAtZero: true },
				y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false } }
			},
			plugins: { legend: { position: 'bottom' } }
		}
	});
}

async function loadTopProducts() {
	const params = getFilters(); params.set('limit', '10');
	const res = await fetch('reports.php?ajax=top_products&' + params.toString());
	const data = await res.json();
	const labels = data.map(r => r.product_name);
	const qty = data.map(r => parseInt(r.quantity_sold||0));
	const rev = data.map(r => parseFloat(r.revenue||0));

	const ctx = document.getElementById('topProductsChart').getContext('2d');
	if (topProductsChart) topProductsChart.destroy();
	topProductsChart = new Chart(ctx, {
		type: 'bar',
		data: { labels, datasets: [{ label: 'Quantité', data: qty, backgroundColor: 'rgba(139,92,246,0.5)', borderColor: 'rgba(139,92,246,1)', borderWidth: 1 }] },
		options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
	});

	const tbody = document.querySelector('#topProductsTable tbody');
	tbody.innerHTML = data.map(r => `<tr><td>${escapeHtml(r.product_name||'')}</td><td>${r.quantity_sold||0}</td><td>${(parseFloat(r.revenue||0)).toFixed(2)} €</td></tr>`).join('');
}

async function loadTopClients() {
	const params = getFilters(); params.set('limit', '10');
	const res = await fetch('reports.php?ajax=top_clients&' + params.toString());
	const data = await res.json();
	const labels = data.map(r => r.client_name || 'Inconnu');
	const spent = data.map(r => parseFloat(r.total_spent||0));

	const ctx = document.getElementById('topClientsChart').getContext('2d');
	if (topClientsChart) topClientsChart.destroy();
	topClientsChart = new Chart(ctx, {
		type: 'bar',
		data: { labels, datasets: [{ label: 'Montant (€)', data: spent, backgroundColor: 'rgba(16,185,129,0.5)', borderColor: 'rgba(16,185,129,1)', borderWidth: 1 }] },
		options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
	});

	const tbody = document.querySelector('#topClientsTable tbody');
	tbody.innerHTML = data.map(r => `<tr><td>${escapeHtml(r.client_name||'')}</td><td>${r.orders_count||0}</td><td>${(parseFloat(r.total_spent||0)).toFixed(2)} €</td></tr>`).join('');
}

function updateExportLinks() {
	const params = getFilters();
	document.getElementById('exportCsvPeriod').onclick = () => { window.location = 'reports.php?ajax=export_csv&type=by_period&' + params.toString(); };
	document.getElementById('exportCsvProducts').onclick = () => { window.location = 'reports.php?ajax=export_csv&type=top_products&' + params.toString(); };
	document.getElementById('exportPdf').href = 'reports_export_pdf.php?' + params.toString();
}

function escapeHtml(str) {
	return String(str||'').replace(/[&<>"']/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;','\'':'&#39;'}[s]));
}

async function initReports() {
	await loadFilters();
	await loadKpis();
	await loadByPeriod('day');
	await loadTopProducts();
	await loadTopClients();
	updateExportLinks();
}

document.addEventListener('DOMContentLoaded', () => {
	// Default dates: last 30 days
	const end = new Date();
	const start = new Date(); start.setDate(start.getDate()-29);
	document.getElementById('filterEnd').value = end.toISOString().substring(0,10);
	document.getElementById('filterStart').value = start.toISOString().substring(0,10);

	// Tabs
	document.querySelectorAll('.tab-btn').forEach(btn => {
		btn.addEventListener('click', async () => {
			document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
			btn.classList.add('active');
			await loadByPeriod(btn.dataset.granularity);
		});
	});

	document.getElementById('applyFilters').addEventListener('click', async () => {
		await loadKpis();
		const active = document.querySelector('.tab-btn.active');
		await loadByPeriod(active ? active.dataset.granularity : 'day');
		await loadTopProducts();
		await loadTopClients();
		updateExportLinks();
	});

	initReports();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


