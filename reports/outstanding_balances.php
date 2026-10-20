<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$summary_statement = $conn->prepare(
    'SELECT COUNT(*), COALESCE(SUM(balance_due), 0.00)
     FROM (
         SELECT o.id,
                GREATEST(o.total_amount - COALESCE(SUM(p.amount), 0.00), 0.00) AS balance_due
         FROM orders AS o
         LEFT JOIN payments AS p ON p.order_id = o.id AND p.is_void = 0
         WHERE o.is_void = 0
           AND o.payment_status IN (\'unpaid\', \'partial\')
         GROUP BY o.id, o.total_amount
         HAVING balance_due > 0
     ) AS outstanding_orders'
);
$summary_statement->execute();
$summary_statement->bind_result($total_orders, $total_outstanding);
$summary_statement->fetch();
$summary_statement->close();

$requested_page = requested_page_number();
$pagination = paginate((int) $total_orders, $requested_page, 15);

$orders_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, o.total_amount, o.payment_status, o.created_at,
            c.name AS customer_name, c.phone, c.phone_digits,
            COALESCE(SUM(p.amount), 0.00) AS amount_paid,
            GREATEST(o.total_amount - COALESCE(SUM(p.amount), 0.00), 0.00) AS balance_due,
            DATEDIFF(CURDATE(), o.created_at) AS days_since_order
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     LEFT JOIN payments AS p ON p.order_id = o.id AND p.is_void = 0
     WHERE o.is_void = 0
       AND o.payment_status IN (\'unpaid\', \'partial\')
     GROUP BY o.id, o.ticket_no, o.total_amount, o.payment_status, o.created_at,
              c.name, c.phone, c.phone_digits
     HAVING balance_due > 0
     ORDER BY o.created_at ASC, o.id ASC
     LIMIT ? OFFSET ?'
);
$orders_statement->bind_param('ii', $pagination['per_page'], $pagination['offset']);
$orders_statement->execute();
$outstanding_orders = $orders_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$orders_statement->close();

$page_title = 'Outstanding balances';
$report_print_title = 'Outstanding balances';
$report_print_range = 'As of ' . date('F j, Y');
$page_breadcrumb = [
    'back_label' => 'Reports',
    'back_url' => url('reports/dashboard.php'),
    'items' => [
        ['label' => 'Reports', 'url' => url('reports/dashboard.php')],
        ['label' => 'Outstanding balances'],
    ],
];
require __DIR__ . '/../includes/header.php';
?>
<div class="report-print-outer mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="report-print-page">
    <?php require __DIR__ . '/_print_controls.php'; ?>
    <div class="report-navigation print:hidden"><?php require __DIR__ . '/_navigation.php'; ?></div>

    <section class="report-screen-only space-y-2" aria-labelledby="outstanding-balances-heading">
        <p class="text-base font-semibold text-emerald-900">Admin</p>
        <h1 id="outstanding-balances-heading" class="text-2xl font-bold tracking-tight text-slate-950">Outstanding balances</h1>
        <p class="text-base text-slate-700">Orders with an unpaid or partial balance, oldest first.</p>
    </section>

    <section aria-label="Total outstanding amount">
        <article class="rounded-xl border border-emerald-200 bg-emerald-50 p-5 shadow-sm">
            <h2 class="text-base font-semibold text-emerald-950">Total outstanding</h2>
            <p class="mt-2 text-3xl font-bold tracking-tight text-emerald-950"><?= e(format_money($total_outstanding)) ?></p>
            <p class="mt-1 text-base text-emerald-900">Across <?= (int) $total_orders ?> orders with a balance due.</p>
        </article>
    </section>

    <section aria-labelledby="outstanding-orders-heading" class="space-y-3">
        <h2 id="outstanding-orders-heading" class="text-xl font-bold text-slate-950">Orders with a balance due</h2>

        <?php if ($outstanding_orders === []): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">No outstanding balances</h3>
                <p class="mt-2 text-base text-slate-700">All active orders are fully paid.</p>
            </div>
        <?php else: ?>
            <ul class="report-mobile-cards space-y-3 md:hidden" aria-label="Orders with outstanding balances">
                <?php foreach ($outstanding_orders as $order): ?>
                    <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <a class="inline-flex min-h-11 break-all items-center font-mono text-sm font-semibold text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('orders/view.php?id=' . (int) $order['id'])) ?>"><?= e($order['ticket_no']) ?></a>
                        <p class="mt-1 text-base font-medium text-slate-900"><?= e($order['customer_name']) ?></p>
                        <?php if ($order['phone'] !== null && $order['phone'] !== '' && $order['phone_digits'] !== null && $order['phone_digits'] !== ''): ?>
                            <a class="mt-1 inline-flex min-h-11 items-center text-base text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e('tel:' . $order['phone_digits']) ?>"><?= e($order['phone']) ?></a>
                        <?php else: ?>
                            <p class="mt-1 min-h-11 content-center text-base text-slate-600">No phone number</p>
                        <?php endif; ?>
                        <dl class="mt-3 grid grid-cols-2 gap-3 text-base">
                            <div>
                                <dt class="text-slate-600">Total</dt>
                                <dd class="font-medium text-slate-900"><?= e(format_money($order['total_amount'])) ?></dd>
                            </div>
                            <div>
                                <dt class="text-slate-600">Paid</dt>
                                <dd class="font-medium text-slate-900"><?= e(format_money($order['amount_paid'])) ?></dd>
                            </div>
                            <div>
                                <dt class="text-slate-600">Balance</dt>
                                <dd class="font-semibold text-red-900"><?= e(format_money($order['balance_due'])) ?></dd>
                            </div>
                            <div>
                                <dt class="text-slate-600">Order age</dt>
                                <dd class="font-medium text-slate-900"><?= (int) $order['days_since_order'] ?> <?= (int) $order['days_since_order'] === 1 ? 'day' : 'days' ?></dd>
                            </div>
                        </dl>
                        <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('payments/create.php?order_id=' . (int) $order['id'])) ?>">Record Payment</a>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="report-table-wrap hidden max-w-full overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm md:block" tabindex="0" role="region" aria-label="Outstanding balance table. Scroll horizontally to view all columns.">
                <table class="report-print-table hidden w-full border-collapse text-left text-base md:table">
                    <thead class="bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3" scope="col">Ticket</th>
                            <th class="px-4 py-3" scope="col">Customer</th>
                            <th class="px-4 py-3" scope="col">Phone</th>
                            <th class="px-4 py-3 text-right" scope="col">Total</th>
                            <th class="px-4 py-3 text-right" scope="col">Paid</th>
                            <th class="px-4 py-3 text-right" scope="col">Balance</th>
                            <th class="px-4 py-3 text-right" scope="col">Days since order</th>
                            <th class="report-print-hide px-4 py-3 text-right" scope="col">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        <?php foreach ($outstanding_orders as $order): ?>
                            <tr>
                                <td class="px-4 py-3 font-mono text-sm font-semibold">
                                    <a class="inline-flex min-h-11 items-center text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('orders/view.php?id=' . (int) $order['id'])) ?>"><?= e($order['ticket_no']) ?></a>
                                </td>
                                <td class="px-4 py-3"><?= e($order['customer_name']) ?></td>
                                <td class="px-4 py-3">
                                    <?php if ($order['phone'] !== null && $order['phone'] !== '' && $order['phone_digits'] !== null && $order['phone_digits'] !== ''): ?>
                                        <a class="inline-flex min-h-11 items-center text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e('tel:' . $order['phone_digits']) ?>"><?= e($order['phone']) ?></a>
                                    <?php else: ?>
                                        <span class="text-slate-600">No phone number</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right"><?= e(format_money($order['total_amount'])) ?></td>
                                <td class="px-4 py-3 text-right"><?= e(format_money($order['amount_paid'])) ?></td>
                                <td class="px-4 py-3 text-right font-semibold text-red-900"><?= e(format_money($order['balance_due'])) ?></td>
                                <td class="px-4 py-3 text-right"><?= (int) $order['days_since_order'] ?></td>
                                <td class="report-print-hide px-4 py-3 text-right">
                                    <a class="inline-flex min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-2 font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('payments/create.php?order_id=' . (int) $order['id'])) ?>">Record Payment</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="report-screen-only flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center" aria-label="Outstanding balance pages">
                <p class="text-base text-slate-700">Page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['total_pages'] ?></p>
                <div class="flex w-full gap-2 sm:w-auto">
                    <?php if ($pagination['page'] > 1): ?>
                        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('reports/outstanding_balances.php?' . http_build_query(['page' => $pagination['page'] - 1]))) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('reports/outstanding_balances.php?' . http_build_query(['page' => $pagination['page'] + 1]))) ?>">Next</a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
