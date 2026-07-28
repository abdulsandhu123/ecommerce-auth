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
            $pdo->prepare('DELETE FROM reviews WHERE id = ?')->execute([$id]);
            header('Location: reviews.php?deleted=1');
            exit;
        }
    }
}

if (isset($_GET['deleted'])) { $banner = 'Review removed.'; $bannerType = 'success'; }

$reviews = $pdo->query(
    'SELECT r.id, r.rating, r.comment, r.created_at, u.full_name, p.name AS product_name
     FROM reviews r
     JOIN users u ON u.id = r.user_id
     JOIN products p ON p.id = r.product_id
     ORDER BY r.created_at DESC'
)->fetchAll();

$activeNav = 'reviews';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reviews — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h1>Reviews</h1>
      <div class="admin-user">Signed in as <strong><?= e($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
    </div>

    <div class="admin-content">
      <?php if ($banner): ?>
        <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
      <?php endif; ?>

      <div class="section-head">
        <h2>Customer Reviews</h2>
      </div>

      <?php if (empty($reviews)): ?>
        <div class="empty-state">No reviews have been submitted yet.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Customer</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($reviews as $r): ?>
                <tr>
                  <td><?= e($r['product_name']) ?></td>
                  <td><?= e($r['full_name']) ?></td>
                  <td><div class="stars"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i > (int) $r['rating'] ? 'empty' : '' ?>">★</span><?php endfor; ?></div></td>
                  <td style="max-width:280px;"><?= $r['comment'] ? e($r['comment']) : '<span class="cell-muted">No comment</span>' ?></td>
                  <td class="cell-muted"><?= e(date('M j, Y', strtotime($r['created_at']))) ?></td>
                  <td>
                    <form method="POST" onsubmit="return confirm('Remove this review?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
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
