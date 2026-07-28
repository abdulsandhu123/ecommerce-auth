<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';

// Already logged in? Skip straight to their home (admin or shopper).
if (is_logged_in()) {
    header('Location: ' . home_path_for_role());
    exit;
}

$errors = [];
$banner = '';
$bannerType = '';
$emailOld = '';

if (isset($_GET['redirected'])) {
    $banner = 'Please log in to continue.';
    $bannerType = 'info';
}
if (isset($_GET['registered'])) {
    $banner = 'Account created! Please log in.';
    $bannerType = 'success';
}
if (isset($_GET['loggedout'])) {
    $banner = 'You have been logged out.';
    $bannerType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $banner = 'Your session expired, please try again.';
        $bannerType = 'error';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $emailOld = $email;

        if ($email === '') $errors['email'] = 'Email is required.';
        if ($password === '') $errors['password'] = 'Password is required.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('SELECT id, full_name, email, password_hash, role FROM users WHERE email = ?');
            $stmt->execute([strtolower($email)]);
            $user = $stmt->fetch();

            // Same generic message whether the email doesn't exist or the
            // password is wrong, so we don't leak which emails are registered.
            if (!$user || !password_verify($password, $user['password_hash'])) {
                $banner = 'Invalid email or password.';
                $bannerType = 'error';
            } else {
                session_regenerate_id(true); // prevent session fixation
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                header('Location: ' . home_path_for_role());
                exit;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log In — Store</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="auth-wrapper">

  <div class="auth-hero">
    <div class="brand">
      <span class="logo-dot">S</span>
      <span class="brand-name">Storefront</span>
    </div>

    <div class="hero-copy">
      <h2>Everything you love,<br>one login away.</h2>
      <p>Track orders, save your wishlist, and check out faster every time you shop with us.</p>
    </div>

    <div class="hero-stats">
      <div><strong>50k+</strong>Happy customers</div>
      <div><strong>4.9★</strong>Average rating</div>
      <div><strong>24/7</strong>Support</div>
    </div>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <h1>Welcome back</h1>
      <p class="subtitle">Log in to your account to continue</p>

      <?php if ($banner): ?>
        <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
      <?php endif; ?>

      <form method="POST" action="login.php" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" autocomplete="email"
                 value="<?= e($emailOld) ?>" required>
          <div class="error-msg" id="emailError"><?= e($errors['email'] ?? '') ?></div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="current-password" required>
            <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
              <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.6 18.6 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a18.6 18.6 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div class="error-msg" id="passwordError"><?= e($errors['password'] ?? '') ?></div>
        </div>

        <div class="remember-row">
          <label><input type="checkbox" name="remember"> Remember me</label>
          <a href="#">Forgot password?</a>
        </div>

        <button type="submit" class="submit-btn">Log In</button>
      </form>

      <div class="switch-link">
        Don't have an account? <a href="signup.php">Sign up</a>
      </div>
    </div>
  </div>

</div>

<script src="assets/js/app.js"></script>
</body>
</html>
