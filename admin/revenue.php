<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$db = getDB();

$daily   = $db->query("SELECT * FROM v_daily_revenue ORDER BY payment_date DESC LIMIT 30");
$weekly  = $db->query("SELECT SUM(amount) as r FROM payment WHERE payment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)")->fetch_assoc()['r'] ?? 0;
$monthly = $db->query("SELECT SUM(amount) as r FROM payment WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE())")->fetch_assoc()['r'] ?? 0;
$today   = $db->query("SELECT SUM(amount) as r FROM payment WHERE payment_date=CURDATE()")->fetch_assoc()['r'] ?? 0;

$topItems = $db->query("
    SELECT mi.name, SUM(oi.quantity) as sold, SUM(oi.quantity * oi.unit_price) as revenue
    FROM order_item oi
    JOIN menu_item mi ON oi.menu_id = mi.menu_id
    GROUP BY oi.menu_id
    ORDER BY sold DESC
    LIMIT 8
");
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Revenue — Ocarang Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">🍛 Ocarang <span>Admin</span></a>
    <div class="navbar-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="menu.php">Menu</a>
        <a href="orders.php">Orders</a>
        <a href="revenue.php" class="active">Revenue</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1>Revenue Reports</h1>
</div>
<div class="main-content">

    <div class="stats-grid">
        <div class="stat-card gold">
            <div class="stat-icon">☀</div>
            <div class="stat-label">Today's Revenue</div>
            <div class="stat-value">₱<?= number_format($today, 2) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon">📅</div>
            <div class="stat-label">This Week</div>
            <div class="stat-value">₱<?= number_format($weekly, 2) ?></div>
        </div>
        <div class="stat-card green">
            <div class="stat-icon">📆</div>
            <div class="stat-label">This Month</div>
            <div class="stat-value">₱<?= number_format($monthly, 2) ?></div>
        </div>
    </div>

    <div class="grid-2" style="align-items:start;">

        <div class="card">
            <div class="card-header"><h2>📊 Daily Revenue (Last 30 Days)</h2></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                <table>
                    <thead><tr><th>Date</th><th>Transactions</th><th>Total Revenue</th></tr></thead>
                    <tbody>
                    <?php while ($row = $daily->fetch_assoc()): ?>
                    <tr>
                        <td><?= date('M d, Y', strtotime($row['payment_date'])) ?></td>
                        <td><?= $row['total_transactions'] ?></td>
                        <td><strong>₱<?= number_format($row['total_revenue'], 2) ?></strong></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($daily->num_rows === 0): ?>
                    <tr><td colspan="3" style="text-align:center;padding:32px;color:var(--brown-light);">No payment records yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h2>🏆 Top Selling Items</h2></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                <table>
                    <thead><tr><th>Item</th><th>Qty Sold</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php while ($item = $topItems->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['name']) ?></strong></td>
                        <td><?= $item['sold'] ?></td>
                        <td>₱<?= number_format($item['revenue'], 2) ?></td>
                    </tr>
                    <?php endwhile; ?>
                    <?php if ($topItems->num_rows === 0): ?>
                    <tr><td colspan="3" style="text-align:center;padding:32px;color:var(--brown-light);">No sales data yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>
</div>
</body>
</html>
