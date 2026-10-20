<?php

$flash_message = ($public_page ?? false) ? null : flash_get();
$flash_classes = [
    'success' => 'border-emerald-300 bg-emerald-50 text-emerald-950',
    'error' => 'border-red-300 bg-red-50 text-red-950',
    'warning' => 'border-amber-300 bg-amber-50 text-amber-950',
    'info' => 'border-blue-300 bg-blue-50 text-blue-950',
];
$flash_class = 'border-slate-300 bg-slate-100 text-slate-900';

if ($flash_message !== null && isset($flash_classes[$flash_message['type'] ?? ''])) {
    $flash_class = $flash_classes[$flash_message['type']];
}
?>
            </main>
            <?php if ($flash_message !== null): ?>
                <div class="mx-auto w-full max-w-7xl px-4 pb-6 sm:px-6 lg:px-8" role="status" aria-live="polite">
                    <p class="rounded-lg border px-4 py-3 text-base <?= e($flash_class) ?>">
                        <?= e($flash_message['message'] ?? '') ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        document.querySelectorAll('[data-copy-ticket]').forEach(function (button) {
            button.addEventListener('click', async function () {
                const container = button.closest('[data-ticket-copy-container]');
                const status = container ? container.querySelector('[data-copy-status]') : null;

                try {
                    if (!navigator.clipboard || !navigator.clipboard.writeText) {
                        throw new Error('Clipboard access is unavailable.');
                    }

                    await navigator.clipboard.writeText(button.dataset.copyTicket);
                    if (status) {
                        status.textContent = 'Ticket number copied.';
                    }
                } catch (error) {
                    if (status) {
                        status.textContent = 'Could not copy. Select the ticket number and copy it manually.';
                    }
                }
            });
        });
    </script>
</body>
</html>
