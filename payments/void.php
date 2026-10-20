<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Payment corrections must be submitted from an order.');
}

csrf_verify();

$order_id = isset($_POST['order_id']) && is_string($_POST['order_id'])
    ? filter_var($_POST['order_id'], FILTER_VALIDATE_INT)
    : false;
$payment_id = isset($_POST['payment_id']) && is_string($_POST['payment_id'])
    ? filter_var($_POST['payment_id'], FILTER_VALIDATE_INT)
    : false;
$reason = isset($_POST['reason']) && is_string($_POST['reason'])
    ? trim($_POST['reason'])
    : '';
$reason_length = preg_match_all('/./us', $reason);

if ($order_id === false || $order_id < 1 || $payment_id === false || $payment_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

if ($reason_length === false || $reason_length < 5 || $reason_length > 255) {
    flash_set('error', 'Enter a reason between 5 and 255 characters to void this payment.');
    redirect('orders/view.php?id=' . $order_id);
}

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
    $order_statement->bind_result($order_status, $order_is_void);
    $order_exists = $order_statement->fetch();
    $order_statement->close();

    if (!$order_exists) {
        $conn->rollback();
        $transaction_started = false;
        http_response_code(404);
        require __DIR__ . '/../errors/404.php';
        exit;
    }

    $payment_statement = $conn->prepare(
        'SELECT is_void
         FROM payments
         WHERE id = ? AND order_id = ?
         FOR UPDATE'
    );
    $payment_statement->bind_param('ii', $payment_id, $order_id);
    $payment_statement->execute();
    $payment_statement->bind_result($payment_is_void);
    $payment_exists = $payment_statement->fetch();
    $payment_statement->close();

    if (!$payment_exists) {
        $conn->rollback();
        $transaction_started = false;
        http_response_code(404);
        require __DIR__ . '/../errors/404.php';
        exit;
    }

    if ((int) $order_is_void === 1 || (int) $payment_is_void === 1) {
        $conn->rollback();
        $transaction_started = false;
        flash_set('warning', 'This payment has already been voided.');
        redirect('orders/view.php?id=' . $order_id);
    }

    if ($order_status === 'claimed') {
        $conn->rollback();
        $transaction_started = false;
        flash_set('error', 'A payment cannot be voided after the order has been claimed.');
        redirect('orders/view.php?id=' . $order_id);
    }

    $admin_id = (int) $_SESSION['user_id'];
    $update_statement = $conn->prepare(
        'UPDATE payments
         SET is_void = 1, voided_at = NOW(), void_reason = ?, voided_by = ?
         WHERE id = ? AND order_id = ? AND is_void = 0'
    );
    $update_statement->bind_param('siii', $reason, $admin_id, $payment_id, $order_id);
    $update_statement->execute();
    $payment_updated = $update_statement->affected_rows === 1;
    $update_statement->close();

    if (!$payment_updated) {
        $conn->rollback();
        $transaction_started = false;
        flash_set('warning', 'This payment has already been voided.');
        redirect('orders/view.php?id=' . $order_id);
    }

    recalc_payment_status($conn, $order_id);
    $conn->commit();
    $transaction_started = false;

    flash_set('success', 'Payment voided. The order balance has been recalculated.');
    redirect('orders/view.php?id=' . $order_id);
} catch (Throwable $exception) {
    if ($transaction_started) {
        try {
            $conn->rollback();
        } catch (Throwable $rollback_exception) {
            error_log('WashFlow payment void rollback failed: ' . $rollback_exception->getMessage());
        }
    }

    error_log('WashFlow payment void failed: ' . $exception->getMessage());
    http_response_code(500);
    require __DIR__ . '/../errors/500.php';
    exit;
}
