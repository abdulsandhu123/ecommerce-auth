<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_admin();

$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
$totalOrders = (int) $pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$pendingOrders = (int) $pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn();
$totalUsers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalReviews = (int) $pdo->query('SELECT COUNT(*) FROM reviews')->fetchColumn();
$revenue = (float) $pdo->query("SELECT COALESCE(SUM(total_amount),0) FROM orders WHERE status != 'cancelled'")->fetchColumn();

$recentOrders = $pdo->query(
    'SELECT o.id, o.status, o.total_amount, o.created_at, u.full_name
     FROM orders o JOIN users u ON u.id = o.user_id
     ORDER BY o.created_at DESC LIMIT 8'
)->fetchAll();

$activeNav = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — Storefront</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h1>Dashboard</h1>
      <div class="admin-user">Signed in as <strong><?= e($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
    </div>

    <div class="admin-content">
      <div class="stat-grid">
        <div class="stat-card">
          <div class="stat-label">Total Products</div>
          <div class="stat-value"><?= $totalProducts ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Total Orders</div>
          <div class="stat-value"><?= $totalOrders ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Pending Orders</div>
          <div class="stat-value"><?= $pendingOrders ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Customers</div>
          <div class="stat-value"><?= $totalUsers ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Reviews</div>
          <div class="stat-value"><?= $totalReviews ?></div>
        </div>
        <div class="stat-card">
          <div class="stat-label">Revenue</div>
          <div class="stat-value"><?= money($revenue) ?></div>
        </div>
      </div>

      <div class="section-head">
        <h2>Recent Orders</h2>
        <a href="orders.php" class="btn btn-secondary btn-sm">View all</a>
      </div>

      <?php if (empty($recentOrders)): ?>
        <div class="empty-state">No orders have been placed yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Status</th>
                <th>Placed On</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($recentOrders as $o): ?>
                <tr>
                  <td>#<?= (int) $o['id'] ?></td>
                  <td><?= e($o['full_name']) ?></td>
                  <td><?= money((float) $o['total_amount']) ?></td>
                  <td><span class="badge badge-<?= e($o['status']) ?>"><?= e($o['status']) ?></span></td>
                  <td class="cell-muted"><?= e(date('M j, Y g:i A', strtotime($o['created_at']))) ?></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

</body>
</html>
