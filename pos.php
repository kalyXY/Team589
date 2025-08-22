<?php
/**
 * Scolaria - POS (Point of Sale)
 * Vente directe des fournitures scolaires (connectée MySQL)
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/auth.php';

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

require_roles(['admin','gestionnaire','caissier']);

// Endpoints AJAX
if (isset($_GET['ajax'])) {
	header('Content-Type: application/json; charset=utf-8');
	$pdo = Database::getConnection();

	try {
		if ($_GET['ajax'] === 'search') {
			$q = trim((string)($_GET['q'] ?? ''));
			if ($q === '') {
				// Listing par défaut (50 premiers en stock)
				$stmt = $pdo->query('SELECT id, nom_article, COALESCE(categorie, "") AS categorie, quantite, COALESCE(prix_vente, 0) AS prix_vente, COALESCE(code_barres, "") AS code_barres FROM stocks WHERE quantite > 0 ORDER BY nom_article ASC LIMIT 50');
				$rows = $stmt->fetchAll() ?: [];
				echo json_encode($rows);
				exit;
			}
			// Recherche par code-barres exact ou LIKE nom/catégorie/code_barres
			$sql = 'SELECT id, nom_article, COALESCE(categorie, "") AS categorie, quantite, COALESCE(prix_vente, 0) AS prix_vente, COALESCE(code_barres, "") AS code_barres
					FROM stocks
					WHERE quantite > 0 AND (
						code_barres = :qexact OR nom_article LIKE :qlike OR categorie LIKE :qlike OR code_barres LIKE :qlike
					)
					ORDER BY nom_article ASC LIMIT 50';
			$stmt = $pdo->prepare($sql);
			$stmt->bindValue(':qexact', $q, PDO::PARAM_STR);
			$like = '%' . $q . '%';
			$stmt->bindValue(':qlike', $like, PDO::PARAM_STR);
			$stmt->execute();
			$rows = $stmt->fetchAll() ?: [];
			echo json_encode($rows);
			exit;
		}

		if ($_GET['ajax'] === 'clients') {
			$cl = $pdo->query('SELECT id, CONCAT(last_name, " ", first_name) AS name FROM clients ORDER BY last_name ASC, first_name ASC LIMIT 200')->fetchAll() ?: [];
			echo json_encode($cl);
			exit;
		}

		if ($_GET['ajax'] === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$raw = file_get_contents('php://input');
			$data = json_decode($raw, true);
			
			$items = is_array($data['items'] ?? null) ? $data['items'] : [];
			$clientId = isset($data['client_id']) && $data['client_id'] !== '' ? (int)$data['client_id'] : null;
			$discountType = in_array(($data['discount_type'] ?? ''), ['percent','amount'], true) ? (string)$data['discount_type'] : null;
			$discountValue = max(0.0, (float)($data['discount_value'] ?? 0));
			$modePaiement = (string)($data['mode_paiement'] ?? 'cash'); // cash, mobile_money, card, transfer
			$caissierId = (int)($_SESSION['user_id'] ?? 0);

			if (empty($items)) {
				echo json_encode(['ok' => false, 'error' => 'Panier vide']);
				exit;
			}

			$pdo->beginTransaction();
			try {
				$subtotal = 0.0; // Sera calculé dynamiquement
				// Vérification et calcul du sous-total
				foreach ($items as $it) {
					$productId = (int)($it['product_id'] ?? 0);
					$qty = max(1, (int)($it['quantity'] ?? 0));
					$price = (float)($it['price'] ?? 0);
					
					if ($productId <= 0 || $qty <= 0 || $price < 0) {
						throw new RuntimeException('Données article invalides');
					}
					// Stock disponible
					$st = $pdo->prepare('SELECT quantite, COALESCE(prix_vente,0) AS prix_vente, COALESCE(seuil_alerte, 0) AS seuil_alerte FROM stocks WHERE id = ? FOR UPDATE');
					$st->execute([$productId]);
					$row = $st->fetch();
					if (!$row) {
						throw new RuntimeException('Article introuvable');
					}
					if ((int)$row['quantite'] < $qty) {
						throw new RuntimeException('Stock insuffisant pour un article');
					}
					if ($price <= 0) {
						$price = (float)$row['prix_vente'];
					}
					$subtotal += $price * $qty;
				}

				$discountAmount = 0.0; // Sera calculé dynamiquement
				if ($discountType === 'percent') {
					$discountAmount = round($subtotal * min(100.0, $discountValue) / 100.0, 2);
				} elseif ($discountType === 'amount') {
					$discountAmount = min($subtotal, $discountValue);
				}
				$total = max(0.0, $subtotal - $discountAmount);

				// Insérer la vente (sales)
				$insSale = $pdo->prepare('INSERT INTO sales (client_id, total) VALUES (:client_id, :total)');
				$insSale->bindValue(':client_id', $clientId, $clientId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
				$insSale->bindValue(':total', $total);
				$insSale->execute();
				$saleId = (int)$pdo->lastInsertId();

				// Insérer les items + décrémenter le stock + alertes faibles
				$insItem = $pdo->prepare('INSERT INTO sales_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
				$decStock = $pdo->prepare('UPDATE stocks SET quantite = quantite - ? WHERE id = ?');
				$selStock = $pdo->prepare('SELECT quantite, COALESCE(seuil_alerte, 0) AS seuil_alerte FROM stocks WHERE id = ?');
				$insAlerte = $pdo->prepare('INSERT INTO alertes (stock_id, type, message) VALUES (?, ?, ?)');
				foreach ($items as $it) {
					$productId = (int)$it['product_id'];
					$qty = max(1, (int)$it['quantity']);
					$price = (float)$it['price'];
					$insItem->execute([$saleId, $productId, $qty, $price]);
					$decStock->execute([$qty, $productId]);
					$selStock->execute([$productId]);
					$strow = $selStock->fetch();
					if ($strow && (int)$strow['quantite'] <= (int)$strow['seuil_alerte']) {
						$type = ((int)$strow['quantite'] <= 0) ? 'out_of_stock' : 'low_stock';
						$insAlerte->execute([$productId, $type, 'Stock ' . $type . ' après vente POS']);
					}
				}

				// Enregistrer la transaction (paiement)
				$insTxn = $pdo->prepare('INSERT INTO transactions (sale_id, payment_method, amount) VALUES (?, ?, ?)');
				$insTxn->execute([$saleId, $modePaiement, $total]);

				$pdo->commit();
				echo json_encode(['ok' => true, 'sale_id' => $saleId, 'total' => $total, 'invoice_url' => ('invoice.php?id=' . $saleId)]);
				exit;
			} catch (Throwable $e) {
				if ($pdo->inTransaction()) { $pdo->rollBack(); }
				echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
				exit;
			}
		}

		// Endpoint inconnu
		echo json_encode(['ok' => false, 'error' => 'action inconnue']);
		exit;
	} catch (Throwable $e) {
		header('Content-Type: application/json; charset=utf-8', true, 500);
		echo json_encode(['ok' => false, 'error' => 'server_error']);
		exit;
	}
}

// Configuration de la page
$currentPage = 'pos';
$pageTitle = 'Point de Vente (POS)';
$showSidebar = true;
$additionalCSS = ['assets/css/pos.css'];
$additionalJS = [];

// Début du contenu HTML
ob_start();
?>

<div class="pos-container">
	<div class="pos-left">
		<div class="pos-search">
			<input type="text" id="searchInput" class="pos-input" placeholder="Rechercher (code-barres, nom, catégorie)">
		</div>
		<div id="productsList" class="pos-products"></div>
	</div>
	<div class="pos-right">
		<div class="pos-cart">
			<h3 class="pos-cart-title"><i class="fas fa-shopping-cart"></i> Panier</h3>
			<div id="cartItems" class="pos-cart-items"></div>

			<div class="pos-controls">
				<div class="control-row">
					<label>Remise</label>
					<div style="display:flex; gap:6px; align-items:center;">
						<select id="discountType" class="form-control" style="max-width:130px">
							<option value="none">Aucune</option>
							<option value="percent">%</option>
							<option value="amount">Montant</option>
						</select>
						<input type="number" id="discountValue" class="form-control" min="0" step="0.01" placeholder="">
					</div>
				</div>
				<div class="control-row">
					<label>Mode de paiement</label>
					<select id="paymentMode" class="form-control">
						<option value="cash">Espèces</option>
						<option value="mobile_money">Mobile Money</option>
						<option value="card">Carte</option>
						<option value="transfer">Virement</option>
					</select>
				</div>
				<div class="control-row">
					<label>Client</label>
					<select id="clientSelect" class="form-control">
						<option value="">Client par défaut</option>
					</select>
				</div>
			</div>

			<div class="pos-cart-footer">
				<div class="pos-total-row">
					<span>Total</span>
					<span id="cartTotal">0.00 €</span>
				</div>
				<button id="checkoutBtn" class="btn btn-primary pos-checkout"><i class="fas fa-check"></i> Valider la vente</button>
				<div id="checkoutMsg" class="pos-message" aria-live="polite"></div>
			</div>
		</div>
	</div>
</div>

<script>
let products = [];
let cart = [];

function formatPrice(value) { return (parseFloat(value || 0)).toFixed(2) + ' €'; }

function renderProducts() {
	const list = document.getElementById('productsList');
	list.innerHTML = '';
	if (products.length === 0) {
		list.innerHTML = '<div class="pos-empty">Aucun article trouvé.</div>';
		return;
	}
	products.forEach(p => {
		const item = document.createElement('div');
		item.className = 'pos-product';
		const disabled = (parseInt(p.quantite||0) <= 0);
		item.innerHTML = `
			<div class="pos-product-info">
				<div class="pos-product-name">${p.nom_article}</div>
				<div class="pos-product-meta">Catégorie: ${p.categorie || '-'} • Stock: ${p.quantite} ${p.code_barres ? '• CB: '+p.code_barres : ''}</div>
			</div>
			<div class="pos-product-actions">
				<div class="pos-product-price">${formatPrice(p.prix_vente)}</div>
				<button class="btn btn-sm btn-primary" ${disabled?'disabled':''} onclick="addToCart(${p.id}, '${p.nom_article.replace(/'/g, "\\'")}', ${p.prix_vente}, ${p.quantite})">Ajouter</button>
			</div>
		`;
		list.appendChild(item);
	});
}

function computeSubtotal() {
	return cart.reduce((acc,c)=> acc + (c.price * c.quantity), 0);
}

function updateTotal() {
	const subtotal = computeSubtotal();
	const type = document.getElementById('discountType').value;
	const val = parseFloat(document.getElementById('discountValue').value || '0');
	let discount = 0; // Sera calculé dynamiquement
	if (type === 'percent') discount = subtotal * Math.min(100, Math.max(0, val)) / 100;
	if (type === 'amount') discount = Math.min(subtotal, Math.max(0, val));
	const total = Math.max(0, subtotal - discount);
	document.getElementById('cartTotal').textContent = formatPrice(total);
}

function renderCart() {
	const list = document.getElementById('cartItems');
	list.innerHTML = '';
	cart.forEach((c, idx) => {
		const lineTotal = c.price * c.quantity;
		const row = document.createElement('div');
		row.className = 'pos-cart-item';
		row.innerHTML = `
			<div class="pos-cart-item-name">${c.name}</div>
			<div class="pos-cart-item-actions">
				<div class="qty-control">
					<button onclick="changeQty(${idx}, -1)">-</button>
					<input type="number" min="1" max="${c.max}" value="${c.quantity}" oninput="setQty(${idx}, this.value)">
					<button onclick="changeQty(${idx}, 1)">+</button>
				</div>
				<div class="pos-line-price">${formatPrice(lineTotal)}</div>
				<button class="btn btn-sm btn-danger" onclick="removeItem(${idx})"><i class="fas fa-trash"></i></button>
			</div>
		`;
		list.appendChild(row);
	});
	updateTotal();
}

function addToCart(id, name, price, stock) {
	const existing = cart.find(c => c.product_id === id);
	if (existing) {
		if (existing.quantity < existing.max) { existing.quantity++; }
	} else {
		const cartItem = { 
			product_id: parseInt(id) || 0, 
			name: name || '', 
			price: parseFloat(price || 0), 
			quantity: 1, 
			max: parseInt(stock || 0) 
		};
		cart.push(cartItem);
	}
	renderCart();
}

function removeItem(idx) { cart.splice(idx, 1); renderCart(); }
function changeQty(idx, delta) { const item = cart[idx]; if (!item) return; const q = Math.max(1, Math.min(item.max, (item.quantity + delta))); item.quantity = q; renderCart(); }
function setQty(idx, val) { const item = cart[idx]; if (!item) return; let v = parseInt(val || 1); if (isNaN(v)) v = 1; v = Math.max(1, Math.min(item.max, v)); item.quantity = v; renderCart(); }

async function searchProducts() {
	const q = document.getElementById('searchInput').value.trim();
	const res = await fetch(`pos.php?ajax=search&q=${encodeURIComponent(q)}`);
	const data = await res.json();
	products = Array.isArray(data) ? data : [];
	renderProducts();
}

async function loadClients() {
	const res = await fetch('pos.php?ajax=clients');
	const data = await res.json();
	const sel = document.getElementById('clientSelect');
	(data||[]).forEach(c => {
		const opt = document.createElement('option'); opt.value = c.id; opt.textContent = c.name; sel.appendChild(opt);
	});
}

async function checkout() {
	if (cart.length === 0) { showMsg('Panier vide', 'error'); return; }
	// Vérifier les quantités
	for (const it of cart) { if (it.quantity > it.max) { showMsg(`Stock insuffisant pour ${it.name}`, 'error'); return; } }
	
	const btn = document.getElementById('checkoutBtn');
	btn.disabled = true; btn.classList.add('loading');
	try {
		const payload = {
			items: cart,
			client_id: document.getElementById('clientSelect').value,
			discount_type: (document.getElementById('discountType').value === 'none' ? null : document.getElementById('discountType').value),
			discount_value: parseFloat(document.getElementById('discountValue').value || '0'),
			mode_paiement: document.getElementById('paymentMode').value
		};
		
		const res = await fetch('pos.php?ajax=checkout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
		const data = await res.json();
		
		if (!data.ok) { throw new Error(data.error || 'Erreur inconnue'); }
		showMsg(`Vente validée. Ticket #${data.sale_id} - Total ${formatPrice(data.total)}`, 'success');
		if (data.invoice_url) { window.open(data.invoice_url, '_blank'); }
		cart = []; renderCart(); searchProducts();
	} catch (e) {
		showMsg(e.message || 'Erreur lors de la validation', 'error');
	} finally {
		btn.disabled = false; btn.classList.remove('loading');
	}
}

function showMsg(text, type) { const el = document.getElementById('checkoutMsg'); el.textContent = text; el.className = 'pos-message ' + (type || 'info'); }

document.addEventListener('DOMContentLoaded', () => {
	document.getElementById('searchInput').addEventListener('input', () => { clearTimeout(window.__t); window.__t = setTimeout(searchProducts, 250); });
	document.getElementById('checkoutBtn').addEventListener('click', checkout);
	document.getElementById('discountType').addEventListener('change', updateTotal);
	document.getElementById('discountValue').addEventListener('input', updateTotal);
	loadClients();
	searchProducts();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


