<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$user_id_value = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST'
    ? (isset($_POST['user_id']) && is_string($_POST['user_id']) ? $_POST['user_id'] : '')
    : (isset($_GET['id']) && is_string($_GET['id']) ? $_GET['id'] : '');
$user_id = filter_var($user_id_value, FILTER_VALIDATE_INT);

if ($user_id === false || $user_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$statement = $conn->prepare('SELECT full_name, username FROM users WHERE id = ? LIMIT 1');
$statement->bind_param('i', $user_id);
$statement->execute();
$user = $statement->get_result()->fetch_assoc();
$statement->close();

if ($user === null) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$password_error = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    csrf_verify();

    $new_password = isset($_POST['new_password']) && is_string($_POST['new_password'])
        ? $_POST['new_password']
        : '';
    $confirm_password = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
        ? $_POST['confirm_password']
        : '';
    $password_length = preg_match_all('/./us', $new_password);

    if ($password_length === false || $password_length < 8 || strlen($new_password) > 255) {
        $password_error = 'Use a password of at least 8 characters and no more than 255 bytes.';
    } elseif (!hash_equals($new_password, $confirm_password)) {
        $password_error = 'The passwords do not match.';
    } else {
        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
        $statement = $conn->prepare(
            'UPDATE users
             SET password_hash = ?, must_change_password = 1, failed_attempts = 0, locked_until = NULL
             WHERE id = ?'
        );
        $statement->bind_param('si', $password_hash, $user_id);
        $statement->execute();
        $statement->close();

        flash_set('success', 'The password was reset. The user must change it the next time they sign in.');
        redirect('users/index.php');
    }
}

$page_title = 'Reset password';
$page_breadcrumb = [
    'back_label' => 'Users',
    'back_url' => url('users/index.php'),
    'items' => [
        ['label' => 'Users', 'url' => url('users/index.php')],
        ['label' => 'Reset password for ' . $user['full_name']],
    ],
];
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section>
        <p class="text-base font-semibold text-emerald-900">Admin · User management</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Reset password</h1>
        <p class="mt-2 text-base text-slate-700">Set a temporary password for <?= e($user['full_name']) ?> (<?= e($user['username']) ?>). Give it to them securely; they must choose a new password at sign-in.</p>
    </section>

    <?php if ($password_error !== ''): ?>
        <p class="rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-base text-red-950" role="alert"><?= e($password_error) ?></p>
    <?php endif; ?>

    <form action="<?= e(url('users/reset_password.php')) ?>" method="post" class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-single-submit>
        <?= csrf_field() ?>
        <input type="hidden" name="user_id" value="<?= (int) $user_id ?>">
        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="new-password">Temporary password</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="new-password" name="new_password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required autofocus>
            <p class="mt-1 text-base text-slate-600">Use at least 8 characters. The password is not displayed after saving.</p>
        </div>
        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="confirm-password">Confirm temporary password</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="confirm-password" name="confirm_password" type="password" minlength="8" maxlength="255" autocomplete="new-password" required>
        </div>
        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="w-full min-h-11 rounded-lg bg-red-800 px-4 py-3 text-base font-semibold text-white hover:bg-red-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 disabled:cursor-wait disabled:opacity-70 sm:w-auto" type="submit">Reset password</button>
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
