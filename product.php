<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';

require_login();
if (is_admin()) {
    header('Location: admin/dashboard.php');
    exit;
}

$productId = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    header('Location: home.php');
    exit;
}

$banner = '';
$bannerType = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $banner = 'Your session expired, please try again.';
        $bannerType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // ---- Place an order ----
        if ($action === 'order') {
            $qty = (int) ($_POST['quantity'] ?? 1);
            if ($qty < 1) $qty = 1;

            if ($qty > (int) $product['stock']) {
                $banner = 'Sorry, only ' . (int) $product['stock'] . ' left in stock.';
                $bannerType = 'error';
            } else {
                $pdo->beginTransaction();
                try {
                    $total = $qty * (float) $product['price'];

                    $orderStmt = $pdo->prepare(
                        'INSERT INTO orders (user_id, status, total_amount) VALUES (?, ?, ?)'
                    );
                    $orderStmt->execute([current_user_id(), 'pending', $total]);
                    $orderId = (int) $pdo->lastInsertId();

                    $itemStmt = $pdo->prepare(
                        'INSERT INTO order_items (order_id, product_id, product_name, quantity, unit_price)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $itemStmt->execute([$orderId, $product['id'], $product['name'], $qty, $product['price']]);

                    $stockStmt = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
                    $stockStmt->execute([$qty, $product['id']]);

                    $pdo->commit();
                    header('Location: orders.php?placed=1');
                    exit;
                } catch (Exception $ex) {
                    $pdo->rollBack();
                    $banner = 'Could not place the order. Please try again.';
                    $bannerType = 'error';
                }
            }
        }

        // ---- Leave a review ----
        if ($action === 'review') {
            $rating = (int) ($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');

            if ($rating < 1 || $rating > 5) {
                $errors['review'] = 'Please choose a rating from 1 to 5 stars.';
            } else {
                try {
                    $reviewStmt = $pdo->prepare(
                        'INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)'
                    );
                    $reviewStmt->execute([$product['id'], current_user_id(), $rating, $comment ?: null]);
                    header('Location: product.php?id=' . $product['id'] . '&reviewed=1');
                    exit;
                } catch (PDOException $ex) {
                    // Unique constraint (product_id, user_id) — they already reviewed this product.
                    $errors['review'] = 'You have already reviewed this product.';
                }
            }
        }
    }

    // Refresh product row in case stock changed.
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
}

if (isset($_GET['reviewed'])) {
    $banner = 'Thanks for your review!';
    $bannerType = 'success';
}

$reviewStmt = $pdo->prepare(
    'SELECT r.rating, r.comment, r.created_at, u.full_name
     FROM reviews r JOIN users u ON u.id = r.user_id
     WHERE r.product_id = ? ORDER BY r.created_at DESC'
);
$reviewStmt->execute([$product['id']]);
$reviews = $reviewStmt->fetchAll();

$myReviewStmt = $pdo->prepare('SELECT id FROM reviews WHERE product_id = ? AND user_id = ?');
$myReviewStmt->execute([$product['id'], current_user_id()]);
$alreadyReviewed = (bool) $myReviewStmt->fetch();

$avgRating = 0;
if (count($reviews) > 0) {
    $avgRating = array_sum(array_column($reviews, 'rating')) / count($reviews);
}
$roundedAvg = round($avgRating);

$stock = (int) $product['stock'];
$activeNav = 'home';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($product['name']) ?> — Storefront</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php require __DIR__ . '/includes/nav_user.php'; ?>

<div class="shop-main">
  <p><a href="home.php" style="color:var(--muted);text-decoration:none;font-size:13px;">&larr; Back to products</a></p>

  <?php if ($banner): ?>
    <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
  <?php endif; ?>

  <div class="product-detail">
    <div>
      <div class="product-thumb">
        <?php if (!empty($product['image_url'])): ?>
          <img src="<?= e($product['image_url']) ?>" alt="<?= e($product['name']) ?>">
        <?php else: ?>
          No image
        <?php endif; ?>
      </div>
    </div>

    <div>
      <h1 style="margin:0 0 6px;font-size:22px;"><?= e($product['name']) ?></h1>

      <?php if (count($reviews) > 0): ?>
        <div class="rating-summary" style="margin-bottom:10px;">
          <span class="stars"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i > $roundedAvg ? 'empty' : '' ?>">★</span><?php endfor; ?></span>
          <?= number_format($avgRating, 1) ?> (<?= count($reviews) ?> review<?= count($reviews) === 1 ? '' : 's' ?>)
        </div>
      <?php endif; ?>

      <p style="color:var(--muted);line-height:1.7;"><?= nl2br(e($product['description'] ?? 'No description provided.')) ?></p>
      <div class="price" style="font-size:22px;"><?= money((float) $product['price']) ?></div>
      <div class="stock-note <?= $stock === 0 ? 'out' : ($stock <= 5 ? 'low' : '') ?>">
        <?= $stock === 0 ? 'Out of stock' : "{$stock} in stock" ?>
      </div>

      <?php if ($stock > 0): ?>
        <form method="POST" class="order-box">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="order">
          <label for="quantity" style="font-size:13px;font-weight:600;display:block;margin-bottom:6px;">Quantity</label>
          <input type="number" id="quantity" name="quantity" min="1" max="<?= $stock ?>" value="1">
          <button type="submit" class="btn btn-primary" style="width:100%;">Place Order</button>
        </form>
      <?php else: ?>
        <div class="order-box" style="color:var(--muted);font-size:13.5px;">Currently out of stock.</div>
      <?php endif; ?>
    </div>
  </div>

  <div class="review-list">
    <div class="section-head"><h2>Reviews</h2></div>

    <?php if (!$alreadyReviewed): ?>
      <form method="POST" class="review-form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="review">
        <label style="font-size:13px;font-weight:600;display:block;margin-bottom:8px;">Leave a review</label>

        <div class="rating-select">
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>" <?= $i === 5 ? 'checked' : '' ?>>
            <label for="star<?= $i ?>">★</label>
          <?php endfor; ?>
        </div>

        <textarea name="comment" placeholder="Share your thoughts about this product (optional)"></textarea>
        <?php if (!empty($errors['review'])): ?>
          <div class="error-msg"><?= e($errors['review']) ?></div>
        <?php endif; ?>
        <button type="submit" class="btn btn-primary" style="margin-top:10px;">Submit Review</button>
      </form>
    <?php else: ?>
      <p class="cell-muted" style="color:var(--muted);font-size:13.5px;">You've already reviewed this product — thanks!</p>
    <?php endif; ?>

    <?php if (empty($reviews)): ?>
      <p class="cell-muted" style="color:var(--muted);font-size:13.5px;margin-top:14px;">No reviews yet. Be the first!</p>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div class="review-item">
          <div class="review-head">
            <strong><?= e($r['full_name']) ?></strong>
            <span><?= e(date('M j, Y', strtotime($r['created_at']))) ?></span>
          </div>
          <div class="stars"><?php for ($i = 1; $i <= 5; $i++): ?><span class="<?= $i > (int) $r['rating'] ? 'empty' : '' ?>">★</span><?php endfor; ?></div>
          <?php if (!empty($r['comment'])): ?>
            <p style="margin:6px 0 0;font-size:13.5px;color:var(--text);"><?= nl2br(e($r['comment'])) ?></p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
