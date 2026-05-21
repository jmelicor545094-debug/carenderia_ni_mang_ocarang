<?php
require_once '../includes/db.php';
session_start();

$db   = getDB();
$msg  = '';
$err  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    $items = json_decode($_POST['cart_items'], true);
    if ($items && count($items) > 0) {
        $order_id = 'ORD' . strtoupper(substr(uniqid(), -8));

        $stmt = $db->prepare("INSERT INTO orders (order_id, status) VALUES (?, 'Pending')");
        $stmt->bind_param('s', $order_id);
        if ($stmt->execute()) {
            $stmt->close();

            $ok = true;
            foreach ($items as $item) {
                $stmt2 = $db->prepare("INSERT INTO order_item (order_id, menu_id, quantity) VALUES (?,?,?)");
                $stmt2->bind_param('ssi', $order_id, $item['menu_id'], $item['qty']);
                if (!$stmt2->execute()) {
                    $err = "Could not add item: " . $stmt2->error;
                    $ok  = false;
                    $stmt2->close();

                    $db->query("DELETE FROM orders WHERE order_id='$order_id'");
                    break;
                }
                $stmt2->close();
            }
            if ($ok) {
                $_SESSION['last_order_id'] = $order_id;
                header("Location: status.php?order_id=" . urlencode($order_id));
                exit;
            }
        } else {
            $err = "Could not create order. Please try again.";
        }
    } else {
        $err = "Your cart is empty!";
    }
}

$menu = $db->query("SELECT * FROM v_customer_menu ORDER BY item_name ASC");
$db->close();

function getPlateIcon() {
    return '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" width="56" height="56">
        <!-- Plate -->
        <ellipse cx="32" cy="38" rx="22" ry="6" fill="#e8d5b7" stroke="#c4956a" stroke-width="1.5"/>
        <ellipse cx="32" cy="36" rx="22" ry="6" fill="#f5e6cc" stroke="#c4956a" stroke-width="1.5"/>
        <!-- Inner plate ring -->
        <ellipse cx="32" cy="35.5" rx="16" ry="4.5" fill="none" stroke="#c4956a" stroke-width="1" stroke-dasharray="2,2"/>
        <!-- Fork -->
        <line x1="16" y1="14" x2="16" y2="30" stroke="#7A4423" stroke-width="2" stroke-linecap="round"/>
        <line x1="14" y1="14" x2="14" y2="20" stroke="#7A4423" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="16" y1="14" x2="16" y2="20" stroke="#7A4423" stroke-width="1.5" stroke-linecap="round"/>
        <line x1="18" y1="14" x2="18" y2="20" stroke="#7A4423" stroke-width="1.5" stroke-linecap="round"/>
        <!-- Knife -->
        <line x1="48" y1="14" x2="48" y2="30" stroke="#7A4423" stroke-width="2" stroke-linecap="round"/>
        <path d="M48 14 Q52 18 48 22" fill="#c4956a" stroke="#7A4423" stroke-width="1"/>
    </svg>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ocarang Carenderia — Order Now</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.customer-layout {
    display: grid;
    grid-template-columns: 1fr 340px;
    gap: 28px;
    align-items: start;
    max-width: 1200px;
    margin: 0 auto;
    padding: 28px 24px;
}
@media (max-width: 768px) {
    .customer-layout { grid-template-columns: 1fr; }
    .order-sidebar { position: static; }
}
.section-title {
    font-family: var(--font-display);
    font-size: 1.4rem;
    color: var(--brown-deep);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.customer-hero {
    background: linear-gradient(135deg, var(--brown-deep) 0%, #6B3010 100%);
    padding: 28px 32px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.customer-hero-text h1 {
    font-family: var(--font-display);
    color: var(--gold);
    font-size: 1.8rem;
}
.customer-hero-text p { color: #d4b898; margin-top: 4px; }
.place-order-btn {
    padding: 16px 24px;
    background: var(--terra);
    color: #fff;
    border: none;
    border-radius: var(--radius);
    font-family: var(--font-body);
    font-weight: 700;
    font-size: 1rem;
    cursor: pointer;
    width: 100%;
    transition: all 0.2s;
    margin-top: 16px;
}
.place-order-btn:hover { background: var(--terra-dark); transform: translateY(-1px); }
.place-order-btn:disabled { background: #ccc; cursor: not-allowed; transform: none; }
</style>
</head>
<body>

<div class="customer-hero">
    <div class="customer-hero-text">
        <h1>🍛 Ocarang Carenderia</h1>
        <p>Self-Ordering System — Pick your dishes and place your order!</p>
    </div>
    <div>
        <a href="track.php" class="btn btn-gold">Track Order</a>
        <a href="../admin/login.php" class="btn btn-outline" style="border-color:rgba(255,255,255,0.3);color:#fff;margin-left:8px;">Admin</a>
    </div>
</div>

<div class="customer-layout">

    <!-- MENU SECTION -->
    <div>
        <?php if ($err): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($err) ?></div>
        <?php endif; ?>

        <div class="section-title">🍽 Today's Menu <span style="font-family:var(--font-body);font-size:0.9rem;color:var(--brown-light);font-weight:400;">Click to add to order</span></div>
        <div class="menu-grid" id="menuGrid">
            <?php while ($item = $menu->fetch_assoc()): ?>
            <div class="menu-card"
                 id="card_<?= $item['menu_id'] ?>"
                 onclick="addItem('<?= $item['menu_id'] ?>', <?= htmlspecialchars(json_encode($item['item_name'])) ?>, <?= $item['price'] ?>)"
            >
                <div class="menu-icon"><?= getPlateIcon() ?></div>
                <div class="menu-name"><?= htmlspecialchars($item['item_name']) ?></div>
                <div class="menu-price">₱<?= number_format($item['price'], 2) ?></div>
                <div class="qty-badge" id="badge_<?= $item['menu_id'] ?>">0</div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- ORDER SIDEBAR -->
    <div class="order-sidebar">
        <h2>🧾 Your Order</h2>
        <div id="cartItems">
            <div class="empty-cart">
                <span class="icon">🛒</span>
                No items yet.<br>Click a dish to add it!
            </div>
        </div>
        <div class="order-total" id="orderTotal" style="display:none;">
            <div class="total-label">Total Amount</div>
            <div class="total-value" id="totalValue">₱0.00</div>
        </div>
        <form method="POST" id="orderForm">
            <input type="hidden" name="place_order" value="1">
            <input type="hidden" name="cart_items" id="cartPayload">
            <button type="submit" class="place-order-btn" id="placeBtn" disabled onclick="submitOrder(event)">
                🍽 Place Order
            </button>
        </form>
        <p style="font-size:0.78rem;color:var(--brown-light);margin-top:10px;text-align:center;">Your order will be sent to the kitchen</p>
    </div>

</div>

<script>
const cart = {}; // { menu_id: { name, price, qty } }

function addItem(menuId, name, price) {
    if (!cart[menuId]) {
        cart[menuId] = { menu_id: menuId, name, price, qty: 0 };
    }
    cart[menuId].qty++;
    renderCart();
}

function changeQty(menuId, delta) {
    if (!cart[menuId]) return;
    cart[menuId].qty += delta;
    if (cart[menuId].qty <= 0) {
        delete cart[menuId];
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const totalDiv  = document.getElementById('orderTotal');
    const totalVal  = document.getElementById('totalValue');
    const placeBtn  = document.getElementById('placeBtn');
    const keys      = Object.keys(cart);

    // Update menu card badges
    document.querySelectorAll('.menu-card').forEach(card => {
        card.classList.remove('selected');
        const badge = card.querySelector('.qty-badge');
        if (badge) badge.style.display = 'none';
    });
    keys.forEach(id => {
        const card  = document.getElementById('card_' + id);
        const badge = document.getElementById('badge_' + id);
        if (card && cart[id]) {
            card.classList.add('selected');
            badge.textContent = cart[id].qty;
            badge.style.display = 'flex';
        }
    });

    if (keys.length === 0) {
        container.innerHTML = `<div class="empty-cart"><span class="icon">🛒</span>No items yet.<br>Click a dish to add it!</div>`;
        totalDiv.style.display = 'none';
        placeBtn.disabled = true;
        return;
    }

    let total = 0;
    let html  = '';
    keys.forEach(id => {
        const it      = cart[id];
        const subtotal = it.price * it.qty;
        total         += subtotal;
        html += `
        <div class="order-item-row">
            <div class="item-info">
                <div class="item-name">${it.name}</div>
                <div class="item-subtotal">₱${it.price.toFixed(2)} each · ₱${subtotal.toFixed(2)}</div>
            </div>
            <div class="qty-controls">
                <button class="qty-btn" onclick="changeQty('${id}', -1)">−</button>
                <span class="qty-display">${it.qty}</span>
                <button class="qty-btn" onclick="changeQty('${id}', 1)">+</button>
            </div>
        </div>`;
    });

    container.innerHTML = html;
    totalDiv.style.display = 'block';
    totalVal.textContent   = '₱' + total.toFixed(2);
    placeBtn.disabled      = false;
}

function submitOrder(e) {
    const items = Object.values(cart).map(it => ({ menu_id: it.menu_id, qty: it.qty }));
    if (items.length === 0) { e.preventDefault(); return; }
    document.getElementById('cartPayload').value = JSON.stringify(items);
}
</script>
</body>
</html>
