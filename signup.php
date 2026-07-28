<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';

if (is_logged_in()) {
    header('Location: ' . home_path_for_role());
    exit;
}

$errors = [];
$banner = '';
$bannerType = '';
$old = ['fullName' => '', 'email' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $banner = 'Your session expired, please try again.';
        $bannerType = 'error';
    } else {
        $fullName = trim($_POST['fullName'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirmPassword'] ?? '';
        $old = ['fullName' => $fullName, 'email' => $email];

        $errors['fullName'] = validate_full_name($fullName);
        $errors['email'] = validate_email_field($email);
        $errors['password'] = validate_password_field($password);
        if ($confirmPassword === '') {
            $errors['confirmPassword'] = 'Please confirm your password.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirmPassword'] = 'Passwords do not match.';
        } else {
            $errors['confirmPassword'] = '';
        }
        $errors = array_filter($errors);

        if (empty($errors)) {
            $normalizedEmail = strtolower($email);

            $check = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$normalizedEmail]);

            if ($check->fetch()) {
                $errors['email'] = 'An account with this email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

                $insert = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash) VALUES (?, ?, ?)'
                );
                $insert->execute([$fullName, $normalizedEmail, $hash]);

                header('Location: login.php?registered=1');
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
<title>Sign Up — Store</title>
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
      <h2>Create your account,<br>start shopping in minutes.</h2>
      <p>Join thousands of shoppers enjoying faster checkout, order tracking, and members-only deals.</p>
    </div>

    <div class="hero-stats">
      <div><strong>50k+</strong>Happy customers</div>
      <div><strong>4.9★</strong>Average rating</div>
      <div><strong>24/7</strong>Support</div>
    </div>
  </div>

  <div class="auth-form-side">
    <div class="auth-card">
      <h1>Create your account</h1>
      <p class="subtitle">Join to start shopping today</p>

      <?php if ($banner): ?>
        <div class="form-banner <?= e($bannerType) ?>"><?= e($banner) ?></div>
      <?php endif; ?>

      <form method="POST" action="signup.php" novalidate>
        <?= csrf_field() ?>

        <div class="field">
          <label for="fullName">Full Name</label>
          <input type="text" id="fullName" name="fullName" autocomplete="name"
                 value="<?= e($old['fullName']) ?>" required>
          <div class="error-msg" id="fullNameError"><?= e($errors['fullName'] ?? '') ?></div>
        </div>

        <div class="field">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" autocomplete="email"
                 value="<?= e($old['email']) ?>" required>
          <div class="error-msg" id="emailError"><?= e($errors['email'] ?? '') ?></div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="password-wrap">
            <input type="password" id="password" name="password" autocomplete="new-password" required>
            <button type="button" class="toggle-password" data-target="password" aria-label="Show password">
              <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.6 18.6 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a18.6 18.6 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div class="error-msg" id="passwordError"><?= e($errors['password'] ?? '') ?></div>
          <div class="hint">Min 8 characters, with uppercase, lowercase, number &amp; special character.</div>
        </div>

        <div class="field">
          <label for="confirmPassword">Confirm Password</label>
          <div class="password-wrap">
            <input type="password" id="confirmPassword" name="confirmPassword" autocomplete="new-password" required>
            <button type="button" class="toggle-password" data-target="confirmPassword" aria-label="Show password">
              <svg class="icon-show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>
              <svg class="icon-hide" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 19c-7 0-11-7-11-7a18.6 18.6 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 7 11 7a18.6 18.6 0 0 1-2.16 3.19M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
            </button>
          </div>
          <div class="error-msg" id="confirmPasswordError"><?= e($errors['confirmPassword'] ?? '') ?></div>
        </div>

        <button type="submit" class="submit-btn">Create Account</button>
      </form>

      <div class="switch-link">
        Already have an account? <a href="login.php">Log in</a>
      </div>
    </div>
  </div>

</div>

<script src="assets/js/app.js"></script>
</body>
</html>
