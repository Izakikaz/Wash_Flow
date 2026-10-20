<?php

// Copy this file to config.php and fill in the local database settings.
define('DB_HOST', 'localhost');
define('DB_NAME', 'washflow');
define('DB_USER', 'your_mysql_username');
define('DB_PASS', 'your_mysql_password');
define('BASE_URL', '/washflow');
define('DEBUG', false);

// Replace these sample details with the laundry shop's contact information.
define('SHOP_NAME', 'Your Laundry Shop');
define('SHOP_PHONE', 'Your shop phone number');
define('SHOP_HOURS', 'Your shop opening hours');

// Keep PHP and MySQL dates consistent with the shop's local time.
date_default_timezone_set('Asia/Manila');

// Show diagnostics only during local debugging; otherwise write them to the log.
error_reporting(E_ALL);
ini_set('display_errors', DEBUG ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', __DIR__ . '/../logs/error.log');
