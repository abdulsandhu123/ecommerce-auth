<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';

require_login();
if (is_admin()) {
    header('Location: admin/dashboard.php');
    exit;
}

$ordersStmt = $pdo->prepare(
    'SELECT id, status, total_amount, created_at FROM orders WHERE user_id = ? ORDER BY created_at DESC'
);
$ordersStmt->execute([current_user_id()]);
$orders = $ordersStmt->fetchAll();

$itemsByOrder = [];
if (!empty($orders)) {
    $ids = array_column($orders, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $itemsStmt = $pdo->prepare(
        "SELECT order_id, product_name, quantity, unit_price FROM order_items WHERE order_id IN ($placeholders)"
    );
    $itemsStmt->execute($ids);
    foreach ($itemsStmt->fetchAll() as $row) {
        $itemsByOrder[$row['order_id']][] = $row;
    }
}

$banner = '';
$bannerType = '';
if (isset($_GET['placed'])) {
    $banner = 'Your order was placed successfully!';
    $bannerType = 'success';
}

$activeNav = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Orders — Storefront</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/includes/nav_user.php'; ?>

<div class="shop-main">
  <h1>My Orders</h1>
  <p class="page-subtitle">Track the status of everything you've ordered.</p>

  <?php if ($banner): ?>
    <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
  <?php endif; ?>

  <?php if (empty($orders)): ?>
    <div class="empty-state">You haven't placed any orders yet. <a href="home.php" style="color:var(--primary);font-weight:600;text-decoration:none;">Browse products</a>.</div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th>Order #</th>
            <th>Items</th>
            <th>Total</th>
            <th>Status</th>
            <th>Placed On</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= (int) $o['id'] ?></td>
              <td>
                <?php foreach (($itemsByOrder[$o['id']] ?? []) as $it): ?>
                  <div><?= e($it['product_name']) ?> <span class="cell-muted">× <?= (int) $it['quantity'] ?></span></div>
                <?php endforeach; ?>
              </td>
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

</body>
</html>
