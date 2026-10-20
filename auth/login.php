<?php

$allow_guest = true;
require_once __DIR__ . '/../includes/auth_check.php';

if (isset($_SESSION['user_id'])) {
    redirect(home_url());
}

$page_title = 'Sign in';
$public_page = true;
$username = '';
$login_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $username = isset($_POST['username']) && is_string($_POST['username'])
        ? trim($_POST['username'])
        : '';
    $password = isset($_POST['password']) && is_string($_POST['password'])
        ? $_POST['password']
        : '';

    try {
        $conn->begin_transaction();

        $statement = $conn->prepare(
            'SELECT id, full_name, password_hash, role, is_active, failed_attempts, locked_until
             FROM users
             WHERE username = ?
             LIMIT 1
             FOR UPDATE'
        );
        $statement->bind_param('s', $username);
        $statement->execute();
        $statement->bind_result(
            $user_id,
            $full_name,
            $password_hash,
            $role,
            $is_active,
            $failed_attempts,
            $locked_until
        );
        $user_found = $statement->fetch();
        $statement->close();

        $expired_lock = $user_found
            && $locked_until !== null
            && strtotime($locked_until) <= time();
        if ($expired_lock) {
            $reset_attempts = $conn->prepare(
                'UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = ?'
            );
            $reset_attempts->bind_param('i', $user_id);
            $reset_attempts->execute();
            $reset_attempts->close();
            $failed_attempts = 0;
            $locked_until = null;
        }

        $locked = $user_found
            && $locked_until !== null
            && strtotime($locked_until) > time();
        $valid_user = $user_found && (int) $is_active === 1 && !$locked;

        if ($valid_user && password_verify($password, $password_hash)) {
            $update = $conn->prepare(
                'UPDATE users
                 SET failed_attempts = 0, locked_until = NULL, last_login_at = NOW()
                 WHERE id = ?'
            );
            $update->bind_param('i', $user_id);
            $update->execute();
            $update->close();
            $conn->commit();

            session_regenerate_id(true);
            unset($_SESSION['csrf_token']);
            $_SESSION['user_id'] = (int) $user_id;
            $_SESSION['full_name'] = $full_name;
            $_SESSION['role'] = $role;
            $_SESSION['last_activity'] = time();

            redirect(home_url());
        }

        if ($valid_user) {
            $increment = $conn->prepare(
                'UPDATE users SET failed_attempts = failed_attempts + 1 WHERE id = ?'
            );
            $increment->bind_param('i', $user_id);
            $increment->execute();
            $increment->close();

            $lock = $conn->prepare(
                'UPDATE users
                 SET locked_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                 WHERE id = ?
                   AND failed_attempts >= 5
                   AND (locked_until IS NULL OR locked_until <= NOW())'
            );
            $lock->bind_param('i', $user_id);
            $lock->execute();
            $lock->close();
        }

        $conn->commit();
        $login_error = 'We could not sign you in. Check your username and password, or contact an administrator.';
    } catch (mysqli_sql_exception $exception) {
        if ($conn->thread_id) {
            $conn->rollback();
        }

        error_log('WashFlow login database error: ' . $exception->getMessage());
        http_response_code(500);
        require __DIR__ . '/../errors/500.php';
        exit;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<div class="flex min-h-[calc(100vh-4rem)] items-center justify-center px-4 py-10 sm:px-6">
    <section class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:max-w-sm sm:p-8" aria-labelledby="login-heading">
        <div class="mb-7 text-center">
            <img class="mx-auto mb-5 h-14 w-auto" src="<?= e(url('assets/images/washflow-logo.png')) ?>" alt="WashFlow">
            <p class="text-base font-semibold text-emerald-900"><?= e(SHOP_NAME) ?></p>
            <h1 id="login-heading" class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Welcome back</h1>
            <p class="mt-2 text-base text-slate-600">Sign in to manage laundry orders.</p>
        </div>

        <?php if ($login_error !== ''): ?>
            <p class="mb-5 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-base text-red-950" role="alert">
                <?= e($login_error) ?>
            </p>
        <?php endif; ?>

        <?php $flash_message = flash_get(); ?>
        <?php if ($flash_message !== null): ?>
            <p class="mb-5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-3 text-base text-amber-950" role="status" aria-live="polite">
                <?= e($flash_message['message'] ?? '') ?>
            </p>
        <?php endif; ?>

        <form action="<?= e(url('auth/login.php')) ?>" method="post" class="space-y-5" data-single-submit>
            <?= csrf_field() ?>

            <div>
                <label class="mb-1 block text-base font-medium text-slate-800" for="username">Username</label>
                <input
                    class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    id="username"
                    name="username"
                    type="text"
                    value="<?= e($username) ?>"
                    autocomplete="username"
                    autocapitalize="none"
                    required
                    autofocus
                >
            </div>

            <div>
                <label class="mb-1 block text-base font-medium text-slate-800" for="password">Password</label>
                <input
                    class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="current-password"
                    required
                >
                <button
                    class="mt-2 min-h-11 rounded-lg px-3 text-base font-medium text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    id="toggle-password"
                    type="button"
                    aria-controls="password"
                    aria-pressed="false"
                >Show password</button>
            </div>

            <button
                class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:cursor-wait disabled:opacity-70"
                type="submit"
            >Sign in</button>
        </form>
    </section>
</div>
<script>
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('toggle-password');

    passwordToggle.addEventListener('click', function () {
        const showPassword = passwordInput.type === 'password';
        passwordInput.type = showPassword ? 'text' : 'password';
        passwordToggle.textContent = showPassword ? 'Hide password' : 'Show password';
        passwordToggle.setAttribute('aria-pressed', String(showPassword));
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
