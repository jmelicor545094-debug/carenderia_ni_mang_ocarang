<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';

if (isAdminLoggedIn()) {
    header('Location: dashboard.php'); exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $admin_id = trim($_POST['admin_id'] ?? '');
    $name     = trim($_POST['name'] ?? '');

    if ($admin_id && $name) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM admin WHERE admin_id = ? AND name = ?");
        $stmt->bind_param('ss', $admin_id, $name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $_SESSION['admin_id']   = $row['admin_id'];
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['admin_role'] = $row['role'];
            header('Location: dashboard.php'); exit;
        } else {
            $error = 'Invalid Admin ID or Name. Please try again.';
        }
        $stmt->close(); $db->close();
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — Ocarang Carenderia</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="login-wrap">
    <div class="login-box">
        <div class="logo">🍛 Ocarang</div>
        <p class="subtitle">Carenderia Self-Ordering System</p>
        <p style="font-size:0.85rem;color:var(--brown-light);margin-bottom:24px;">Admin / Staff Login</p>

        <?php if ($error): ?>
            <div class="alert alert-error">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" style="text-align:left;">
            <div class="form-group">
                <label class="form-label">Admin ID</label>
                <input type="text" name="admin_id" class="form-control" placeholder="e.g. ADM001" required>
            </div>
            <div class="form-group">
                <label class="form-label">Full Name</label>
                <input type="text" name="name" class="form-control" placeholder="Your registered name" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:8px;">
                🔐 Login
            </button>
        </form>
        <hr style="margin:24px 0;border-color:rgba(196,149,106,0.3);">
        <a href="../customer/index.php" class="btn btn-outline" style="width:100%;justify-content:center;">
            🧾 Go to Customer Ordering
        </a>
        <p style="margin-top:16px;font-size:0.78rem;color:var(--brown-light);">Default: ADM001 / Admin</p>
    </div>
</div>
</body>
</html>
