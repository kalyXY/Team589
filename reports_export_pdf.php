<?php
/**
 * Exemple d'export PDF pour les rapports de ventes (Dompdf)
 * Prérequis: installer dompdf via Composer et inclure l'autoload si disponible.
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';

// Charger Dompdf si dispo (composer)
// @phpstan-ignore-next-line
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use Dompdf\Dompdf;
use Dompdf\Options;

if (!class_exists(Dompdf::class)) {
    header('Content-Type: text/plain; charset=utf-8');
    echo "Dompdf non installé. Installez via Composer: composer require dompdf/dompdf\n";
    exit;
}

[$start, $end] = (function(): array {
    $end = isset($_GET['end']) ? trim((string)$_GET['end']) : '';
    $start = isset($_GET['start']) ? trim((string)$_GET['start']) : '';
    if ($start === '' || $end === '') {
        $endDate = new DateTime('today');
        $startDate = (new DateTime('today'))->modify('-29 days');
        return [$startDate->format('Y-m-d') . ' 00:00:00', $endDate->format('Y-m-d') . ' 23:59:59'];
    }
    return [$start . ' 00:00:00', $end . ' 23:59:59'];
})();

$pdo = Database::getConnection();

// Récupérer données simples: KPI + ventes par jour + top produits + top clients
$kpiStmt = $pdo->prepare("SELECT COALESCE(SUM(total),0) AS total, COALESCE(AVG(total),0) AS avg_ticket, COUNT(*) AS orders
                          FROM sales WHERE created_at BETWEEN :s AND :e");
$kpiStmt->execute([':s' => $start, ':e' => $end]);
$kpis = $kpiStmt->fetch() ?: ['total' => 0, 'avg_ticket' => 0, 'orders' => 0];

$byDayStmt = $pdo->prepare("SELECT DATE_FORMAT(created_at, '%Y-%m-%d') AS d, SUM(total) AS t, COUNT(*) AS c
                            FROM sales WHERE created_at BETWEEN :s AND :e
                            GROUP BY DATE(created_at) ORDER BY DATE(created_at)");
$byDayStmt->execute([':s' => $start, ':e' => $end]);
$byDay = $byDayStmt->fetchAll() ?: [];

$topProdStmt = $pdo->prepare("SELECT st.nom_article AS name, SUM(si.quantity) AS qty, SUM(si.quantity*si.price) AS revenue
                               FROM sales sa JOIN sales_items si ON si.sale_id=sa.id
                               JOIN stocks st ON st.id=si.product_id
                               WHERE sa.created_at BETWEEN :s AND :e
                               GROUP BY st.id, st.nom_article ORDER BY qty DESC LIMIT 10");
$topProdStmt->execute([':s' => $start, ':e' => $end]);
$topProd = $topProdStmt->fetchAll() ?: [];

$topCliStmt = $pdo->prepare("SELECT CONCAT(c.last_name,' ',c.first_name) AS name, COUNT(sa.id) AS orders, SUM(sa.total) AS spent
                              FROM sales sa LEFT JOIN clients c ON c.id=sa.client_id
                              WHERE sa.created_at BETWEEN :s AND :e
                              GROUP BY c.id, c.last_name, c.first_name ORDER BY spent DESC LIMIT 10");
$topCliStmt->execute([':s' => $start, ':e' => $end]);
$topCli = $topCliStmt->fetchAll() ?: [];

// Construire HTML simple
$periodText = htmlspecialchars(substr($start,0,10) . ' → ' . substr($end,0,10));
$html = '<html><head><meta charset="utf-8"><style>
body{font-family: DejaVu Sans, Arial, sans-serif; font-size:12px; color:#111}
h1{font-size:18px;margin:0 0 10px 0}
h2{font-size:14px;margin:20px 0 6px 0}
table{width:100%; border-collapse:collapse; margin:8px 0}
th,td{border:1px solid #ddd; padding:6px}
th{background:#f3f4f6; text-transform:uppercase; font-size:11px}
.kpi{display:flex; gap:10px}
.kpi div{border:1px solid #ddd; padding:8px 10px}
</style></head><body>';
$html .= '<h1>Rapport de ventes</h1>';
$html .= '<div>Période: ' . $periodText . '</div>';
$html .= '<div class="kpi">'
       . '<div><strong>Chiffre d\'affaires:</strong> ' . number_format((float)$kpis['total'], 2, ',', ' ') . ' €</div>'
       . '<div><strong>Commandes:</strong> ' . (int)$kpis['orders'] . '</div>'
       . '<div><strong>Panier moyen:</strong> ' . number_format((float)$kpis['avg_ticket'], 2, ',', ' ') . ' €</div>'
       . '</div>';

$html .= '<h2>Ventes par jour</h2><table><thead><tr><th>Date</th><th>Montant</th><th>Commandes</th></tr></thead><tbody>';
foreach ($byDay as $r) {
    $html .= '<tr><td>' . htmlspecialchars($r['d']) . '</td><td>' . number_format((float)$r['t'], 2, ',', ' ') . ' €</td><td>' . (int)$r['c'] . '</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<h2>Top produits</h2><table><thead><tr><th>Produit</th><th>Quantité</th><th>Chiffre</th></tr></thead><tbody>';
foreach ($topProd as $r) {
    $html .= '<tr><td>' . htmlspecialchars($r['name']) . '</td><td>' . (int)$r['qty'] . '</td><td>' . number_format((float)$r['revenue'], 2, ',', ' ') . ' €</td></tr>';
}
$html .= '</tbody></table>';

$html .= '<h2>Meilleurs clients</h2><table><thead><tr><th>Client</th><th>Commandes</th><th>Montant</th></tr></thead><tbody>';
foreach ($topCli as $r) {
    $html .= '<tr><td>' . htmlspecialchars($r['name'] ?: 'Inconnu') . '</td><td>' . (int)$r['orders'] . '</td><td>' . number_format((float)$r['spent'], 2, ',', ' ') . ' €</td></tr>';
}
$html .= '</tbody></table>';

$html .= '</body></html>';

$options = new Options();
$options->set('isRemoteEnabled', true);
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$dompdf->stream('rapport_ventes.pdf', ['Attachment' => true]);
exit;


