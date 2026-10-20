<?php

require_once __DIR__ . '/../includes/auth_check.php';

$customer_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($customer_id === false || $customer_id === null || $customer_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$statement = $conn->prepare(
    'SELECT id, name, phone, address
     FROM customers
     WHERE id = ?
     LIMIT 1'
);
$statement->bind_param('i', $customer_id);
$statement->execute();
$customer = $statement->get_result()->fetch_assoc();
$statement->close();

if ($customer === null) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$page_breadcrumb = [
    'back_label' => 'Customers',
    'back_url' => url('customers/index.php'),
    'items' => [
        ['label' => 'Customers', 'url' => url('customers/index.php')],
        ['label' => 'Edit ' . $customer['name']],
    ],
];
$page_title = 'Edit customer';
$values = [
    'name' => $customer['name'],
    'phone' => $customer['phone'] ?? '',
    'address' => $customer['address'] ?? '',
];
$errors = [
    'name' => '',
    'phone' => '',
    'address' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (array_keys($values) as $field) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $values[$field] = trim($_POST[$field]);
        }
    }

    $name_length = preg_match_all('/./us', $values['name'], $name_characters);
    $address_length = preg_match_all('/./us', $values['address'], $address_characters);
    if ($values['name'] === '' || $name_length === false || $name_length > 100) {
        $errors['name'] = 'Enter a customer name using no more than 100 characters.';
    }
    $phone_length = preg_match_all('/./us', $values['phone'], $phone_characters);
    if (
        $values['phone'] !== ''
        && (
            !preg_match('/\A[0-9+\- ]+\z/', $values['phone'])
            || $phone_length === false
            || $phone_length > 20
        )
    ) {
        $errors['phone'] = 'Use only digits, plus signs, hyphens, or spaces, up to 20 characters.';
    }
    if ($address_length === false || $address_length > 255) {
        $errors['address'] = 'Keep the address to 255 characters or fewer.';
    }

    if ($errors['name'] === '' && $errors['phone'] === '' && $errors['address'] === '') {
        try {
            $phone = $values['phone'] === '' ? null : $values['phone'];
            $phone_digits = normalize_phone($values['phone']);
            $address = $values['address'] === '' ? null : $values['address'];
            $statement = $conn->prepare(
                'UPDATE customers
                 SET name = ?, phone = ?, phone_digits = ?, address = ?
                 WHERE id = ?'
            );
            $statement->bind_param('ssssi', $values['name'], $phone, $phone_digits, $address, $customer_id);
            $statement->execute();
            $statement->close();

            flash_set('success', 'The customer details were updated.');
            redirect('customers/index.php');
        } catch (mysqli_sql_exception $exception) {
            error_log('WashFlow customer update database error: ' . $exception->getMessage());
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
        <p class="text-base font-semibold text-emerald-900">Daily work · Customers</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Edit customer</h1>
    </section>

    <form action="<?= e(url('customers/edit.php?id=' . (int) $customer_id)) ?>" method="post" class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-single-submit>
        <?= csrf_field() ?>
        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="name">Customer name</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="name" name="name" type="text" maxlength="100" value="<?= e($values['name']) ?>" aria-invalid="<?= $errors['name'] !== '' ? 'true' : 'false' ?>" required autofocus>
            <?php if ($errors['name'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="phone">Phone number <span class="font-normal text-slate-600">(optional)</span></label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="phone" name="phone" type="tel" autocomplete="tel" maxlength="20" value="<?= e($values['phone']) ?>" aria-invalid="<?= $errors['phone'] !== '' ? 'true' : 'false' ?>">
            <p class="mt-1 text-base text-slate-600">Enter digits, spaces, hyphens, or a leading plus sign (up to 20 characters).</p>
            <?php if ($errors['phone'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['phone']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="address">Address <span class="font-normal text-slate-600">(optional)</span></label>
            <textarea class="w-full min-h-24 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="address" name="address" maxlength="255" aria-invalid="<?= $errors['address'] !== '' ? 'true' : 'false' ?>"><?= e($values['address']) ?></textarea>
            <?php if ($errors['address'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['address']) ?></p><?php endif; ?>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Save changes</button>
            <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 sm:w-auto" href="<?= e(url('customers/index.php')) ?>">Cancel</a>
        </div>
    </form>

    <section class="rounded-xl border border-red-200 bg-white p-4 shadow-sm sm:p-6" aria-labelledby="remove-customer-heading">
        <h2 id="remove-customer-heading" class="text-lg font-semibold text-slate-950">Remove customer</h2>
        <p class="mt-1 text-base text-slate-700">A customer can be removed only if they have no orders.</p>
        <form action="<?= e(url('customers/delete.php')) ?>" method="post" class="mt-4" data-confirm-delete>
            <?= csrf_field() ?>
            <input type="hidden" name="customer_id" value="<?= (int) $customer_id ?>">
            <button class="w-full min-h-11 rounded-lg border border-red-300 px-4 py-3 text-base font-semibold text-red-800 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800 sm:w-auto" type="submit">Remove customer</button>
        </form>
    </section>
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

    document.querySelectorAll('[data-confirm-delete]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!window.confirm('Remove this customer? This cannot be undone, and customers with orders cannot be removed.')) {
                event.preventDefault();
                return;
            }

            if (form.dataset.submitted === 'true') {
                event.preventDefault();
                return;
            }

            form.dataset.submitted = 'true';
            form.querySelector('button[type="submit"]').disabled = true;
        });
    });
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
