<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Service status changes must be submitted from the services list.');
}

csrf_verify();

$service_id = isset($_POST['service_id']) && is_string($_POST['service_id'])
    ? filter_var($_POST['service_id'], FILTER_VALIDATE_INT)
    : false;

if ($service_id === false || $service_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

try {
    $conn->begin_transaction();

    $statement = $conn->prepare('SELECT is_active FROM services WHERE id = ? FOR UPDATE');
    $statement->bind_param('i', $service_id);
    $statement->execute();
    $statement->bind_result($is_active);
    $service_exists = $statement->fetch();
    $statement->close();

    if (!$service_exists) {
        $conn->rollback();
        http_response_code(404);
        require __DIR__ . '/../errors/404.php';
        exit;
    }

    $new_status = (int) $is_active === 1 ? 0 : 1;
    $statement = $conn->prepare('UPDATE services SET is_active = ? WHERE id = ?');
    $statement->bind_param('ii', $new_status, $service_id);
    $statement->execute();
    $statement->close();
    $conn->commit();

    flash_set('success', $new_status === 1 ? 'The service is active and available for new orders.' : 'The service was deactivated and will not appear on new orders.');
    redirect('services/index.php');
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    error_log('WashFlow service status update database error: ' . $exception->getMessage());
    http_response_code(500);
    require __DIR__ . '/../errors/500.php';
    exit;
}
