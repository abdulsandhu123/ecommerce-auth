<?php
require __DIR__ . '/../includes/functions.php';
require __DIR__ . '/../config/db.php';

require_admin();

$banner = '';
$bannerType = '';
$errors = [];

$editId = (int) ($_GET['edit'] ?? 0);
$editProduct = null;
if ($editId > 0) {
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = ?');
    $stmt->execute([$editId]);
    $editProduct = $stmt->fetch();
    if (!$editProduct) {
        $editId = 0;
    }
}

$old = [
    'name'        => $editProduct['name'] ?? '',
    'description' => $editProduct['description'] ?? '',
    'price'       => $editProduct['price'] ?? '',
    'stock'       => $editProduct['stock'] ?? '',
    'image_url'   => $editProduct['image_url'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $banner = 'Your session expired, please try again.';
        $bannerType = 'error';
    } else {
        $action = $_POST['action'] ?? '';

        // ---- Delete a product (removes it everywhere: listings, reviews;
        //      keeps past order history intact but detaches it from this product) ----
        if ($action === 'delete') {
            $id = (int) ($_POST['id'] ?? 0);
            try {
                $pdo->beginTransaction();

                // Order history keeps its own snapshot (product_name, quantity,
                // unit_price) already, so we just detach the product reference —
                // this preserves the customer's past order details.
                $pdo->prepare('UPDATE order_items SET product_id = NULL WHERE product_id = ?')->execute([$id]);

                // Reviews belong to this product only, so they go with it.
                $pdo->prepare('DELETE FROM reviews WHERE product_id = ?')->execute([$id]);

                $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);

                $pdo->commit();
                header('Location: products.php?deleted=1');
                exit;
            } catch (Throwable $e) {
                $pdo->rollBack();
                $banner = 'Could not delete this product. Please try again.';
                $bannerType = 'error';
            }
        }

        // ---- Create or update a product ----
        if ($action === 'save') {
            $id = (int) ($_POST['id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $price = $_POST['price'] ?? '';
            $stock = $_POST['stock'] ?? '';
            $removeImage = isset($_POST['remove_image']);
            // Existing image (when editing) stays unless a new file is uploaded or "remove" is checked.
            $imageUrl = trim($_POST['existing_image'] ?? '');

            $old = compact('name', 'description', 'price', 'stock') + ['image_url' => $imageUrl];

            if ($name === '') $errors['name'] = 'Product name is required.';
            if (!is_numeric($price) || (float) $price < 0) $errors['price'] = 'Enter a valid price.';
            if (!is_numeric($stock) || (int) $stock < 0) $errors['stock'] = 'Enter a valid stock quantity.';

            // ---- Handle uploaded image file (browse from computer) ----
            $uploadedPath = null;
            if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] !== UPLOAD_ERR_NO_FILE) {
                $file = $_FILES['image_file'];

                if ($file['error'] !== UPLOAD_ERR_OK) {
                    $errors['image_file'] = 'There was a problem uploading the image. Please try again.';
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors['image_file'] = 'Image must be smaller than 5MB.';
                } else {
                    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
                    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                    $mime = mime_content_type($file['tmp_name']);

                    if (!isset($allowed[$ext]) || $mime !== $allowed[$ext]) {
                        $errors['image_file'] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
                    } else {
                        $uploadDir = __DIR__ . '/../assets/uploads/products/';
                        if (!is_dir($uploadDir)) {
                            mkdir($uploadDir, 0775, true);
                        }
                        $filename = uniqid('product_', true) . '.' . $ext;
                        if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                            $uploadedPath = 'assets/uploads/products/' . $filename;
                        } else {
                            $errors['image_file'] = 'Could not save the uploaded image. Please try again.';
                        }
                    }
                }
            }

            if ($uploadedPath) {
                $imageUrl = $uploadedPath;
            } elseif ($removeImage) {
                $imageUrl = '';
            }

            if (empty($errors)) {
                if ($id > 0) {
                    $stmt = $pdo->prepare(
                        'UPDATE products SET name = ?, description = ?, price = ?, stock = ?, image_url = ? WHERE id = ?'
                    );
                    $stmt->execute([$name, $description ?: null, $price, $stock, $imageUrl ?: null, $id]);
                    header('Location: products.php?updated=1');
                    exit;
                } else {
                    $stmt = $pdo->prepare(
                        'INSERT INTO products (name, description, price, stock, image_url, created_by) VALUES (?, ?, ?, ?, ?, ?)'
                    );
                    $stmt->execute([$name, $description ?: null, $price, $stock, $imageUrl ?: null, current_user_id()]);
                    header('Location: products.php?created=1');
                    exit;
                }
            } else {
                // Validation failed — stay on the form with the same id (edit vs create).
                $editId = $id;
            }
        }
    }
}

if (isset($_GET['created'])) { $banner = 'Product added successfully.'; $bannerType = 'success'; }
if (isset($_GET['updated'])) { $banner = 'Product updated successfully.'; $bannerType = 'success'; }
if (isset($_GET['deleted'])) { $banner = 'Product deleted.'; $bannerType = 'success'; }

$showForm = isset($_GET['add']) || $editId > 0 || !empty($errors);

$products = $pdo->query(
    'SELECT p.*, COUNT(r.id) AS review_count
     FROM products p LEFT JOIN reviews r ON r.product_id = p.id
     GROUP BY p.id ORDER BY p.created_at DESC'
)->fetchAll();

$activeNav = 'products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Products — Admin</title>
<link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<div class="admin-shell">
  <?php require __DIR__ . '/../includes/nav_admin.php'; ?>

  <div class="admin-main">
    <div class="admin-topbar">
      <h1>Products</h1>
      <div class="admin-user">Signed in as <strong><?= e($_SESSION['full_name'] ?? 'Admin') ?></strong></div>
    </div>

    <div class="admin-content">
      <?php if ($banner): ?>
        <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
      <?php endif; ?>

      <?php if ($showForm): ?>
        <div class="section-head">
          <h2><?= $editId > 0 ? 'Edit Product' : 'Add Product' ?></h2>
          <a href="products.php" class="btn btn-secondary btn-sm">Cancel</a>
        </div>

        <form method="POST" class="form-card" style="margin-bottom:30px;" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="id" value="<?= $editId ?>">
          <input type="hidden" name="existing_image" value="<?= e($old['image_url']) ?>">

          <div class="field">
            <label for="name">Product Name</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" required>
            <div class="error-msg"><?= e($errors['name'] ?? '') ?></div>
          </div>

          <div class="field">
            <label for="description">Description</label>
            <textarea id="description" name="description"><?= e($old['description']) ?></textarea>
          </div>

          <div class="field-row">
            <div class="field">
              <label for="price">Price ($)</label>
              <input type="number" step="0.01" min="0" id="price" name="price" value="<?= e((string) $old['price']) ?>" required>
              <div class="error-msg"><?= e($errors['price'] ?? '') ?></div>
            </div>
            <div class="field">
              <label for="stock">Stock Quantity</label>
              <input type="number" step="1" min="0" id="stock" name="stock" value="<?= e((string) $old['stock']) ?>" required>
              <div class="error-msg"><?= e($errors['stock'] ?? '') ?></div>
            </div>
          </div>

          <div class="field">
            <label for="image_file">Product Image (optional)</label>

            <?php if (!empty($old['image_url'])): ?>
              <div style="display:flex;align-items:center;gap:12px;margin-bottom:10px;">
                <img src="../<?= e($old['image_url']) ?>" alt="Current image" style="width:64px;height:64px;object-fit:cover;border-radius:8px;border:1px solid var(--border);">
                <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;color:var(--muted);font-weight:500;cursor:pointer;">
                  <input type="checkbox" name="remove_image" value="1"> Remove current image
                </label>
              </div>
            <?php endif; ?>

            <input type="file" id="image_file" name="image_file" accept="image/png,image/jpeg,image/gif,image/webp">
            <div class="hint">Browse an image from your computer (JPG, PNG, GIF, or WEBP — max 5MB).</div>
            <div class="error-msg"><?= e($errors['image_file'] ?? '') ?></div>
          </div>

          <div class="form-actions">
            <button type="submit" class="btn btn-primary"><?= $editId > 0 ? 'Save Changes' : 'Add Product' ?></button>
          </div>
        </form>
      <?php else: ?>
        <div class="section-head">
          <h2>All Products</h2>
          <a href="products.php?add=1" class="btn btn-primary btn-sm">+ Add Product</a>
        </div>
      <?php endif; ?>

      <?php if (empty($products)): ?>
        <div class="empty-state">No products yet. Click "Add Product" to create your first one.</div>
      <?php else: ?>
        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr>
                <th>Name</th>
                <th>Price</th>
                <th>Stock</th>
                <th>Reviews</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td><?= e($p['name']) ?></td>
                  <td><?= money((float) $p['price']) ?></td>
                  <td><?= (int) $p['stock'] ?></td>
                  <td class="cell-muted"><?= (int) $p['review_count'] ?></td>
                  <td>
                    <div class="row-actions">
                      <a href="products.php?edit=<?= (int) $p['id'] ?>" class="btn btn-secondary btn-sm">Edit</a>
                      <form method="POST" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                      </form>
                    </div>
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
