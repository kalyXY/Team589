<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

require_roles(['admin','gestionnaire','caissier']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo 'Vente invalide'; exit; }

$pdo = Database::getConnection();

// Vente + client
$stmt = $pdo->prepare('SELECT sa.id, sa.total, sa.created_at, sa.client_id, c.first_name, c.last_name, c.phone, c.email, c.address
                       FROM sales sa
                       LEFT JOIN clients c ON c.id = sa.client_id
                       WHERE sa.id = :id');
$stmt->bindValue(':id', $id, PDO::PARAM_INT);
$stmt->execute();
$sale = $stmt->fetch();
if (!$sale) { http_response_code(404); echo 'Vente introuvable'; exit; }

// Articles
$it = $pdo->prepare('SELECT si.product_id, si.quantity, si.price, st.nom_article, st.categorie
                     FROM sales_items si
                     LEFT JOIN stocks st ON st.id = si.product_id
                     WHERE si.sale_id = :id');
$it->bindValue(':id', $id, PDO::PARAM_INT);
$it->execute();
$items = $it->fetchAll() ?: [];

// Transactions (paiements)
$tx = $pdo->prepare('SELECT payment_method, amount, reference, paid_at FROM transactions WHERE sale_id = :id ORDER BY paid_at ASC');
$tx->bindValue(':id', $id, PDO::PARAM_INT);
$tx->execute();
$payments = $tx->fetchAll() ?: [];

// Calculs
$subtotal = 0.0;
foreach ($items as $row) { $subtotal += ((float)($row['price'] ?? 0)) * ((int)($row['quantity'] ?? 0)); }
$total = (float)($sale['total'] ?? 0);
$discount = max(0.0, $subtotal - $total);
$amountPaid = 0.0; foreach ($payments as $p) { $amountPaid += (float)($p['amount'] ?? 0); }

$clientName = trim(($sale['last_name'] ?? '') . ' ' . ($sale['first_name'] ?? '')) ?: 'Client par défaut';

$currentPage = 'reports';
$pageTitle = 'Détail de la vente #' . (int)$sale['id'];
$showSidebar = true;
$additionalCSS = ['assets/css/reports.css'];
$additionalJS = [];

ob_start();
?>

<div class="card" style="margin-bottom: var(--spacing-xl);">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-receipt"></i> Vente #<?php echo (int)$sale['id']; ?></h3>
        <p class="card-subtitle">Date: <?php echo htmlspecialchars((string)$sale['created_at']); ?> • Client: <?php echo htmlspecialchars($clientName); ?></p>
    </div>
    <div class="card-body">
        <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: var(--spacing-lg); margin-bottom: var(--spacing-lg);">
            <div class="list-item">
                <div class="list-item-content">
                    <div class="list-item-title">Client</div>
                    <div class="list-item-subtitle"><?php echo htmlspecialchars($clientName); ?></div>
                    <?php if (!empty($sale['phone']) || !empty($sale['email'])): ?>
                        <div class="text-muted" style="margin-top:4px; font-size:12px;">
                            <?php if (!empty($sale['phone'])): ?><i class="fas fa-phone"></i> <?php echo htmlspecialchars((string)$sale['phone']); ?><?php endif; ?>
                            <?php if (!empty($sale['email'])): ?> • <i class="fas fa-envelope"></i> <?php echo htmlspecialchars((string)$sale['email']); ?><?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="list-item">
                <div class="list-item-content">
                    <div class="list-item-title">Montant total</div>
                    <div class="list-item-subtitle" style="font-weight:600; font-size:18px;">$<?php echo number_format($total, 2, ',', ' '); ?></div>
                    <?php if ($discount > 0): ?><div class="text-muted" style="margin-top:4px; font-size:12px;">Remise: $<?php echo number_format($discount, 2, ',', ' '); ?> (Sous-total $<?php echo number_format($subtotal, 2, ',', ' '); ?>)</div><?php endif; ?>
                </div>
            </div>
            <div class="list-item">
                <div class="list-item-content">
                    <div class="list-item-title">Paiements</div>
                    <div class="list-item-subtitle" style="font-weight:600;">$<?php echo number_format($amountPaid, 2, ',', ' '); ?></div>
                    <?php if (!empty($payments)): ?><div class="text-muted" style="margin-top:4px; font-size:12px;">Méthode: <?php echo htmlspecialchars((string)($payments[0]['payment_method'] ?? '-')); ?></div><?php endif; ?>
                </div>
            </div>
            <div class="list-item">
                <div class="list-item-content">
                    <div class="list-item-title">Actions</div>
                    <div class="list-item-subtitle">
                        <a class="btn btn-sm btn-primary" href="invoice.php?id=<?php echo (int)$sale['id']; ?>" target="_blank"><i class="fas fa-print"></i> Imprimer la facture</a>
                    </div>
                </div>
            </div>
        </div>

        <h4 style="margin-bottom:8px;">Articles</h4>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Produit</th><th>Catégorie</th><th>Qté</th><th>Prix U.</th><th>Sous-total</th></tr></thead>
                <tbody>
                <?php if (!empty($items)): foreach ($items as $r): $st = ((float)$r['price']) * ((int)$r['quantity']); ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)($r['nom_article'] ?? '')); ?></td>
                        <td><?php echo htmlspecialchars((string)($r['categorie'] ?? '')); ?></td>
                        <td><?php echo (int)($r['quantity'] ?? 0); ?></td>
                        <td><?php echo number_format((float)($r['price'] ?? 0), 2, ',', ' '); ?> $</td>
                        <td><strong><?php echo number_format($st, 2, ',', ' '); ?> $</strong></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="5" class="text-muted">Aucun article</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <h4 style="margin:16px 0 8px;">Paiements</h4>
        <div class="table-responsive">
            <table class="table">
                <thead><tr><th>Méthode</th><th>Montant</th><th>Référence</th><th>Date</th></tr></thead>
                <tbody>
                <?php if (!empty($payments)): foreach ($payments as $p): ?>
                    <tr>
                        <td><?php echo htmlspecialchars((string)($p['payment_method'] ?? '')); ?></td>
                        <td><?php echo number_format((float)($p['amount'] ?? 0), 2, ',', ' '); ?> $</td>
                        <td><?php echo htmlspecialchars((string)($p['reference'] ?? '-')); ?></td>
                        <td><?php echo htmlspecialchars((string)($p['paid_at'] ?? '')); ?></td>
                    </tr>
                <?php endforeach; else: ?>
                    <tr><td colspan="4" class="text-muted">Aucun paiement enregistré.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


