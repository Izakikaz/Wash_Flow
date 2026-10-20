<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$page_title = 'User management';
$flash_message = flash_get();

$requested_page = requested_page_number();

$count_statement = $conn->prepare('SELECT COUNT(*) FROM users');
$count_statement->execute();
$count_statement->bind_result($total_users);
$count_statement->fetch();
$count_statement->close();
$total_users = (int) $total_users;
$pagination = paginate($total_users, $requested_page, 15);

$statement = $conn->prepare(
    'SELECT id, full_name, username, role, is_active, last_login_at
     FROM users
     ORDER BY full_name, id
     LIMIT ? OFFSET ?'
);
$statement->bind_param('ii', $pagination['per_page'], $pagination['offset']);
$statement->execute();
$users = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
$statement->close();

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-base font-semibold text-emerald-900">Admin</p>
            <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Users</h1>
            <p class="mt-2 text-base text-slate-700">Create accounts, update access, and reset passwords.</p>
        </div>
        <a
            class="inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto"
            href="<?= e(url('users/create.php')) ?>"
        >Add user</a>
    </section>

    <?php if ($flash_message !== null): ?>
        <p class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-3 text-base text-emerald-950" role="status" aria-live="polite">
            <?= e($flash_message['message'] ?? '') ?>
        </p>
    <?php endif; ?>

    <?php if ($users === []): ?>
        <section class="rounded-xl border border-slate-200 bg-white p-6 text-center shadow-sm">
            <h2 class="text-lg font-semibold text-slate-950">No user accounts yet</h2>
            <p class="mt-2 text-base text-slate-700">Add a user to give a staff member access to WashFlow.</p>
            <a class="mt-4 inline-flex w-full min-h-11 items-center justify-center rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white sm:w-auto" href="<?= e(url('users/create.php')) ?>">Add user</a>
        </section>
    <?php else: ?>
        <ul class="space-y-4 md:hidden" aria-label="User accounts">
            <?php foreach ($users as $user): ?>
                <li class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h2 class="break-words text-lg font-semibold text-slate-950"><?= e($user['full_name']) ?></h2>
                            <p class="mt-1 break-all text-base text-slate-700"><?= e($user['username']) ?></p>
                        </div>
                        <?php if ((int) $user['is_active'] === 1): ?>
                            <span class="shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">Active</span>
                        <?php else: ?>
                            <span class="shrink-0 rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-800">Inactive</span>
                        <?php endif; ?>
                    </div>
                    <dl class="mt-4 space-y-2 text-base">
                        <div class="flex justify-between gap-4"><dt class="text-slate-600">Role</dt><dd class="font-medium text-slate-900"><?= e(ucfirst($user['role'])) ?></dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-slate-600">Last login</dt><dd class="text-right text-slate-900"><?= $user['last_login_at'] === null ? 'Never' : e($user['last_login_at']) ?></dd></div>
                    </dl>
                    <div class="mt-4 flex flex-col gap-2 border-t border-slate-200 pt-4 sm:flex-row">
                        <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 text-base font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" href="<?= e(url('users/edit.php?id=' . (int) $user['id'])) ?>">Edit user</a>
                        <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-red-300 px-4 py-2 text-base font-semibold text-red-800 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 sm:w-auto" href="<?= e(url('users/reset_password.php?id=' . (int) $user['id'])) ?>">Reset password</a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>

        <div class="hidden overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm md:block">
            <table class="hidden w-full text-left text-base md:table">
                <thead class="bg-slate-100 text-sm font-semibold uppercase tracking-wide text-slate-700">
                    <tr>
                        <th class="px-4 py-3" scope="col">Name</th>
                        <th class="px-4 py-3" scope="col">Username</th>
                        <th class="px-4 py-3" scope="col">Role</th>
                        <th class="px-4 py-3" scope="col">Status</th>
                        <th class="px-4 py-3" scope="col">Last login</th>
                        <th class="px-4 py-3" scope="col"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 text-slate-800">
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-4 py-3 font-medium text-slate-950"><?= e($user['full_name']) ?></td>
                            <td class="px-4 py-3"><?= e($user['username']) ?></td>
                            <td class="px-4 py-3"><?= e(ucfirst($user['role'])) ?></td>
                            <td class="px-4 py-3">
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-sm font-semibold text-emerald-900">Active</span>
                                <?php else: ?>
                                    <span class="rounded-full bg-slate-200 px-3 py-1 text-sm font-semibold text-slate-800">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-3"><?= $user['last_login_at'] === null ? 'Never' : e($user['last_login_at']) ?></td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <a class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 px-3 py-2 font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('users/edit.php?id=' . (int) $user['id'])) ?>">Edit</a>
                                    <a class="inline-flex min-h-11 items-center rounded-lg border border-red-300 px-3 py-2 font-semibold text-red-800 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800" href="<?= e(url('users/reset_password.php?id=' . (int) $user['id'])) ?>">Reset password</a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <nav class="flex flex-col items-stretch justify-between gap-3 sm:flex-row sm:items-center" aria-label="User list pages">
            <p class="text-base text-slate-700">Page <?= (int) $pagination['page'] ?> of <?= (int) $pagination['total_pages'] ?></p>
            <div class="flex w-full gap-2 sm:w-auto">
                <?php if ($pagination['page'] > 1): ?>
                    <a class="inline-flex flex-1 min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('users/index.php?page=' . ($pagination['page'] - 1))) ?>">Previous</a>
                <?php endif; ?>
                <?php if ($pagination['page'] < $pagination['total_pages']): ?>
                    <a class="inline-flex flex-1 min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-2 font-semibold text-slate-800 sm:flex-none" href="<?= e(url('users/index.php?page=' . ($pagination['page'] + 1))) ?>">Next</a>
                <?php endif; ?>
            </div>
        </nav>
    <?php endif; ?>
</div>
<script>
    document.querySelectorAll('[data-single-submit]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitted = 'true';
            form.querySelectorAll('button[type="submit"]').forEach(function (button) {
                button.disabled = true;
            });
        });
    });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
