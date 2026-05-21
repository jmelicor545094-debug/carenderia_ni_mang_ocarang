<?php
require_once '../includes/db.php';
session_start();

$order_id = $_GET['order_id'] ?? $_SESSION['last_order_id'] ?? '';
$order    = null;
$items    = [];

if ($order_id) {
    $db   = getDB();
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_id=?");
    $stmt->bind_param('s', $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order) {
        $stmt2 = $db->prepare("SELECT * FROM v_order_receipt WHERE order_id=?");
        $stmt2->bind_param('s', $order_id);
        $stmt2->execute();
        $res = $stmt2->get_result();
        while ($r = $res->fetch_assoc()) $items[] = $r;
        $stmt2->close();
    }
    $db->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Status — Ocarang</title>
<link rel="stylesheet" href="../css/style.css">
<?php if ($order && $order['status'] !== 'Completed'): ?>
<meta http-equiv="refresh" content="10"><!-- Auto-refresh every 10s -->
<?php endif; ?>
<style>
.status-page { max-width: 700px; margin: 0 auto; padding: 32px 24px; }
.big-status {
    text-align: center;
    padding: 40px;
    background: var(--warm-white);
    border-radius: var(--radius);
    box-shadow: var(--shadow-md);
    margin-bottom: 24px;
}
.status-emoji { font-size: 4rem; display: block; margin-bottom: 12px; }
.status-label { font-family: var(--font-display); font-size: 1.8rem; color: var(--brown-deep); }
.status-sublabel { color: var(--brown-light); margin-top: 8px; }
.pulse { animation: pulse 1.5s infinite; }
@keyframes pulse { 0%,100%{opacity:1;} 50%{opacity:0.5;} }
</style>
</head>
<body>

<div style="background:var(--brown-deep);padding:16px 32px;display:flex;align-items:center;gap:20px;">
    <a href="index.php" class="navbar-brand" style="font-family:var(--font-display);color:var(--gold);text-decoration:none;font-size:1.2rem;">🍛 Ocarang</a>
    <span style="color:#d4b898;">Order Status</span>
</div>

<div class="status-page">

    <?php if (!$order_id): ?>
        <div class="alert alert-info">Please enter your Order ID to track your order.</div>
        <div class="card">
            <div class="card-header"><h2>Track Your Order</h2></div>
            <div class="card-body">
                <form method="GET">
                    <div class="form-group">
                        <label class="form-label">Order ID</label>
                        <input type="text" name="order_id" class="form-control" placeholder="e.g. ORD1A2B3C4D" required>
                    </div>
                    <button type="submit" class="btn btn-primary">🔍 Track</button>
                </form>
            </div>
        </div>

    <?php elseif (!$order): ?>
        <div class="alert alert-error">⚠ Order <strong><?= htmlspecialchars($order_id) ?></strong> not found.</div>
        <a href="index.php" class="btn btn-primary">← Back to Menu</a>

    <?php else:
        $statusMap = [
            'Pending'   => ['emoji' => '⏳', 'label' => 'Order Received!', 'sub' => 'Your order is queued. Hang tight!', 'class' => 'badge-pending'],
            'Cooking'   => ['emoji' => '🍳', 'label' => 'We\'re Cooking!', 'sub' => 'Your food is being prepared fresh for you.', 'class' => 'badge-cooking'],
            'Ready'     => ['emoji' => '✅', 'label' => 'Your Order is Ready!', 'sub' => 'Please proceed to the counter to pick up and pay.', 'class' => 'badge-ready'],
            'Completed' => ['emoji' => '🎉', 'label' => 'Order Complete!', 'sub' => 'Thank you for dining with us! Enjoy your meal!', 'class' => 'badge-completed'],
        ];
        $s = $statusMap[$order['status']] ?? $statusMap['Pending'];
        $isPending  = $order['status'] === 'Pending';
        $isCooking  = $order['status'] === 'Cooking';
        $isReady    = $order['status'] === 'Ready';
        $isDone     = $order['status'] === 'Completed';
    ?>

    <div class="big-status">
        <span class="status-emoji <?= (!$isDone) ? 'pulse' : '' ?>"><?= $s['emoji'] ?></span>
        <div class="status-label"><?= $s['label'] ?></div>
        <p class="status-sublabel"><?= $s['sub'] ?></p>
        <?php if (!$isDone): ?><p style="font-size:0.78rem;color:var(--brown-light);margin-top:12px;">🔄 Page refreshes automatically every 10 seconds</p><?php endif; ?>
    </div>

    <!-- STATUS TRACKER -->
    <div class="status-tracker" style="margin-bottom:24px;">
        <div class="status-step <?= $isDone || $isReady || $isCooking ? 'done' : 'active' ?>">📋 Pending</div>
        <div class="status-step <?= $isDone || $isReady ? 'done' : ($isCooking ? 'active' : '') ?>">🍳 Cooking</div>
        <div class="status-step <?= $isDone ? 'done' : ($isReady ? 'active' : '') ?>">✅ Ready</div>
        <div class="status-step <?= $isDone ? 'active' : '' ?>">🎉 Complete</div>
    </div>

    <!-- RECEIPT -->
    <div class="receipt">
        <div class="receipt-header">
            <div style="font-size:1.3rem;font-weight:bold;">🍛 Ocarang Carenderia</div>
            <div style="font-size:0.85rem;margin-top:4px;">Order #<?= htmlspecialchars($order_id) ?></div>
        </div>
        <?php
        $grand = 0;
        foreach ($items as $it):
            $grand += $it['line_total'];
        ?>
        <div class="receipt-row">
            <span><?= htmlspecialchars($it['item_name']) ?> x<?= $it['quantity'] ?></span>
            <span>₱<?= number_format($it['line_total'], 2) ?></span>
        </div>
        <?php endforeach; ?>
        <div class="receipt-total">
            <div class="receipt-row">
                <span>TOTAL</span>
                <span style="color:var(--terra);">₱<?= number_format($grand, 2) ?></span>
            </div>
        </div>
    </div>

    <div style="text-align:center;margin-top:24px;display:flex;gap:12px;justify-content:center;">
        <a href="index.php" class="btn btn-outline">← New Order</a>
        <a href="track.php" class="btn btn-primary">🔍 Track Another</a>
    </div>

    <?php endif; ?>

</div>
</body>
</html>
