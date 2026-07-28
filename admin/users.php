<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_admin();

$banner = '';
$bannerType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $banner = 'Your session expired, please try again.';
        $bannerType = 'error';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int) ($_POST['id'] ?? 0);

        if ($action === 'delete') {
            if ($id === current_user_id()) {
                $banner = "You can't delete your own admin account.";
                $bannerType = 'error';
            } else {
                $pdo->prepare("DELETE FROM users WHERE id = ? AND role = 'user'")->execute([$id]);
                header('Location: users.php?deleted=1');
                exit;
            }
        }
    }
}

if (isset($_GET['deleted'])) { $banner = 'User removed.'; $bannerType = 'success'; }

$users = $pdo->query(
    "SELECT u.id, u.full_name, u.email, u.role, u.created_at,
            (SELECT COUNT(*) FROM orders o WHERE o.user_id = u.id) AS order_count
     FROM users u
     WHERE u.role = 'user'
     ORDER BY u.created_at DESC"
)->fetchAll();

$activeNav = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h1>Users</h1>
      <div class="admin-user">Signed in as <strong><?= e($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
    </div>

    <div class="admin-content">
      <?php if ($banner): ?>
        <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
      <?php endif; ?>

      <div class="section-head">
        <h2>Customer Accounts</h2>
      </div>

      <?php if (empty($users)): ?>
        <div class="empty-state">No customer accounts yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Orders</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($users as $u): ?>
                <tr>
                  <td><?= e($u['full_name']) ?></td>
                  <td><?= e($u['email']) ?></td>
                  <td><span class="badge badge-<?= e($u['role']) ?>"><?= e($u['role']) ?></span></td>
                  <td class="cell-muted"><?= (int) $u['order_count'] ?></td>
                  <td class="cell-muted"><?= e(date('M j, Y', strtotime($u['created_at']))) ?></td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Remove this user? This cannot be undone.');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                      <button type="submit" class="btn btn-danger btn-sm">Remove</button>
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
