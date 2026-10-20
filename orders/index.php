<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Orders';
$is_admin = is_admin();
$show_voided = $is_admin
    && isset($_GET['show_voided'])
    && is_string($_GET['show_voided'])
    && $_GET['show_voided'] === '1';
$status_info = order_statuses();
$status_filter = isset($_GET['status']) && is_string($_GET['status'])
    ? $_GET['status']
    : 'active';
$valid_filters = array_merge(['active', 'all'], array_keys($status_info));
$invalid_status_filter = !in_array($status_filter, $valid_filters, true);
if ($invalid_status_filter) {
    $status_filter = 'active';
}
$search_term = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$requested_page = requested_page_number();

$today_counts = array_fill_keys(array_keys($status_info), 0);
$today_statement = $conn->prepare(
    'SELECT status, COUNT(*) AS order_count
     FROM orders
     WHERE is_void = 0 AND DATE(created_at) = CURDATE()
     GROUP BY status'
);
$today_statement->execute();
$today_result = $today_statement->get_result();
while ($today_row = $today_result->fetch_assoc()) {
    $today_counts[$today_row['status']] = (int) $today_row['order_count'];
}
$today_statement->close();

$where_parts = $show_voided ? ['1 = 1'] : ['o.is_void = 0'];
$query_types = '';
$query_values = [];

if ($status_filter === 'active') {
    $where_parts[] = "o.status <> 'claimed'";
} elseif (in_array($status_filter, array_keys($status_info), true)) {
    $where_parts[] = 'o.status = ?';
    $query_types .= 's';
    $query_values[] = $status_filter;
}

if ($search_term !== '') {
    $where_parts[] = '(o.ticket_no LIKE ? OR c.name LIKE ? OR c.phone LIKE ? OR c.phone_digits LIKE ?)';
    $search_pattern = '%' . $search_term . '%';
    $query_types .= 'ssss';
    $query_values[] = $search_pattern;
    $query_values[] = $search_pattern;
    $query_values[] = $search_pattern;
    $query_values[] = $search_pattern;
}

$where_sql = implode(' AND ', $where_parts);
$count_statement = $conn->prepare(
    'SELECT COUNT(*)
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     WHERE ' . $where_sql
);
if ($query_types !== '') {
    $count_bind_values = [$query_types];
    foreach ($query_values as $index => $value) {
        $count_bind_values[] = &$query_values[$index];
    }
    call_user_func_array([$count_statement, 'bind_param'], $count_bind_values);
}
$count_statement->execute();
$count_statement->bind_result($total_orders);
$count_statement->fetch();
$count_statement->close();

$pagination = paginate((int) $total_orders, $requested_page, 15);
$list_types = $query_types . 'ii';
$list_values = $query_values;
$list_values[] = $pagination['per_page'];
$list_values[] = $pagination['offset'];
$list_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, o.status, o.total_amount, o.payment_status,
            o.created_at, o.is_void, c.name AS customer_name
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     WHERE ' . $where_sql . '
     ORDER BY o.created_at DESC, o.id DESC
     LIMIT ? OFFSET ?'
);
$list_bind_values = [$list_types];
foreach ($list_values as $index => $value) {
    $list_bind_values[] = &$list_values[$index];
}
call_user_func_array([$list_statement, 'bind_param'], $list_bind_values);
$list_statement->execute();
$orders = $list_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$list_statement->close();

$filter_labels = ['active' => 'Active', 'all' => 'All'];
foreach ($status_info as $status => $status_data) {
    $filter_labels[$status] = $status_data['label'];
}
$pagination_query = http_build_query(array_filter([
    'q' => $search_term,
    'status' => $status_filter === 'active' ? null : $status_filter,
    'show_voided' => $show_voided ? '1' : null,
], static fn($value) => $value !== null && $value !== ''));

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="space-y-4">
        <div>
            <p class="text-base font-semibold text-emerald-900">Daily work</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Orders</h1>
            <p class="mt-2 text-base text-slate-700">Find an order, check its progress, or start a new one.</p>
        </div>
        <a class="inline-flex w-full min-h-14 items-center justify-center rounded-xl bg-emerald-800 px-5 py-3 text-lg font-semibold text-white shadow-sm hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('orders/create.php')) ?>">+ New Order</a>
    </section>

    <section aria-labelledby="today-heading">
        <h2 id="today-heading" class="mb-3 text-lg font-semibold text-slate-950">Today at a glance</h2>
        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5">
            <?php foreach ($status_info as $status => $status_data): ?>
                <li class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold <?= e($status_data['badge']) ?>"><?= e($status_data['label']) ?></span>
                    <p class="mt-2 text-2xl font-bold text-slate-950"><?= (int) $today_counts[$status] ?></p>
                    <p class="text-base text-slate-700">orders today</p>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <section class="space-y-4" aria-label="Search and filters">
        <form action="<?= e(url('orders/index.php')) ?>" method="get" role="search" class="flex flex-col gap-3 sm:flex-row">
            <?php if ($status_filter !== 'active'): ?>
                <input type="hidden" name="status" value="<?= e($status_filter) ?>">
            <?php endif; ?>
            <?php if ($show_voided): ?>
                <input type="hidden" name="show_voided" value="1">
            <?php endif; ?>
            <div class="flex-1">
                <label class="mb-1 block text-base font-medium text-slate-800" for="order-search">Quick search</label>
                <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 placeholder:text-slate-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="order-search" name="q" type="search" value="<?= e($search_term) ?>" placeholder="Ticket number, customer name, or phone">
            </div>
            <button class="w-full min-h-11 self-end rounded-lg border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Search</button>
        </form>

        <nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0" aria-label="Filter orders by status">
            <?php foreach ($filter_labels as $filter => $label): ?>
                <?php
                $filter_query = http_build_query(array_filter([
                    'q' => $search_term,
                    'status' => $filter === 'active' ? null : $filter,
                    'show_voided' => $show_voided ? '1' : null,
                ], static fn($value) => $value !== null && $value !== ''));
                ?>
                <a class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-full border px-4 py-2 text-base font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 <?= $status_filter === $filter ? 'border-emerald-800 bg-emerald-800 text-white' : 'border-slate-300 bg-white text-slate-800 hover:bg-slate-100' ?>" href="<?= e(url('orders/index.php' . ($filter_query === '' ? '' : '?' . $filter_query))) ?>" <?= $status_filter === $filter ? 'aria-current="page"' : '' ?>><?= e($label) ?></a>
            <?php endforeach; ?>
        </nav>

        <?php if ($is_admin): ?>
            <form action="<?= e(url('orders/index.php')) ?>" method="get" class="flex flex-col gap-3 rounded-xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center sm:justify-between">
                <?php if ($search_term !== ''): ?>
                    <input type="hidden" name="q" value="<?= e($search_term) ?>">
                <?php endif; ?>
                <?php if ($status_filter !== 'active'): ?>
                    <input type="hidden" name="status" value="<?= e($status_filter) ?>">
                <?php endif; ?>
                <label class="inline-flex min-h-11 items-center gap-3 text-base font-medium text-slate-800">
                    <input class="h-5 w-5 rounded border-slate-400 text-emerald-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" type="checkbox" name="show_voided" value="1" <?= $show_voided ? 'checked' : '' ?>>
                    Show voided orders
                </label>
                <button class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-4 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Apply filter</button>
            </form>
        <?php endif; ?>
    </section>

    <?php if ($invalid_status_filter): ?>
        <p class="rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-base text-amber-950" role="status">That status filter was not recognized. Showing active orders.</p>
    <?php endif; ?>

    <?php if ($orders === []): ?>
        <section class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950"><?= $search_term !== '' ? 'No matching orders' : 'No orders here yet' ?></h2>
            <p class="mt-2 text-base text-slate-700"><?= $search_term !== '' ? 'Try a different ticket number, customer name, or phone number.' : 'Create an order to start tracking laundry through the shop.' ?></p>
            <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white sm:w-auto" href="<?= e(url('orders/create.php')) ?>">+ New Order</a>
        </section>
    <?php else: ?>
        <ul class="space-y-4 md:hidden" aria-label="Orders">
            <?php foreach ($orders as $order): ?>
                <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="break-all font-mono text-sm font-bold text-slate-950"><?= e($order['ticket_no']) ?></h2>
                            <p class="mt-1 break-words text-base font-medium text-slate-800"><?= e($order['customer_name']) ?></p>
                        </div>
                        <?php if ((int) $order['is_void'] === 1): ?>
                            <span class="inline-flex min-h-8 items-center rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-800">Void</span>
                        <?php else: ?>
                            <span class="inline-flex min-h-8 items-center rounded-full px-3 py-1 text-sm font-semibold <?= e($status_info[$order['status']]['badge']) ?>"><?= e($status_info[$order['status']]['label']) ?></span>
                        <?php endif; ?>
                    </div>
                    <dl class="mt-3 grid grid-cols-2 gap-3 text-base">
                        <div>
                            <dt class="text-slate-600">Total</dt>
                            <dd class="font-semibold <?= (int) $order['is_void'] === 1 ? 'text-slate-600 line-through' : 'text-slate-950' ?>"><?= e(format_money($order['total_amount'])) ?></dd>
                        </div>
                        <div>
                            <dt class="text-slate-600">Date</dt>
                            <dd class="font-medium text-slate-900"><?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></dd>
                        </div>
                    </dl>
                    <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('orders/view.php?id=' . (int) $order['id'])) ?>">View order</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="hidden w-full text-left text-base md:table">
                <thead class="bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-700">
                    <tr>
                        <th class="px-4 py-3" scope="col">Ticket</th>
                        <th class="px-4 py-3" scope="col">Customer</th>
                        <th class="px-4 py-3" scope="col">Status</th>
                        <th class="px-4 py-3" scope="col">Total</th>
                        <th class="px-4 py-3" scope="col">Date</th>
                        <th class="px-4 py-3" scope="col"><span class="sr-only">Action</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-800">
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="px-4 py-3 font-mono text-sm font-semibold text-slate-950"><?= e($order['ticket_no']) ?></td>
                            <td class="px-4 py-3"><?= e($order['customer_name']) ?></td>
                            <td class="px-4 py-3">
                                <?php if ((int) $order['is_void'] === 1): ?>
                                    <span class="inline-flex rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-800">Void</span>
                                <?php else: ?>
                                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold <?= e($status_info[$order['status']]['badge']) ?>"><?= e($status_info[$order['status']]['label']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3 font-semibold <?= (int) $order['is_void'] === 1 ? 'text-slate-600 line-through' : '' ?>"><?= e(format_money($order['total_amount'])) ?></td>
                            <td class="px-4 py-3"><?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></td>
                            <td class="px-4 py-3 text-right"><a class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('orders/view.php?id=' . (int) $order['id'])) ?>">View</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center" aria-label="Order list pages">
            <p class="text-base text-slate-700">Page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['total_pages'] ?></p>
            <div class="flex w-full gap-2 sm:w-auto">
                <?php if ($pagination['page'] > 1): ?>
                    <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('orders/index.php?page=' . ($pagination['page'] - 1) . ($pagination_query === '' ? '' : '&' . $pagination_query))) ?>">Previous</a>
                <?php endif; ?>
                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('orders/index.php?page=' . ($pagination['page'] + 1) . ($pagination_query === '' ? '' : '&' . $pagination_query))) ?>">Next</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
