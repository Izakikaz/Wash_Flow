<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Customers';
$search_term = isset($_GET['q']) && is_string($_GET['q']) ? trim($_GET['q']) : '';
$requested_page = requested_page_number();

if ($search_term === '') {
    $count_statement = $conn->prepare('SELECT COUNT(*) FROM customers');
    $count_statement->execute();
    $count_statement->bind_result($total_customers);
    $count_statement->fetch();
    $count_statement->close();
} else {
    $search_pattern = '%' . $search_term . '%';
    $count_statement = $conn->prepare(
        'SELECT COUNT(*)
         FROM customers
         WHERE name LIKE ? OR phone LIKE ? OR phone_digits LIKE ?'
    );
    $count_statement->bind_param('sss', $search_pattern, $search_pattern, $search_pattern);
    $count_statement->execute();
    $count_statement->bind_result($total_customers);
    $count_statement->fetch();
    $count_statement->close();
}

$pagination = paginate((int) $total_customers, $requested_page, 15);

if ($search_term === '') {
    $statement = $conn->prepare(
        'SELECT c.id, c.name, c.phone, c.address, COUNT(o.id) AS order_count
         FROM customers AS c
         LEFT JOIN orders AS o ON o.customer_id = c.id AND o.is_void = 0
         GROUP BY c.id, c.name, c.phone, c.address
         ORDER BY c.name, c.id
         LIMIT ? OFFSET ?'
    );
    $statement->bind_param('ii', $pagination['per_page'], $pagination['offset']);
} else {
    $search_pattern = '%' . $search_term . '%';
    $statement = $conn->prepare(
        'SELECT c.id, c.name, c.phone, c.address, COUNT(o.id) AS order_count
         FROM customers AS c
         LEFT JOIN orders AS o ON o.customer_id = c.id AND o.is_void = 0
         WHERE c.name LIKE ? OR c.phone LIKE ? OR c.phone_digits LIKE ?
         GROUP BY c.id, c.name, c.phone, c.address
         ORDER BY c.name, c.id
         LIMIT ? OFFSET ?'
    );
    $statement->bind_param(
        'sssii',
        $search_pattern,
        $search_pattern,
        $search_pattern,
        $pagination['per_page'],
        $pagination['offset']
    );
}
$statement->execute();
$customers = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

$flash_message = flash_get();
$search_query = $search_term === '' ? '' : '&' . http_build_query(['q' => $search_term]);

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-base font-semibold text-emerald-900">Daily work</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Customers</h1>
            <p class="mt-2 text-base text-slate-700">Find customer contact details and their order history.</p>
        </div>
        <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('customers/create.php')) ?>">Add customer</a>
    </section>

    <?php if ($flash_message !== null): ?>
        <p class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-base text-emerald-950" role="status" aria-live="polite">
            <?= e($flash_message['message'] ?? '') ?>
        </p>
    <?php endif; ?>

    <form action="<?= e(url('customers/index.php')) ?>" method="get" role="search" class="space-y-3 sm:flex sm:items-end sm:gap-3 sm:space-y-0">
        <div class="flex-1">
            <label class="mb-1 block text-base font-medium text-slate-800" for="customer-search">Search customers</label>
            <input
                class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 placeholder:text-slate-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                id="customer-search"
                name="q"
                type="search"
                value="<?= e($search_term) ?>"
                placeholder="Search by name or phone"
            >
        </div>
        <button class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Search</button>
        <?php if ($search_term !== ''): ?>
            <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg px-4 py-3 text-base font-semibold text-emerald-900 underline sm:w-auto" href="<?= e(url('customers/index.php')) ?>">Clear search</a>
        <?php endif; ?>
    </form>

    <?php if ($customers === []): ?>
        <section class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <?php if ($search_term !== ''): ?>
                <h2 class="text-lg font-semibold text-slate-950">No customers found</h2>
                <p class="mt-2 text-base text-slate-700">Try a different name or phone number.</p>
                <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-3 text-base font-semibold text-slate-800 sm:w-auto" href="<?= e(url('customers/index.php')) ?>">Clear search</a>
            <?php else: ?>
                <h2 class="text-lg font-semibold text-slate-950">No customers yet</h2>
                <p class="mt-2 text-base text-slate-700">Add a customer to start creating laundry orders.</p>
                <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white sm:w-auto" href="<?= e(url('customers/create.php')) ?>">Add customer</a>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <ul class="space-y-4 md:hidden" aria-label="Customers">
            <?php foreach ($customers as $customer): ?>
                <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <h2 class="break-words text-lg font-semibold text-slate-950"><?= e($customer['name']) ?></h2>
                    <dl class="mt-3 space-y-2 text-base">
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Phone</dt>
                            <dd class="break-all text-right text-slate-900"><?= $customer['phone'] === null || $customer['phone'] === '' ? 'Not provided' : e($customer['phone']) ?></dd>
                        </div>
                        <div class="flex justify-between gap-4">
                            <dt class="text-slate-600">Orders</dt>
                            <dd class="text-right text-slate-900"><?= (int) $customer['order_count'] ?></dd>
                        </div>
                        <?php if ($customer['address'] !== null && $customer['address'] !== ''): ?>
                            <div class="flex justify-between gap-4">
                                <dt class="text-slate-600">Address</dt>
                                <dd class="max-w-[65%] break-words text-right text-slate-900"><?= e($customer['address']) ?></dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                    <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('customers/edit.php?id=' . (int) $customer['id'])) ?>">Edit customer</a>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="hidden w-full text-left text-base md:table">
                <thead class="bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-700">
                    <tr>
                        <th class="px-4 py-3" scope="col">Name</th>
                        <th class="px-4 py-3" scope="col">Phone</th>
                        <th class="px-4 py-3" scope="col">Address</th>
                        <th class="px-4 py-3 text-right" scope="col">Orders</th>
                        <th class="px-4 py-3" scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-800">
                    <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-950"><?= e($customer['name']) ?></td>
                            <td class="px-4 py-3"><?= $customer['phone'] === null || $customer['phone'] === '' ? 'Not provided' : e($customer['phone']) ?></td>
                            <td class="max-w-sm break-words px-4 py-3"><?= $customer['address'] === null || $customer['address'] === '' ? 'Not provided' : e($customer['address']) ?></td>
                            <td class="px-4 py-3 text-right"><?= (int) $customer['order_count'] ?></td>
                            <td class="px-4 py-3 text-right">
                                <a class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('customers/edit.php?id=' . (int) $customer['id'])) ?>">Edit</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center" aria-label="Customer list pages">
            <p class="text-base text-slate-700">Page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['total_pages'] ?></p>
            <div class="flex w-full gap-2 sm:w-auto">
                <?php if ($pagination['page'] > 1): ?>
                    <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('customers/index.php?page=' . ($pagination['page'] - 1) . $search_query)) ?>">Previous</a>
                <?php endif; ?>
                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <a class="inline-flex min-h-11 flex-1 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('customers/index.php?page=' . ($pagination['page'] + 1) . $search_query)) ?>">Next</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
