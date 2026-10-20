<?php

require_once __DIR__ . '/../includes/auth_check.php';
require_admin();

$service_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($service_id === false || $service_id === null || $service_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$statement = $conn->prepare(
    'SELECT id, name, pricing_type, unit_price
     FROM services
     WHERE id = ?
     LIMIT 1'
);
$statement->bind_param('i', $service_id);
$statement->execute();
$service = $statement->get_result()->fetch_assoc();
$statement->close();

if ($service === null) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$page_title = 'Edit service';
$page_breadcrumb = [
    'back_label' => 'Services',
    'back_url' => url('services/index.php'),
    'items' => [
        ['label' => 'Services', 'url' => url('services/index.php')],
        ['label' => 'Edit ' . $service['name']],
    ],
];
$values = [
    'name' => $service['name'],
    'pricing_type' => $service['pricing_type'],
    'unit_price' => number_format((float) $service['unit_price'], 2, '.', ''),
];
$errors = [
    'name' => '',
    'pricing_type' => '',
    'unit_price' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    foreach (array_keys($values) as $field) {
        if (isset($_POST[$field]) && is_string($_POST[$field])) {
            $values[$field] = trim($_POST[$field]);
        }
    }

    $name_length = preg_match_all('/./us', $values['name'], $name_characters);
    if ($values['name'] === '' || $name_length === false || $name_length > 100) {
        $errors['name'] = 'Enter a service name using no more than 100 characters.';
    }
    if (!in_array($values['pricing_type'], ['per_kg', 'per_piece'], true)) {
        $errors['pricing_type'] = 'Choose Per kilogram or Per piece.';
    }
    if (
        !preg_match('/\A\d+(?:\.\d{1,2})?\z/', $values['unit_price'])
        || (float) $values['unit_price'] <= 0
        || (float) $values['unit_price'] > 99999999.99
    ) {
        $errors['unit_price'] = 'Enter a price greater than 0 with no more than 2 decimal places.';
    }

    if ($errors['name'] === '' && $errors['pricing_type'] === '' && $errors['unit_price'] === '') {
        try {
            $unit_price = number_format((float) $values['unit_price'], 2, '.', '');
            $statement = $conn->prepare(
                'UPDATE services
                 SET name = ?, pricing_type = ?, unit_price = ?
                 WHERE id = ?'
            );
            $statement->bind_param('sssi', $values['name'], $values['pricing_type'], $unit_price, $service_id);
            $statement->execute();
            $statement->close();

            flash_set('success', 'The service details and price were updated.');
            redirect('services/index.php');
        } catch (mysqli_sql_exception $exception) {
            error_log('WashFlow service update database error: ' . $exception->getMessage());
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
        <p class="text-base font-semibold text-emerald-900">Admin · Services and pricing</p>
        <h1 class="mt-1 text-2xl font-bold tracking-tight text-slate-950">Edit service</h1>
        <p class="mt-2 text-base text-slate-700">Price changes apply to new orders. Existing orders keep their saved prices.</p>
    </section>

    <form action="<?= e(url('services/edit.php?id=' . (int) $service_id)) ?>" method="post" class="space-y-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6" data-single-submit>
        <?= csrf_field() ?>
        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="name">Service name</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="name" name="name" type="text" maxlength="100" value="<?= e($values['name']) ?>" aria-invalid="<?= $errors['name'] !== '' ? 'true' : 'false' ?>" required autofocus>
            <?php if ($errors['name'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['name']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="pricing-type">Pricing type</label>
            <select class="w-full min-h-11 rounded-lg border border-slate-300 bg-white px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="pricing-type" name="pricing_type" required>
                <option value="per_kg" <?= $values['pricing_type'] === 'per_kg' ? 'selected' : '' ?>>Per kilogram</option>
                <option value="per_piece" <?= $values['pricing_type'] === 'per_piece' ? 'selected' : '' ?>>Per piece</option>
            </select>
            <?php if ($errors['pricing_type'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['pricing_type']) ?></p><?php endif; ?>
        </div>

        <div>
            <label class="mb-1 block text-base font-medium text-slate-800" for="unit-price">Unit price (₱)</label>
            <input class="w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" id="unit-price" name="unit_price" type="number" inputmode="decimal" min="0.01" step="0.01" value="<?= e($values['unit_price']) ?>" aria-invalid="<?= $errors['unit_price'] !== '' ? 'true' : 'false' ?>" required>
            <p class="mt-1 text-base text-slate-600">Enter an amount greater than zero, up to two decimal places.</p>
            <?php if ($errors['unit_price'] !== ''): ?><p class="mt-1 text-base text-red-800"><?= e($errors['unit_price']) ?></p><?php endif; ?>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row">
            <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 sm:w-auto" type="submit">Save changes</button>
            <a class="inline-flex w-full min-h-11 items-center justify-center rounded-lg border border-slate-300 px-4 py-3 text-base font-semibold text-slate-800 hover:bg-slate-50 sm:w-auto" href="<?= e(url('services/index.php')) ?>">Cancel</a>
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
