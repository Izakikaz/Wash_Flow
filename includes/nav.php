<?php

$admin_user = is_admin();
$active_area = basename(dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$current_page = basename($_SERVER['SCRIPT_NAME'] ?? '');
$admin_page = in_array($active_area, ['reports', 'services', 'users'], true);
$show_admin_tag = $admin_user || $admin_page;
$daily_links = [
    ['label' => 'Orders', 'path' => 'orders/index.php', 'area' => 'orders'],
    ['label' => 'Customers', 'path' => 'customers/index.php', 'area' => 'customers'],
    ['label' => 'My Account', 'path' => 'auth/change_password.php', 'area' => 'auth'],
];
$admin_links = [
    ['label' => 'Dashboard', 'path' => 'reports/dashboard.php', 'area' => 'reports'],
    ['label' => 'Services', 'path' => 'services/index.php', 'area' => 'services'],
    ['label' => 'Reports', 'path' => 'reports/daily_sales.php', 'area' => 'reports'],
    ['label' => 'Users', 'path' => 'users/index.php', 'area' => 'users'],
];
$link_classes = 'flex min-h-12 items-center rounded-lg px-3 py-2 text-base font-medium transition-colors focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800';
?>
<header class="sticky top-0 z-30 border-b border-slate-200 bg-white/95 shadow-sm backdrop-blur lg:hidden">
    <div class="flex min-h-16 items-center justify-between gap-3 px-4">
        <a class="flex min-h-11 items-center rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(home_url()) ?>">
            <img class="h-9 w-auto" src="<?= e(url('assets/images/washflow-logo.png')) ?>" alt="WashFlow">
        </a>
        <div class="ml-auto flex items-center gap-2">
            <?php if (isset($_SESSION['full_name']) && is_string($_SESSION['full_name'])): ?>
                <?php $user_role_label = is_admin() ? 'Admin' : 'Staff'; ?>
                <details class="group relative shrink-0">
                    <summary class="flex min-h-11 cursor-pointer list-none items-center gap-2 rounded-lg px-2 text-base font-semibold text-slate-800 hover:bg-slate-100 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800">
                        <span class="flex items-center gap-2">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-[#e6eeff] text-sm font-bold text-[#005049]">
                                <?= e(strtoupper(substr($_SESSION['full_name'], 0, 1))) ?>
                            </span>
                            <span><?= e($_SESSION['full_name']) ?></span>
                            <span class="inline-flex items-center rounded-full bg-[#e6eeff] px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-[#005049]">
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
            <?php if ($show_admin_tag): ?>
                <span class="rounded-full bg-[#e6eeff] px-3 py-1 text-sm font-semibold text-[#005049]">Admin</span>
            <?php endif; ?>
            <button
                id="mobile-menu-button"
                class="min-h-11 min-w-11 rounded-lg border border-slate-300 bg-white px-3 text-base font-semibold text-slate-800 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
                type="button"
                aria-label="Open navigation menu"
                aria-expanded="false"
                aria-controls="mobile-navigation"
            >
                Menu
            </button>
        </div>
    </div>

    <form action="<?= e(url('orders/index.php')) ?>" method="get" role="search" class="px-4 pb-3 md:hidden">
        <label for="mobile-order-search" class="sr-only">Search tickets, customer names, or phone numbers</label>
        <input
            id="mobile-order-search"
            class="w-full min-h-11 rounded-lg border border-slate-300 bg-[#eff4ff] px-4 py-2 text-base text-slate-900 placeholder:text-slate-500 focus-visible:bg-white focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800"
            type="search"
            name="q"
            placeholder="Search ticket, customer, or phone"
        >
    </form>

    <div id="mobile-navigation" class="hidden border-t border-slate-200 bg-white px-4 py-3 shadow-lg">
        <nav aria-label="Main navigation" class="space-y-1">
            <?php foreach ($daily_links as $link): ?>
                <a
                    class="<?= $link_classes ?> <?= $active_area === $link['area'] ? 'bg-[#006c49] font-semibold text-white shadow-sm' : 'text-slate-700 hover:bg-[#eff4ff] hover:text-slate-950' ?>"
                    href="<?= e(url($link['path'])) ?>"
                    <?= $active_area === $link['area'] ? 'aria-current="page"' : '' ?>
                ><?= e($link['label']) ?></a>
            <?php endforeach; ?>

            <?php if ($admin_user): ?>
                <h2 class="px-3 pb-1 pt-4 text-sm font-bold uppercase tracking-wide text-slate-600">Admin</h2>
                <?php foreach ($admin_links as $link): ?>
                    <?php
                    $is_active = $link['path'] === 'reports/dashboard.php'
                        ? $active_area === 'reports' && $current_page === 'dashboard.php'
                        : ($link['area'] === 'reports'
                            ? $active_area === 'reports' && $current_page !== 'dashboard.php'
                            : $active_area === $link['area']);
                    ?>
                    <a
                        class="<?= $link_classes ?> <?= $is_active ? 'bg-[#006c49] font-semibold text-white shadow-sm' : 'text-slate-700 hover:bg-[#eff4ff] hover:text-slate-950' ?>"
                        href="<?= e(url($link['path'])) ?>"
                        <?= $is_active ? 'aria-current="page"' : '' ?>
                    ><?= e($link['label']) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>

    </div>
</header>

<aside class="fixed inset-y-0 left-0 z-30 hidden w-64 flex-col border-r border-slate-200 bg-white lg:flex">
    <div class="border-b border-slate-200 px-5 py-4">
        <a class="flex min-h-11 items-center rounded-lg focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-800" href="<?= e(home_url()) ?>">
            <img class="h-11 w-auto" src="<?= e(url('assets/images/washflow-logo.png')) ?>" alt="WashFlow">
        </a>
        <p class="mt-1 text-sm text-slate-600">Laundry operations</p>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-4">
        <?php if (isset($_SESSION['full_name']) && is_string($_SESSION['full_name'])): ?>
            <div class="mb-5 rounded-xl bg-[#eff4ff] px-3 py-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Signed in</p>
                <p class="mt-1 truncate text-base font-semibold text-slate-950"><?= e($_SESSION['full_name']) ?></p>
                <p class="mt-1 text-sm font-medium text-[#006c49]"><?= is_admin() ? 'Admin' : 'Staff' ?> access</p>
            </div>
        <?php endif; ?>

        <nav aria-label="Main navigation" class="space-y-1">
            <?php foreach ($daily_links as $link): ?>
                <a
                    class="<?= $link_classes ?> <?= $active_area === $link['area'] ? 'bg-[#006c49] font-semibold text-white shadow-sm' : 'text-slate-700 hover:bg-[#eff4ff] hover:text-slate-950' ?>"
                    href="<?= e(url($link['path'])) ?>"
                    <?= $active_area === $link['area'] ? 'aria-current="page"' : '' ?>
                ><?= e($link['label']) ?></a>
            <?php endforeach; ?>

            <?php if ($admin_user): ?>
                <h2 class="px-3 pb-1 pt-6 text-sm font-bold uppercase tracking-wide text-slate-600">Admin</h2>
                <?php foreach ($admin_links as $link): ?>
                    <?php
                    $is_active = $link['path'] === 'reports/dashboard.php'
                        ? $active_area === 'reports' && $current_page === 'dashboard.php'
                        : ($link['area'] === 'reports'
                            ? $active_area === 'reports' && $current_page !== 'dashboard.php'
                            : $active_area === $link['area']);
                    ?>
                    <a
                        class="<?= $link_classes ?> <?= $is_active ? 'bg-[#006c49] font-semibold text-white shadow-sm' : 'text-slate-700 hover:bg-[#eff4ff] hover:text-slate-950' ?>"
                        href="<?= e(url($link['path'])) ?>"
                        <?= $is_active ? 'aria-current="page"' : '' ?>
                    ><?= e($link['label']) ?></a>
                <?php endforeach; ?>
            <?php endif; ?>
        </nav>
    </div>

</aside>

<script>
    const menuButton = document.getElementById('mobile-menu-button');
    const mobileNavigation = document.getElementById('mobile-navigation');

    if (menuButton && mobileNavigation) {
        menuButton.addEventListener('click', function () {
            const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
            menuButton.setAttribute('aria-expanded', String(!isOpen));
            menuButton.setAttribute('aria-label', isOpen ? 'Open navigation menu' : 'Close navigation menu');
            menuButton.textContent = isOpen ? 'Menu' : 'Close';
            mobileNavigation.classList.toggle('hidden', isOpen);
        });

        mobileNavigation.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                mobileNavigation.classList.add('hidden');
                menuButton.setAttribute('aria-expanded', 'false');
                menuButton.setAttribute('aria-label', 'Open navigation menu');
                menuButton.textContent = 'Menu';
            });
        });
    }
</script>
