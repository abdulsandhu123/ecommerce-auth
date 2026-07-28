<?php
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/config/db.php';

// --- Route guard: this is the whole point ---
// If there's no valid session, bounce to login.php immediately.
require_login();

$stmt = $pdo->prepare('SELECT full_name, email, created_at FROM users WHERE id = ?');
$stmt->execute([current_user_id()]);
$user = $stmt->fetch();

if (!$user) {
    // Session pointed at a user that no longer exists — log out safely.
    session_destroy();
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Account — Store</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<div class="dashboard-wrapper">
  <div class="dashboard-card">
    <div class="top-row">
      <h1>My Account</h1>
      <form method="POST" action="logout.php">
        <?= csrf_field() ?>
        <button type="submit" class="logout-btn">Log out</button>
      </form>
    </div>

    <div class="profile-box">
      <p><strong>Name:</strong> <?= e($user['full_name']) ?></p>
      <p><strong>Email:</strong> <?= e($user['email']) ?></p>
      <p><strong>Member since:</strong> <?= e(date('F j, Y', strtotime($user['created_at']))) ?></p>
    </div>
  </div>
</div>

</body>
</html>
