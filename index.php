<?php
require __DIR__ . '/includes/functions.php';
header('Location: ' . (is_logged_in() ? home_path_for_role() : 'login.php'));
exit;
