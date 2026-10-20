<?php

require_once __DIR__ . '/../includes/auth_check.php';

$order_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($order_id === false || $order_id === null || $order_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$order_statement = $conn->prepare(
    'SELECT o.id, o.ticket_no, o.status, o.total_amount, o.payment_status,
            o.created_at, o.is_void, c.name AS customer_name, c.phone AS customer_phone
     FROM orders AS o
     INNER JOIN customers AS c ON c.id = o.customer_id
     WHERE o.id = ?
     LIMIT 1'
);
$order_statement->bind_param('i', $order_id);
$order_statement->execute();
$order = $order_statement->get_result()->fetch_assoc();
$order_statement->close();

if ($order === null) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$item_statement = $conn->prepare(
    'SELECT s.name AS service_name, s.pricing_type, oi.quantity,
            oi.unit_price_snapshot, oi.subtotal
     FROM order_items AS oi
     INNER JOIN services AS s ON s.id = oi.service_id
     WHERE oi.order_id = ?
     ORDER BY oi.id'
);
$item_statement->bind_param('i', $order_id);
$item_statement->execute();
$items = $item_statement->get_result()->fetch_all(MYSQLI_ASSOC);
$item_statement->close();

$payment_statement = $conn->prepare(
    'SELECT COALESCE(SUM(amount), 0.00) AS amount_paid
     FROM payments
     WHERE order_id = ? AND is_void = 0'
);
$payment_statement->bind_param('i', $order_id);
$payment_statement->execute();
$payment_statement->bind_result($amount_paid);
$payment_statement->fetch();
$payment_statement->close();

$total_amount = (float) $order['total_amount'];
$amount_paid = (int) $order['is_void'] === 1 ? 0.0 : (float) $amount_paid;
$balance_due = (int) $order['is_void'] === 1
    ? 0
    : max(0, round($total_amount - $amount_paid, 2));
$tracking_address = url('track.php');
$page_title = 'Claim ticket ' . $order['ticket_no'];
$page_breadcrumb = [
    'back_label' => 'order',
    'back_url' => url('orders/view.php?id=' . (int) $order['id']),
    'items' => [
        ['label' => 'Orders', 'url' => url('orders/index.php')],
        ['label' => $order['ticket_no'], 'url' => url('orders/view.php?id=' . (int) $order['id'])],
        ['label' => 'Claim ticket'],
    ],
];

require __DIR__ . '/../includes/header.php';
?>
<style>
    @media print {
        body {
            background: #fff !important;
        }

        body > div.min-h-screen {
            display: block !important;
            min-height: 0 !important;
        }

        body > div.min-h-screen > aside,
        body > div.min-h-screen > header,
        body > div.min-h-screen > div > header {
            display: none !important;
        }

        body > div.min-h-screen > div {
            padding: 0 !important;
        }

        main {
            min-height: 0 !important;
        }
    }
</style>
<div class="mx-auto w-full max-w-sm px-0 py-0 sm:px-4 sm:py-6 print:max-w-none print:px-0 print:py-0">
    <article class="ticket-paper w-full space-y-5 bg-white px-5 py-6 text-slate-950 sm:rounded-xl sm:border sm:border-slate-200 sm:shadow-sm print:mx-auto print:max-w-sm print:border-0 print:p-0 print:shadow-none">
        <header class="border-b-2 border-dashed border-slate-300 pb-4 text-center">
            <img class="mx-auto mb-3 h-12 w-auto" src="<?= e(url('assets/images/washflow-logo.png')) ?>" alt="WashFlow">
            <p class="text-lg font-bold tracking-tight"><?= e(SHOP_NAME) ?></p>
            <p class="mt-1 text-base text-slate-700"><?= e(SHOP_PHONE) ?></p>
            <p class="text-base text-slate-700"><?= e(SHOP_HOURS) ?></p>
            <h1 class="mt-4 text-xl font-bold tracking-widest">CLAIM TICKET</h1>
        </header>

        <?php if ((int) $order['is_void'] === 1): ?>
            <p class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-center text-base font-semibold text-red-900 print:border print:bg-white" role="status">VOID ORDER — this ticket is not valid for pickup.</p>
        <?php endif; ?>

        <section class="space-y-3" aria-label="Order details">
            <div class="text-center">
                <p class="text-base font-semibold uppercase tracking-wide text-slate-600">Ticket number</p>
                <div class="mt-1 flex flex-wrap items-center justify-center gap-2" data-ticket-copy-container>
                    <p class="break-all font-mono text-2xl font-bold tracking-[0.12em]"><?= e(format_ticket_no($order['ticket_no'])) ?></p>
                    <button class="ticket-controls inline-flex min-h-11 items-center justify-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-800 hover:bg-slate-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 print:hidden" type="button" data-copy-ticket="<?= e($order['ticket_no']) ?>">Copy ticket number</button>
                    <span class="ticket-controls text-sm text-slate-700 print:hidden" role="status" aria-live="polite" data-copy-status></span>
                </div>
            </div>
            <dl class="space-y-2 border-y border-dashed border-slate-300 py-3 text-base">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Date</dt>
                    <dd class="text-right font-medium"><?= e(date('M j, Y g:i A', strtotime($order['created_at']))) ?></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Customer</dt>
                    <dd class="max-w-[65%] break-words text-right font-medium"><?= e($order['customer_name']) ?></dd>
                </div>
                <?php if ($order['customer_phone'] !== null && $order['customer_phone'] !== ''): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">Phone</dt>
                        <dd class="break-all text-right font-medium"><?= e($order['customer_phone']) ?></dd>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Order status</dt>
                    <dd class="text-right font-semibold"><?= e(ucfirst($order['status'])) ?></dd>
                </div>
            </dl>
        </section>

        <section aria-labelledby="ticket-items-heading">
            <h2 id="ticket-items-heading" class="mb-3 text-base font-bold uppercase tracking-wide">Services</h2>
            <?php if ($items === []): ?>
                <p class="text-base text-slate-700">No service lines are recorded for this order.</p>
            <?php else: ?>
                <ul class="space-y-3">
                    <?php foreach ($items as $item): ?>
                        <li class="border-b border-dashed border-slate-300 pb-3">
                            <div class="flex justify-between gap-3">
                                <p class="break-words font-semibold"><?= e($item['service_name']) ?></p>
                                <p class="shrink-0 text-right font-semibold <?= (int) $order['is_void'] === 1 ? 'text-slate-600 line-through' : '' ?>"><?= e(format_money($item['subtotal'])) ?></p>
                            </div>
                            <p class="mt-1 text-base text-slate-600">
                                <?= e(number_format((float) $item['quantity'], 2, '.', '')) ?>
                                <?= $item['pricing_type'] === 'per_kg' ? 'kg' : 'piece(s)' ?>
                                × <?= e(format_money($item['unit_price_snapshot'])) ?>
                                <?= $item['pricing_type'] === 'per_kg' ? 'per kg' : 'each' ?>
                            </p>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="space-y-2 border-t-2 border-dashed border-slate-300 pt-4" aria-label="Payment summary">
            <div class="flex justify-between gap-4 text-base">
                <span class="text-slate-700">Total</span>
                <span class="font-semibold <?= (int) $order['is_void'] === 1 ? 'text-slate-600 line-through' : '' ?>"><?= e(format_money($total_amount)) ?></span>
            </div>
            <div class="flex justify-between gap-4 text-base">
                <span class="text-slate-700">Amount paid</span>
                <span class="font-semibold"><?= e(format_money($amount_paid)) ?></span>
            </div>
            <div class="flex justify-between gap-4 text-lg">
                <span class="font-bold">Balance due</span>
                <span class="font-bold"><?= e(format_money($balance_due)) ?></span>
            </div>
            <p class="pt-1 text-center text-base text-slate-700">
                Payment status:
                <span class="font-semibold"><?= (int) $order['is_void'] === 1 ? 'Voided' : e(ucfirst($order['payment_status'])) ?></span>
            </p>
        </section>

        <section class="border-t border-dashed border-slate-300 pt-4 text-center" aria-labelledby="tracking-heading">
            <h2 id="tracking-heading" class="text-base font-bold">Check your order status</h2>
            <p class="mt-2 break-all font-mono text-base"><?= e($tracking_address) ?></p>
            <p class="mt-2 text-base">Enter ticket number <span class="font-mono font-bold tracking-wider"><?= e(format_ticket_no($order['ticket_no'])) ?></span> and the last 4 digits of your phone number.</p>
        </section>

        <p class="border-t border-dashed border-slate-300 pt-4 text-center text-base text-slate-700">Thank you for choosing <?= e(SHOP_NAME) ?>.</p>
    </article>

    <div class="ticket-controls space-y-3 px-4 py-4 sm:px-0 print:hidden">
        <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" type="button" onclick="window.print()">Print ticket</button>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
