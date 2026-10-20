<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Create order';
$customers = [];
$services = [];
$customer_statement = $conn->prepare('SELECT id, name, phone FROM customers ORDER BY name, id');
$customer_statement->execute();
$customers = $customer_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$customer_statement->close();

$service_statement = $conn->prepare(
    'SELECT id, name, pricing_type, unit_price
     FROM services
     WHERE is_active = 1
     ORDER BY name, id'
);
$service_statement->execute();
$services = $service_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$service_statement->close();

$customer_id_value = '';
$amount_paid_value = '0';
$line_values = [
    ['service_id' => '', 'quantity' => '1'],
];
$line_errors = [
    ['service_id' => '', 'quantity' => ''],
];
$form_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $customer_id_value = isset($_POST['customer_id']) && is_string($_POST['customer_id'])
        ? trim($_POST['customer_id'])
        : '';
    $amount_paid_value = isset($_POST['amount_paid']) && is_string($_POST['amount_paid'])
        ? trim($_POST['amount_paid'])
        : '';

    $service_ids = isset($_POST['service_id']) && is_array($_POST['service_id'])
        ? $_POST['service_id']
        : [];
    $quantities = isset($_POST['quantity']) && is_array($_POST['quantity'])
        ? $_POST['quantity']
        : [];
    $line_count = max(count($service_ids), count($quantities));
    $line_values = [];
    $line_errors = [];

    for ($index = 0; $index < $line_count; $index++) {
        $line_values[] = [
            'service_id' => isset($service_ids[$index]) && is_string($service_ids[$index])
                ? trim($service_ids[$index])
                : '',
            'quantity' => isset($quantities[$index]) && is_string($quantities[$index])
                ? trim($quantities[$index])
                : '',
        ];
        $line_errors[] = ['service_id' => '', 'quantity' => ''];
    }

    if ($line_count === 0) {
        $form_error = 'Add at least one service line to create an order.';
    }

    $customer_id = filter_var($customer_id_value, FILTER_VALIDATE_INT);
    if ($customer_id === false || $customer_id < 1) {
        $form_error = 'Choose a customer for this order.';
    }

    $amount_paid_cents = 0;
    if (
        strlen($amount_paid_value) > 11
        || !preg_match('/\A\d+(?:\.\d{1,2})?\z/', $amount_paid_value)
    ) {
        $form_error = 'Enter an amount paid using up to two decimal places.';
    } else {
        $payment_parts = explode('.', $amount_paid_value, 2);
        $amount_paid_cents = ((int) $payment_parts[0] * 100)
            + (int) str_pad($payment_parts[1] ?? '', 2, '0');
    }

    $line_total_cents = 0;
    $valid_lines = [];
    foreach ($line_values as $index => $line) {
        $service_id = filter_var($line['service_id'], FILTER_VALIDATE_INT);
        if ($service_id === false || $service_id < 1) {
            $line_errors[$index]['service_id'] = 'Choose an active service.';
        }

        $quantity = $line['quantity'];
        if (
            strlen($quantity) > 9
            || !preg_match('/\A\d+(?:\.\d{1,2})?\z/', $quantity)
            || (float) $quantity <= 0
            || (float) $quantity > 999999.99
        ) {
            $line_errors[$index]['quantity'] = 'Enter a quantity greater than zero, using up to two decimal places.';
        } elseif ($service_id !== false && $service_id > 0) {
            $quantity_parts = explode('.', $quantity, 2);
            $quantity_hundredths = ((int) $quantity_parts[0] * 100)
                + (int) str_pad($quantity_parts[1] ?? '', 2, '0');

            $valid_lines[] = [
                'index' => $index,
                'service_id' => $service_id,
                'quantity' => $quantity,
                'quantity_hundredths' => $quantity_hundredths,
            ];
        }
    }

    foreach ($line_errors as $line_error) {
        if ($line_error['service_id'] !== '' || $line_error['quantity'] !== '') {
            $form_error = 'Check the service and quantity marked below.';
            break;
        }
    }

    if ($form_error === '') {
        $transaction_started = false;
        try {
            $conn->begin_transaction();
            $transaction_started = true;

            $customer_check = $conn->prepare('SELECT id FROM customers WHERE id = ? FOR UPDATE');
            $customer_check->bind_param('i', $customer_id);
            $customer_check->execute();
            $customer_check->bind_result($found_customer_id);
            $customer_exists = $customer_check->fetch();
            $customer_check->close();

            if (!$customer_exists) {
                $conn->rollback();
                $transaction_started = false;
                $form_error = 'That customer is no longer available. Choose a customer and try again.';
            } else {
                $service_check = $conn->prepare(
                    'SELECT pricing_type, unit_price
                     FROM services
                     WHERE id = ? AND is_active = 1
                     FOR UPDATE'
                );

                foreach ($valid_lines as &$line) {
                    $service_check->bind_param('i', $line['service_id']);
                    $service_check->execute();
                    $service_check->bind_result($pricing_type, $unit_price);
                    $service_exists = $service_check->fetch();
                    $service_check->free_result();

                    if (!$service_exists) {
                        $line_errors[$line['index']]['service_id'] = 'This service is no longer active. Choose another service.';
                        $form_error = 'Check the service and quantity marked below.';
                        continue;
                    }

                    if ($pricing_type === 'per_piece' && $line['quantity_hundredths'] % 100 !== 0) {
                        $line_errors[$line['index']]['quantity'] = 'Enter a whole number of pieces.';
                        $form_error = 'Check the service and quantity marked below.';
                        continue;
                    }

                    $unit_price_cents = (int) round((float) $unit_price * 100);
                    if ($unit_price_cents <= 0) {
                        $line_errors[$line['index']]['service_id'] = 'This service has an invalid price. Choose another service.';
                        $form_error = 'Check the service and quantity marked below.';
                        continue;
                    }
                    $subtotal_cents = (int) round(
                        compute_line_total($line['quantity'], $unit_price) * 100
                    );
                    if ($subtotal_cents > 9999999999) {
                        $line_errors[$line['index']]['quantity'] = 'This quantity is too large for a single service line.';
                        $form_error = 'Check the service and quantity marked below.';
                        continue;
                    }

                    $line['unit_price'] = number_format($unit_price_cents / 100, 2, '.', '');
                    $line['subtotal'] = number_format($subtotal_cents / 100, 2, '.', '');
                    $line_total_cents += $subtotal_cents;

                    if ($line_total_cents > 9999999999) {
                        $form_error = 'The order total is above the supported amount.';
                    }
                }
                unset($line);
                $service_check->close();

                if ($form_error !== '') {
                    $conn->rollback();
                    $transaction_started = false;
                } elseif ($amount_paid_cents > $line_total_cents) {
                    $conn->rollback();
                    $transaction_started = false;
                    $form_error = 'The amount paid now cannot be more than the order total.';
                } else {
                    $total_amount = number_format($line_total_cents / 100, 2, '.', '');
                    $created_by = (int) $_SESSION['user_id'];
                    $payment_status = $amount_paid_cents === 0
                        ? 'unpaid'
                        : ($amount_paid_cents === $line_total_cents ? 'paid' : 'partial');

                    $order_insert = $conn->prepare(
                        'INSERT INTO orders (ticket_no, customer_id, status, total_amount, payment_status, created_by)
                         VALUES (?, ?, \'pending\', ?, ?, ?)'
                    );
                    $order_saved = false;
                    for ($attempt = 0; $attempt < 5; $attempt++) {
                        $ticket_no = generate_ticket_no($conn);
                        $order_insert->bind_param(
                            'sissi',
                            $ticket_no,
                            $customer_id,
                            $total_amount,
                            $payment_status,
                            $created_by
                        );

                        try {
                            $order_insert->execute();
                            $order_saved = true;
                            break;
                        } catch (mysqli_sql_exception $exception) {
                            if ($exception->getCode() !== 1062 || $attempt === 4) {
                                throw $exception;
                            }
                        }
                    }
                    $order_insert->close();

                    if (!$order_saved) {
                        throw new RuntimeException('Could not create an order with a unique ticket number.');
                    }
                    $order_id = (int) $conn->insert_id;

                    $item_insert = $conn->prepare(
                        'INSERT INTO order_items (order_id, service_id, quantity, unit_price_snapshot, subtotal)
                         VALUES (?, ?, ?, ?, ?)'
                    );
                    foreach ($valid_lines as $line) {
                        $quantity_value = $line['quantity'];
                        $unit_price_value = $line['unit_price'];
                        $subtotal_value = $line['subtotal'];
                        $item_insert->bind_param(
                            'iisss',
                            $order_id,
                            $line['service_id'],
                            $quantity_value,
                            $unit_price_value,
                            $subtotal_value
                        );
                        $item_insert->execute();
                    }
                    $item_insert->close();

                    if ($amount_paid_cents > 0) {
                        $payment_amount = number_format($amount_paid_cents / 100, 2, '.', '');
                        $payment_insert = $conn->prepare(
                            'INSERT INTO payments (order_id, amount, method, received_by)
                             VALUES (?, ?, \'cash\', ?)'
                        );
                        $payment_insert->bind_param('isi', $order_id, $payment_amount, $created_by);
                        $payment_insert->execute();
                        $payment_insert->close();
                        recalc_payment_status($conn, $order_id);
                    }

                    $conn->commit();
                    $transaction_started = false;
                    flash_set('success', 'The order was created. Print the claim ticket for the customer.');
                    redirect('orders/ticket.php?id=' . $order_id);
                }
            }
        } catch (Throwable $exception) {
            if ($transaction_started) {
                try {
                    $conn->rollback();
                    $transaction_started = false;
                } catch (Throwable $rollback_exception) {
                    error_log('WashFlow order transaction rollback failed: ' . $rollback_exception->getMessage());
                }
            }
            error_log('WashFlow order creation failed: ' . $exception->getMessage());
            http_response_code(500);
            require __DIR__ . '/../errors/500.php';
            exit;
        }
    }
}

if ($line_values === []) {
    $line_values = [['service_id' => '', 'quantity' => '1']];
    $line_errors = [['service_id' => '', 'quantity' => '']];
}

$page_breadcrumb = [
    'back_label' => 'Orders',
    'back_url' => url('orders/index.php'),
    'items' => [
        ['label' => 'Orders', 'url' => url('orders/index.php')],
        ['label' => 'Create order'],
    ],
];

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 pb-44 sm:px-6 md:pb-6 lg:px-8">
    <section>
        <p class="text-base font-semibold text-emerald-900">Daily work · Orders</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Create order</h1>
        <p class="mt-2 text-base text-slate-700">Choose the customer and services. The saved prices are checked again when you save.</p>
    </section>

    <?php if ($form_error !== ''): ?>
        <p class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-base text-red-950" role="alert"><?= e($form_error) ?></p>
    <?php endif; ?>

    <?php if ($customers === []): ?>
        <section class="rounded-xl border border-amber-300 bg-amber-50 p-5" role="status">
            <h2 class="text-lg font-semibold text-amber-950">Add a customer first</h2>
            <p class="mt-2 text-base text-amber-950">An order must belong to a customer.</p>
            <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white sm:w-auto" href="<?= e(url('customers/create.php')) ?>">Add customer</a>
        </section>
    <?php elseif ($services === []): ?>
        <section class="rounded-xl border border-amber-300 bg-amber-50 p-5" role="status">
            <h2 class="text-lg font-semibold text-amber-950">No active services</h2>
            <p class="mt-2 text-base text-amber-950">Ask an administrator to activate a service before creating an order.</p>
            <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 sm:w-auto" href="<?= e(url('orders/index.php')) ?>">Back to orders</a>
        </section>
    <?php else: ?>
        <form id="order-form" action="<?= e(url('orders/create.php')) ?>" method="post" class="space-y-6" data-order-form>
            <?= csrf_field() ?>
            <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="customer-heading">
                <div>
                    <h2 id="customer-heading" class="text-lg font-semibold text-slate-950">Customer</h2>
                    <p class="mt-1 text-base text-slate-700">Select who is bringing in this laundry.</p>
                </div>
                <div>
                    <label class="mb-1 block text-base font-medium text-slate-800" for="customer_id">Customer</label>
                    <select class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="customer_id" name="customer_id" required>
                        <option value="">Choose a customer</option>
                        <?php foreach ($customers as $customer): ?>
                            <?php
                            $customer_label = $customer['name'];
                            if ($customer['phone'] !== null && $customer['phone'] !== '') {
                                $customer_label .= ' · ' . $customer['phone'];
                            }
                            ?>
                            <option value="<?= (int) $customer['id'] ?>" <?= (string) $customer['id'] === $customer_id_value ? 'selected' : '' ?>><?= e($customer_label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a class="mt-2 inline-flex min-h-11 items-center text-base font-semibold text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('customers/create.php')) ?>">Add a customer</a>
                </div>
            </section>

            <section class="space-y-4" aria-labelledby="services-heading">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 id="services-heading" class="text-lg font-semibold text-slate-950">Services</h2>
                        <p class="mt-1 text-base text-slate-700">Add each service and its weight or piece count.</p>
                    </div>
                    <button class="w-full min-h-11 rounded-lg border border-emerald-800 px-4 py-3 text-base font-semibold text-emerald-900 hover:bg-emerald-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" id="add-order-line" type="button">Add line</button>
                </div>

                <div class="space-y-4" id="order-lines">
                    <?php foreach ($line_values as $index => $line): ?>
                        <article class="order-line space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-order-line>
                            <div class="flex items-center justify-between gap-3">
                                <h3 class="text-base font-semibold text-slate-900">Service line <span data-line-number><?= $index + 1 ?></span></h3>
                                <button class="remove-order-line inline-flex min-h-11 items-center rounded-lg px-3 text-base font-semibold text-red-800 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800" type="button">Remove</button>
                            </div>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-base font-medium text-slate-800" for="service-<?= $index ?>">Service</label>
                                    <select class="service-select w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="service-<?= $index ?>" name="service_id[]" aria-invalid="<?= $line_errors[$index]['service_id'] !== '' ? 'true' : 'false' ?>" <?= $line_errors[$index]['service_id'] !== '' ? 'aria-describedby="service-error-' . $index . '"' : '' ?> required>
                                        <option value="">Choose a service</option>
                                        <?php foreach ($services as $service): ?>
                                            <option
                                                value="<?= (int) $service['id'] ?>"
                                                data-price="<?= e($service['unit_price']) ?>"
                                                data-pricing-type="<?= e($service['pricing_type']) ?>"
                                                <?= (string) $service['id'] === $line['service_id'] ? 'selected' : '' ?>
                                            ><?= e($service['name']) ?> — <?= e($service['pricing_type'] === 'per_kg' ? 'per kg' : 'per piece') ?> (<?= e(format_money($service['unit_price'])) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if ($line_errors[$index]['service_id'] !== ''): ?><p class="mt-1 text-base text-red-800" id="service-error-<?= $index ?>"><?= e($line_errors[$index]['service_id']) ?></p><?php endif; ?>
                                </div>
                                <div>
                                    <label class="quantity-label mb-1 block text-base font-medium text-slate-800" for="quantity-<?= $index ?>">Weight in kg or piece count</label>
                                    <input class="quantity-input w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="quantity-<?= $index ?>" name="quantity[]" type="number" inputmode="decimal" min="0.01" max="999999.99" step="0.01" value="<?= e($line['quantity']) ?>" aria-invalid="<?= $line_errors[$index]['quantity'] !== '' ? 'true' : 'false' ?>" <?= $line_errors[$index]['quantity'] !== '' ? 'aria-describedby="quantity-error-' . $index . '"' : '' ?> required>
                                    <p class="quantity-help mt-1 text-base text-slate-600">For per-piece services, enter a whole number.</p>
                                    <?php if ($line_errors[$index]['quantity'] !== ''): ?><p class="mt-1 text-base text-red-800" id="quantity-error-<?= $index ?>"><?= e($line_errors[$index]['quantity']) ?></p><?php endif; ?>
                                </div>
                            </div>
                            <p class="rounded-lg bg-slate-50 px-3 py-2 text-base text-slate-800">Line subtotal: <strong class="line-subtotal" aria-live="polite">₱0.00</strong></p>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="space-y-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="payment-heading">
                <h2 id="payment-heading" class="text-lg font-semibold text-slate-950">Payment at intake</h2>
                <div>
                    <label class="mb-1 block text-base font-medium text-slate-800" for="amount_paid">Amount paid now</label>
                    <div class="flex items-center gap-2">
                        <span class="text-base font-semibold text-slate-700" aria-hidden="true">₱</span>
                        <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="amount_paid" name="amount_paid" type="number" inputmode="decimal" min="0" step="0.01" value="<?= e($amount_paid_value) ?>">
                    </div>
                    <p class="mt-1 text-base text-slate-600">Leave at 0 if the customer will pay later. Payments are recorded as cash.</p>
                </div>
            </section>

            <div class="fixed inset-x-0 bottom-0 z-30 border-t border-slate-200 bg-white/95 px-4 py-3 shadow-[0_-4px_12px_rgba(15,23,42,0.08)] backdrop-blur md:static md:rounded-xl md:border md:shadow-sm">
                <div class="mx-auto flex w-full max-w-4xl flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <p class="text-lg font-semibold text-slate-950">Grand total: <span id="grand-total" aria-live="polite">₱0.00</span></p>
                    <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:cursor-wait disabled:opacity-70 md:w-auto" id="save-order" type="submit">Save order</button>
                </div>
            </div>
        </form>

        <template id="order-line-template">
            <article class="order-line space-y-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-5" data-order-line>
                <div class="flex items-center justify-between gap-3">
                    <h3 class="text-base font-semibold text-slate-900">Service line <span data-line-number></span></h3>
                    <button class="remove-order-line inline-flex min-h-11 items-center rounded-lg px-3 text-base font-semibold text-red-800 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800" type="button">Remove</button>
                </div>
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-base font-medium text-slate-800" data-service-label>Service</label>
                        <select class="service-select w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" name="service_id[]" required>
                            <option value="">Choose a service</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= (int) $service['id'] ?>" data-price="<?= e($service['unit_price']) ?>" data-pricing-type="<?= e($service['pricing_type']) ?>"><?= e($service['name']) ?> — <?= e($service['pricing_type'] === 'per_kg' ? 'per kg' : 'per piece') ?> (<?= e(format_money($service['unit_price'])) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="quantity-label mb-1 block text-base font-medium text-slate-800">Weight in kg or piece count</label>
                        <input class="quantity-input w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" name="quantity[]" type="number" inputmode="decimal" min="0.01" max="999999.99" step="0.01" value="1" required>
                        <p class="quantity-help mt-1 text-base text-slate-600">For per-piece services, enter a whole number.</p>
                    </div>
                </div>
                <p class="rounded-lg bg-slate-50 px-3 py-2 text-base text-slate-800">Line subtotal: <strong class="line-subtotal" aria-live="polite">₱0.00</strong></p>
            </article>
        </template>
        <script src="<?= e(url('assets/js/order_form.js')) ?>" defer></script>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
