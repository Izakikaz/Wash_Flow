<?php

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Customer removal must be submitted from the customer page.');
}

csrf_verify();

$customer_id = isset($_POST['customer_id']) && is_string($_POST['customer_id'])
    ? filter_var($_POST['customer_id'], FILTER_VALIDATE_INT)
    : false;

if ($customer_id === false || $customer_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

try {
    $conn->begin_transaction();

    $statement = $conn->prepare('SELECT id FROM customers WHERE id = ? FOR UPDATE');
    $statement->bind_param('i', $customer_id);
    $statement->execute();
    $statement->bind_result($found_customer_id);
    $customer_exists = $statement->fetch();
    $statement->close();

    if (!$customer_exists) {
        $conn->rollback();
        http_response_code(404);
        require __DIR__ . '/../errors/404.php';
        exit;
    }

    $statement = $conn->prepare('SELECT COUNT(*) FROM orders WHERE customer_id = ?');
    $statement->bind_param('i', $customer_id);
    $statement->execute();
    $statement->bind_result($order_count);
    $statement->fetch();
    $statement->close();

    if ((int) $order_count > 0) {
        $conn->rollback();
        flash_set('error', 'This customer has orders and cannot be removed. Edit their details instead.');
        redirect('customers/edit.php?id=' . (int) $customer_id);
    }

    $statement = $conn->prepare('DELETE FROM customers WHERE id = ?');
    $statement->bind_param('i', $customer_id);
    $statement->execute();
    $statement->close();
    $conn->commit();

    flash_set('success', 'The customer was removed.');
    redirect('customers/index.php');
} catch (mysqli_sql_exception $exception) {
    $conn->rollback();
    error_log('WashFlow customer deletion database error: ' . $exception->getMessage());
    http_response_code(500);
    require __DIR__ . '/../errors/500.php';
    exit;
}
