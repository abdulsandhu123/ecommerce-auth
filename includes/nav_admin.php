<?php
/**
 * Shared admin sidebar. Include after functions.php/db.php are loaded and
 * after require_admin() has run. Expects $activeNav to be set by the
 * including page for the active-link highlight.
 */
$activeNav = $activeNav ?? '';
function nav_active($key, $activeNav) { return $key === $activeNav ? 'active' : ''; }
?>
<div class="admin-sidebar">
  <div class="brand">
    <span class="logo-dot">S</span>
    <span>Admin Panel</span>
  </div>
  <nav>
    <a href="dashboard.php" class="<?= nav_active('dashboard', $activeNav) ?>">Dashboard</a>
    <a href="products.php" class="<?= nav_active('products', $activeNav) ?>">Products</a>
    <a href="orders.php" class="<?= nav_active('orders', $activeNav) ?>">Orders</a>
    <a href="users.php" class="<?= nav_active('users', $activeNav) ?>">Users</a>
    <a href="reviews.php" class="<?= nav_active('reviews', $activeNav) ?>">Reviews</a>
  </nav>
  <div class="sidebar-foot">
    <form method="POST" action="../logout.php">
      <?= csrf_field() ?>
      <button type="submit">Log out</button>
    </form>
  </div>
</div>
