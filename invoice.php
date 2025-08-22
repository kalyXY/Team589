<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

require_roles(['admin','caissier','gestionnaire']);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { http_response_code(400); echo 'Facture invalide'; exit; }

$pdo = Database::getConnection();
$sale = $pdo->prepare('SELECT sa.id, sa.total, sa.created_at, sa.client_id, c.first_name, c.last_name FROM sales sa LEFT JOIN clients c ON c.id = sa.client_id WHERE sa.id = ?');
$sale->execute([$id]);
$s = $sale->fetch();
if (!$s) { http_response_code(404); echo 'Vente introuvable'; exit; }

$items = $pdo->prepare('SELECT si.product_id, si.quantity, si.price, st.nom_article FROM sales_items si LEFT JOIN stocks st ON st.id = si.product_id WHERE si.sale_id = ?');
$items->execute([$id]);
$rows = $items->fetchAll() ?: [];

// Paiement (transactions)
$txn = $pdo->prepare('SELECT payment_method, amount, reference, paid_at FROM transactions WHERE sale_id = ? ORDER BY paid_at ASC');
$txn->execute([$id]);
$txns = $txn->fetchAll() ?: [];
$paymentMethod = $txns[0]['payment_method'] ?? null;
$amountPaid = 0.0;
foreach ($txns as $t) { $amountPaid += (float)($t['amount'] ?? 0); }

$clientName = trim(($s['last_name'] ?? '') . ' ' . ($s['first_name'] ?? '')) ?: 'Client par défaut';
$caissier = (string)($_SESSION['username'] ?? 'caissier');
$date = date('d/m/Y H:i', strtotime((string)$s['created_at']));
$invoiceNo = sprintf('MS-%06d', (int)$s['id']);

// HTML simple (imprimable) - compatible impression navigateur; pour PDF, renvoyer vers reports_export_pdf.php adapté
?><!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?php echo htmlspecialchars($invoiceNo); ?></title>
    <style>
        body { font-family: Arial, sans-serif; color:#111; padding: 24px; }
        .header { display:flex; justify-content: space-between; align-items: center; margin-bottom: 16px; }
        .logo { font-size: 20px; font-weight: 700; display:flex; gap:8px; align-items:center; }
        .meta { text-align:right; font-size: 12px; color:#555; }
        table { width:100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border:1px solid #ddd; padding:8px; text-align:left; }
        th { background:#f3f4f6; text-transform: uppercase; font-size: 12px; }
        .totals { margin-top: 12px; float: right; }
        .footer { margin-top: 40px; font-size:12px; color:#555; }
        .stamp { margin-top: 24px; display:inline-block; padding: 8px 12px; border:2px solid #1e40af; color:#1e40af; font-weight:700; }
        @media print { .actions { display:none; } }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="actions" style="margin-bottom: 16px; display:flex; gap:8px;">
        <button onclick="window.print()" style="padding:8px 12px;">Imprimer</button>
        <a href="#" onclick="window.print()" style="padding:8px 12px;">Télécharger (impression PDF)</a>
    </div>

    <div class="header">
        <div class="logo"><i class="fas fa-school"></i> Mama Sophie – Scolaria</div>
        <div class="meta">
            <div>Facture: <strong><?php echo htmlspecialchars($invoiceNo); ?></strong></div>
            <div>Date: <?php echo htmlspecialchars($date); ?></div>
            <div>Caissier: <?php echo htmlspecialchars($caissier); ?></div>
        </div>
    </div>
    <div>
        Client: <strong><?php echo htmlspecialchars($clientName); ?></strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th>Qté</th>
                <th>Prix U.</th>
                <th>Sous-total</th>
            </tr>
        </thead>
        <tbody>
            <?php $subtotal = 0.0; foreach ($rows as $r): $st = (float)$r['price'] * (int)$r['quantity']; $subtotal += $st; ?>
            <tr>
                <td><?php echo htmlspecialchars($r['nom_article'] ?? ''); ?></td>
                <td><?php echo (int)$r['quantity']; ?></td>
                <td><?php echo number_format((float)$r['price'], 2, ',', ' '); ?> €</td>
                <td><strong><?php echo number_format($st, 2, ',', ' '); ?> €</strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php $saleTotal = (float)$s['total']; $discount = max(0.0, $subtotal - $saleTotal); ?>
    <div class="totals">
        <div style="font-size: 14px;">Sous-total: <strong><?php echo number_format($subtotal, 2, ',', ' '); ?> €</strong></div>
        <?php if ($discount > 0): ?>
        <div style="font-size: 14px;">Remise: <strong>-<?php echo number_format($discount, 2, ',', ' '); ?> €</strong></div>
        <?php endif; ?>
        <div style="font-size: 16px; margin-top:4px;">Total à payer: <strong><?php echo number_format($saleTotal, 2, ',', ' '); ?> €</strong></div>
        <?php if ($paymentMethod): ?>
        <div style="font-size: 12px; color:#444; margin-top:6px;">Mode de paiement: <strong><?php echo htmlspecialchars($paymentMethod); ?></strong> • Encaissé: <strong><?php echo number_format($amountPaid, 2, ',', ' '); ?> €</strong></div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <div class="stamp">Cachet / Signature – Mama Sophie</div>
        <p>Merci pour votre achat.</p>
    </div>
</body>
</html>


