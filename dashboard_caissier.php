<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

require_roles(['admin','caissier']);

$pdo = Database::getConnection();

$todayStart = (new DateTime('today'))->format('Y-m-d 00:00:00');
$todayEnd = (new DateTime('today'))->format('Y-m-d 23:59:59');

$kpiStmt = $pdo->prepare('SELECT COALESCE(SUM(total),0) AS total, COUNT(*) AS orders FROM sales WHERE created_at BETWEEN :s AND :e');
$kpiStmt->execute([':s' => $todayStart, ':e' => $todayEnd]);
$kpi = $kpiStmt->fetch() ?: ['total' => 0, 'orders' => 0];

$clientsStmt = $pdo->query('SELECT id, first_name, last_name, phone, created_at FROM clients ORDER BY created_at DESC LIMIT 10');
$latestClients = $clientsStmt->fetchAll() ?: [];

$currentPage = 'dashboard';
$pageTitle = 'Dashboard Caissier';
$showSidebar = true;
$additionalCSS = ['assets/css/dashboard.css', 'assets/css/clients.css'];

ob_start();
?>

<div class="clients-page">
	<div class="clients-header">
		<h2 class="clients-title"><i class="fas fa-cash-register"></i> Bienvenue, <?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></h2>
		<div style="display:flex; gap:8px;">
			<a href="pos.php" class="btn btn-primary"><i class="fas fa-shopping-cart"></i> Accéder au POS</a>
			<a href="clients.php" class="btn btn-outline"><i class="fas fa-user-friends"></i> Gérer les clients</a>
		</div>
	</div>

	<div class="clients-stats">
		<div class="client-stat-card">
			<div class="client-stat-header"><h3 class="client-stat-title">Ventes du jour</h3><div class="client-stat-icon"><i class="fas fa-coins"></i></div></div>
			<div class="client-stat-value"><?php echo number_format((float)$kpi['total'], 2); ?> €</div>
			<div class="client-stat-subtitle">Chiffre d'affaires</div>
		</div>
		<div class="client-stat-card">
			<div class="client-stat-header"><h3 class="client-stat-title">Transactions</h3><div class="client-stat-icon"><i class="fas fa-receipt"></i></div></div>
			<div class="client-stat-value"><?php echo (int)$kpi['orders']; ?></div>
			<div class="client-stat-subtitle">Nombre de tickets</div>
		</div>
	</div>

	<div class="clients-table card">
		<div class="card-header"><h3 class="card-title">Derniers clients</h3></div>
		<div class="card-body">
			<div class="table-responsive">
				<table class="table"><thead><tr><th>Client</th><th>Téléphone</th><th>Créé le</th></tr></thead>
				<tbody>
					<?php foreach ($latestClients as $c): ?>
					<tr>
						<td><?php echo htmlspecialchars(($c['last_name'] ?? '') . ' ' . ($c['first_name'] ?? '')); ?></td>
						<td><?php echo htmlspecialchars($c['phone'] ?? ''); ?></td>
						<td><?php echo htmlspecialchars($c['created_at']); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody></table>
			</div>
		</div>
	</div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


