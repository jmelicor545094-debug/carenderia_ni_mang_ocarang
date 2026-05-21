<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$db = getDB();

// Stats
$totalOrders   = $db->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$pendingOrders = $db->query("SELECT COUNT(*) as c FROM orders WHERE status='Pending'")->fetch_assoc()['c'];
$cookingOrders = $db->query("SELECT COUNT(*) as c FROM orders WHERE status='Cooking'")->fetch_assoc()['c'];
$todayRevenue  = $db->query("SELECT SUM(amount) as r FROM payment WHERE payment_date=CURDATE()")->fetch_assoc()['r'] ?? 0;
$menuCount     = $db->query("SELECT COUNT(*) as c FROM menu_item")->fetch_assoc()['c'];

// Active orders (not completed)
$activeOrders = $db->query("
    SELECT o.order_id, o.status, o.admin_id,
           COUNT(oi.order_item_id) as items,
           SUM(oi.quantity * oi.unit_price) as total
    FROM orders o
    LEFT JOIN order_item oi ON o.order_id = oi.order_id
    WHERE o.status != 'Completed'
    GROUP BY o.order_id
    ORDER BY o.order_id DESC
    LIMIT 20
");
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Ocarang Admin</title>
<link rel="stylesheet" href="../css/style.css">
<style>
.quick-actions { display:flex; gap:12px; flex-wrap:wrap; margin-bottom:28px; }
.action-tile {
    background:var(--warm-white);
    border-radius:var(--radius);
    padding:20px 28px;
    box-shadow:var(--shadow-sm);
    text-align:center;
    text-decoration:none;
    color:var(--brown-deep);
    border:2px solid transparent;
    transition:all 0.2s;
    flex:1; min-width:140px;
}
.action-tile:hover { border-color:var(--terra); transform:translateY(-2px); }
.action-tile .tile-icon { font-size:2rem; display:block; margin-bottom:8px; }
.action-tile .tile-label { font-weight:700; font-size:0.9rem; }
</style>
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">🍛 Ocarang <span>Admin</span></a>
    <div class="navbar-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="menu.php">Menu</a>
        <a href="orders.php">Orders</a>
        <a href="revenue.php">Revenue</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1>Dashboard</h1>
    <p>Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?> (<?= htmlspecialchars($_SESSION['admin_role']) ?>)</p>
</div>

<div class="main-content">

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">📋</div>
            <div class="stat-label">Total Orders</div>
            <div class="stat-value"><?= $totalOrders ?></div>
        </div>
        <div class="stat-card" style="border-left-color:#F59E0B;">
            <div class="stat-icon">⏳</div>
            <div class="stat-label">Pending</div>
            <div class="stat-value"><?= $pendingOrders ?></div>
        </div>
        <div class="stat-card" style="border-left-color:var(--terra-light);">
            <div class="stat-icon">🍳</div>
            <div class="stat-label">Cooking</div>
            <div class="stat-value"><?= $cookingOrders ?></div>
        </div>
        <div class="stat-card gold">
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-value">₱<?= number_format($todayRevenue, 2) ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">🍽</div>
            <div class="stat-label">Menu Items</div>
            <div class="stat-value"><?= $menuCount ?></div>
        </div>
    </div>

    <div class="quick-actions">
        <a href="menu.php" class="action-tile"><span class="tile-icon">🍽</span><span class="tile-label">Manage Menu</span></a>
        <a href="orders.php" class="action-tile"><span class="tile-icon">📋</span><span class="tile-label">View Orders</span></a>
        <a href="revenue.php" class="action-tile"><span class="tile-icon">📊</span><span class="tile-label">Daily Revenue</span></a>
        <a href="../customer/index.php" target="_blank" class="action-tile"><span class="tile-icon">🧾</span><span class="tile-label">Customer View</span></a>
    </div>

    <div class="card">
        <div class="card-header">
            <h2>Active Orders</h2>
            <a href="orders.php" class="btn btn-outline btn-sm">View All</a>
        </div>
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
                <?php while ($o = $activeOrders->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($o['order_id']) ?></strong></td>
                    <td>
                        <span class="badge badge-<?= strtolower($o['status']) ?>">
                            <?= htmlspecialchars($o['status']) ?>
                        </span>
                    </td>
                    <td><?= $o['items'] ?> items</td>
                    <td>₱<?= number_format($o['total'] ?? 0, 2) ?></td>
                    <td>
                        <a href="orders.php?highlight=<?= urlencode($o['order_id']) ?>" class="btn btn-sm btn-primary">Manage</a>
                    </td>
                </tr>
                <?php endwhile; ?>
                <?php if ($activeOrders->num_rows === 0): ?>
                <tr><td colspan="5" style="text-align:center;color:var(--brown-light);padding:32px;">No active orders at the moment 🎉</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
            </div>
        </div>
    </div>

</div>
</body>
</html>
