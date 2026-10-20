<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$user_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($user_id === false || $user_id === null || $user_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$statement = $conn->prepare(
    'SELECT id, full_name, username, role, is_active
     FROM users
     WHERE id = ?
     LIMIT 1'
);
$statement->bind_param('i', $user_id);
$statement->execute();
$user = $statement->get_result()->fetch_assoc();
$statement->close();

if ($user === null) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$page_title = 'Edit user';
$page_breadcrumb = [
    'back_label' => 'Users',
    'back_url' => url('users/index.php'),
    'items' => [
        ['label' => 'Users', 'url' => url('users/index.php')],
        ['label' => 'Edit ' . $user['full_name']],
    ],
];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $full_name = isset($_POST['full_name']) && is_string($_POST['full_name'])
        ? trim($_POST['full_name'])
        : '';
    $role = isset($_POST['role']) && is_string($_POST['role'])
        ? $_POST['role']
        : '';
    $is_active = isset($_POST['is_active']) && is_string($_POST['is_active'])
        ? $_POST['is_active']
        : '';

    $full_name_length = preg_match_all('/./us', $full_name, $name_characters);
    if ($full_name === '' || $full_name_length === false || $full_name_length > 100) {
        $errors['full_name'] = 'Enter a name using no more than 100 characters.';
    }
    if (!in_array($role, ['admin', 'staff'], true)) {
        $errors['role'] = 'Choose either Admin or Staff.';
    }
    if (!in_array($is_active, ['0', '1'], true)) {
        $errors['is_active'] = 'Choose whether this account is active.';
    }

    $user['full_name'] = $full_name;
    $user['role'] = $role;
    $user['is_active'] = $is_active === '1' ? 1 : 0;

    if ($errors === []) {
        try {
            $conn->begin_transaction();

            // Lock user rows while checking the last active administrator rule.
            $lock_statement = $conn->prepare('SELECT id, role, is_active FROM users FOR UPDATE');
            $lock_statement->execute();
            $lock_result = $lock_statement->get_result();
            $locked_users = $lock_result->fetch_all(MYSQLI_ASSOC);
            $lock_statement->close();

            $current_user = null;
            $active_admin_count = 0;
            foreach ($locked_users as $locked_user) {
                if ((int) $locked_user['id'] === (int) $_SESSION['user_id']) {
                    $current_user = $locked_user;
                }
                if ($locked_user['role'] === 'admin' && (int) $locked_user['is_active'] === 1) {
                    $active_admin_count++;
                }
            }

            $target_user = null;
            foreach ($locked_users as $locked_user) {
                if ((int) $locked_user['id'] === $user_id) {
                    $target_user = $locked_user;
                    break;
                }
            }

            if ($target_user === null) {
                $conn->rollback();
                http_response_code(404);
                require __DIR__ . '/../errors/404.php';
                exit;
            }

            $will_be_inactive_or_demoted = $role !== 'admin' || $is_active !== '1';
            if (
                (int) $user_id === (int) $_SESSION['user_id']
                && $will_be_inactive_or_demoted
            ) {
                $errors['role'] = 'You cannot deactivate your own account or change your own role.';
            } elseif (
                $target_user['role'] === 'admin'
                && (int) $target_user['is_active'] === 1
                && $will_be_inactive_or_demoted
                && $active_admin_count <= 1
            ) {
                $errors['role'] = 'The last active admin cannot be deactivated or changed to Staff.';
            }

            if ($errors !== []) {
                $conn->rollback();
            } else {
                $update = $conn->prepare(
                    'UPDATE users SET full_name = ?, role = ?, is_active = ? WHERE id = ?'
                );
                $active_value = (int) $is_active;
                $update->bind_param('ssii', $full_name, $role, $active_value, $user_id);
                $update->execute();
                $update->close();
                $conn->commit();

                if ((int) $user_id === (int) $_SESSION['user_id']) {
                    $_SESSION['full_name'] = $full_name;
                    $_SESSION['role'] = $role;
                }

                flash_set('success', 'The user account was updated.');
                redirect('users/index.php');
            }
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log('WashFlow user update database error: ' . $exception->getMessage());
            http_response_code(500);
            require __DIR__ . '/../errors/500.php';
            exit;
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section>
        <p class="text-base font-semibold text-emerald-900">Admin · User management</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Edit user</h1>
    </section>

    <form action="<?= e(url('users/edit.php?id=' . (int) $user['id'])) ?>" method="post" class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-single-submit>
        <?= csrf_field() ?>
        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="full-name">Full name</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="full-name" name="full_name" type="text" maxlength="100" value="<?= e($user['full_name']) ?>" required autofocus>
            <?php if (isset($errors['full_name'])): ?><p class="mt-1 text-base text-red-800"><?= e($errors['full_name']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="username">Username</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-base text-slate-700" id="username" type="text" value="<?= e($user['username']) ?>" disabled>
            <p class="mt-1 text-base text-slate-600">Usernames cannot be changed after account creation.</p>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="role">Role</label>
            <select class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="role" name="role" required>
                <option value="staff" <?= $user['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <?php if (isset($errors['role'])): ?><p class="mt-1 text-base text-red-800"><?= e($errors['role']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="is-active">Account status</label>
            <select class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="is-active" name="is_active" required>
                <option value="1" <?= (int) $user['is_active'] === 1 ? 'selected' : '' ?>>Active</option>
                <option value="0" <?= (int) $user['is_active'] !== 1 ? 'selected' : '' ?>>Inactive</option>
            </select>
            <?php if (isset($errors['is_active'])): ?><p class="mt-1 text-base text-red-800"><?= e($errors['is_active']) ?></p><?php endif; ?>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Save changes</button>
            <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 sm:w-auto" href="<?= e(url('users/index.php')) ?>">Cancel</a>
        </div>
    </form>
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
