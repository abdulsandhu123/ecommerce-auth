<?php
// ---- Database connection settings ----
// Update these to match your MySQL setup.
$DB_HOST = 'localhost';
$DB_NAME = 'ecommerce_auth';
$DB_USER = 'root';
$DB_PASS = '';           // XAMPP/WAMP default is an empty password for root
$DB_CHARSET = 'utf8mb4';

// Set this to true while developing locally so you can see the REAL error
// (wrong password, unknown database, MySQL not running, etc.) instead of
// the generic "Database connection failed" message. Set back to false
// before putting this on a live/shared server.
$DB_DEBUG = true;

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false, // use real prepared statements
];

try {
    $pdo = new PDO($dsn, $DB_USER, $DB_PASS, $options);
} catch (PDOException $e) {
    error_log('DB connection error: ' . $e->getMessage());
    http_response_code(500);
    if ($DB_DEBUG) {
        die('Database connection failed: ' . $e->getMessage());
    }
    die('Database connection failed. Please try again later.');
}
