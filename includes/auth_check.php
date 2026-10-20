<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/functions.php';

// Set the session cookie protections before PHP starts the session.
ini_set('session.use_strict_mode', '1');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// Change this to 60 temporarily to test the idle timeout in one minute.
const IDLE_TIMEOUT_SECONDS = 1800;

// Clear the current login and start a fresh session for a one-time message.
function auth_end_session(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'] ?? 'Lax',
        ]);
    }

    session_destroy();
    session_start();
}

$allow_guest = $allow_guest ?? false;

if (!isset($_SESSION['user_id'])) {
    if (!$allow_guest) {
        redirect('auth/login.php');
    }

    return;
}

// End sessions that have been idle longer than the allowed 30 minutes.
if (
    !isset($_SESSION['last_activity'])
    || time() - (int) $_SESSION['last_activity'] > IDLE_TIMEOUT_SECONDS
) {
    auth_end_session();
    flash_set('warning', 'Your session expired. Please sign in again.');
    redirect('auth/login.php');
}

// Re-check the account on every request so deactivated users lose access promptly.
$user_id = filter_var($_SESSION['user_id'], FILTER_VALIDATE_INT);
if ($user_id === false || $user_id < 1) {
    auth_end_session();
    redirect('auth/login.php');
}

$statement = $conn->prepare(
    'SELECT full_name, role, is_active, must_change_password
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$statement->bind_param('i', $user_id);
$statement->execute();
$statement->bind_result($full_name, $role, $is_active, $must_change_password);
$user_exists = $statement->fetch();
$statement->close();

if (!$user_exists || (int) $is_active !== 1) {
    auth_end_session();
    flash_set('error', 'Your account is not available. Please contact an administrator.');
    redirect('auth/login.php');
}

$_SESSION['full_name'] = $full_name;
$_SESSION['role'] = $role;
$_SESSION['last_activity'] = time();

// Let users reach password change and logout even while a change is required.
$current_path = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
$allowed_password_paths = [
    url('auth/change_password.php'),
    url('auth/logout.php'),
];

if ((int) $must_change_password === 1 && !in_array($current_path, $allowed_password_paths, true)) {
    redirect('auth/change_password.php');
}
