<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$page_title = 'Add user';
$page_breadcrumb = [
    'back_label' => 'Users',
    'back_url' => url('users/index.php'),
    'items' => [
        ['label' => 'Users', 'url' => url('users/index.php')],
        ['label' => 'Add user'],
    ],
];
$values = [
    'full_name' => '',
    'username' => '',
    'role' => 'staff',
];
$errors = [
    'full_name' => '',
    'username' => '',
    'role' => '',
    'password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (['full_name', 'username', 'role'] as $field) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $values[$field] = trim($_POST[$field]);
        }
    }
    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    $full_name_length = preg_match_all('/./us', $values['full_name'], $name_characters);
    if ($values['full_name'] === '' || $full_name_length === false || $full_name_length > 100) {
        $errors['full_name'] = 'Enter a name using no more than 100 characters.';
    }

    if (!preg_match('/\A[A-Za-z0-9][A-Za-z0-9._-]{2,49}\z/', $values['username'])) {
        $errors['username'] = 'Use 3–50 letters, numbers, dots, underscores, or hyphens. Start with a letter or number.';
    }

    if (!in_array($values['role'], ['admin', 'staff'], true)) {
        $errors['role'] = 'Choose either Admin or Staff.';
    }

    $password_length = preg_match_all('/./us', $password, $password_characters);
    if ($password_length === false || $password_length < 8) {
        $errors['password'] = 'Use at least 8 characters for the initial password.';
    }

    if ($errors['username'] === '') {
        $statement = $conn->prepare('SELECT id FROM users WHERE username = ? LIMIT 1');
        $statement->bind_param('s', $values['username']);
        $statement->execute();
        $statement->store_result();
        if ($statement->num_rows > 0) {
            $errors['username'] = 'That username is already in use.';
        }
        $statement->close();
    }

    $has_errors = $errors['full_name'] !== ''
        || $errors['username'] !== ''
        || $errors['role'] !== ''
        || $errors['password'] !== '';

    if (!$has_errors) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $statement = $conn->prepare(
                'INSERT INTO users (full_name, username, password_hash, role, must_change_password)
                 VALUES (?, ?, ?, ?, 1)'
            );
            $statement->bind_param(
                'ssss',
                $values['full_name'],
                $values['username'],
                $password_hash,
                $values['role']
            );
            $statement->execute();
            $statement->close();

            flash_set('success', 'The user account was created. They must change their password when they sign in.');
            redirect('users/index.php');
        } catch (mysqli_sql_exception $exception) {
            if ($exception->getCode() === 1062) {
                $errors['username'] = 'That username is already in use.';
            } else {
                error_log('WashFlow user creation database error: ' . $exception->getMessage());
                http_response_code(500);
                require __DIR__ . '/../errors/500.php';
                exit;
            }
        }
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section>
        <p class="text-base font-semibold text-emerald-900">Admin · User management</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Add user</h1>
        <p class="mt-2 text-base text-slate-700">New accounts must choose their own password at first sign-in.</p>
    </section>

    <form action="<?= e(url('users/create.php')) ?>" method="post" class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-single-submit>
        <?= csrf_field() ?>
        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="full-name">Full name</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="full-name" name="full_name" type="text" maxlength="100" value="<?= e($values['full_name']) ?>" aria-invalid="<?= $errors['full_name'] !== '' ? 'true' : 'false' ?>" required autofocus>
            <?php if ($errors['full_name'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['full_name']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="username">Username</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="username" name="username" type="text" maxlength="50" autocomplete="username" autocapitalize="none" value="<?= e($values['username']) ?>" aria-invalid="<?= $errors['username'] !== '' ? 'true' : 'false' ?>" required>
            <p class="mt-1 text-base text-slate-600">Use 3–50 letters, numbers, dots, underscores, or hyphens.</p>
            <?php if ($errors['username'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['username']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="role">Role</label>
            <select class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="role" name="role" required>
                <option value="staff" <?= $values['role'] === 'staff' ? 'selected' : '' ?>>Staff</option>
                <option value="admin" <?= $values['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <?php if ($errors['role'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['role']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="initial-password">Initial password</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="initial-password" name="password" type="password" autocomplete="new-password" aria-invalid="<?= $errors['password'] !== '' ? 'true' : 'false' ?>" required>
            <p class="mt-1 text-base text-slate-600">Use at least 8 characters. The user will replace this at first sign-in.</p>
            <?php if ($errors['password'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['password']) ?></p><?php endif; ?>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Create account</button>
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
