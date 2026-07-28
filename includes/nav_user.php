<?php
/**
 * Shared shop navbar. Include after functions.php/db.php are loaded and
 * after require_login() has run. Expects $activeNav to be set by the
 * including page ('home' | 'orders') for the active-link highlight.
 */
$activeNav = $activeNav ?? '';
?>
<div class="shop-topbar">
  <div class="brand">
    <span class="logo-dot">S</span>
    <span class="brand-name">Storefront</span>
  </div>
  <div class="shop-nav">
    <a href="home.php" class="<?= $activeNav === 'home' ? 'active' : '' ?>">Home</a>
    <a href="orders.php" class="<?= $activeNav === 'orders' ? 'active' : '' ?>">My Orders</a>
    <form method="POST" action="logout.php">
      <?= csrf_field() ?>
      <button type="submit" class="logout-btn">Log out</button>
    </form>
  </div>
</div>
