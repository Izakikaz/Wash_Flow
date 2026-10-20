<?php

require_once __DIR__ . '/../includes/auth_check.php';

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($order_id === false || $order_id === null || $order_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$order_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, o.status, o.total_amount, o.payment_status,
            o.created_at, o.is_void, o.void_reason,
            c.name AS customer_name, c.phone AS customer_phone, c.address AS customer_address
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

$item_statement = $conn->prepare(
    'SELECT s.name AS service_name, s.pricing_type, oi.quantity,
            oi.unit_price_snapshot, oi.subtotal
     FROM order_items AS oi
     INNER JOIN services AS s ON s.id = oi.service_id
     WHERE oi.order_id = ?
     ORDER BY oi.id'
);
$item_statement->bind_param('i', $order_id);
$item_statement->execute();
$items = $item_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$item_statement->close();

$payment_count_statement = $conn->prepare('SELECT COUNT(*) FROM payments WHERE order_id = ?');
$payment_count_statement->bind_param('i', $order_id);
$payment_count_statement->execute();
$payment_count_statement->bind_result($payment_count);
$payment_count_statement->fetch();
$payment_count_statement->close();

$requested_payment_page = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT);
$requested_payment_page = $requested_payment_page === false || $requested_payment_page === null
    ? 1
    : $requested_payment_page;
if ($requested_payment_page < 1) {
    $requested_payment_page = 1;
}
$payment_pagination = paginate((int) $payment_count, $requested_payment_page, 15);

$payment_total_statement = $conn->prepare(
    'SELECT COALESCE(SUM(amount), 0.00)
     FROM payments
     WHERE order_id = ? AND is_void = 0'
);
$payment_total_statement->bind_param('i', $order_id);
$payment_total_statement->execute();
$payment_total_statement->bind_result($amount_paid);
$payment_total_statement->fetch();
$payment_total_statement->close();

$payment_statement = $conn->prepare(
    'SELECT p.id, p.amount, p.method, p.reference_no, p.paid_at, p.is_void, p.void_reason,
            u.full_name AS received_by_name
     FROM payments AS p
     INNER JOIN users AS u ON u.id = p.received_by
     WHERE p.order_id = ?
     ORDER BY p.paid_at DESC, p.id DESC
     LIMIT ? OFFSET ?'
);
$payment_statement->bind_param(
    'iii',
    $order_id,
    $payment_pagination['per_page'],
    $payment_pagination['offset']
);
$payment_statement->execute();
$payments = $payment_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$payment_statement->close();

$amount_paid = (int) $order['is_void'] === 1 ? 0.0 : round($amount_paid, 2);
$balance_due = (int) $order['is_void'] === 1
    ? 0
    : max(0, round((float) $order['total_amount'] - $amount_paid, 2));
$is_admin = is_admin();
$next = next_status($order['status']);
$status_info = order_statuses();
$status_steps = array_map(
    static fn(array $status_data): string => $status_data['label'],
    $status_info
);
$status_position = array_search($order['status'], array_keys($status_steps), true);
$flash_message = flash_get();
$page_title = 'Order ' . $order['ticket_no'];
$page_breadcrumb = [
    'back_label' => 'Orders',
    'back_url' => url('orders/index.php'),
    'items' => [
        ['label' => 'Orders', 'url' => url('orders/index.php')],
        ['label' => $order['ticket_no']],
    ],
];

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-4xl space-y-6 px-4 py-6 pb-6 sm:px-6 lg:px-8" data-order-view>
    <section class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <p class="text-base font-semibold text-emerald-900">Daily work · Orders</p>
            <div class="mt-1 flex flex-wrap items-center gap-2" data-ticket-copy-container>
                <h1 class="break-all font-mono text-2xl font-bold tracking-[0.12em] text-slate-950"><?= e(format_ticket_no($order['ticket_no'])) ?></h1>
                <button class="inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" type="button" data-copy-ticket="<?= e($order['ticket_no']) ?>">Copy ticket number</button>
                <span class="text-sm text-slate-700" role="status" aria-live="polite" data-copy-status></span>
            </div>
            <p class="mt-2 text-base text-slate-700">Order received <?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></p>
        </div>
        <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 sm:w-auto" href="<?= e(url('orders/ticket.php?id=' . (int) $order['id'])) ?>">View claim ticket</a>
    </section>

    <?php if ($flash_message !== null): ?>
        <?php
        $flash_class = match ($flash_message['type'] ?? '') {
            'success' => 'border-emerald-300 bg-emerald-50 text-emerald-950',
            'warning' => 'border-amber-300 bg-amber-50 text-amber-950',
            'error' => 'border-red-300 bg-red-50 text-red-950',
            default => 'border-slate-300 bg-slate-100 text-slate-900',
        };
        ?>
        <div class="rounded-lg border px-4 py-3 text-base <?= e($flash_class) ?>" role="status" aria-live="polite">
            <p><?= e($flash_message['message'] ?? '') ?></p>
        </div>
    <?php endif; ?>

    <?php if ((int) $order['is_void'] === 1): ?>
        <div class="rounded-lg border border-slate-300 bg-slate-100 px-4 py-3 text-base text-slate-800" role="status">
            <p class="font-semibold">This order is void. It cannot be advanced or claimed.</p>
            <?php if ($order['void_reason'] !== null && $order['void_reason'] !== ''): ?>
                <p class="mt-1">Reason: <?= e($order['void_reason']) ?></p>
            <?php endif; ?>
        </div>
    <?php elseif ($is_admin && $order['status'] !== 'claimed'): ?>
        <details class="rounded-xl border border-red-200 bg-red-50 p-4">
            <summary class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-red-800 px-4 py-2 text-base font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800">Void this order</summary>
            <form class="mt-4 space-y-3" action="<?= e(url('orders/void.php')) ?>" method="post" data-void-form onsubmit="return window.confirm('Void this order and all of its payments? This cannot be undone.');">
                <?= csrf_field() ?>
                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                <div>
                    <label class="mb-1 block text-base font-medium text-slate-800" for="order-void-reason">Reason for voiding</label>
                    <textarea class="w-full min-h-24 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800" id="order-void-reason" name="reason" minlength="5" maxlength="255" required></textarea>
                    <p class="mt-1 text-base text-slate-700">Enter 5 to 255 characters. All payments for this order will also be voided.</p>
                </div>
                <button class="w-full min-h-11 rounded-lg bg-red-800 px-4 py-3 text-base font-semibold text-white hover:bg-red-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 sm:w-auto" type="submit">Confirm void order</button>
            </form>
        </details>
    <?php endif; ?>

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="progress-heading">
        <h2 id="progress-heading" class="mb-5 text-lg font-semibold text-slate-950">Order progress</h2>
        <ol class="space-y-4 md:flex md:items-start md:space-y-0">
            <?php foreach ($status_steps as $step_status => $step_label): ?>
                <?php
                $step_index = array_search($step_status, array_keys($status_steps), true);
                $is_complete = $step_index < $status_position;
                $is_current = $step_status === $order['status'];
                $step_style = $is_current
                    ? 'border-emerald-800 bg-emerald-100 text-emerald-950'
                    : ($is_complete ? 'border-emerald-700 bg-emerald-700 text-white' : 'border-slate-300 bg-white text-slate-600');
                ?>
                <li class="flex items-center gap-3 md:flex-1 md:flex-col md:items-start md:gap-2">
                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full border-2 text-sm font-bold <?= e($step_style) ?>" aria-hidden="true"><?= $is_complete ? '✓' : (int) ($step_index + 1) ?></span>
                    <span class="text-base font-semibold <?= $is_current ? 'text-slate-950' : 'text-slate-700' ?>" <?= $is_current ? 'aria-current="step"' : '' ?>><?= e($step_label) ?><?= $is_current ? ' — Current' : '' ?></span>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php if ($next !== null && (int) $order['is_void'] !== 1): ?>
            <span class="block h-px" aria-hidden="true" data-order-action-sentinel></span>
            <div class="mt-5 border-t border-slate-100 pt-4" data-order-action>
                <?php if ($next === 'claimed' && $balance_due > 0): ?>
                    <p class="text-base font-semibold text-red-900">Balance due: <?= e(format_money($balance_due)) ?></p>
                    <a class="mt-2 inline-flex w-full min-h-12 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 md:w-auto" href="<?= e(url('payments/create.php?order_id=' . (int) $order['id'])) ?>">Record Payment</a>
                <?php else: ?>
                    <form class="w-full" action="<?= e(url('orders/update_status.php')) ?>" method="post" data-status-update>
                        <?= csrf_field() ?>
                        <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                        <input type="hidden" name="next_status" value="<?= e($next) ?>">
                        <button class="w-full min-h-12 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 md:w-auto" type="submit">Advance to <?= e($status_steps[$next]) ?></button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-950">Customer</h2>
            <p class="mt-3 break-words text-lg font-medium text-slate-900"><?= e($order['customer_name']) ?></p>
            <?php if ($order['customer_phone'] !== null && $order['customer_phone'] !== ''): ?>
                <p class="mt-2 text-base text-slate-700"><?= e($order['customer_phone']) ?></p>
            <?php endif; ?>
            <?php if ($order['customer_address'] !== null && $order['customer_address'] !== ''): ?>
                <p class="mt-2 break-words text-base text-slate-700"><?= e($order['customer_address']) ?></p>
            <?php endif; ?>
        </article>

        <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
            <h2 class="text-lg font-semibold text-slate-950">Payment summary</h2>
            <dl class="mt-3 space-y-2 text-base">
                <div class="flex justify-between gap-4"><dt class="text-slate-600">Order total</dt><dd class="font-semibold <?= (int) $order['is_void'] === 1 ? 'text-slate-600 line-through' : '' ?>"><?= e(format_money($order['total_amount'])) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-600">Amount paid</dt><dd class="font-semibold"><?= e(format_money($amount_paid)) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-950">Balance due</dt><dd class="font-bold <?= $balance_due > 0 ? 'text-red-800' : 'text-emerald-900' ?>"><?= e(format_money($balance_due)) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-600">Payment status</dt><dd class="font-semibold"><?= (int) $order['is_void'] === 1 ? 'Voided' : e(ucfirst($order['payment_status'])) ?></dd></div>
            </dl>
        </article>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="items-heading">
        <h2 id="items-heading" class="mb-4 text-lg font-semibold text-slate-950">Services</h2>
        <?php if ($items === []): ?>
            <p class="text-base text-slate-700">No service lines are recorded for this order.</p>
        <?php else: ?>
            <ul class="divide-y divide-slate-200">
                <?php foreach ($items as $item): ?>
                    <li class="flex flex-col gap-1 py-3 first:pt-0 last:pb-0 sm:flex-row sm:items-start sm:justify-between sm:gap-4">
                        <div>
                            <h3 class="break-words text-base font-semibold text-slate-950"><?= e($item['service_name']) ?></h3>
                            <p class="mt-1 text-base text-slate-700"><?= e(number_format((float) $item['quantity'], 2, '.', '')) ?> <?= $item['pricing_type'] === 'per_kg' ? 'kg' : 'piece(s)' ?> × <?= e(format_money($item['unit_price_snapshot'])) ?> <?= $item['pricing_type'] === 'per_kg' ? 'per kg' : 'each' ?></p>
                        </div>
                        <p class="font-semibold <?= (int) $order['is_void'] === 1 ? 'text-slate-600 line-through' : 'text-slate-950' ?>"><?= e(format_money($item['subtotal'])) ?></p>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="payments-heading">
        <h2 id="payments-heading" class="mb-4 text-lg font-semibold text-slate-950">Payment history</h2>
        <?php if ($payments === []): ?>
            <p class="text-base text-slate-700">No payments have been recorded for this order.</p>
        <?php else: ?>
            <ul class="space-y-3">
                <?php foreach ($payments as $payment): ?>
                    <li class="rounded-lg border border-slate-200 p-3 <?= (int) $payment['is_void'] === 1 ? 'bg-slate-50' : '' ?>">
                        <div class="flex flex-wrap justify-between gap-2">
                            <p class="font-semibold text-slate-950">
                                <span class="<?= (int) $payment['is_void'] === 1 ? 'text-slate-600 line-through' : '' ?>"><?= e(format_money($payment['amount'])) ?> · <?= e(ucfirst($payment['method'])) ?></span>
                                <?php if ((int) $payment['is_void'] === 1): ?>
                                    <span class="ml-1 inline-flex rounded-full bg-slate-200 px-2 py-1 text-sm font-semibold text-slate-800">Void</span>
                                <?php endif; ?>
                            </p>
                            <p class="text-base text-slate-700"><?= e(date('M j, Y g:i A', strtotime($payment['paid_at']))) ?></p>
                        </div>
                        <p class="mt-1 text-base text-slate-700">Received by <?= e($payment['received_by_name']) ?></p>
                        <?php if ($payment['reference_no'] !== null && $payment['reference_no'] !== ''): ?>
                            <p class="mt-1 break-words text-base text-slate-700">Reference: <?= e($payment['reference_no']) ?></p>
                        <?php endif; ?>
                        <?php if ((int) $payment['is_void'] === 1 && $payment['void_reason'] !== null && $payment['void_reason'] !== ''): ?>
                            <p class="mt-1 break-words text-base text-slate-700">Void reason: <?= e($payment['void_reason']) ?></p>
                        <?php endif; ?>
                        <a class="mt-2 inline-flex min-h-11 items-center text-base font-semibold text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('payments/receipt.php?id=' . (int) $payment['id'])) ?>">View receipt</a>
                        <?php if ($is_admin && (int) $order['is_void'] !== 1 && $order['status'] !== 'claimed' && (int) $payment['is_void'] === 0): ?>
                            <details class="mt-3 rounded-lg border border-red-200 bg-red-50 p-3">
                                <summary class="inline-flex min-h-11 cursor-pointer items-center rounded-lg bg-red-800 px-3 py-2 text-base font-semibold text-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800">Void payment</summary>
                                <form class="mt-3 space-y-3" action="<?= e(url('payments/void.php')) ?>" method="post" data-void-form onsubmit="return window.confirm('Void this payment? The order balance and payment status will be recalculated.');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                                    <div>
                                        <label class="mb-1 block text-base font-medium text-slate-800" for="payment-void-reason-<?= (int) $payment['id'] ?>">Reason for voiding</label>
                                        <textarea class="w-full min-h-24 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800" id="payment-void-reason-<?= (int) $payment['id'] ?>" name="reason" minlength="5" maxlength="255" required></textarea>
                                    </div>
                                    <button class="w-full min-h-11 rounded-lg bg-red-800 px-4 py-3 text-base font-semibold text-white hover:bg-red-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 sm:w-auto" type="submit">Confirm void payment</button>
                                </form>
                            </details>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>

            <?php if ($payment_pagination['total_pages'] > 1): ?>
                <nav class="mt-4 flex flex-col items-stretch justify-between gap-3 border-t border-slate-200 pt-4 sm:flex-row sm:items-center" aria-label="Payment history pages">
                    <p class="text-base text-slate-700">Page <?= (int) $payment_pagination['page'] ?> of <?= (int) $payment_pagination['total_pages'] ?></p>
                    <div class="flex w-full gap-2 sm:w-auto">
                        <?php if ($payment_pagination['page'] > 1): ?>
                            <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('orders/view.php?' . http_build_query(['id' => $order_id, 'page' => $payment_pagination['page'] - 1]))) ?>">Previous</a>
                        <?php endif; ?>
                        <?php if ($payment_pagination['page'] < $payment_pagination['total_pages']): ?>
                            <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('orders/view.php?' . http_build_query(['id' => $order_id, 'page' => $payment_pagination['page'] + 1]))) ?>">Next</a>
                        <?php endif; ?>
                    </div>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </section>

</div>
<style>
    @media (max-width: 767px) {
        [data-order-action][data-sticky="true"] {
            position: fixed;
            inset-inline: 0;
            bottom: 0;
            z-index: 30;
            padding: .75rem 1rem calc(.75rem + env(safe-area-inset-bottom));
            border-top: 1px solid #e2e8f0;
            background: rgb(255 255 255 / 95%);
            box-shadow: 0 -4px 12px rgb(15 23 42 / 8%);
            -webkit-backdrop-filter: blur(8px);
            backdrop-filter: blur(8px);
        }

        [data-order-view][data-action-sticky="true"] {
            padding-bottom: calc(6rem + env(safe-area-inset-bottom));
        }
    }
</style>
<script>
    const orderAction = document.querySelector('[data-order-action]');
    const orderActionSentinel = document.querySelector('[data-order-action-sentinel]');
    const orderView = document.querySelector('[data-order-view]');
    const mobileViewport = window.matchMedia('(max-width: 767px)');

    if (orderAction && orderActionSentinel && orderView) {
        let actionObserver;

        function observeOrderAction() {
            if (actionObserver) {
                actionObserver.disconnect();
            }

            if (!mobileViewport.matches) {
                orderAction.dataset.sticky = 'false';
                orderView.dataset.actionSticky = 'false';
                return;
            }

            actionObserver = new IntersectionObserver(function (entries) {
                const sentinel = entries[0];
                const shouldStick = !sentinel.isIntersecting && sentinel.boundingClientRect.top < 0;
                orderAction.dataset.sticky = shouldStick ? 'true' : 'false';
                orderView.dataset.actionSticky = shouldStick ? 'true' : 'false';
            });
            actionObserver.observe(orderActionSentinel);
        }

        observeOrderAction();
        mobileViewport.addEventListener('change', observeOrderAction);
    }

    document.querySelectorAll('[data-status-update]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitted = 'true';
            form.querySelector('button[type="submit"]').disabled = true;
        });
    });

    document.querySelectorAll('[data-void-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) {
                return;
            }

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
