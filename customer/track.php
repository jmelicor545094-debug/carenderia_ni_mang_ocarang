<?php
require_once '../includes/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Track Order — Ocarang</title>
<link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div style="background:var(--brown-deep);padding:16px 32px;display:flex;align-items:center;gap:20px;">
    <a href="index.php" style="font-family:var(--font-display);color:var(--gold);text-decoration:none;font-size:1.2rem;">🍛 Ocarang</a>
    <span style="color:#d4b898;">Track Order</span>
</div>

<div style="max-width:500px;margin:60px auto;padding:24px;">
    <div class="card">
        <div class="card-header"><h2>🔍 Track Your Order</h2></div>
        <div class="card-body">
            <p style="color:var(--brown-light);margin-bottom:20px;">Enter your Order ID to check the current status of your order.</p>
            <form method="GET" action="status.php">
                <div class="form-group">
                    <label class="form-label">Order ID</label>
                    <input type="text" name="order_id" class="form-control" placeholder="e.g. ORD1A2B3C4D" required autofocus>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;">🔍 Track Order</button>
            </form>
            <div style="text-align:center;margin-top:20px;">
                <a href="index.php" class="btn btn-outline" style="width:100%;justify-content:center;">← Back to Menu</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
