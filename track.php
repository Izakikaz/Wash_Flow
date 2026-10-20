<?php

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

// Start a protected session for the public form's CSRF token only.
ini_set('session.use_strict_mode', '1');
ini_set('session.cookie_httponly', '1');
ini_set('session.cookie_samesite', 'Lax');
session_set_cookie_params([
    'httponly' => true,
    'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    'samesite' => 'Lax',
    'path' => '/',
]);
session_start();

$is_post = ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
$ticket_input = isset($_POST['ticket_no']) && is_string($_POST['ticket_no'])
    ? trim($_POST['ticket_no'])
    : '';
$phone_suffix = isset($_POST['phone_suffix']) && is_string($_POST['phone_suffix'])
    ? trim($_POST['phone_suffix'])
    : '';
$normalized_ticket_input = strtoupper(str_replace(' ', '', $ticket_input));
if (preg_match('/\A(WF-[0-9]{8})-?([A-Z0-9]{4})\z/', $normalized_ticket_input, $ticket_parts) === 1) {
    $normalized_ticket_input = $ticket_parts[1] . '-' . $ticket_parts[2];
}
$ticket_no = strlen($normalized_ticket_input) <= 20 ? $normalized_ticket_input : '';
$attempt_ticket_no = $normalized_ticket_input !== '' && strlen($normalized_ticket_input) <= 30
    ? $normalized_ticket_input
    : null;
$ticket_is_valid = preg_match('/^WF-[0-9]{8}-[ABCDEFGHJKMNPQRSTUVWXYZ23456789]{4}$/D', $ticket_no) === 1;
$phone_suffix_is_valid = preg_match('/^[0-9]{4}$/D', $phone_suffix) === 1;
if (strlen($phone_suffix) > 4) {
    $phone_suffix = '';
}
$lookup_failed = false;
$tracked_order = null;
$service_names = [];

if ($is_post) {
    csrf_verify();

    $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if ($remote_ip === '' || strlen($remote_ip) > 45) {
        throw new RuntimeException('The request did not contain a valid client IP address.');
    }

    $ip_attempts_statement = $conn->prepare(
        'SELECT COUNT(*)
         FROM tracking_attempts
         WHERE ip_address = ?
           AND was_success = 0
           AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
    );
    $ip_attempts_statement->bind_param('s', $remote_ip);
    $ip_attempts_statement->execute();
    $ip_attempts_statement->bind_result($recent_ip_failures);
    $ip_attempts_statement->fetch();
    $ip_attempts_statement->close();

    $recent_ticket_failures = 0;
    if ($attempt_ticket_no !== null) {
        $ticket_attempts_statement = $conn->prepare(
            'SELECT COUNT(*)
             FROM tracking_attempts
             WHERE ticket_no = ?
               AND was_success = 0
               AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)'
        );
        $ticket_attempts_statement->bind_param('s', $attempt_ticket_no);
        $ticket_attempts_statement->execute();
        $ticket_attempts_statement->bind_result($recent_ticket_failures);
        $ticket_attempts_statement->fetch();
        $ticket_attempts_statement->close();
    }

    $is_throttled = (int) $recent_ip_failures >= 5 || (int) $recent_ticket_failures >= 5;
    if (!$is_throttled && $ticket_is_valid && $phone_suffix_is_valid) {
        $order_statement = $conn->prepare(
            'SELECT o.id, o.ticket_no, SUBSTRING_INDEX(TRIM(c.name), \' \', 1) AS first_name,
                    o.created_at, o.status, o.total_amount,
                    (SELECT COALESCE(SUM(p.amount), 0.00)
                     FROM payments AS p
                     WHERE p.order_id = o.id AND p.is_void = 0) AS amount_paid
             FROM orders AS o
             INNER JOIN customers AS c ON c.id = o.customer_id
             WHERE o.ticket_no = ?
               AND o.is_void = 0
               AND c.phone_digits IS NOT NULL
               AND c.phone_digits <> \'\'
               AND RIGHT(c.phone_digits, 4) = ?
             LIMIT 1'
        );
        $order_statement->bind_param('ss', $ticket_no, $phone_suffix);
        $order_statement->execute();
        $tracked_order = $order_statement->get_result()->fetch_assoc();
        $order_statement->close();
    }

    $was_successful = $tracked_order !== null;
    $was_successful_value = $was_successful ? 1 : 0;
    $logged_ticket_no = $attempt_ticket_no;
    $attempt_statement = $conn->prepare(
        'INSERT INTO tracking_attempts (ip_address, ticket_no, was_success)
         VALUES (?, ?, ?)'
    );
    $attempt_statement->bind_param('ssi', $remote_ip, $logged_ticket_no, $was_successful_value);
    $attempt_statement->execute();
    $attempt_statement->close();

    $lookup_failed = !$was_successful;

    if ($was_successful) {
        $phone_suffix = '';
        $service_statement = $conn->prepare(
            'SELECT s.name
             FROM order_items AS oi
             INNER JOIN services AS s ON s.id = oi.service_id
             WHERE oi.order_id = ?
             ORDER BY oi.id'
        );
        $order_id = (int) $tracked_order['id'];
        $service_statement->bind_param('i', $order_id);
        $service_statement->execute();
        $service_result = $service_statement->get_result();
        while ($service_row = $service_result->fetch_assoc()) {
            $service_names[] = $service_row['name'];
        }
        $service_statement->close();
    }
}

$status_steps = [];
foreach (order_statuses() as $status => $status_data) {
    $status_steps[$status] = [
        'label' => $status_data['tracking_label'],
        'message' => $status_data['tracking_message'],
    ];
}
$status_order = array_keys($status_steps);
$current_status_index = $tracked_order === null
    ? -1
    : array_search($tracked_order['status'], $status_order, true);
$amount_paid = $tracked_order === null ? 0.0 : (float) $tracked_order['amount_paid'];
$balance_due = $tracked_order === null
    ? 0.0
    : max((float) $tracked_order['total_amount'] - $amount_paid, 0.0);
$shop_phone_digits = normalize_phone(SHOP_PHONE);

$public_page = true;
$page_title = 'Track your laundry';
require __DIR__ . '/includes/header.php';
?>
<div class="mx-auto flex w-full max-w-md flex-col gap-6 px-4 py-6 sm:px-6">
    <img class="h-12 w-auto self-start" src="<?= e(url('assets/images/washflow-logo.png')) ?>" alt="WashFlow">
    <section class="space-y-3" aria-labelledby="tracking-heading">
        <p class="inline-flex min-h-11 items-center rounded-full bg-emerald-100 px-4 text-base font-semibold text-emerald-950">WashFlow order tracking</p>
        <h1 id="tracking-heading" class="text-3xl font-bold tracking-tight text-slate-950">Track your laundry</h1>
        <p class="text-lg leading-relaxed text-slate-700">Check your order progress and balance. No account needed.</p>
    </section>

    <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="lookup-heading">
        <h2 id="lookup-heading" class="text-xl font-bold text-slate-950">Find your order</h2>
        <form action="<?= e(url('track.php')) ?>" method="post" class="mt-5 space-y-5" data-tracking-form>
            <?= csrf_field() ?>
            <div>
                <label for="ticket-no" class="mb-1 block text-lg font-semibold text-slate-900">Ticket number</label>
                <input
                    id="ticket-no"
                    class="w-full min-h-14 rounded-lg border border-slate-300 px-4 py-3 text-lg uppercase text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    type="text"
                    name="ticket_no"
                    maxlength="20"
                    autocomplete="off"
                    autocapitalize="characters"
                    placeholder="WF-20261008-A2B3"
                    value="<?= e($ticket_no) ?>"
                    required
                >
                <p class="mt-1 text-base text-slate-700">Printed on your claim ticket</p>
            </div>
            <div>
                <label for="phone-suffix" class="mb-1 block text-lg font-semibold text-slate-900">Last 4 digits of your phone number</label>
                <input
                    id="phone-suffix"
                    class="w-full min-h-14 rounded-lg border border-slate-300 px-4 py-3 text-lg text-slate-950 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                    type="tel"
                    name="phone_suffix"
                    inputmode="numeric"
                    pattern="[0-9]{4}"
                    maxlength="4"
                    autocomplete="tel-national"
                    placeholder="0101"
                    value="<?= e($phone_suffix) ?>"
                    required
                >
                <p class="mt-1 text-base text-slate-700">Last 4 digits of the phone number you gave us</p>
            </div>
            <button
                class="w-full min-h-14 rounded-lg bg-emerald-800 px-5 py-3 text-lg font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 disabled:cursor-wait disabled:opacity-70"
                type="submit"
                data-tracking-submit
            >Track order</button>
        </form>
    </section>

    <?php if ($lookup_failed): ?>
        <section class="rounded-xl border border-red-300 bg-red-50 p-5 text-red-950" role="alert" aria-live="assertive">
            <h2 class="text-lg font-bold">No matching order found.</h2>
            <p class="mt-2 text-lg leading-relaxed">We couldn't find that order. Please check your ticket number and the last 4 digits of your phone number.</p>
        </section>
    <?php elseif ($tracked_order !== null): ?>
        <section class="space-y-5 rounded-xl border border-emerald-200 bg-white p-5 shadow-sm sm:p-6" aria-labelledby="tracking-result-heading">
            <div>
                <p class="text-base font-semibold text-emerald-900">Order status</p>
                <h2 id="tracking-result-heading" class="mt-1 break-all font-mono text-2xl font-bold tracking-[0.12em] text-slate-950"><?= e(format_ticket_no($tracked_order['ticket_no'])) ?></h2>
                <p class="mt-2 text-lg text-slate-800">Hello, <?= e($tracked_order['first_name']) ?>.</p>
                <p class="mt-1 text-base text-slate-700">Order date: <?= e(date('F j, Y', strtotime($tracked_order['created_at']))) ?></p>
            </div>

            <p class="rounded-lg bg-emerald-50 p-4 text-lg font-semibold leading-relaxed text-emerald-950" role="status"><?= e($status_steps[$tracked_order['status']]['message']) ?></p>

            <ol class="space-y-0 md:flex md:items-start md:space-y-0" aria-label="Order progress">
                <?php foreach ($status_steps as $status => $step): ?>
                    <?php
                    $step_index = array_search($status, $status_order, true);
                    $is_complete = $step_index < $current_status_index;
                    $is_current = $step_index === $current_status_index;
                    $step_style = $is_complete || $is_current
                        ? 'border-emerald-700 bg-emerald-800 text-white'
                        : 'border-slate-300 bg-white text-slate-600';
                    ?>
                    <li class="relative flex min-h-12 items-start gap-3 md:min-h-0 md:flex-1 md:flex-col md:items-center md:px-1">
                        <?php if ($step_index < count($status_order) - 1): ?>
                            <span class="absolute left-5 top-10 h-6 w-0.5 bg-slate-300 md:left-1/2 md:top-5 md:h-0.5 md:w-full" aria-hidden="true"></span>
                        <?php endif; ?>
                        <span class="relative z-10 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full border-2 text-base font-bold <?= e($step_style) ?>" aria-hidden="true"><?= $is_complete ? '✓' : (int) ($step_index + 1) ?></span>
                        <span class="pt-2 text-lg <?= $is_current ? 'font-bold text-emerald-950' : ($is_complete ? 'font-semibold text-slate-800' : 'text-slate-600') ?>" <?= $is_current ? 'aria-current="step"' : '' ?>><?= e($step['label']) ?></span>
                    </li>
                <?php endforeach; ?>
            </ol>

            <div>
                <h3 class="text-lg font-bold text-slate-950">Services</h3>
                <?php if ($service_names === []): ?>
                    <p class="mt-2 text-lg text-slate-700">No service details are available.</p>
                <?php else: ?>
                    <ul class="mt-2 list-inside list-disc space-y-1 text-lg text-slate-800">
                        <?php foreach ($service_names as $service_name): ?>
                            <li><?= e($service_name) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>

            <dl class="space-y-2 border-t border-slate-200 pt-4 text-lg">
                <div class="flex justify-between gap-4"><dt class="text-slate-700">Total</dt><dd class="font-semibold text-slate-950"><?= e(format_money($tracked_order['total_amount'])) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="text-slate-700">Amount paid</dt><dd class="font-semibold text-slate-950"><?= e(format_money($amount_paid)) ?></dd></div>
                <div class="flex justify-between gap-4"><dt class="font-semibold text-slate-900">Balance due</dt><dd class="font-bold text-slate-950"><?= e(format_money($balance_due)) ?></dd></div>
            </dl>

            <p class="text-lg leading-relaxed text-slate-700"><?= $balance_due > 0 ? 'Please bring your claim ticket and pay the remaining balance when you pick up your laundry.' : ($tracked_order['status'] === 'claimed' ? 'Thank you for choosing us.' : 'Please bring your claim ticket when you pick up your laundry.') ?></p>
        </section>
    <?php endif; ?>
</div>

<section class="mt-2 border-t border-slate-200 bg-white" aria-label="Shop contact information">
    <div class="mx-auto w-full max-w-md space-y-2 px-4 py-6 text-base text-slate-700 sm:px-6">
        <h2 class="text-lg font-bold text-slate-950"><?= e(SHOP_NAME) ?></h2>
        <?php if ($shop_phone_digits !== ''): ?>
            <p><span class="font-semibold">Phone:</span> <a class="inline-flex min-h-11 items-center text-emerald-900 underline focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e('tel:' . $shop_phone_digits) ?>"><?= e(SHOP_PHONE) ?></a></p>
        <?php else: ?>
            <p><span class="font-semibold">Phone:</span> <?= e(SHOP_PHONE) ?></p>
        <?php endif; ?>
        <p><span class="font-semibold">Opening hours:</span> <?= e(SHOP_HOURS) ?></p>
    </div>
</section>
<script>
    const trackingForm = document.querySelector('[data-tracking-form]');
    const trackingSubmit = document.querySelector('[data-tracking-submit]');

    if (trackingForm && trackingSubmit) {
        trackingForm.addEventListener('submit', function () {
            trackingSubmit.disabled = true;
            trackingSubmit.textContent = 'Checking your order...';
        });
    }
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
