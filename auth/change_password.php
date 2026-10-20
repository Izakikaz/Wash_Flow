<?php

require_once __DIR__ . '/../includes/auth_check.php';

$page_title = 'Change password';
$errors = [
    'current_password' => '',
    'new_password' => '',
    'confirm_password' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $current_password = isset($_POST['current_password']) && is_string($_POST['current_password'])
        ? $_POST['current_password']
        : '';
    $new_password = isset($_POST['new_password']) && is_string($_POST['new_password'])
        ? $_POST['new_password']
        : '';
    $confirm_password = isset($_POST['confirm_password']) && is_string($_POST['confirm_password'])
        ? $_POST['confirm_password']
        : '';

    if ($current_password === '') {
        $errors['current_password'] = 'Enter your current password.';
    }

    $new_password_length = preg_match_all('/./us', $new_password, $password_characters);
    if ($new_password_length === false || $new_password_length < 8) {
        $errors['new_password'] = 'Use at least 8 characters for your new password.';
    }

    if ($confirm_password === '') {
        $errors['confirm_password'] = 'Confirm your new password.';
    } elseif ($new_password !== $confirm_password) {
        $errors['confirm_password'] = 'The new passwords do not match.';
    }

    if ($errors['current_password'] === '') {
        try {
            $conn->begin_transaction();

            $statement = $conn->prepare(
                'SELECT password_hash
                 FROM users
                 WHERE id = ?
                 LIMIT 1
                 FOR UPDATE'
            );
            $statement->bind_param('i', $_SESSION['user_id']);
            $statement->execute();
            $statement->bind_result($password_hash);
            $user_found = $statement->fetch();
            $statement->close();

            if (!$user_found || !password_verify($current_password, $password_hash)) {
                $conn->rollback();
                $errors['current_password'] = 'That current password does not match your account.';
            } elseif (
                $errors['new_password'] === ''
                && $errors['confirm_password'] === ''
            ) {
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare(
                    'UPDATE users
                     SET password_hash = ?, must_change_password = 0
                     WHERE id = ?'
                );
                $update->bind_param('si', $new_password_hash, $_SESSION['user_id']);
                $update->execute();
                $update->close();
                $conn->commit();

                session_regenerate_id(true);
                unset($_SESSION['csrf_token']);
                $_SESSION['last_activity'] = time();

                flash_set('success', 'Your password was changed successfully.');
                redirect(home_url());
            } else {
                $conn->rollback();
            }
        } catch (mysqli_sql_exception $exception) {
            $conn->rollback();
            error_log('WashFlow password change database error: ' . $exception->getMessage());
            http_response_code(500);
            require __DIR__ . '/../errors/500.php';
            exit;
        }
    }
}

$page_breadcrumb = [
    'back_label' => 'Home',
    'back_url' => home_url(),
    'items' => [
        ['label' => 'Home', 'url' => home_url()],
        ['label' => 'Change password'],
    ],
];
require __DIR__ . '/../includes/header.php';
?>
<div class="mx-auto w-full max-w-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <section aria-labelledby="password-heading" class="space-y-2">
        <p class="text-base font-semibold text-emerald-900">Account security</p>
        <h1 id="password-heading" class="text-2xl font-bold tracking-tight text-slate-950">Change password</h1>
        <p class="text-base leading-6 text-slate-700">Choose a new password with at least 8 characters.</p>
    </section>

    <form action="<?= e(url('auth/change_password.php')) ?>" method="post" class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-single-submit>
        <?= csrf_field() ?>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="current-password">Current password</label>
            <div class="flex gap-2">
                <input
                    class="min-w-0 flex-1 min-h-11 rounded-lg border px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 <?= $errors['current_password'] !== '' ? 'border-red-700' : 'border-slate-300' ?>"
                    id="current-password"
                    name="current_password"
                    type="password"
                    autocomplete="current-password"
                    aria-invalid="<?= $errors['current_password'] !== '' ? 'true' : 'false' ?>"
                    <?= $errors['current_password'] !== '' ? 'aria-describedby="current-password-error"' : '' ?>
                    required
                    autofocus
                >
                <button
                    class="min-h-11 min-w-11 rounded-lg border border-slate-300 px-3 text-base font-semibold text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    type="button"
                    data-password-toggle="current-password"
                    aria-label="Show current password"
                    aria-pressed="false"
                >Show</button>
            </div>
            <?php if ($errors['current_password'] !== ''): ?>
                <p class="mt-1 text-base text-red-800" id="current-password-error"><?= e($errors['current_password']) ?></p>
            <?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="new-password">New password</label>
            <div class="flex gap-2">
                <input
                    class="min-w-0 flex-1 min-h-11 rounded-lg border px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 <?= $errors['new_password'] !== '' ? 'border-red-700' : 'border-slate-300' ?>"
                    id="new-password"
                    name="new_password"
                    type="password"
                    autocomplete="new-password"
                    aria-invalid="<?= $errors['new_password'] !== '' ? 'true' : 'false' ?>"
                    <?= $errors['new_password'] !== '' ? 'aria-describedby="new-password-error"' : '' ?>
                    required
                >
                <button
                    class="min-h-11 min-w-11 rounded-lg border border-slate-300 px-3 text-base font-semibold text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    type="button"
                    data-password-toggle="new-password"
                    aria-label="Show new password"
                    aria-pressed="false"
                >Show</button>
            </div>
            <?php if ($errors['new_password'] !== ''): ?>
                <p class="mt-1 text-base text-red-800" id="new-password-error"><?= e($errors['new_password']) ?></p>
            <?php else: ?>
                <p class="mt-1 text-base text-slate-600">Use 8 or more characters.</p>
            <?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="confirm-password">Confirm new password</label>
            <div class="flex gap-2">
                <input
                    class="min-w-0 flex-1 min-h-11 rounded-lg border px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 <?= $errors['confirm_password'] !== '' ? 'border-red-700' : 'border-slate-300' ?>"
                    id="confirm-password"
                    name="confirm_password"
                    type="password"
                    autocomplete="new-password"
                    aria-invalid="<?= $errors['confirm_password'] !== '' ? 'true' : 'false' ?>"
                    <?= $errors['confirm_password'] !== '' ? 'aria-describedby="confirm-password-error"' : '' ?>
                    required
                >
                <button
                    class="min-h-11 min-w-11 rounded-lg border border-slate-300 px-3 text-base font-semibold text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    type="button"
                    data-password-toggle="confirm-password"
                    aria-label="Show confirmation password"
                    aria-pressed="false"
                >Show</button>
            </div>
            <?php if ($errors['confirm_password'] !== ''): ?>
                <p class="mt-1 text-base text-red-800" id="confirm-password-error"><?= e($errors['confirm_password']) ?></p>
            <?php endif; ?>
        </div>

        <button
            class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:cursor-wait disabled:opacity-70 sm:w-auto"
            type="submit"
        >Save new password</button>
    </form>
</div>
<script>
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const passwordInput = document.getElementById(button.dataset.passwordToggle);
            const showPassword = passwordInput.type === 'password';

            passwordInput.type = showPassword ? 'text' : 'password';
            button.textContent = showPassword ? 'Hide' : 'Show';
            button.setAttribute('aria-label', (showPassword ? 'Hide ' : 'Show ') + button.dataset.passwordToggle.replace(/-/g, ' '));
            button.setAttribute('aria-pressed', String(showPassword));
        });
    });

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
