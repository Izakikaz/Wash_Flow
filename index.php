<?php

$allow_guest = true;
require_once __DIR__ . '/includes/auth_check.php';

if (isset($_SESSION['user_id'])) {
    redirect(home_url());
}

redirect('auth/login.php');
