<?php

// Escape text before placing it in HTML.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Add visual separators to a ticket number without changing its stored value.
function format_ticket_no(string $ticket_no): string
{
    if (preg_match('/\AWF-([0-9]{4})([0-9]{4})-([A-Z0-9]{4})\z/', $ticket_no, $matches) !== 1) {
        return $ticket_no;
    }

    return 'WF-' . $matches[1] . ' ' . $matches[2] . ' ' . $matches[3];
}

// Send the browser to a project URL and stop the current request.
function redirect(string $path): void
{
    if (headers_sent()) {
        throw new RuntimeException('Cannot redirect after response output has started.');
    }

    $base_url = rtrim(BASE_URL, '/');
    $already_project_url = $base_url !== ''
        && ($path === $base_url || str_starts_with($path, $base_url . '/'));
    $location = $already_project_url ? $path : url($path);

    header('Location: ' . $location);
    exit;
}

// Save one message for display after the next redirect.
function flash_set(string $type, string $msg): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Start the session before saving a flash message.');
    }

    $_SESSION['flash'] = ['type' => $type, 'message' => $msg];
}

// Retrieve and clear the message saved for this request.
function flash_get(): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE || !isset($_SESSION['flash'])) {
        return null;
    }

    $message = $_SESSION['flash'];
    unset($_SESSION['flash']);

    return $message;
}

// Check whether the signed-in user's session role is admin.
function is_admin(): bool
{
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Restrict the current page to administrators.
function require_admin(): void
{
    require_role('admin');
}

// Keep status labels and badge colors consistent across order screens and reports.
function order_statuses(): array
{
    return [
        'pending' => [
            'label' => 'Pending',
            'badge' => 'bg-slate-100 text-slate-800',
            'tracking_label' => 'Received',
            'tracking_message' => 'We have received your laundry and it is waiting to be washed.',
        ],
        'washing' => [
            'label' => 'Washing',
            'badge' => 'bg-blue-100 text-blue-900',
            'tracking_label' => 'Washing',
            'tracking_message' => 'We\'re washing your clothes.',
        ],
        'drying' => [
            'label' => 'Drying',
            'badge' => 'bg-amber-100 text-amber-950',
            'tracking_label' => 'Drying',
            'tracking_message' => 'Your laundry is drying.',
        ],
        'ready' => [
            'label' => 'Ready for pickup',
            'badge' => 'bg-emerald-100 text-emerald-900',
            'tracking_label' => 'Ready for pickup',
            'tracking_message' => 'Your laundry is ready for pickup. Please bring your claim ticket.',
        ],
        'claimed' => [
            'label' => 'Claimed',
            'badge' => 'bg-slate-700 text-white',
            'tracking_label' => 'Picked up',
            'tracking_message' => 'This order has been picked up.',
        ],
    ];
}

// Return the next order status, or null after the final status.
function next_status(string $current): ?string
{
    $statuses = [
        'pending' => 'washing',
        'washing' => 'drying',
        'drying' => 'ready',
        'ready' => 'claimed',
    ];

    return $statuses[$current] ?? null;
}

// Generate an unused ticket number, checking for existing orders up to five times.
function generate_ticket_no(mysqli $conn): string
{
    $characters = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $statement = $conn->prepare('SELECT 1 FROM orders WHERE ticket_no = ? LIMIT 1');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $suffix = '';

        for ($index = 0; $index < 4; $index++) {
            $suffix .= $characters[random_int(0, strlen($characters) - 1)];
        }

        $ticket_no = 'WF-' . date('Ymd') . '-' . $suffix;
        $statement->bind_param('s', $ticket_no);
        $statement->execute();
        $statement->store_result();
        $already_exists = $statement->num_rows > 0;
        $statement->free_result();

        if (!$already_exists) {
            $statement->close();
            return $ticket_no;
        }
    }

    $statement->close();
    throw new RuntimeException('Could not generate a unique ticket number after five attempts.');
}

// Calculate a service line amount rounded to cents.
function compute_line_total($qty, $unit_price): float
{
    if (!is_numeric($qty) || !is_numeric($unit_price) || $qty < 0 || $unit_price < 0) {
        throw new InvalidArgumentException('Quantity and unit price must be non-negative numbers.');
    }

    return round((float) $qty * (float) $unit_price, 2);
}

// Build a URL using the configured project folder.
function url(string $path = ''): string
{
    $base_url = rtrim(BASE_URL, '/');
    $path = ltrim($path, '/');

    if ($path === '') {
        return $base_url === '' ? '/' : $base_url . '/';
    }

    return ($base_url === '' ? '' : $base_url) . '/' . $path;
}

// Return a hidden field containing the session's CSRF token.
function csrf_field(): string
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        throw new RuntimeException('Start the session before creating a CSRF token.');
    }

    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return '<input type="hidden" name="csrf_token" value="' . e($_SESSION['csrf_token']) . '">';
}

// Reject requests that do not contain the current session's CSRF token.
function csrf_verify(): void
{
    $submitted_token = $_POST['csrf_token'] ?? '';
    $session_token = $_SESSION['csrf_token'] ?? '';

    if (
        session_status() !== PHP_SESSION_ACTIVE
        || !is_string($submitted_token)
        || !is_string($session_token)
        || $session_token === ''
        || !hash_equals($session_token, $submitted_token)
    ) {
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit;
    }
}

// Keep only digits when saving a customer's phone number for tracking.
function normalize_phone(string $phone): string
{
    return preg_replace('/\D+/', '', $phone) ?? '';
}

// Validate paging values and calculate a safe page, limit, and row offset.
function paginate($total, $page, $per_page): array
{
    $total = filter_var($total, FILTER_VALIDATE_INT);
    $page = filter_var($page, FILTER_VALIDATE_INT);
    $per_page = filter_var($per_page, FILTER_VALIDATE_INT);

    if ($total === false || $total < 0 || $page === false || $page < 1 || $per_page === false || $per_page < 1) {
        throw new InvalidArgumentException('Pagination values must be valid non-negative or positive integers.');
    }

    $total_pages = max(1, (int) ceil($total / $per_page));
    $page = min($page, $total_pages);

    return [
        'total' => $total,
        'page' => $page,
        'per_page' => $per_page,
        'total_pages' => $total_pages,
        'offset' => ($page - 1) * $per_page,
    ];
}

// Return a safe positive page number from the current GET request.
function requested_page_number(string $parameter = 'page'): int
{
    $page = filter_input(INPUT_GET, $parameter, FILTER_VALIDATE_INT);

    return $page === false || $page === null || $page < 1 ? 1 : $page;
}

// Choose the correct home page for the signed-in user's role.
function home_url(): string
{
    if (is_admin()) {
        return url('reports/dashboard.php');
    }

    if (isset($_SESSION['role']) && $_SESSION['role'] === 'staff') {
        return url('orders/index.php');
    }

    return url('auth/login.php');
}

// Show the friendly forbidden page when a session lacks the required role.
function require_role(string $role): void
{
    if (!in_array($role, ['admin', 'staff'], true)) {
        throw new InvalidArgumentException('The requested user role is not supported.');
    }

    if (!isset($_SESSION['role']) || $_SESSION['role'] !== $role) {
        http_response_code(403);
        require __DIR__ . '/../errors/403.php';
        exit;
    }
}

// Return zero for a void order; otherwise subtract payments that have not been voided.
function get_order_balance(mysqli $conn, int $order_id): float
{
    $statement = $conn->prepare(
        'SELECT CASE
                   WHEN o.is_void = 1 THEN 0.00
                   ELSE o.total_amount - COALESCE(SUM(p.amount), 0.00)
               END AS balance_due
         FROM orders AS o
         LEFT JOIN payments AS p ON p.order_id = o.id AND p.is_void = 0
         WHERE o.id = ?
         GROUP BY o.id, o.total_amount, o.is_void'
    );
    $statement->bind_param('i', $order_id);
    $statement->execute();
    $statement->bind_result($balance_due);

    if (!$statement->fetch()) {
        $statement->close();
        throw new RuntimeException('The requested order does not exist.');
    }

    $statement->close();
    return round((float) $balance_due, 2);
}

// Update payment status using only payments on an active order that are not voided.
function recalc_payment_status(mysqli $conn, int $order_id): string
{
    $statement = $conn->prepare(
        'SELECT o.total_amount, COALESCE(SUM(p.amount), 0.00) AS amount_paid
         FROM orders AS o
         LEFT JOIN payments AS p ON p.order_id = o.id AND p.is_void = 0 AND o.is_void = 0
         WHERE o.id = ?
         GROUP BY o.id, o.total_amount'
    );
    $statement->bind_param('i', $order_id);
    $statement->execute();
    $statement->bind_result($total_amount, $amount_paid);

    if (!$statement->fetch()) {
        $statement->close();
        throw new RuntimeException('The requested order does not exist.');
    }

    $statement->close();
    $total_amount = round((float) $total_amount, 2);
    $amount_paid = round((float) $amount_paid, 2);

    if ($amount_paid <= 0) {
        $payment_status = 'unpaid';
    } elseif ($amount_paid < $total_amount) {
        $payment_status = 'partial';
    } else {
        $payment_status = 'paid';
    }

    $statement = $conn->prepare('UPDATE orders SET payment_status = ? WHERE id = ?');
    $statement->bind_param('si', $payment_status, $order_id);
    $statement->execute();
    $statement->close();

    return $payment_status;
}

// Format a money amount as Philippine pesos with two decimal places.
function format_money($amount): string
{
    if (!is_numeric($amount)) {
        throw new InvalidArgumentException('Money amounts must be numeric.');
    }

    return '₱' . number_format((float) $amount, 2, '.', ',');
}
