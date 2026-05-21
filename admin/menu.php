<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
requireAdmin();

$db  = getDB();
$msg = '';
$err = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $id     = 'MNU' . strtoupper(substr(uniqid(), -6));
        $name   = trim($_POST['name']);
        $price  = floatval($_POST['price']);
        $status = $_POST['status'];
        $stmt   = $db->prepare("INSERT INTO menu_item (menu_id, name, current_price, status, admin_id) VALUES (?,?,?,?,?)");
        $stmt->bind_param('ssdss', $id, $name, $price, $status, $_SESSION['admin_id']);
        if ($stmt->execute()) $msg = "Menu item '$name' added successfully!";
        else $err = "Error: " . $stmt->error;
        $stmt->close();

    } elseif ($action === 'edit') {
        $id     = $_POST['menu_id'];
        $name   = trim($_POST['name']);
        $price  = floatval($_POST['price']);
        $status = $_POST['status'];
        $stmt   = $db->prepare("UPDATE menu_item SET name=?, current_price=?, status=? WHERE menu_id=?");
        $stmt->bind_param('sdss', $name, $price, $status, $id);
        if ($stmt->execute()) $msg = "Menu item updated successfully!";
        else $err = "Error: " . $stmt->error;
        $stmt->close();

    } elseif ($action === 'delete') {
        $id   = $_POST['menu_id'];
        $stmt = $db->prepare("DELETE FROM menu_item WHERE menu_id=?");
        $stmt->bind_param('s', $id);
        if ($stmt->execute()) $msg = "Menu item deleted.";
        else $err = "Cannot delete — item may be linked to existing orders.";
        $stmt->close();

    } elseif ($action === 'toggle') {
        $id        = $_POST['menu_id'];
        $newStatus = $_POST['new_status'];
        $stmt      = $db->prepare("UPDATE menu_item SET status=? WHERE menu_id=?");
        $stmt->bind_param('ss', $newStatus, $id);
        if ($stmt->execute()) $msg = "Status updated.";
        $stmt->close();
    }
}

$items = $db->query("SELECT * FROM menu_item ORDER BY name ASC");
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Menu Management — Ocarang Admin</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<nav class="navbar">
    <a href="dashboard.php" class="navbar-brand">🍛 Ocarang <span>Admin</span></a>
    <div class="navbar-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="menu.php" class="active">Menu</a>
        <a href="orders.php">Orders</a>
        <a href="revenue.php">Revenue</a>
        <a href="logout.php" class="btn-logout">Logout</a>
    </div>
</nav>

<div class="page-header">
    <h1>Menu Management</h1>
    <p>Add, edit, and manage available dishes</p>
</div>

<div class="main-content">
    <?php if ($msg): ?><div class="alert alert-success">✅ <?= htmlspecialchars($msg) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="alert alert-error">⚠ <?= htmlspecialchars($err) ?></div><?php endif; ?>

    <div class="grid-2" style="align-items:start;">

        <!-- ADD FORM -->
        <div class="card">
            <div class="card-header"><h2>Add New Item</h2></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="form-group">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Adobo" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Price (₱)</label>
                        <input type="number" name="price" step="0.01" min="0" class="form-control" placeholder="0.00" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-control">
                            <option value="Available">Available</option>
                            <option value="Unavailable">Unavailable</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">➕ Add Item</button>
                </form>
            </div>
        </div>

        <!-- MENU LIST -->
        <div class="card">
            <div class="card-header"><h2>All Menu Items</h2></div>
            <div class="card-body" style="padding:0;">
                <div class="table-wrap">
                <table>
                    <thead>
                        <tr><th>Name</th><th>Price</th><th>Status</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($item = $items->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($item['name']) ?></strong><br><small style="color:var(--brown-light);"><?= $item['menu_id'] ?></small></td>
                        <td>₱<?= number_format($item['current_price'], 2) ?></td>
                        <td>
                            <span class="badge badge-<?= strtolower($item['status']) ?>">
                                <?= $item['status'] ?>
                            </span>
                        </td>
                        <td style="display:flex;gap:6px;flex-wrap:wrap;">
                            <button class="btn btn-sm btn-gold" onclick="openEdit(<?= htmlspecialchars(json_encode($item)) ?>)">✏ Edit</button>
                            <!-- Toggle status -->
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="menu_id" value="<?= $item['menu_id'] ?>">
                                <input type="hidden" name="new_status" value="<?= $item['status'] === 'Available' ? 'Unavailable' : 'Available' ?>">
                                <button type="submit" class="btn btn-sm <?= $item['status'] === 'Available' ? 'btn-outline' : 'btn-success' ?>">
                                    <?= $item['status'] === 'Available' ? '🚫 Disable' : '✅ Enable' ?>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this item?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="menu_id" value="<?= $item['menu_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">🗑</button>
                            </form>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <h2>✏ Edit Menu Item</h2>
        <form method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="menu_id" id="edit_menu_id">
            <div class="form-group">
                <label class="form-label">Item Name</label>
                <input type="text" name="name" id="edit_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Price (₱)</label>
                <input type="number" name="price" id="edit_price" step="0.01" min="0" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">Status</label>
                <select name="status" id="edit_status" class="form-control">
                    <option value="Available">Available</option>
                    <option value="Unavailable">Unavailable</option>
                </select>
            </div>
            <div style="display:flex;gap:10px;">
                <button type="submit" class="btn btn-primary">💾 Save Changes</button>
                <button type="button" class="btn btn-outline" onclick="closeEdit()">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function openEdit(item) {
    document.getElementById('edit_menu_id').value = item.menu_id;
    document.getElementById('edit_name').value     = item.name;
    document.getElementById('edit_price').value    = item.current_price;
    document.getElementById('edit_status').value   = item.status;
    document.getElementById('editModal').classList.add('active');
}
function closeEdit() {
    document.getElementById('editModal').classList.remove('active');
}
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEdit();
});
</script>
</body>
</html>
