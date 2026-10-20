<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_role('admin');

$today = date('Y-m-d');
$date_value = isset($_GET['date']) && is_string($_GET['date'])
    ? $_GET['date']
    : $today;
$date_object = DateTimeImmutable::createFromFormat('!Y-m-d', $date_value);
$date_errors = DateTimeImmutable::getLastErrors();
$valid_date = $date_object !== false
    && $date_object->format('Y-m-d') === $date_value
    && ($date_errors === false || ($date_errors['warning_count'] === 0 && $date_errors['error_count'] === 0));
$invalid_date = !$valid_date;
if ($invalid_date) {
    $date_value = $today;
    $date_object = DateTimeImmutable::createFromFormat('!Y-m-d', $today);
}
$date_start = $date_object->format('Y-m-d 00:00:00');
$date_end = $date_object->modify('+1 day')->format('Y-m-d 00:00:00');

$summary_statement = $conn->prepare(
    'SELECT COUNT(*), COALESCE(SUM(p.amount), 0.00)
     FROM payments AS p
     INNER JOIN orders AS o ON o.id = p.order_id
     WHERE p.is_void = 0 AND o.is_void = 0
       AND p.paid_at >= ? AND p.paid_at < ?'
);
$summary_statement->bind_param('ss', $date_start, $date_end);
$summary_statement->execute();
$summary_statement->bind_result($payment_count, $sales_total);
$summary_statement->fetch();
$summary_statement->close();

$new_orders_statement = $conn->prepare(
    'SELECT COUNT(*)
     FROM orders
     WHERE is_void = 0 AND created_at >= ? AND created_at < ?'
);
$new_orders_statement->bind_param('ss', $date_start, $date_end);
$new_orders_statement->execute();
$new_orders_statement->bind_result($new_order_count);
$new_orders_statement->fetch();
$new_orders_statement->close();

$total_rows = (int) $payment_count;

$requested_page = requested_page_number();
$pagination = paginate((int) $total_rows, $requested_page, 15);

$list_statement = $conn->prepare(
    'SELECT p.id, p.paid_at, p.amount, p.method, p.reference_no,
            o.id AS order_id, o.ticket_no, c.name AS customer_name
     FROM payments AS p
     INNER JOIN orders AS o ON o.id = p.order_id
     INNER JOIN customers AS c ON c.id = o.customer_id
     WHERE p.is_void = 0 AND o.is_void = 0
       AND p.paid_at >= ? AND p.paid_at < ?
     ORDER BY p.paid_at DESC, p.id DESC
     LIMIT ? OFFSET ?'
);
$list_statement->bind_param(
    'ssii',
    $date_start,
    $date_end,
    $pagination['per_page'],
    $pagination['offset']
);
$list_statement->execute();
$payments = $list_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$list_statement->close();

$page_title = 'Daily sales';
$report_print_title = 'Daily sales';
$report_print_range = 'Date: ' . date('F j, Y', strtotime($date_value));
$page_breadcrumb = [
    'back_label' => 'Reports',
    'back_url' => url('reports/dashboard.php'),
    'items' => [
        ['label' => 'Reports', 'url' => url('reports/dashboard.php')],
        ['label' => 'Daily sales'],
    ],
];
require __DIR__ . '/../includes/header.php';
?>
<div class="report-print-outer mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="report-print-page">
    <?php require __DIR__ . '/_print_controls.php'; ?>
    <div class="report-navigation print:hidden"><?php require __DIR__ . '/_navigation.php'; ?></div>

    <section class="space-y-4" aria-labelledby="daily-sales-heading">
        <div class="report-screen-only">
            <p class="text-base font-semibold text-emerald-900">Admin</p>
            <h1 id="daily-sales-heading" class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Daily sales</h1>
            <p class="mt-2 text-base text-slate-700">Payments received and orders opened on the selected date.</p>
        </div>

        <?php if ($invalid_date): ?>
            <p class="report-screen-only rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-base text-amber-950" role="status">That date was not valid. Showing today instead.</p>
        <?php endif; ?>

        <form action="<?= e(url('reports/daily_sales.php')) ?>" method="get" class="report-screen-only flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-end">
            <div class="w-full sm:max-w-xs">
                <label class="mb-1 block text-base font-medium text-slate-800" for="sales-date">Sales date</label>
                <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="sales-date" name="date" type="date" value="<?= e($date_value) ?>" required>
            </div>
            <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Show sales</button>
        </form>
    </section>

    <section aria-labelledby="summary-heading">
        <h2 id="summary-heading" class="mb-3 text-lg font-semibold text-slate-950">Summary for <?= e(date('F j, Y', strtotime($date_value))) ?></h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-base font-semibold text-slate-700">Sales received</h3>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= e(format_money($sales_total)) ?></p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-base font-semibold text-slate-700">Payments received</h3>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= (int) $payment_count ?></p>
            </article>
            <article class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <h3 class="text-base font-semibold text-slate-700">New orders</h3>
                <p class="mt-2 text-2xl font-bold text-slate-950"><?= (int) $new_order_count ?></p>
            </article>
        </div>
    </section>

    <section class="space-y-3" aria-labelledby="payments-heading">
        <div>
            <h2 id="payments-heading" class="text-xl font-bold text-slate-950">Payments received</h2>
            <p class="mt-1 text-base text-slate-700"><?= (int) $total_rows ?> non-voided payments on <?= e(date('F j, Y', strtotime($date_value))) ?>.</p>
        </div>

        <?php if ($payments === []): ?>
            <div class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
                <h3 class="text-lg font-semibold text-slate-950">No records</h3>
                <p class="mt-2 text-base text-slate-700">There were no non-voided payments on this date.</p>
            </div>
        <?php else: ?>
            <ul class="report-mobile-cards space-y-3 md:hidden" aria-label="Payments received">
                <?php foreach ($payments as $payment): ?>
                    <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                        <a class="inline-flex min-h-11 items-center break-all font-mono text-sm font-semibold text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('orders/view.php?id=' . (int) $payment['order_id'])) ?>"><?= e($payment['ticket_no']) ?></a>
                        <p class="mt-1 text-base text-slate-800"><?= e($payment['customer_name']) ?></p>
                        <dl class="mt-3 grid grid-cols-2 gap-3 text-base">
                            <div><dt class="text-slate-600">Time</dt><dd class="font-medium text-slate-900"><?= e(date('g:i A', strtotime($payment['paid_at']))) ?></dd></div>
                            <div><dt class="text-slate-600">Method</dt><dd class="font-medium text-slate-900"><?= e(match ($payment['method']) {
                                'cash' => 'Cash',
                                'gcash' => 'GCash',
                                'other' => 'Other',
                                default => 'Other',
                            }) ?></dd></div>
                            <div class="col-span-2"><dt class="text-slate-600">Amount</dt><dd class="font-semibold text-slate-950"><?= e(format_money($payment['amount'])) ?></dd></div>
                        </dl>
                    </li>
                <?php endforeach; ?>
            </ul>

            <div class="report-table-wrap overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                <table class="report-print-table hidden w-full border-collapse text-left text-base md:table">
                    <thead class="bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-700">
                        <tr>
                            <th class="px-4 py-3" scope="col">Time</th>
                            <th class="px-4 py-3" scope="col">Ticket</th>
                            <th class="px-4 py-3" scope="col">Customer</th>
                            <th class="px-4 py-3" scope="col">Method</th>
                            <th class="px-4 py-3 text-right" scope="col">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 text-slate-800">
                        <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td class="px-4 py-3"><?= e(date('g:i A', strtotime($payment['paid_at']))) ?></td>
                                <td class="px-4 py-3 font-mono text-sm font-semibold"><a class="inline-flex min-h-11 items-center underline text-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('orders/view.php?id=' . (int) $payment['order_id'])) ?>"><?= e($payment['ticket_no']) ?></a></td>
                                <td class="px-4 py-3"><?= e($payment['customer_name']) ?></td>
                                <td class="px-4 py-3"><?= e(match ($payment['method']) {
                                    'cash' => 'Cash',
                                    'gcash' => 'GCash',
                                    'other' => 'Other',
                                    default => 'Other',
                                }) ?></td>
                                <td class="px-4 py-3 text-right font-semibold"><?= e(format_money($payment['amount'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <nav class="report-screen-only flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center" aria-label="Daily sales pages">
                <p class="text-base text-slate-700">Page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['total_pages'] ?></p>
                <div class="flex w-full gap-2 sm:w-auto">
                    <?php if ($pagination['page'] > 1): ?>
                        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('reports/daily_sales.php?' . http_build_query(['date' => $date_value, 'page' => $pagination['page'] - 1]))) ?>">Previous</a>
                    <?php endif; ?>
                    <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                        <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('reports/daily_sales.php?' . http_build_query(['date' => $date_value, 'page' => $pagination['page'] + 1]))) ?>">Next</a>
                    <?php endif; ?>
                </div>
            </nav>
        <?php endif; ?>
    </section>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
