<?php

require_once __DIR__ . '/config.php';

// Open the project's one shared MySQL connection.
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+08:00'");
} catch (mysqli_sql_exception $exception) {
    error_log('WashFlow database connection failed: ' . $exception->getMessage());
    http_response_code(500);
    require __DIR__ . '/../errors/500.php';
    exit;
}
