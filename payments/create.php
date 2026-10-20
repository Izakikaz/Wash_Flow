<?php

require_once __DIR__ . '/../includes/auth_check.php';

$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$order_id_value = $is_post
    ? (isset($_POST['order_id']) && is_string($_POST['order_id']) ? $_POST['order_id'] : '')
    : (isset($_GET['order_id']) && is_string($_GET['order_id']) ? $_GET['order_id'] : '');
$order_id = filter_var($order_id_value, FILTER_VALIDATE_INT);

if ($order_id === false || $order_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

if ($is_post) {
    csrf_verify();

    $submitted_form_token = isset($_POST['payment_form_token']) && is_string($_POST['payment_form_token'])
        ? $_POST['payment_form_token']
        : '';
    $payment_tokens = $_SESSION['payment_form_tokens'] ?? [];
    $token_entry = isset($payment_tokens[$submitted_form_token]) && is_array($payment_tokens[$submitted_form_token])
        ? $payment_tokens[$submitted_form_token]
        : null;

    if (
        $token_entry === null
        || (int) ($token_entry['order_id'] ?? 0) !== $order_id
        || (int) ($token_entry['expires_at'] ?? 0) < time()
    ) {
        flash_set('warning', 'This payment form has expired or was already submitted. Open the order and try again.');
        redirect('orders/view.php?id=' . $order_id);
    }

    unset($_SESSION['payment_form_tokens'][$submitted_form_token]);
}

$order_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, o.status, o.total_amount, o.payment_status, o.is_void,
            c.name AS customer_name, c.phone AS customer_phone,
            (SELECT COALESCE(SUM(p.amount), 0.00)
             FROM payments AS p
             WHERE p.order_id = o.id AND p.is_void = 0 AND o.is_void = 0) AS amount_paid
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     WHERE o.id = ?
     LIMIT 1'
);
$order_statement->bind_param('i', $order_id);
$order_statement->execute();
$order = $order_statement->get_result()->fetch_assoc();
$order_statement->close();

if ($order === null) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$amount_paid_cents = (int) round((float) $order['amount_paid'] * 100);
$total_cents = (int) round((float) $order['total_amount'] * 100);
$balance_cents = max(0, $total_cents - $amount_paid_cents);
$amount_paid_value = number_format($balance_cents / 100, 2, '.', '');
$method_value = 'cash';
$reference_value = '';
$form_error = '';
$amount_error = '';
$method_error = '';
$reference_error = '';
if ($is_post) {
    $amount_paid_value = isset($_POST['amount']) && is_string($_POST['amount'])
        ? trim($_POST['amount'])
        : '';
    $method_value = isset($_POST['method']) && is_string($_POST['method'])
        ? trim($_POST['method'])
        : '';
    $reference_value = isset($_POST['reference_no']) && is_string($_POST['reference_no'])
        ? trim($_POST['reference_no'])
        : '';

    $amount_is_numeric = strlen($amount_paid_value) <= 11
        && preg_match('/\A-?\d{1,8}(?:\.\d{1,2})?\z/', $amount_paid_value) === 1;
    $amount_cents = 0;
    if ($amount_is_numeric) {
        $is_negative = str_starts_with($amount_paid_value, '-');
        $unsigned_amount = $is_negative ? substr($amount_paid_value, 1) : $amount_paid_value;
        $amount_parts = explode('.', $unsigned_amount, 2);
        $amount_cents = ((int) $amount_parts[0] * 100)
            + (int) str_pad($amount_parts[1] ?? '', 2, '0');
        if ($is_negative) {
            $amount_cents *= -1;
        }
    } else {
        $amount_error = 'Enter a valid amount using up to two decimal places.';
        $form_error = 'Correct the payment details below and try again.';
    }

    if (!in_array($method_value, ['cash', 'gcash', 'other'], true)) {
        $method_error = 'Choose Cash, GCash, or Other.';
        $form_error = 'Correct the payment details below and try again.';
    }

    $reference_length = preg_match_all('/./us', $reference_value, $reference_characters);
    if ($reference_length === false || $reference_length > 50) {
        $reference_error = 'Keep the reference number to 50 characters or fewer.';
        $form_error = 'Correct the payment details below and try again.';
    }

    if ($form_error === '') {
        $transaction_started = false;
        try {
            $conn->begin_transaction();
            $transaction_started = true;

            $locked_order_statement = $conn->prepare(
                'SELECT total_amount, is_void
                 FROM orders
                 WHERE id = ?
                 FOR UPDATE'
            );
            $locked_order_statement->bind_param('i', $order_id);
            $locked_order_statement->execute();
            $locked_order_statement->bind_result($locked_total, $locked_is_void);
            $order_exists = $locked_order_statement->fetch();
            $locked_order_statement->close();

            if (!$order_exists) {
                $conn->rollback();
                $transaction_started = false;
                http_response_code(404);
                require __DIR__ . '/../errors/404.php';
                exit;
            }

            if ((int) $locked_is_void === 1) {
                $conn->rollback();
                $transaction_started = false;
                $form_error = 'A payment cannot be recorded for a void order.';
            } else {
                $balance_due = get_order_balance($conn, $order_id);
                $locked_balance_cents = (int) round($balance_due * 100);

                if ($amount_cents <= 0) {
                    $amount_error = 'Enter an amount greater than zero.';
                    $form_error = 'Correct the payment details below and try again.';
                } elseif ($locked_balance_cents <= 0) {
                    $amount_error = 'This order is already fully paid.';
                    $form_error = 'No further payment is due for this order.';
                } elseif ($amount_cents > $locked_balance_cents) {
                    $amount_error = 'The payment cannot be more than the current balance due.';
                    $form_error = 'Correct the payment details below and try again.';
                }

                if ($form_error !== '') {
                    $conn->rollback();
                    $transaction_started = false;
                } else {
                    $amount = number_format($amount_cents / 100, 2, '.', '');
                    $reference_no = $reference_value === '' ? null : $reference_value;
                    $received_by = (int) $_SESSION['user_id'];
                    $payment_statement = $conn->prepare(
                        'INSERT INTO payments (order_id, amount, method, reference_no, received_by)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    $payment_statement->bind_param(
                        'isssi',
                        $order_id,
                        $amount,
                        $method_value,
                        $reference_no,
                        $received_by
                    );
                    $payment_statement->execute();
                    $payment_id = (int) $conn->insert_id;
                    $payment_statement->close();

                    recalc_payment_status($conn, $order_id);
                    $conn->commit();
                    $transaction_started = false;

                    flash_set('success', 'Payment recorded. Print the receipt for the customer.');
                    redirect('payments/receipt.php?id=' . $payment_id);
                }
            }
        } catch (Throwable $exception) {
            if ($transaction_started) {
                try {
                    $conn->rollback();
                } catch (Throwable $rollback_exception) {
                    error_log('WashFlow payment transaction rollback failed: ' . $rollback_exception->getMessage());
                }
            }

            error_log('WashFlow payment creation failed: ' . $exception->getMessage());
            http_response_code(500);
            require __DIR__ . '/../errors/500.php';
            exit;
        }
    }
}

if ($form_error !== '') {
    $order_statement = $conn->prepare(
        'SELECT o.id, o.ticket_no, o.status, o.total_amount, o.payment_status, o.is_void,
                c.name AS customer_name, c.phone AS customer_phone,
                (SELECT COALESCE(SUM(p.amount), 0.00)
                 FROM payments AS p
                 WHERE p.order_id = o.id AND p.is_void = 0 AND o.is_void = 0) AS amount_paid
         FROM orders AS o
         INNER JOIN customers AS c ON c.id = o.customer_id
         WHERE o.id = ?
         LIMIT 1'
    );
    $order_statement->bind_param('i', $order_id);
    $order_statement->execute();
    $order = $order_statement->get_result()->fetch_assoc();
    $order_statement->close();

    if ($order === null) {
        http_response_code(404);
        require __DIR__ . '/../errors/404.php';
        exit;
    }

    $amount_paid_cents = (int) round((float) $order['amount_paid'] * 100);
    $total_cents = (int) round((float) $order['total_amount'] * 100);
    $balance_cents = max(0, $total_cents - $amount_paid_cents);
}

if (!isset($_SESSION['payment_form_tokens']) || !is_array($_SESSION['payment_form_tokens'])) {
    $_SESSION['payment_form_tokens'] = [];
}
foreach ($_SESSION['payment_form_tokens'] as $saved_token => $saved_entry) {
    if (!is_array($saved_entry) || (int) ($saved_entry['expires_at'] ?? 0) < time()) {
        unset($_SESSION['payment_form_tokens'][$saved_token]);
    }
}
$payment_form_token = bin2hex(random_bytes(32));
$_SESSION['payment_form_tokens'][$payment_form_token] = [
    'order_id' => (int) $order_id,
    'expires_at' => time() + 1800,
];

$balance_due = $balance_cents / 100;
$page_title = 'Record payment';
$page_breadcrumb = [
    'back_label' => 'order',
    'back_url' => url('orders/view.php?id=' . (int) $order['id']),
    'items' => [
        ['label' => 'Orders', 'url' => url('orders/index.php')],
        ['label' => $order['ticket_no'], 'url' => url('orders/view.php?id=' . (int) $order['id'])],
        ['label' => 'Record payment'],
    ],
];
$flash_message = flash_get();
$flash_class = match ($flash_message['type'] ?? '') {
    'success' => 'border-emerald-300 bg-emerald-50 text-emerald-950',
    'warning' => 'border-amber-300 bg-amber-50 text-amber-950',
    'error' => 'border-red-300 bg-red-50 text-red-950',
    default => 'border-slate-300 bg-slate-100 text-slate-900',
};

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-2xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <section>
        <p class="text-base font-semibold text-emerald-900">Daily work · Orders</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Record payment</h1>
        <p class="mt-2 text-base text-slate-700">Record a payment toward this order. The amount due is checked again when you save.</p>
    </section>

    <?php if ($flash_message !== null): ?>
        <p class="rounded-lg border px-4 py-3 text-base <?= e($flash_class) ?>" role="status" aria-live="polite"><?= e($flash_message['message'] ?? '') ?></p>
    <?php endif; ?>

    <section class="rounded-xl border border-emerald-300 bg-emerald-50 p-5 shadow-sm" aria-labelledby="balance-heading">
        <p class="text-base font-semibold text-emerald-950"><?= e($order['ticket_no']) ?> · <?= e($order['customer_name']) ?></p>
        <h2 id="balance-heading" class="mt-3 text-base font-semibold text-emerald-950">Balance due</h2>
        <p class="mt-1 text-3xl font-bold tracking-tight text-emerald-950"><?= e(format_money($balance_due)) ?></p>
        <dl class="mt-3 space-y-1 border-t border-emerald-200 pt-3 text-base text-slate-800">
            <div class="flex justify-between gap-4"><dt>Order total</dt><dd class="font-medium"><?= e(format_money($order['total_amount'])) ?></dd></div>
            <div class="flex justify-between gap-4"><dt>Paid so far</dt><dd class="font-medium"><?= e(format_money($order['amount_paid'])) ?></dd></div>
        </dl>
    </section>

    <?php if ($form_error !== ''): ?>
        <p class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-base text-red-950" role="alert"><?= e($form_error) ?></p>
    <?php endif; ?>

    <?php if ((int) $order['is_void'] === 1): ?>
        <section class="rounded-xl border border-slate-300 bg-slate-100 p-5" role="status">
            <h2 class="text-lg font-semibold text-slate-950">This order is void</h2>
            <p class="mt-2 text-base text-slate-700">A payment cannot be added to a void order.</p>
        </section>
    <?php elseif ($balance_due <= 0): ?>
        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm" role="status">
            <h2 class="text-lg font-semibold text-slate-950">This order is fully paid</h2>
            <p class="mt-2 text-base text-slate-700">No further payment is due.</p>
        </section>
    <?php else: ?>
        <form action="<?= e(url('payments/create.php?order_id=' . (int) $order['id'])) ?>" method="post" class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-payment-form>
            <?= csrf_field() ?>
            <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
            <input type="hidden" name="payment_form_token" value="<?= e($payment_form_token) ?>">

            <div>
                <label class="mb-1 block text-base font-medium text-slate-800" for="amount">Amount paid</label>
                <div class="flex items-center gap-2">
                    <span class="text-base font-semibold text-slate-700" aria-hidden="true">₱</span>
                    <input class="w-full min-h-12 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="amount" name="amount" type="number" inputmode="decimal" min="0.01" max="<?= e(number_format($balance_due, 2, '.', '')) ?>" step="0.01" value="<?= e($amount_paid_value) ?>" aria-invalid="<?= $amount_error !== '' ? 'true' : 'false' ?>" <?= $amount_error !== '' ? 'aria-describedby="amount-error"' : '' ?> required autofocus>
                </div>
                <?php if ($amount_error !== ''): ?><p class="mt-1 text-base text-red-800" id="amount-error"><?= e($amount_error) ?></p><?php endif; ?>
            </div>

            <div>
                <label class="mb-1 block text-base font-medium text-slate-800" for="method">Payment method</label>
                <select class="w-full min-h-12 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="method" name="method" aria-invalid="<?= $method_error !== '' ? 'true' : 'false' ?>" <?= $method_error !== '' ? 'aria-describedby="method-error"' : '' ?> required>
                    <option value="cash" <?= $method_value === 'cash' ? 'selected' : '' ?>>Cash</option>
                    <option value="gcash" <?= $method_value === 'gcash' ? 'selected' : '' ?>>GCash</option>
                    <option value="other" <?= $method_value === 'other' ? 'selected' : '' ?>>Other</option>
                </select>
                <?php if ($method_error !== ''): ?><p class="mt-1 text-base text-red-800" id="method-error"><?= e($method_error) ?></p><?php endif; ?>
            </div>

            <div>
                <label class="mb-1 block text-base font-medium text-slate-800" for="reference_no">Reference number <span class="font-normal text-slate-600">(optional)</span></label>
                <input class="w-full min-h-12 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="reference_no" name="reference_no" type="text" maxlength="50" value="<?= e($reference_value) ?>" aria-invalid="<?= $reference_error !== '' ? 'true' : 'false' ?>" <?= $reference_error !== '' ? 'aria-describedby="reference-error"' : '' ?>>
                <?php if ($reference_error !== ''): ?><p class="mt-1 text-base text-red-800" id="reference-error"><?= e($reference_error) ?></p><?php endif; ?>
            </div>

            <div>
                <button class="w-full min-h-12 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:cursor-wait disabled:opacity-70 sm:w-auto" type="submit">Record payment</button>
            </div>
        </form>
    <?php endif; ?>
</div>
<script>
    document.querySelectorAll('[data-payment-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitted = 'true';
            form.querySelector('button[type="submit"]').disabled = true;
        });
    });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
