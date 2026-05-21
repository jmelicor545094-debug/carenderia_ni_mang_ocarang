<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$db  = getDB();
$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action'] ?? '';
    $order_id = $_POST['order_id'] ?? '';

    if ($action === 'update_status') {
        $new_status = $_POST['new_status'];
        $stmt = $db->prepare("UPDATE orders SET status=?, admin_id=? WHERE order_id=?");
        $stmt->bind_param('sss', $new_status, $_SESSION['admin_id'], $order_id);
        if ($stmt->execute()) $msg = "Order $order_id status updated to $new_status";
        $stmt->close();

    } elseif ($action === 'process_payment') {
        // Insert payment and trigger will mark order as Completed
        $amount  = floatval($_POST['amount']);
        $pay_id  = 'PAY' . strtoupper(substr(uniqid(), -7));
        $today   = date('Y-m-d');
        $stmt    = $db->prepare("INSERT INTO payment (payment_id, amount, payment_date, order_id) VALUES (?,?,?,?)");
        $stmt->bind_param('sdss', $pay_id, $amount, $today, $order_id);
        if ($stmt->execute()) $msg = "Payment recorded for order $order_id. Order marked as Completed.";
        else $err = "Payment error: " . $stmt->error;
        $stmt->close();
    }
}

$filter = $_GET['filter'] ?? 'active';
$highlight = $_GET['highlight'] ?? '';

$where = $filter === 'active'
    ? "WHERE o.status != 'Completed'"
    : ($filter === 'completed' ? "WHERE o.status = 'Completed'" : '');

$orders = $db->query("
    SELECT o.order_id, o.status, o.admin_id,
           COUNT(oi.order_item_id) as item_count,
           SUM(oi.quantity * oi.unit_price) as total
    FROM orders o
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    $where
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
");
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders — Ocarang Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.filter-tabs { display:flex; gap:8px; margin-bottom:20px; }
.filter-tab {
    padding:8px 20px;
    border-radius:99px;
    text-decoration:none;
    font-weight:700;
    font-size:0.85rem;
    background:var(--warm-white);
    color:var(--brown-mid);
    border:2px solid rgba(196,149,106,0.3);
    transition:all 0.2s;
}
.filter-tab.active, .filter-tab:hover { background:var(--terra); color:#fff; border-color:var(--terra); }
.order-detail-panel {
    background:var(--warm-white);
    border-radius:var(--radius);
    padding:24px;
    box-shadow:var(--shadow-md);
    border-left:4px solid var(--terra);
    margin-bottom:20px;
}
.highlight-row { background:#FFF5F0 !important; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">🍛 Ocarang <span>Admin</span></a>
    <div class="navbar-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="menu.php">Menu</a>
        <a href="orders.php" class="active">Orders</a>
        <a href="revenue.php">Revenue</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1>Order Management</h1>
    <p>Track and process customer orders</p>
</div>

<div class="main-content">
    <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="filter-tabs">
        <a href="?filter=active" class="filter-tab <?= $filter === 'active' ? 'active' : '' ?>">⏳ Active Orders</a>
        <a href="?filter=completed" class="filter-tab <?= $filter === 'completed' ? 'active' : '' ?>">✅ Completed</a>
        <a href="?filter=all" class="filter-tab <?= $filter === 'all' ? 'active' : '' ?>">📋 All Orders</a>
    </div>

    <div class="card">
        <div class="card-header"><h2>Orders</h2></div>
        <div class="card-body" style="padding:0;">
            <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>Status</th>
                        <th>Items</th>
                        <th>Total</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($o = $orders->fetch_assoc()): ?>
                <tr class="<?= $highlight === $o['order_id'] ? 'highlight-row' : '' ?>">
                    <td>
                        <strong><?= htmlspecialchars($o['order_id']) ?></strong>
                        <br><small><a href="?view=<?= urlencode($o['order_id']) ?>&filter=<?= $filter ?>" style="color:var(--terra);">View Details</a></small>
                    </td>
                    <td><span class="badge badge-<?= strtolower($o['status']) ?>"><?= $o['status'] ?></span></td>
                    <td><?= $o['item_count'] ?></td>
                    <td><strong>₱<?= number_format($o['total'] ?? 0, 2) ?></strong></td>
                    <td>
                        <?php if ($o['status'] !== 'Completed'): ?>
                        <div style="display:flex;gap:6px;flex-wrap:wrap;">
                            <?php if ($o['status'] === 'Pending'): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                <input type="hidden" name="new_status" value="Cooking">
                                <button class="btn btn-sm btn-primary">🍳 Start Cooking</button>
                            </form>
                            <?php elseif ($o['status'] === 'Cooking'): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                <input type="hidden" name="new_status" value="Ready">
                                <button class="btn btn-sm btn-success">✅ Mark Ready</button>
                            </form>
                            <?php elseif ($o['status'] === 'Ready'): ?>
                            <button class="btn btn-sm btn-gold" onclick="openPayment('<?= $o['order_id'] ?>', <?= $o['total'] ?? 0 ?>)">
                                💳 Collect Payment
                            </button>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <span style="color:var(--green-fresh);font-weight:700;">Paid ✅</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

    <?php
    // Show order detail if requested
    if (isset($_GET['view'])):
        $db2 = getDB();
        $view_id = $_GET['view'];
        $stmt = $db2->prepare("SELECT * FROM v_order_receipt WHERE order_id=?");
        $stmt->bind_param('s', $view_id);
        $stmt->execute();
        $receipt = $stmt->get_result();
        $rows = [];
        while ($r = $receipt->fetch_assoc()) $rows[] = $r;
        $stmt->close();
        $db2->close();
        if ($rows):
    ?>
    <div class="order-detail-panel" style="margin-top:24px;">
        <h2 style="font-family:var(--font-display);margin-bottom:16px;">📋 Order Receipt — <?= htmlspecialchars($view_id) ?></h2>
        <p style="margin-bottom:16px;">Status: <span class="badge badge-<?= strtolower($rows[0]['order_status']) ?>"><?= $rows[0]['order_status'] ?></span></p>
        <table style="max-width:500px;">
            <thead><tr><th>Item</th><th>Qty</th><th>Unit Price</th><th>Subtotal</th></tr></thead>
            <tbody>
            <?php
            $grand = 0;
            foreach ($rows as $r):
                $grand += $r['line_total'];
            ?>
            <tr>
                <td><?= htmlspecialchars($r['item_name']) ?></td>
                <td><?= $r['quantity'] ?></td>
                <td>₱<?= number_format($r['unit_price'], 2) ?></td>
                <td>₱<?= number_format($r['line_total'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="background:var(--cream);">
                <td colspan="3"><strong>TOTAL</strong></td>
                <td><strong>₱<?= number_format($grand, 2) ?></strong></td>
            </tr>
            </tbody>
        </table>
    </div>
    <?php endif; endif; ?>

</div>

<!-- PAYMENT MODAL -->
<div class="modal-overlay" id="paymentModal">
    <div class="modal">
        <h2>💳 Collect Payment</h2>
        <form method="POST">
            <input type="hidden" name="action" value="process_payment">
            <input type="hidden" name="order_id" id="pay_order_id">
            <div class="form-group">
                <label class="form-label">Order Total (₱)</label>
                <input type="number" name="amount" id="pay_amount" step="0.01" class="form-control" required readonly style="background:#f5ede3;">
            </div>
            <div class="form-group">
                <label class="form-label">Amount Tendered (₱)</label>
                <input type="number" id="pay_tendered" step="0.01" class="form-control" oninput="calcChange()" placeholder="0.00">
            </div>
            <div class="form-group">
                <label class="form-label">Change</label>
                <input type="text" id="pay_change" class="form-control" readonly style="background:#f5ede3;font-weight:700;color:var(--green-fresh);">
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-success">✅ Confirm Payment</button>
                <button type="button" class="btn btn-outline" onclick="closePayment()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayment(orderId, total) {
    document.getElementById('pay_order_id').value = orderId;
    document.getElementById('pay_amount').value   = parseFloat(total).toFixed(2);
    document.getElementById('pay_tendered').value = '';
    document.getElementById('pay_change').value   = '';
    document.getElementById('paymentModal').classList.add('active');
}
function closePayment() {
    document.getElementById('paymentModal').classList.remove('active');
}
function calcChange() {
    const total    = parseFloat(document.getElementById('pay_amount').value) || 0;
    const tendered = parseFloat(document.getElementById('pay_tendered').value) || 0;
    const change   = tendered - total;
    document.getElementById('pay_change').value = change >= 0 ? '₱ ' + change.toFixed(2) : 'Insufficient';
}
document.getElementById('paymentModal').addEventListener('click', function(e) {
    if (e.target === this) closePayment();
});
</script>
</body>
</html>
