<?php
declare(strict_types=1);
// Local XAMPP defaults. Override with environment variables when deploying.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
try {
    $link = new mysqli(getenv('DB_HOST') ?: 'localhost', getenv('DB_USER') ?: 'root', getenv('DB_PASS') ?: '', getenv('DB_NAME') ?: 'prism');
    $link->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    exit('Database connection failed. Start MySQL and confirm the existing prism database settings.');
}
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
date_default_timezone_set('Asia/Kuala_Lumpur');
