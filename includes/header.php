<?php
/*
 * Shared mobile-first Tailwind patterns:
 * Primary button: w-full sm:w-auto min-h-11 rounded-lg bg-emerald-800 px-4 py-3 font-semibold text-white
 * Secondary button: w-full sm:w-auto min-h-11 rounded-lg border border-slate-300 bg-white px-4 py-3 font-semibold text-slate-800
 * Danger button: w-full sm:w-auto min-h-11 rounded-lg bg-red-700 px-4 py-3 font-semibold text-white
 * Text input: w-full min-h-11 rounded-lg border border-slate-300 px-3 py-2 text-base text-slate-900
 * Label: mb-1 block text-base font-medium text-slate-800
 * Card: rounded-xl border border-slate-200 bg-white p-4 shadow-sm
 * Status badges: Pending=bg-slate-100 text-slate-800; Washing=bg-blue-100 text-blue-900;
 * Drying=bg-amber-100 text-amber-950; Ready=bg-emerald-100 text-emerald-900;
 * Claimed=bg-slate-700 text-white (each badge also uses rounded-full px-3 py-1 text-sm font-semibold)
 * Page container: mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8
 * Responsive table: card list uses md:hidden; the semantic table uses hidden md:table
 * Form grid: grid grid-cols-1 md:grid-cols-2 gap-4
 */

require_once __DIR__ . '/functions.php';

$public_page = $public_page ?? false;
$page_title = $page_title ?? 'WashFlow';
$page_breadcrumb = $page_breadcrumb ?? null;

if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/config.php';
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($page_title) ?> | WashFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-[#f8f9ff] text-base text-[#121c2a] antialiased">
    <div class="min-h-screen lg:flex">
        <?php if (!$public_page): ?>
            <?php require __DIR__ . '/nav.php'; ?>
        <?php endif; ?>

        <div class="min-w-0 flex-1<?= $public_page ? '' : ' lg:pl-64' ?>">
            <?php if (!$public_page): ?>
                <header class="sticky top-0 z-20 hidden min-h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white/95 px-4 shadow-sm backdrop-blur md:flex lg:px-6">
                    <form action="<?= e(url('orders/index.php')) ?>" method="get" role="search" class="hidden w-full max-w-xl md:block">
                        <label for="desktop-order-search" class="sr-only">Search tickets, customer names, or phone numbers</label>
                        <input
                            id="desktop-order-search"
                            class="w-full min-h-11 rounded-lg border border-slate-300 bg-[#eff4ff] px-4 py-2 text-base text-slate-900 placeholder:text-slate-500 focus-visible:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                            type="search"
                            name="q"
                            placeholder="Search ticket, customer, or phone"
                        >
                    </form>
                    <?php if (isset($_SESSION['full_name']) && is_string($_SESSION['full_name'])): ?>
                        <?php $user_role_label = is_admin() ? 'Admin' : 'Staff'; ?>
                        <details class="group relative shrink-0">
                            <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-lg px-3 text-base font-semibold text-slate-800 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800">
                                <span class="flex items-center gap-2">
                                    <span><?= e($_SESSION['full_name']) ?></span>
                                    <span class="inline-flex items-center rounded-full bg-[#e6eeff] px-2 py-0.5 text-xs font-semibold text-[#005049]">
                                        <?= e($user_role_label) ?>
                                    </span>
                                </span>
                                <span aria-hidden="true" class="text-sm text-slate-600">▾</span>
                            </summary>
                            <div class="absolute right-0 top-full z-40 mt-2 w-56 rounded-xl border border-slate-200 bg-white p-2 shadow-lg">
                                <div class="flex items-center gap-2 px-3 py-2 text-sm font-semibold text-slate-800">
                                    <span class="truncate"><?= e($_SESSION['full_name']) ?></span>
                                    <span class="inline-flex items-center rounded-full bg-[#e6eeff] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#005049]">
                                        <?= e($user_role_label) ?>
                                    </span>
                                </div>
                                <a class="flex min-h-11 items-center rounded-lg px-3 text-base text-slate-800 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(url('auth/change_password.php')) ?>">My Account</a>
                                <form action="<?= e(url('auth/logout.php')) ?>" method="post" class="mt-1 border-t border-slate-200 pt-1">
                                    <?= csrf_field() ?>
                                    <button class="flex min-h-11 w-full items-center rounded-lg px-3 text-left text-base font-medium text-red-800 hover:bg-red-50 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-800" type="submit">Logout</button>
                                </form>
                            </div>
                        </details>
                    <?php endif; ?>
                </header>
            <?php endif; ?>

            <main class="min-h-screen">
                <?php if (is_array($page_breadcrumb)): ?>
                    <div class="mx-auto w-full max-w-7xl px-4 pt-4 sm:px-6 lg:px-8 print:hidden">
                        <a
                            class="inline-flex min-h-11 items-center rounded-lg text-base font-semibold text-emerald-900 underline decoration-emerald-300 underline-offset-4 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800 md:hidden"
                            href="<?= e($page_breadcrumb['back_url']) ?>"
                        >← Back to <?= e($page_breadcrumb['back_label']) ?></a>
                        <nav class="hidden text-sm text-slate-600 md:block" aria-label="Breadcrumb">
                            <ol class="flex flex-wrap items-center gap-2">
                                <?php foreach ($page_breadcrumb['items'] as $item_index => $item): ?>
                                    <?php if ($item_index > 0): ?><li aria-hidden="true">›</li><?php endif; ?>
                                    <li>
                                        <?php if (isset($item['url'])): ?>
                                            <a class="font-medium text-emerald-900 underline decoration-emerald-300 underline-offset-4 hover:text-emerald-950" href="<?= e($item['url']) ?>"><?= e($item['label']) ?></a>
                                        <?php else: ?>
                                            <span class="font-medium text-slate-700" aria-current="page"><?= e($item['label']) ?></span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ol>
                        </nav>
                    </div>
                <?php endif; ?>
