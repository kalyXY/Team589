<?php
/**
 * Scolaria - POS (Point of Sale)
 * Vente directe des fournitures scolaires
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

$role = (string)($_SESSION['role'] ?? '');
if (!in_array($role, ['admin', 'gestionnaire', 'caissier'], true)) {
	header('HTTP/1.1 403 Forbidden');
	echo 'Accès refusé';
	exit;
}

// Endpoints AJAX
if (isset($_GET['ajax'])) {
	header('Content-Type: application/json; charset=utf-8');
	$pdo = Database::getConnection();

	try {
		if ($_GET['ajax'] === 'search') {
			$q = trim((string)($_GET['q'] ?? ''));
			$sql = 'SELECT id, nom_article, categorie, quantite, COALESCE(prix_vente, 0) AS prix_vente
					FROM stocks
					WHERE quantite > 0 AND (nom_article LIKE :q OR categorie LIKE :q) 
					ORDER BY nom_article ASC LIMIT 50';
			$stmt = $pdo->prepare($sql);
			$like = '%' . $q . '%';
			$stmt->bindValue(':q', $like, PDO::PARAM_STR);
			$stmt->execute();
			$rows = $stmt->fetchAll() ?: [];
			echo json_encode($rows);
			exit;
		}

		if ($_GET['ajax'] === 'checkout' && $_SERVER['REQUEST_METHOD'] === 'POST') {
			$raw = file_get_contents('php://input');
			$data = json_decode($raw, true);
			$items = is_array($data['items'] ?? null) ? $data['items'] : [];
			$clientId = isset($data['client_id']) && $data['client_id'] !== '' ? (int)$data['client_id'] : null;

			if (empty($items)) {
				echo json_encode(['ok' => false, 'error' => 'Panier vide']);
				exit;
			}

			$pdo->beginTransaction();
			try {
				$total = 0.0;
				// Vérification et calcul du total
				foreach ($items as $it) {
					$productId = (int)($it['product_id'] ?? 0);
					$qty = max(1, (int)($it['quantity'] ?? 0));
					$price = (float)($it['price'] ?? 0);
					if ($productId <= 0 || $qty <= 0 || $price < 0) {
						throw new RuntimeException('Données article invalides');
					}
					// Stock disponible
					$st = $pdo->prepare('SELECT quantite, COALESCE(prix_vente,0) AS prix_vente FROM stocks WHERE id = ? FOR UPDATE');
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
					$total += $price * $qty;
				}

				// Insérer la vente (sales)
				$insSale = $pdo->prepare('INSERT INTO sales (client_id, total) VALUES (:client_id, :total)');
				$insSale->bindValue(':client_id', $clientId, $clientId !== null ? PDO::PARAM_INT : PDO::PARAM_NULL);
				$insSale->bindValue(':total', $total);
				$insSale->execute();
				$saleId = (int)$pdo->lastInsertId();

				// Insérer les items + décrémenter le stock
				$insItem = $pdo->prepare('INSERT INTO sales_items (sale_id, product_id, quantity, price) VALUES (?, ?, ?, ?)');
				$decStock = $pdo->prepare('UPDATE stocks SET quantite = quantite - ? WHERE id = ?');
				foreach ($items as $it) {
					$productId = (int)$it['product_id'];
					$qty = max(1, (int)$it['quantity']);
					$price = (float)$it['price'];
					$insItem->execute([$saleId, $productId, $qty, $price]);
					$decStock->execute([$qty, $productId]);
					// Mouvement stock
					$mv = $pdo->prepare('INSERT INTO mouvements (article_id, action, details, utilisateur) VALUES (?, ?, ?, ?)');
					$mv->execute([$productId, 'modification', 'Vente POS (-'.$qty.')', (string)($_SESSION['username'] ?? 'system')]);
				}

				$pdo->commit();
				echo json_encode(['ok' => true, 'sale_id' => $saleId, 'total' => $total]);
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
			<input type="text" id="searchInput" class="pos-input" placeholder="Rechercher un article (nom, catégorie)">
		</div>
		<div id="productsList" class="pos-products"></div>
	</div>
	<div class="pos-right">
		<div class="pos-cart">
			<h3 class="pos-cart-title"><i class="fas fa-shopping-cart"></i> Panier</h3>
			<div id="cartItems" class="pos-cart-items"></div>
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
	products.forEach(p => {
		const item = document.createElement('div');
		item.className = 'pos-product';
		item.innerHTML = `
			<div class="pos-product-info">
				<div class="pos-product-name">${p.nom_article}</div>
				<div class="pos-product-meta">Catégorie: ${p.categorie || '-'} • Stock: ${p.quantite}</div>
			</div>
			<div class="pos-product-actions">
				<div class="pos-product-price">${formatPrice(p.prix_vente)}</div>
				<button class="btn btn-sm btn-primary" onclick="addToCart(${p.id}, '${p.nom_article.replace(/'/g, "\\'")}', ${p.prix_vente}, ${p.quantite})">Ajouter</button>
			</div>
		`;
		list.appendChild(item);
	});
}

function renderCart() {
	const list = document.getElementById('cartItems');
	list.innerHTML = '';
	let total = 0;
	cart.forEach((c, idx) => {
		const lineTotal = c.price * c.quantity;
		total += lineTotal;
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
	document.getElementById('cartTotal').textContent = formatPrice(total);
}

function addToCart(id, name, price, stock) {
	const existing = cart.find(c => c.product_id === id);
	if (existing) {
		if (existing.quantity < existing.max) { existing.quantity++; }
	} else {
		cart.push({ product_id: id, name, price: parseFloat(price || 0), quantity: 1, max: parseInt(stock || 0) });
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

async function checkout() {
	if (cart.length === 0) { showMsg('Panier vide', 'error'); return; }
	// Vérifier les quantités
	for (const it of cart) {
		if (it.quantity > it.max) { showMsg(`Stock insuffisant pour ${it.name}`, 'error'); return; }
	}
	const btn = document.getElementById('checkoutBtn');
	btn.disabled = true; btn.classList.add('loading');
	try {
		const res = await fetch('pos.php?ajax=checkout', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ items: cart }) });
		const data = await res.json();
		if (!data.ok) { throw new Error(data.error || 'Erreur inconnue'); }
		showMsg(`Vente validée. Ticket #${data.sale_id} - Total ${formatPrice(data.total)}`, 'success');
		cart = []; renderCart(); searchProducts();
	} catch (e) {
		showMsg(e.message || 'Erreur lors de la validation', 'error');
	} finally {
		btn.disabled = false; btn.classList.remove('loading');
	}
}

function showMsg(text, type) {
	const el = document.getElementById('checkoutMsg');
	el.textContent = text;
	el.className = 'pos-message ' + (type || 'info');
}

document.addEventListener('DOMContentLoaded', () => {
	document.getElementById('searchInput').addEventListener('input', () => { clearTimeout(window.__t); window.__t = setTimeout(searchProducts, 300); });
	document.getElementById('checkoutBtn').addEventListener('click', checkout);
	searchProducts();
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/layout/base.php';
?>


