<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$page_title = 'Services and pricing';
$requested_page = requested_page_number();

$count_statement = $conn->prepare('SELECT COUNT(*) FROM services');
$count_statement->execute();
$count_statement->bind_result($total_services);
$count_statement->fetch();
$count_statement->close();

$pagination = paginate((int) $total_services, $requested_page, 15);
$statement = $conn->prepare(
    'SELECT id, name, pricing_type, unit_price, is_active
     FROM services
     ORDER BY name, id
     LIMIT ? OFFSET ?'
);
$statement->bind_param('ii', $pagination['per_page'], $pagination['offset']);
$statement->execute();
$services = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-base font-semibold text-emerald-900">Admin · Shop setup</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Services and pricing</h1>
            <p class="mt-2 text-base text-slate-700">Manage the services staff can add to laundry orders.</p>
        </div>
        <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('services/create.php')) ?>">Add service</a>
    </section>

    <?php if ($services === []): ?>
        <section class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">No services yet</h2>
            <p class="mt-2 text-base text-slate-700">Add your first service so staff can price new orders.</p>
            <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white sm:w-auto" href="<?= e(url('services/create.php')) ?>">Add service</a>
        </section>
    <?php else: ?>
        <ul class="space-y-4 md:hidden" aria-label="Services">
            <?php foreach ($services as $service): ?>
                <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <h2 class="break-words text-lg font-semibold text-slate-950"><?= e($service['name']) ?></h2>
                        <?php if ((int) $service['is_active'] === 1): ?>
                            <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">Active</span>
                        <?php else: ?>
                            <span class="shrink-0 rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-800">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <dl class="mt-3 space-y-2 text-base">
                        <div class="flex justify-between gap-4"><dt class="text-slate-600">Price type</dt><dd class="text-right text-slate-900"><?= $service['pricing_type'] === 'per_kg' ? 'Per kilogram' : 'Per piece' ?></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-600">Unit price</dt><dd class="font-semibold text-slate-950"><?= e(format_money($service['unit_price'])) ?> <span class="font-normal text-slate-700"><?= $service['pricing_type'] === 'per_kg' ? '/ kg' : '/ piece' ?></span></dd></div>
                    </dl>
                    <div class="mt-4 flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('services/edit.php?id=' . (int) $service['id'])) ?>">Edit</a>
                        <form action="<?= e(url('services/toggle.php')) ?>" method="post" class="w-full sm:w-auto" data-confirm-toggle>
                            <?= csrf_field() ?>
                            <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                            <button class="w-full min-h-11 rounded-lg border border-slate-300 px-4 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit"><?= (int) $service['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="hidden w-full text-left text-base md:table">
                <thead class="bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-700">
                    <tr>
                        <th class="px-4 py-3" scope="col">Name</th>
                        <th class="px-4 py-3" scope="col">Pricing type</th>
                        <th class="px-4 py-3 text-right" scope="col">Unit price</th>
                        <th class="px-4 py-3" scope="col">Status</th>
                        <th class="px-4 py-3" scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-800">
                    <?php foreach ($services as $service): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-950"><?= e($service['name']) ?></td>
                            <td class="px-4 py-3"><?= $service['pricing_type'] === 'per_kg' ? 'Per kilogram' : 'Per piece' ?></td>
                            <td class="px-4 py-3 text-right"><?= e(format_money($service['unit_price'])) ?> <?= $service['pricing_type'] === 'per_kg' ? '/ kg' : '/ piece' ?></td>
                            <td class="px-4 py-3">
                                <?php if ((int) $service['is_active'] === 1): ?>
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">Active</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-800">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <a class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('services/edit.php?id=' . (int) $service['id'])) ?>">Edit</a>
                                    <form action="<?= e(url('services/toggle.php')) ?>" method="post" data-confirm-toggle>
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="service_id" value="<?= (int) $service['id'] ?>">
                                        <button class="min-h-11 rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" type="submit"><?= (int) $service['is_active'] === 1 ? 'Deactivate' : 'Activate' ?></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center" aria-label="Service list pages">
            <p class="text-base text-slate-700">Page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['total_pages'] ?></p>
            <div class="flex w-full gap-2 sm:w-auto">
                <?php if ($pagination['page'] > 1): ?>
                    <a class="inline-flex flex-1 min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('services/index.php?page=' . ($pagination['page'] - 1))) ?>">Previous</a>
                <?php endif; ?>
                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <a class="inline-flex flex-1 min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('services/index.php?page=' . ($pagination['page'] + 1))) ?>">Next</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>
<script>
    document.querySelectorAll('[data-confirm-toggle]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            const button = form.querySelector('button[type="submit"]');
            const action = button.textContent.trim().toLowerCase();
            if (!window.confirm(action + ' this service? Existing orders will keep their saved prices.')) {
                event.preventDefault();
                return;
            }

            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitted = 'true';
            button.disabled = true;
        });
    });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
