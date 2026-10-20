<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$today_start = date('Y-m-d 00:00:00');
$tomorrow_start = date('Y-m-d 00:00:00', strtotime('+1 day'));
$ready_cutoff = date('Y-m-d H:i:s', time() - (3 * 24 * 60 * 60));
$unpaid_cutoff = date('Y-m-d H:i:s', time() - (7 * 24 * 60 * 60));

$sales_statement = $conn->prepare(
    'SELECT COALESCE(SUM(p.amount), 0.00)
     FROM payments AS p
     INNER JOIN orders AS o ON o.id = p.order_id
     WHERE p.is_void = 0 AND o.is_void = 0
       AND p.paid_at >= ? AND p.paid_at < ?'
);
$sales_statement->bind_param('ss', $today_start, $tomorrow_start);
$sales_statement->execute();
$sales_statement->bind_result($today_sales);
$sales_statement->fetch();
$sales_statement->close();

$balance_statement = $conn->prepare(
    'SELECT COALESCE(SUM(GREATEST(o.total_amount - COALESCE(paid.amount_paid, 0.00), 0.00)), 0.00)
     FROM orders AS o
     LEFT JOIN (
         SELECT order_id, SUM(amount) AS amount_paid
         FROM payments
         WHERE is_void = 0
         GROUP BY order_id
     ) AS paid ON paid.order_id = o.id
     WHERE o.is_void = 0'
);
$balance_statement->execute();
$balance_statement->bind_result($outstanding_balance);
$balance_statement->fetch();
$balance_statement->close();

$status_info = order_statuses();
$status_counts = array_fill_keys(array_keys($status_info), 0);
$status_statement = $conn->prepare(
    'SELECT status, COUNT(*) AS order_count
     FROM orders
     WHERE is_void = 0
     GROUP BY status'
);
$status_statement->execute();
$status_result = $status_statement->get_result();
while ($status_row = $status_result->fetch_assoc()) {
    if (array_key_exists($status_row['status'], $status_counts)) {
        $status_counts[$status_row['status']] = (int) $status_row['order_count'];
    }
}
$status_statement->close();
$pending_pickup = $status_counts['ready'];

$ready_statement = $conn->prepare(
    'SELECT id, ticket_no, customer_name, ready_at
     FROM (
         SELECT o.id, o.ticket_no, c.name AS customer_name, o.ready_at
         FROM orders AS o
         INNER JOIN customers AS c ON c.id = o.customer_id
         WHERE o.is_void = 0 AND o.status = \'ready\' AND o.ready_at < ?
         ORDER BY o.ready_at
         LIMIT 5
     ) AS ready_orders'
);
$ready_statement->bind_param('s', $ready_cutoff);
$ready_statement->execute();
$ready_attention = $ready_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$ready_statement->close();

$unpaid_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, c.name AS customer_name,
            GREATEST(o.total_amount - COALESCE(paid.amount_paid, 0.00), 0.00) AS balance_due,
            o.created_at
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     LEFT JOIN (
         SELECT order_id, SUM(amount) AS amount_paid
         FROM payments
         WHERE is_void = 0
         GROUP BY order_id
     ) AS paid ON paid.order_id = o.id
     WHERE o.is_void = 0 AND o.created_at < ?
       AND o.total_amount - COALESCE(paid.amount_paid, 0.00) > 0
     ORDER BY o.created_at
     LIMIT 5'
);
$unpaid_statement->bind_param('s', $unpaid_cutoff);
$unpaid_statement->execute();
$unpaid_attention = $unpaid_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$unpaid_statement->close();

$voided_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, c.name AS customer_name, o.voided_at, o.void_reason
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     WHERE o.is_void = 1 AND o.voided_at >= ? AND o.voided_at < ?
     ORDER BY o.voided_at DESC, o.id DESC
     LIMIT 5'
);
$voided_statement->bind_param('ss', $today_start, $tomorrow_start);
$voided_statement->execute();
$voided_attention = $voided_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$voided_statement->close();

$requested_page = requested_page_number();

$recent_count_statement = $conn->prepare("SELECT COUNT(*) FROM orders WHERE is_void = 0 AND status <> 'claimed'");
$recent_count_statement->execute();
$recent_count_statement->bind_result($recent_order_count);
$recent_count_statement->fetch();
$recent_count_statement->close();
$recent_pagination = paginate((int) $recent_order_count, $requested_page, 15);
$recent_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, o.status, o.total_amount, o.created_at,
            c.name AS customer_name,
            GREATEST(o.total_amount - COALESCE(paid.amount_paid, 0.00), 0.00) AS balance_due
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     LEFT JOIN (
         SELECT order_id, SUM(amount) AS amount_paid
         FROM payments
         WHERE is_void = 0
         GROUP BY order_id
     ) AS paid ON paid.order_id = o.id
     WHERE o.is_void = 0 AND o.status <> \'claimed\'
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT ? OFFSET ?'
);
$recent_statement->bind_param('ii', $recent_pagination['per_page'], $recent_pagination['offset']);
$recent_statement->execute();
$recent_orders = $recent_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$recent_statement->close();

$page_title = 'Dashboard';
$report_print_title = 'Dashboard';
$report_print_range = 'As of ' . date('F j, Y');
$flash_message = flash_get();
$attention_groups = [
    [
        'title' => 'Ready for pickup more than 3 days',
        'empty' => 'No orders have been waiting this long.',
        'orders' => $ready_attention,
        'kind' => 'ready',
    ],
    [
        'title' => 'Balance due for more than 7 days',
        'empty' => 'No older unpaid balances need attention.',
        'orders' => $unpaid_attention,
        'kind' => 'balance',
    ],
    [
        'title' => 'Orders voided today',
        'empty' => 'No orders have been voided today.',
        'orders' => $voided_attention,
        'kind' => 'void',
    ],
];

require __DIR__ . '/../includes/header.php';
?>
<div class="report-print-outer mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="report-print-page">
    <?php require __DIR__ . '/_print_controls.php'; ?>
    <div class="report-navigation print:hidden"><?php require __DIR__ . '/_navigation.php'; ?></div>

    <section class="space-y-4" aria-labelledby="dashboard-heading">
        <div class="report-screen-only">
            <p class="text-base font-semibold text-emerald-900">Admin</p>
            <h1 id="dashboard-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Dashboard</h1>
            <p class="mt-2 text-base text-slate-700">A quick look at today's work and what needs attention.</p>
        </div>

        <?php if ($flash_message !== null): ?>
            <p class="report-screen-only rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-base text-emerald-950" role="status" aria-live="polite"><?= e($flash_message['message'] ?? '') ?></p>
        <?php endif; ?>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-semibold text-slate-700">Sales received today</h2>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= e(format_money($today_sales)) ?></p>
            </article>
            <a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('reports/outstanding_balances.php')) ?>">
                <h2 class="text-base font-semibold text-slate-700">Outstanding balance</h2>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= e(format_money($outstanding_balance)) ?></p>
                <p class="mt-1 text-base font-semibold text-emerald-900 underline">View unpaid orders</p>
            </a>
            <a class="block rounded-xl border border-slate-200 bg-white p-4 shadow-sm hover:border-emerald-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('reports/pending_pickup.php')) ?>">
                <h2 class="text-base font-semibold text-slate-700">Ready for pickup</h2>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= (int) $pending_pickup ?></p>
                <p class="mt-1 text-base font-semibold text-emerald-900 underline">View pending pickup</p>
            </a>
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h2 class="text-base font-semibold text-slate-700">Active orders</h2>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= (int) $recent_order_count ?></p>
            </article>
        </div>
    </section>

    <section aria-labelledby="status-counts-heading">
        <h2 id="status-counts-heading" class="mb-3 text-lg font-semibold text-slate-950">Orders by status</h2>
        <ul class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <?php foreach ($status_info as $status => $status_data): ?>
                <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <span class="inline-flex min-h-8 items-center rounded-full px-3 py-1 text-sm font-semibold <?= e($status_data['badge']) ?>"><?= e($status_data['label']) ?></span>
                    <p class="mt-2 text-2xl font-bold text-slate-950"><?= (int) $status_counts[$status] ?></p>
                    <p class="text-base text-slate-700">active orders</p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="space-y-4" aria-labelledby="attention-heading">
        <div>
            <h2 id="attention-heading" class="text-xl font-bold text-slate-950">Needs attention</h2>
            <p class="mt-1 text-base text-slate-700">These items may need a follow-up today.</p>
        </div>
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <?php foreach ($attention_groups as $group): ?>
                <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h3 class="text-lg font-semibold text-slate-950"><?= e($group['title']) ?></h3>
                    <?php if ($group['orders'] === []): ?>
                        <p class="mt-3 text-base text-slate-700"><?= e($group['empty']) ?></p>
                    <?php else: ?>
                        <ul class="mt-3 divide-y divide-slate-200">
                            <?php foreach ($group['orders'] as $attention_order): ?>
                                <li class="py-3 first:pt-0 last:pb-0">
                                    <a class="inline-flex min-h-11 w-full flex-col justify-center rounded focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('orders/view.php?id=' . (int) $attention_order['id'])) ?>">
                                        <span class="break-all font-mono text-sm font-semibold text-emerald-900 underline"><?= e($attention_order['ticket_no']) ?></span>
                                        <span class="mt-1 text-base text-slate-800"><?= e($attention_order['customer_name']) ?></span>
                                        <?php if ($group['kind'] === 'ready'): ?>
                                            <span class="text-base text-slate-700">Ready since <?= e(date('M j, Y', strtotime($attention_order['ready_at']))) ?></span>
                                        <?php elseif ($group['kind'] === 'balance'): ?>
                                            <span class="text-base font-semibold text-red-900">Balance due: <?= e(format_money($attention_order['balance_due'])) ?></span>
                                        <?php else: ?>
                                            <span class="text-base text-slate-700">Voided <?= e(date('g:i A', strtotime($attention_order['voided_at']))) ?><?= $attention_order['void_reason'] !== null && $attention_order['void_reason'] !== '' ? ' · ' . e($attention_order['void_reason']) : '' ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </article>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="report-screen-only" aria-labelledby="quick-actions-heading">
        <h2 id="quick-actions-heading" class="mb-3 text-lg font-semibold text-slate-950">Quick actions</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
            <a class="inline-flex w-full min-h-12 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('orders/create.php')) ?>">New Order</a>
            <a class="inline-flex w-full min-h-12 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('orders/index.php')) ?>">Record Payment</a>
            <a class="inline-flex w-full min-h-12 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('services/create.php')) ?>">Add Service</a>
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="recent-orders-heading">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 id="recent-orders-heading" class="text-xl font-bold text-slate-950">Recent orders</h2>
                <p class="mt-1 text-base text-slate-700">Active orders, newest first.</p>
            </div>
            <a class="report-screen-only inline-flex min-h-11 items-center font-semibold text-emerald-900 underline" href="<?= e(url('orders/index.php')) ?>">View all orders</a>
        </div>

        <?php if ($recent_orders === []): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                <p class="text-base text-slate-700">No active orders yet.</p>
            <a class="report-screen-only mt-3 inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-2 text-base font-semibold text-white" href="<?= e(url('orders/create.php')) ?>">Create the first order</a>
            </div>
        <?php else: ?>
            <ul class="report-mobile-cards space-y-3 md:hidden" aria-label="Recent orders">
                <?php foreach ($recent_orders as $recent_order): ?>
                    <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <h3 class="break-all font-mono text-sm font-semibold text-slate-950"><?= e($recent_order['ticket_no']) ?></h3>
                        <p class="mt-1 text-base text-slate-800"><?= e($recent_order['customer_name']) ?></p>
                        <div class="mt-2 flex flex-wrap items-center justify-between gap-2">
                            <span class="inline-flex min-h-8 items-center rounded-full px-3 py-1 text-sm font-semibold <?= e($status_info[$recent_order['status']]['badge']) ?>"><?= e($status_info[$recent_order['status']]['label']) ?></span>
                            <span class="text-base font-semibold text-slate-950">Balance <?= e(format_money($recent_order['balance_due'])) ?></span>
                        </div>
                        <a class="mt-3 inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-base font-semibold text-slate-800" href="<?= e(url('orders/view.php?id=' . (int) $recent_order['id'])) ?>">View order</a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="report-table-wrap overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="report-print-table hidden w-full border-collapse text-left text-base md:table">
                    <thead class="bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3" scope="col">Ticket</th>
                            <th class="px-4 py-3" scope="col">Customer</th>
                            <th class="px-4 py-3" scope="col">Status</th>
                            <th class="px-4 py-3 text-right" scope="col">Balance due</th>
                            <th class="report-print-hide px-4 py-3" scope="col"><span class="sr-only">Action</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        <?php foreach ($recent_orders as $recent_order): ?>
                            <tr>
                                <td class="px-4 py-3 font-mono text-sm font-semibold"><?= e($recent_order['ticket_no']) ?></td>
                                <td class="px-4 py-3"><?= e($recent_order['customer_name']) ?></td>
                                <td class="px-4 py-3"><span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold <?= e($status_info[$recent_order['status']]['badge']) ?>"><?= e($status_info[$recent_order['status']]['label']) ?></span></td>
                                <td class="px-4 py-3 text-right font-semibold"><?= e(format_money($recent_order['balance_due'])) ?></td>
                                <td class="report-print-hide px-4 py-3 text-right"><a class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 font-semibold" href="<?= e(url('orders/view.php?id=' . (int) $recent_order['id'])) ?>">View</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="report-screen-only flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center" aria-label="Recent order pages">
                <p class="text-base text-slate-700">Page <?= (int) $recent_pagination['page'] ?> of <?= (int) $recent_pagination['total_pages'] ?></p>
                <div class="flex w-full gap-2 sm:w-auto">
                    <?php if ($recent_pagination['page'] > 1): ?>
                        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('reports/dashboard.php?page=' . ($recent_pagination['page'] - 1))) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ($recent_pagination['page'] < $recent_pagination['total_pages']): ?>
                        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('reports/dashboard.php?page=' . ($recent_pagination['page'] + 1))) ?>">Next</a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
