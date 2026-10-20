<?php

require_once __DIR__ . '/../includes/auth_check.php';

$payment_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($payment_id === false || $payment_id === null || $payment_id < 1) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$statement = $conn->prepare(
    'SELECT p.id, p.order_id, p.amount, p.method, p.reference_no, p.received_by,
            p.paid_at, p.is_void, o.ticket_no, o.total_amount, o.is_void AS order_is_void,
            c.name AS customer_name, c.phone AS customer_phone,
            u.full_name AS received_by_name,
            (SELECT COALESCE(SUM(paid.amount), 0.00)
             FROM payments AS paid
             WHERE paid.order_id = p.order_id
               AND paid.is_void = 0
               AND o.is_void = 0
               AND (
                   paid.paid_at < p.paid_at
                   OR (paid.paid_at = p.paid_at AND paid.id <= p.id)
               )) AS paid_through_this_payment
     FROM payments AS p
     INNER JOIN orders AS o ON o.id = p.order_id
     INNER JOIN customers AS c ON c.id = o.customer_id
     INNER JOIN users AS u ON u.id = p.received_by
     WHERE p.id = ?
     LIMIT 1'
);
$statement->bind_param('i', $payment_id);
$statement->execute();
$payment = $statement->get_result()->fetch_assoc();
$statement->close();

if ($payment === null) {
    http_response_code(404);
    require __DIR__ . '/../errors/404.php';
    exit;
}

$total_cents = (int) round((float) $payment['total_amount'] * 100);
$paid_through_cents = (int) round((float) $payment['paid_through_this_payment'] * 100);
$balance_after_payment = (int) $payment['order_is_void'] === 1
    ? 0
    : max(0, $total_cents - $paid_through_cents);
$page_title = 'Payment receipt';
$page_breadcrumb = [
    'back_label' => 'order',
    'back_url' => url('orders/view.php?id=' . (int) $payment['order_id']),
    'items' => [
        ['label' => 'Orders', 'url' => url('orders/index.php')],
        ['label' => $payment['ticket_no'], 'url' => url('orders/view.php?id=' . (int) $payment['order_id'])],
        ['label' => 'Payment receipt'],
    ],
];
$flash_message = flash_get();

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
    <article class="receipt-paper w-full space-y-5 bg-white px-5 py-6 text-slate-950 sm:rounded-xl sm:border sm:border-slate-200 sm:shadow-sm print:mx-auto print:max-w-sm print:border-0 print:p-0 print:shadow-none">
        <header class="border-b-2 border-dashed border-slate-300 pb-4 text-center">
            <img class="mx-auto mb-3 h-12 w-auto" src="<?= e(url('assets/images/washflow-logo.png')) ?>" alt="WashFlow">
            <p class="text-lg font-bold tracking-tight"><?= e(SHOP_NAME) ?></p>
            <p class="mt-1 text-base text-slate-700"><?= e(SHOP_PHONE) ?></p>
            <p class="text-base text-slate-700"><?= e(SHOP_HOURS) ?></p>
            <h1 class="mt-4 text-xl font-bold tracking-widest">PAYMENT RECEIPT</h1>
        </header>

        <?php if ((int) $payment['is_void'] === 1): ?>
            <p class="rounded-lg border border-red-300 bg-red-50 px-3 py-2 text-center text-base font-semibold text-red-900 print:border print:bg-white" role="status">VOID PAYMENT — this payment is not included in the order balance.</p>
        <?php endif; ?>
        <?php if ((int) $payment['order_is_void'] === 1): ?>
            <p class="rounded-lg border border-slate-300 bg-slate-100 px-3 py-2 text-center text-base font-semibold text-slate-800 print:border print:bg-white" role="status">This payment belongs to a void order.</p>
        <?php endif; ?>

        <section class="space-y-3" aria-label="Receipt details">
            <div class="text-center">
                <p class="text-base font-semibold uppercase tracking-wide text-slate-600">Receipt number</p>
                <p class="mt-1 break-all font-mono text-2xl font-bold tracking-wider">PAY-<?= (int) $payment['id'] ?></p>
            </div>
            <dl class="space-y-2 border-y border-dashed border-slate-300 py-3 text-base">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Date and time</dt>
                    <dd class="text-right font-medium"><?= e(date('M j, Y g:i A', strtotime($payment['paid_at']))) ?></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Ticket number</dt>
                    <dd class="break-all text-right font-mono font-semibold"><?= e($payment['ticket_no']) ?></dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Customer</dt>
                    <dd class="max-w-[65%] break-words text-right font-medium"><?= e($payment['customer_name']) ?></dd>
                </div>
                <?php if ($payment['customer_phone'] !== null && $payment['customer_phone'] !== ''): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">Phone</dt>
                        <dd class="break-all text-right font-medium"><?= e($payment['customer_phone']) ?></dd>
                    </div>
                <?php endif; ?>
            </dl>
        </section>

        <section class="space-y-3" aria-label="Payment information">
            <div class="flex justify-between gap-4 text-lg">
                <span class="font-semibold">Amount paid</span>
                <span class="font-bold"><?= e(format_money($payment['amount'])) ?></span>
            </div>
            <dl class="space-y-2 border-t border-dashed border-slate-300 pt-3 text-base">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Method</dt>
                    <dd class="text-right font-medium"><?= e(match ($payment['method']) {
                        'cash' => 'Cash',
                        'gcash' => 'GCash',
                        'other' => 'Other',
                        default => 'Other',
                    }) ?></dd>
                </div>
                <?php if ($payment['reference_no'] !== null && $payment['reference_no'] !== ''): ?>
                    <div class="flex justify-between gap-4">
                        <dt class="text-slate-600">Reference</dt>
                        <dd class="max-w-[65%] break-words text-right font-medium"><?= e($payment['reference_no']) ?></dd>
                    </div>
                <?php endif; ?>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-600">Received by</dt>
                    <dd class="max-w-[65%] break-words text-right font-medium"><?= e($payment['received_by_name']) ?></dd>
                </div>
            </dl>
        </section>

        <section class="rounded-lg border border-emerald-300 bg-emerald-50 p-4 text-center print:border-slate-400 print:bg-white" aria-labelledby="balance-after-heading">
            <h2 id="balance-after-heading" class="text-base font-semibold text-emerald-950">Balance after this payment</h2>
            <p class="mt-1 text-2xl font-bold text-emerald-950"><?= e(format_money($balance_after_payment / 100)) ?></p>
            <?php if ($balance_after_payment === 0): ?>
                <p class="mt-1 text-base font-semibold text-emerald-950">Paid in full</p>
            <?php endif; ?>
        </section>

        <p class="border-t border-dashed border-slate-300 pt-4 text-center text-base text-slate-700">Thank you for your payment.</p>
    </article>

    <div class="receipt-controls space-y-3 px-4 py-4 sm:px-0 print:hidden">
        <button class="w-full min-h-11 rounded-lg bg-emerald-800 px-4 py-3 text-base font-semibold text-white hover:bg-emerald-900 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" type="button" onclick="window.print()">Print receipt</button>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
