<?php
// PDO database connection.
// Every page that needs the DB does: require_once __DIR__ . '/../config/db.php';
// and then uses the $pdo variable.

require_once __DIR__ . '/constants.php';

define('DB_HOST', 'localhost');
define('DB_NAME', 'coffee_shop_db');
define('DB_USER', 'root');
define('DB_PASS', ''); // default XAMPP MySQL root password is empty

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    require_once __DIR__ . '/../500.php';
    exit;
}

// Align MySQL's NOW()/CURDATE() with PHP's GMT+8 clock (config/constants.php),
// so date-based filtering (e.g. admin/print_report.php) never disagrees with
// what PHP considers "today" near a midnight boundary.
$pdo->exec("SET time_zone = '+08:00'");
