<?php

$report_navigation = [
    ['label' => 'Dashboard', 'path' => 'dashboard.php'],
    ['label' => 'Daily Sales', 'path' => 'daily_sales.php'],
    ['label' => 'Pending Pickup', 'path' => 'pending_pickup.php'],
    ['label' => 'Outstanding Balances', 'path' => 'outstanding_balances.php'],
];
$current_report = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="-mx-4 flex gap-2 overflow-x-auto px-4 pb-2 sm:mx-0 sm:flex-wrap sm:overflow-visible sm:px-0" aria-label="Reports">
    <?php foreach ($report_navigation as $report_link): ?>
        <?php $is_current_report = $current_report === $report_link['path']; ?>
        <a
            class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-full border px-4 py-2 text-base font-semibold focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 <?= $is_current_report ? 'border-emerald-800 bg-emerald-800 text-white' : 'border-slate-300 bg-white text-slate-800 hover:bg-slate-100' ?>"
            href="<?= e(url('reports/' . $report_link['path'])) ?>"
            <?= $is_current_report ? 'aria-current="page"' : '' ?>
        ><?= e($report_link['label']) ?></a>
    <?php endforeach; ?>
</nav>
