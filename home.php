<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';

require_login();

// Admins manage the shop from the admin panel, not the customer storefront.
if (is_admin()) {
    header('Location: admin/dashboard.php');
    exit;
}

$products = $pdo->query(
    'SELECT p.*,
            COALESCE(AVG(r.rating), 0) AS avg_rating,
            COUNT(r.id) AS review_count
     FROM products p
     LEFT JOIN reviews r ON r.product_id = p.id
     GROUP BY p.id
     ORDER BY p.created_at DESC'
)->fetchAll();

$activeNav = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Shop — Storefront</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/includes/nav_user.php'; ?>

<div class="shop-main">
  <h1>Welcome, <?= e($_SESSION['full_name'] ?? 'there') ?> 👋</h1>
  <p class="page-subtitle">Browse products below and place an order any time.</p>

  <?php if (empty($products)): ?>
    <div class="empty-state">No products are available yet. Please check back soon.</div>
  <?php else: ?>
    <div class="product-grid">
      <?php foreach ($products as $p):
        $stock = (int) $p['stock'];
        $stockClass = $stock === 0 ? 'out' : ($stock <= 5 ? 'low' : '');
        $stockLabel = $stock === 0 ? 'Out of stock' : ($stock <= 5 ? "Only {$stock} left" : "{$stock} in stock");
        $rounded = round((float) $p['avg_rating']);
      ?>
        <div class="product-card">
          <div class="product-thumb">
            <?php if (!empty($p['image_url'])): ?>
              <img src="<?= e($p['image_url']) ?>" alt="<?= e($p['name']) ?>" loading="lazy">
            <?php else: ?>
              No image
            <?php endif; ?>
          </div>
          <div class="product-body">
            <h3><?= e($p['name']) ?></h3>
            <?php if ((int) $p['review_count'] > 0): ?>
              <div class="rating-summary">
                <span class="stars"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i > $rounded ? 'empty' : '' ?>">★</span><?php endfor; ?></span>
                (<?= (int) $p['review_count'] ?>)
              </div>
            <?php endif; ?>
            <p class="product-desc"><?= e($p['description'] ?? '') ?></p>
            <div class="price"><?= money((float) $p['price']) ?></div>
            <div class="stock-note <?= $stockClass ?>"><?= e($stockLabel) ?></div>
          </div>
          <div class="product-footer">
            <a href="product.php?id=<?= (int) $p['id'] ?>" class="btn btn-primary">View &amp; Order</a>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

</body>
</html>
