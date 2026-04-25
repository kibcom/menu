<?php
declare(strict_types=1);

/**
 * Optional: set your full public URL if the server reports wrong paths (rare).
 * Nested example on cPanel: https://yourdomain.com/project/menu
 * Leave commented to auto-detect from DOCUMENT_ROOT + script path.
 */
// define('APP_BASE_URL', 'https://yourdomain.com/project/menu');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dbHost = 'localhost';
$dbName = 'menus';a
$dbUser = 'root';
$dbPass = '';
$dbCharset = 'utf8mb4';

$dsn = "mysql:host={$dbHost};dbname={$dbName};charset={$dbCharset}";

try {
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die('Database connection failed. Please update includes/db.php credentials.');
}
