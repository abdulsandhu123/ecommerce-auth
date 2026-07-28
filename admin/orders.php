<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_admin();

$banner = '';
$bannerType = '';
$statuses = ['pending', 'received', 'shipped', 'completed', 'cancelled'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $banner = 'Your session expired, please try again.';
        $bannerType = 'error';
    } else {
        $orderId = (int) ($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if (in_array($status, $statuses, true)) {
            $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?')->execute([$status, $orderId]);
            header('Location: orders.php?updated=1');
            exit;
        }
    }
}

if (isset($_GET['updated'])) { $banner = 'Order status updated.'; $bannerType = 'success'; }

$filter = $_GET['status'] ?? '';
if (!in_array($filter, $statuses, true)) {
    $filter = '';
}

$sql = 'SELECT o.id, o.status, o.total_amount, o.created_at, u.full_name, u.email
        FROM orders o JOIN users u ON u.id = o.user_id';
$params = [];
if ($filter !== '') {
    $sql .= ' WHERE o.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY o.created_at DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

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

$activeNav = 'orders';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Orders — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h1>Orders</h1>
      <div class="admin-user">Signed in as <strong><?= e($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
    </div>

    <div class="admin-content">
      <?php if ($banner): ?>
        <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
      <?php endif; ?>

      <div class="section-head">
        <h2>All Orders</h2>
        <form method="GET" style="margin:0;">
          <select name="status" class="field-select" onchange="this.form.submit()" style="padding:8px 12px;border-radius:8px;border:1.5px solid var(--border);font-family:inherit;font-size:13px;">
            <option value="">All statuses</option>
            <?php foreach ($statuses as $s): ?>
              <option value="<?= e($s) ?>" <?= $filter === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>

      <?php if (empty($orders)): ?>
        <div class="empty-state">No orders found.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Order #</th>
                <th>Customer</th>
                <th>Items</th>
                <th>Total</th>
                <th>Placed On</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($orders as $o): ?>
                <tr>
                  <td>#<?= (int) $o['id'] ?></td>
                  <td>
                    <?= e($o['full_name']) ?>
                    <div class="cell-muted"><?= e($o['email']) ?></div>
                  </td>
                  <td>
                    <?php foreach (($itemsByOrder[$o['id']] ?? []) as $it): ?>
                      <div><?= e($it['product_name']) ?> <span class="cell-muted">× <?= (int) $it['quantity'] ?></span></div>
                    <?php endforeach; ?>
                  </td>
                  <td><?= money((float) $o['total_amount']) ?></td>
                  <td class="cell-muted"><?= e(date('M j, Y g:i A', strtotime($o['created_at']))) ?></td>
                  <td>
                    <form method="POST" style="display:flex;align-items:center;gap:8px;">
                      <?= csrf_field() ?>
                      <input type="hidden" name="order_id" value="<?= (int) $o['id'] ?>">
                      <span class="badge badge-<?= e($o['status']) ?>"><?= e($o['status']) ?></span>
                      <select name="status" onchange="this.form.submit()">
                        <?php foreach ($statuses as $s): ?>
                          <option value="<?= e($s) ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= e(ucfirst($s)) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </form>
                  </td>
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
