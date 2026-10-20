<?php

require_once __DIR__ . '/../includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Order status changes must be submitted from the order page.');
}

csrf_verify();

$order_id = isset($_POST['order_id']) && is_string($_POST['order_id'])
    ? filter_var($_POST['order_id'], FILTER_VALIDATE_INT)
    : false;
$requested_next_status = isset($_POST['next_status']) && is_string($_POST['next_status'])
    ? $_POST['next_status']
    : '';

if ($order_id === false || $order_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$redirect_to_order = static function (int $id): void {
    redirect('orders/view.php?id=' . $id);
};

$transaction_started = false;
try {
    $conn->begin_transaction();
    $transaction_started = true;

    $order_statement = $conn->prepare(
        'SELECT status, is_void
         FROM orders
         WHERE id = ?
         FOR UPDATE'
    );
    $order_statement->bind_param('i', $order_id);
    $order_statement->execute();
    $order_statement->bind_result($current_status, $is_void);
    $order_exists = $order_statement->fetch();
    $order_statement->close();

    if (!$order_exists) {
        $conn->rollback();
        $transaction_started = false;
        http_response_code(404);
        require __DIR__ . '/../errors/404.php';
        exit;
    }

    if ((int) $is_void === 1) {
        $conn->rollback();
        $transaction_started = false;
        flash_set('error', 'A void order cannot be advanced.');
        $redirect_to_order($order_id);
    }

    $allowed_next_status = next_status($current_status);
    if ($allowed_next_status === null || $requested_next_status !== $allowed_next_status) {
        $conn->rollback();
        $transaction_started = false;
        flash_set('error', 'That status change is not allowed. Orders can move forward only one step at a time.');
        $redirect_to_order($order_id);
    }

    if ($allowed_next_status === 'claimed') {
        $balance_due = get_order_balance($conn, $order_id);
        if ($balance_due > 0) {
            $conn->rollback();
            $transaction_started = false;
            flash_set('warning', 'This order cannot be claimed until its balance is paid.');
            $redirect_to_order($order_id);
        }

        $update_statement = $conn->prepare(
            'UPDATE orders
             SET status = ?, claimed_at = NOW()
             WHERE id = ?'
        );
    } elseif ($allowed_next_status === 'ready') {
        $update_statement = $conn->prepare(
            'UPDATE orders
             SET status = ?, ready_at = NOW()
             WHERE id = ?'
        );
    } else {
        $update_statement = $conn->prepare(
            'UPDATE orders
             SET status = ?
             WHERE id = ?'
        );
    }

    $update_statement->bind_param('si', $allowed_next_status, $order_id);
    $update_statement->execute();
    $update_statement->close();
    $conn->commit();
    $transaction_started = false;

    $status_info = order_statuses();
    flash_set('success', 'Order status updated to ' . $status_info[$allowed_next_status]['label'] . '.');
    $redirect_to_order($order_id);
} catch (Throwable $exception) {
    if ($transaction_started) {
        try {
            $conn->rollback();
        } catch (Throwable $rollback_exception) {
            error_log('WashFlow status transaction rollback failed: ' . $rollback_exception->getMessage());
        }
    }

    error_log('WashFlow order status update failed: ' . $exception->getMessage());
    http_response_code(500);
    require __DIR__ . '/../errors/500.php';
    exit;
}
