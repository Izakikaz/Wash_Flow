# WashFlow — Master Prompt & Per-Phase Prompts

Companion to `WashFlow-ROADMAP.md`. Put both files in the project at `docs/` (rename the roadmap to `docs/ROADMAP.md`) so the agent can read them.

> **Updated for mobile-first:** every prompt below builds for phones (about 360px) first and enhances for tablet and desktop.
> **Also includes:** secure login (lockout, idle timeout, cookie flags), password change, admin user management, partial payments with receipts, a public customer tracking page, and a weekly schedule (see section 7 of `WashFlow-ROADMAP.md`).
> **Hardening:** CSRF tokens, database-based tracking throttle, void (corrections) for orders and payments, time zone, `BASE_URL`, pagination, error pages, Git hygiene, and Must/Should/Could priorities (ROADMAP section 2.3).
> **Roles & UX:** access matrix and role landing pages (ROADMAP 2.4) and user-friendly design for staff, admin, and customers (ROADMAP 2.5).

**How to use**
1. Paste the **Master Prompt** once, at the start of the session (or into the agent's project/system instructions, e.g. `CLAUDE.md`, `AGENTS.md`, or `.cursorrules`).
2. For each piece of work, paste the matching **Phase Prompt**.
3. Review and test the result in XAMPP before moving on. Commit after each working task.
4. Use the **Utility Prompts** at the end when something breaks or drifts.

Placeholders in `[BRACKETS]` are for you to fill in.

---

## 1. Master Prompt

```
# ROLE
You are a senior PHP/MySQL developer and mentor helping a student team build
"WashFlow", an Order & Service Tracking System for laundry shops (school project
IPT 1, Holy Child Central Colleges, College of Computer Studies). The team must be
able to understand, run, and defend every line of code, so write clear,
student-readable code with short comments.

# SOURCES OF TRUTH
1. docs/ROADMAP.md — phases, schema, conventions, acceptance criteria.
2. docs/prototype/ — Google Stitch screenshots. These are the visual spec.
Read ROADMAP.md fully before doing anything. If a request conflicts with it,
tell me before proceeding.

# STRICT TECH STACK (NON-NEGOTIABLE — instructor's Tech Alignment Check)
- Frontend: HTML5 semantic structure + Tailwind CSS
- Backend: plain PHP 8 with CRUD operations, using mysqli (consistently across the whole project)
- Persistence: MySQL relational database with 2+ related tables (foreign keys)
- No tools outside the course stack. No external frameworks.

FORBIDDEN (do not add, suggest, or import): PHP frameworks (Laravel, CodeIgniter,
Symfony, Slim...), Composer/npm packages, Bootstrap, jQuery, Alpine, React, Vue,
any JS framework or library, chart libraries (Chart.js, D3...), ORMs, query
builders, template engines, icon fonts, CDNs other than the course-approved way
of loading Tailwind. Vanilla JavaScript is allowed only where truly needed
(live total preview, confirm dialogs, print button).
If a feature seems to need something forbidden, solve it with the allowed stack
or STOP and ask me.

# CODING RULES
- Prepared statements with bound parameters for ALL SQL. Never concatenate input.
- Escape every echoed value with e() (htmlspecialchars, ENT_QUOTES, UTF-8).
- Passwords: password_hash() / password_verify() only.
- Every protected page includes includes/auth_check.php first; enforce roles
  (admin / staff) on the server, not just by hiding links.
- Validate all input on the server (required, numeric range, enum values).
- POST for changes, GET for filters. Use Post/Redirect/Get with flash messages.
- Money is DECIMAL(10,2) and computed on the server in PHP (JS is preview only).
- Use database transactions when writing more than one table.
- Semantic HTML5 (header, nav, main, section, form, table with thead/tbody);
  every input has a <label>; one <h1> per page.
- Tailwind utility classes in markup; reuse the shared class patterns
  (buttons, inputs, tables, cards, flash messages, status badges).
- snake_case for DB columns, PHP variables, and functions; lowercase filenames.
- Auth security: idle timeout 30 minutes; secure session cookie flags (httponly,
  samesite=Lax, secure on HTTPS) and strict mode; regenerate the session ID on
  login; lockout after 5 failed logins for 15 minutes stored in the database; one
  generic login error; deactivated users cannot log in; users are deactivated,
  never deleted; passwords minimum 8 characters.
- track.php is the ONLY page without auth_check.php and must show nothing beyond
  the fields defined in ROADMAP.md.
- Payments: append-only; balance due = orders.total_amount - SUM(payments.amount)
  computed on the server; reject zero, negative, and over-balance amounts inside a
  transaction that locks the order row; recompute orders.payment_status after every
  payment; an order cannot be Claimed while a balance exists.
- CSRF: every POST form has csrf_field() and every POST handler calls
  csrf_verify() first (random token in the session, hash_equals); logout is a POST.
- Use url() for every link, form action, redirect, and asset path (BASE_URL in
  config/config.php). Never hard-code ../ paths.
- Timezone: Asia/Manila in PHP (date_default_timezone_set) and SET time_zone =
  '+08:00' in MySQL after connecting.
- Errors: display_errors off unless DEBUG; log to logs/error.log; friendly
  403/404/500 pages. config/config.php and logs/ are in .gitignore; only
  config/config.sample.php is committed.
- Pagination: 15 rows per page on every list via ?page= (validated positive
  integer, bound LIMIT/OFFSET).
- Phones: store digits only in customers.phone_digits via normalize_phone().
- Ticket numbers: WF-YYYYMMDD- plus 4 random characters, UNIQUE, retry on duplicate.
- Orders and payments are NEVER edited or deleted; admins VOID them (reason
  required, not allowed on Claimed orders) and voided rows (is_void = 1) are
  excluded from every total, report, and balance.
- Accessibility: <html lang="en">, visible focus outlines, contrast at least 4.5:1,
  labels on inputs, status shown by text as well as color, aria-label on icon buttons.
- Priority: build MUST items first, then SHOULD, then COULD (ROADMAP section 2.3).
  Ask me before starting a COULD item.
- Do not modify sql/schema.sql without telling me and explaining why.
- No debug output, dead code, or commented-out blocks left behind.

# MOBILE-FIRST RESPONSIVE DESIGN (REQUIRED ON EVERY PAGE)
Staff use phones and tablets at the counter, so design and code for mobile FIRST.
- Unprefixed Tailwind classes define the MOBILE layout (~360px). Enhance ONLY by
  adding sm: (640), md: (768), lg: (1024), xl: (1280). Never write desktop styles
  first and then override them for mobile.
- includes/header.php must contain
  <meta name="viewport" content="width=device-width, initial-scale=1">.
- No horizontal page scroll at 360px. No fixed pixel widths (avoid w-[600px]);
  use w-full, max-w-*, flex, grid.
- Tables: on mobile render each row as a stacked card (md:hidden card list +
  hidden md:table table, or equivalent). overflow-x-auto is a fallback only.
- Navigation: top bar with a menu button on mobile (small vanilla JS toggle or
  <details>, with aria-label and aria-expanded); left sidebar from lg: upward.
- Touch targets at least 44x44px (min-h-11) with spacing between them. Primary
  actions are full-width on mobile (w-full sm:w-auto). An optional sticky bottom
  action bar (fixed inset-x-0 bottom-0 md:static) is allowed; add bottom padding
  so it never covers content.
- Forms: single column on mobile, labels above inputs, text-base (16px) inputs,
  correct types: type="tel", type="number" inputmode="decimal", type="date",
  type="search".
- Status stepper: vertical on mobile, horizontal from md:.
- Test every page at 360px, 390px, 768px, 1024px, and 1280px before reporting.
- Responsive behavior uses Tailwind utilities and tiny vanilla JS only. No CSS
  frameworks, no responsive libraries.

# ACCESS & ROLES (ROADMAP 2.4)
- Three audiences: public (track.php only), staff, admin. Follow the access matrix exactly.
- home_url(): admin -> reports/dashboard.php, staff -> orders/index.php. The login
  redirect, index.php, and the logo/Home link use it.
- Admin-only pages call require_role('admin') right after auth_check.php. A staff
  user gets errors/403.php (never a redirect loop or blank page). Logged out ->
  login page.
- Hiding a link is never enough: every admin page AND every POST handler checks the
  role on the server.
- Staff never see revenue totals, sales reports, user management, service
  management, or void actions.
- Navigation is grouped: daily-work links first, then an "Admin" section heading
  with admin-only links; admin pages show a small "Admin" tag in the header.

# USER-FRIENDLY DESIGN (REQUIRED — ROADMAP 2.5)
- Plain everyday language and consistent terms (Ready for pickup, Claimed, Balance
  due). No codes or jargon.
- One clear primary action per screen, same place and style everywhere.
- Quick search (ticket, name, phone) always reachable for staff.
- Sensible defaults: today's date, payment amount = balance due, most-used services
  first, first field auto-focused.
- Field-level error messages next to the field, keep entered values; success
  messages say what happened and offer the next step.
- Friendly empty states with a button.
- Confirm risky actions (void, deactivate, reset password, claim) with a dialog
  explaining the effect; danger style, kept apart from normal buttons.
- Disable submit buttons after the first click (tiny vanilla JS) plus server-side
  duplicate protection.
- Helper text under non-obvious fields; correct input types.
- Text at least 16px; 18px+ on the customer page (track.php); never rely on color
  alone.
- Customer page: simple, reassuring words, no menu, shop name/phone/hours from
  SHOP_NAME, SHOP_PHONE, SHOP_HOURS (config.php), one friendly error message.

# FOLDER STRUCTURE
Follow section 3.1 of docs/ROADMAP.md exactly. Do not invent new top-level folders.

# WORKFLOW
- Work on ONE task at a time from the phase I name. Do not jump ahead.
- Before writing code, give a 3–6 line plan listing the files you will create or edit.
- After finishing, STOP and report using this format:
  1. Files created/edited
  2. What it does (2–4 lines)
  3. Acceptance criteria satisfied (from ROADMAP.md)
  4. How I should test it manually in XAMPP (exact URLs and steps, including at
     360px mobile width and at desktop width)
  5. Anything you assumed or any deviation from the roadmap
- If requirements are ambiguous, ask ONE short clarifying question instead of guessing.
- When I paste an error, find the root cause and fix that; don't rewrite unrelated files.

# STYLE OF EXPLANATION
Explain like a mentor to a college student: brief, concrete, no jargon without a
one-line definition. Prefer simple solutions over clever ones.

Reply "Ready" after you have read ROADMAP.md and summarized, in 5 bullets,
the rules you will follow.
```

---

## 2. Phase Prompts

### Phase 1 — Prototype in Google Stitch (human step, not for the coding agent)

Use these in Google Stitch. Paste the **Design Direction** first, then one **Screen Prompt** at a time. Refine with follow-ups like "make the status badges color-coded" or "make the table denser".

**Design Direction (prefix for every screen)**
```
Design a MOBILE-FIRST admin web app for a small laundry shop called WashFlow,
used mostly on phones (390px wide) at the shop counter, and also on tablet and
desktop. Style: clean, minimal, professional. Palette: mint green and teal as
primary colors, charcoal text, white background, light gray borders. One
sans-serif font. Mobile: top app bar with a menu button that opens the
navigation (Dashboard, Orders, Customers, Services, Reports, Users for admins, My Account, Logout), large
thumb-friendly buttons (at least 44px tall), single-column forms with labels
above inputs, data lists shown as stacked cards instead of wide tables, and
primary actions full-width at the bottom. Desktop: the same content adapted
with a persistent left sidebar and real tables. Consistent buttons, form
fields, tables/cards on every screen. Status badge colors: Pending = gray,
Washing = blue, Drying = amber, Ready = green, Claimed = dark slate.

USER-FRIENDLY RULES: use plain everyday words (say "Ready for pickup", not codes);
one clear primary action per screen; friendly empty states with a button; helper
text under fields; clear inline error messages; confirmation dialogs for risky
actions; generous spacing, readable text (16px minimum) and high contrast.
TWO EXPERIENCES with the same brand: (1) the staff/admin counter app: efficient,
quick search always visible, admin-only areas labeled "Admin"; (2) the customer
order tracking page: extremely simple and friendly, extra-large text (18px+),
reassuring wording, no navigation menu.
```

**Screen prompts** (append after the Design Direction). For EACH screen, first generate the **mobile version (390px)**. When approved, ask Stitch: *"Now adapt this screen for desktop (1280px) with a sidebar and table layout, keeping the same design system."*
```
1. Login screen: WashFlow logo text, username and password fields, Sign In button, error message area.

2. Admin dashboard (tagged "Admin"): cards for Today's Sales, Orders Today, Ready for Pickup, Outstanding Balance; a "Needs attention" panel listing orders that have been Ready for more than 3 days and orders with an unpaid balance older than 7 days (each row tappable); quick action buttons (New Order, Record Payment, Add Service); a count of orders per status; the 5 most recent orders.

3. New Order form: customer search/select with "add new customer" link; repeating service lines (service dropdown, weight in kg or piece count, unit price, line subtotal); live grand total; Save Order and Save & Print Ticket buttons.

4. Customers: searchable table (name, phone, address, total orders, actions Edit/Delete) and an Add/Edit customer form (name, phone, address).

5. Orders list (this is the STAFF HOME): a big "New Order" button, a quick search box (ticket, customer name, or phone), a "Today at a glance" row with counts per status (no revenue figures), filter tabs by status, the list of active orders (ticket, customer, total, status badge, date, View button), pagination, and a friendly empty state ("No orders yet. Create the first order.") with a New Order button.

6. Order detail: header with ticket number and status badge, customer info, items table, total, payment status, a prominent "Advance to next status" button and a status progress stepper (Pending → Washing → Drying → Ready → Claimed).

7. Printable claim ticket: narrow receipt-style layout with shop name, ticket number, date, customer, items, total, and a "Claim this ticket to pick up your laundry" footer. Black and white, print-friendly.

8. Services & Pricing: table (service name, pricing type per kg / per piece, unit price, active toggle, Edit) and an Add/Edit service form.

9. Pending pickup list: table of orders with status Ready (ticket, customer, phone, total, days waiting), oldest first.

10. Daily sales report: date picker, summary cards (total sales, number of orders), table of that day's orders.

11. Change password: current password, new password, confirm password fields with show/hide toggles, password rules hint (minimum 8 characters), Save button, success and error messages.

12. Users management (admin): list of staff accounts (name, username, role badge, active/inactive badge, last login) with Add User, Edit, Reset Password, and Deactivate actions; an Add/Edit user form (full name, username, role, temporary password); a confirmation for reset and deactivate.

13. Record payment: order summary (ticket, customer, total, already paid, BALANCE DUE highlighted), payment history list, form with amount (defaults to balance due), method selector (Cash, GCash, Other), optional reference number, and a Record Payment button.

14. Payment receipt: narrow receipt-style layout with shop name, receipt number, date and time, ticket number, customer, amount paid, method, reference, balance after payment, received by, and a Print button. Black and white, print-friendly.

15. Outstanding balances: table of orders with a balance due (ticket, customer, phone, total, paid, balance, days since order), total outstanding amount at the top, oldest first.

16. Public order tracking page (customer-facing, no login, no sidebar): simple friendly page with the WashFlow name, a form (ticket number and last 4 digits of phone number, Track button), and a result state showing a status stepper (Pending, Washing, Drying, Ready, Claimed), a friendly message like "Your laundry is ready for pickup", order date, services, total, paid, balance due. Also an error state with one generic message. Very simple, phone-first, extra-large friendly text (18px or more), helper text under each field ("Printed on your claim ticket" and "Last 4 digits of the phone number you gave us"), reassuring plain-words status messages ("We're washing your clothes"), and the shop name, phone number (tap to call) and opening hours at the bottom. No menu and no login button. The error state says: "We couldn't find that order. Please check your ticket number and the last 4 digits of your phone number."

17. (Stretch) Analytics: best-selling services as horizontal bars and revenue per day as vertical bars, simple and flat.
```

**Mobile behavior follow-ups (paste per screen if Stitch doesn't do it by itself)**
```
- New Order (mobile): each service line is its own card with stacked fields; a sticky bottom bar shows the grand total and a full-width Save button.
- Lists (Orders, Customers, Services, Pending pickup, Daily sales): each row becomes a card with the key info at the top (ticket/name, status badge, total) and a full-width or large View/Edit button.
- Order detail (mobile): vertical status stepper, and a large sticky "Advance to next status" button at the bottom.
- Dashboard (mobile): summary cards stacked in one column, then recent orders as cards.
- Claim ticket (mobile): narrow receipt layout that fills the screen width; Print button at the bottom.
- Filters (status tabs): a horizontally scrollable row of pills on mobile.
- Record payment (mobile): balance due shown at the top in a large highlighted card; amount field with numeric keypad; full-width Record Payment button.
- Users management (mobile): each account as a card with badges and large action buttons; confirmations as full-screen or bottom-sheet style dialogs.
- Public tracking (mobile): centered card, large inputs, big Track button; result shows a vertical stepper and the balance due prominently.
- Void (admin): a confirmation dialog with a required reason field and a danger button; voided rows show a gray "Void" badge with struck-through amounts.
- Lists: pagination with large Previous / Next buttons and "Page X of Y".
- Empty states: every list has a friendly message and a button (e.g. "No customers yet. Add your first customer.").
- Field help: short helper text under non-obvious fields (e.g. "Weight in kilograms, e.g. 3.5") and inline error messages under the field.
- Risky actions (void, deactivate, reset password, claim): confirmation dialog explaining the effect in one sentence, with a danger button and a Cancel button.
- Friendly error pages (403 "You don't have access to this page", 404 "Page not found", "Your session expired"): plain words, what happened, and a button back to home.
```

**Phase 1 exit checklist**
- Screens 1–16 exported to `docs/prototype/` in BOTH versions: `01-login-mobile.png`, `01-login-desktop.png`, `02-dashboard-mobile.png`, ...
- `docs/prototype/README.md` lists hex colors, font, and shared components.
- All five members approved.

---

### Phase 2 — Database Design

```
PHASE 2 — DATABASE DESIGN

Task: Create the database scripts for WashFlow.

Do:
1. Create sql/schema.sql using the reference schema in docs/ROADMAP.md
   (users, customers, services, orders, order_items, payments) with all foreign keys,
   ENUMs, the unit_price_snapshot column, the users security columns (is_active,
   must_change_password, failed_attempts, locked_until, last_login_at), and
   orders.payment_status with values unpaid, partial, paid; the void columns on orders and
   payments (is_void, voided_at, void_reason, voided_by); customers.phone_digits; the
   tracking_attempts table; and the indexes defined in ROADMAP.md. Keep names exactly as specified
   unless something is a clear bug; if you change anything, tell me why.
2. Create sql/seed.sql with: 1 admin user and 1 staff user (generate real
   password_hash values using PHP password_hash and tell me the plain-text
   passwords), 6–8 services (mix of per_kg and per_piece), 8–10 customers,
   and 15–20 orders with matching order_items spread across the last 7 days
   and covering ALL five statuses. Make total_amount match the item subtotals. Also seed payments so some orders are
   unpaid, some partially paid, and some fully paid, with orders.payment_status
   matching the payment sums and every claimed order fully paid. Every customer
   needs a phone number with at least 4 digits (used by the tracking page); fill
   customers.phone_digits (digits only). Include one voided order and one voided
   payment (with reasons) so reports can be tested for exclusion.
3. Write docs/SETUP.md with exact steps to import both files in XAMPP
   phpMyAdmin and verify the tables exist.
4. Provide 5 verification SQL queries (row counts, an orders→order_items→services
   join, total check) I can run to confirm the data is consistent.

Do NOT create any PHP pages yet. Stop after reporting using the standard format.
```

---

### Phase 3 — Project Foundation (Auth + shared layout)

**3a — Skeleton, config, helpers**
```
PHASE 3a — FOUNDATION SKELETON

Task: Create the folder structure from ROADMAP.md section 3.1 (empty folders
with a placeholder only where needed) plus these files:
- config/config.php (GITIGNORED) — DB constants (host localhost, db washflow, user
  root, empty password: XAMPP defaults), BASE_URL (e.g. /washflow), DEBUG flag,
  date_default_timezone_set('Asia/Manila'), error settings (display_errors only
  when DEBUG; otherwise log to logs/error.log). Also define SHOP_NAME, SHOP_PHONE,
  and SHOP_HOURS (used on tickets, receipts, and the tracking page).
- config/config.sample.php — the same file with placeholder values, COMMITTED.
- .gitignore — config/config.php and logs/.
- config/db.php — one connection (mysqli) reading config.php; on failure
  show a friendly message and stop; set charset utf8mb4; run SET time_zone = '+08:00'.
- errors/403.php, errors/404.php, errors/500.php — simple friendly pages.
- includes/functions.php — e(), redirect($path), flash_set($type,$msg),
  flash_get(), is_admin(), require_admin(), next_status($current) returning the
  next allowed status or null, generate_ticket_no($conn) (WF-YYYYMMDD- plus 4 random characters from
  ABCDEFGHJKMNPQRSTUVWXYZ23456789; retry up to 5 times on a duplicate-key error),
  compute_line_total($qty,$unit_price), url($path), csrf_field(), csrf_verify(),
  normalize_phone($s), paginate($total,$page,$per_page),
  home_url() (admin -> reports/dashboard.php, staff -> orders/index.php),
  require_role($role) (shows errors/403.php; never a redirect loop),
  get_order_balance($conn,$order_id) (ignores voided payments),
  recalc_payment_status($conn,$order_id),
  format_money($amount), and is_public_page() helpers as needed.
  Each function gets a one-line comment.

No pages or styling yet. Stop and report using the standard format.
```

**3b — Shared layout**
```
PHASE 3b — SHARED LAYOUT

Task: Build includes/header.php, includes/nav.php, includes/footer.php using
semantic HTML5 and Tailwind, MOBILE-FIRST, matching the mobile and desktop
versions in docs/prototype/ (colors, layout).
- header.php: <head> with the viewport meta tag, title variable, Tailwind loaded
  the course-approved way (Tailwind CDN script tag: <script src="https://cdn.tailwindcss.com"></script>), opens
  <body> and the main layout (mobile: single column with a top bar; from lg:
  sidebar + content area).
- nav.php: on mobile a top bar with the app name and a menu button that toggles
  a full-width menu (tiny vanilla JS or <details>; aria-label and aria-expanded;
  closes after tapping a link); from lg: a persistent left sidebar. Links:
  Dashboard, Orders, Customers, Services, Reports, Users (admin only), My
  Account (change password), Logout; highlight current page; hide Services,
  Reports, and Users for the staff role. Group the links: daily-work links first
  (Orders, Customers, My Account), then an "Admin" section heading with the
  admin-only links (Dashboard, Services, Reports, Users); admin pages show a small
  "Admin" tag in the header; the logo/Home link goes to home_url(); a quick search
  (ticket, name, phone) is reachable from the top bar. Every link at least 44px
  tall. When
  $public_page is true (used by track.php) header.php renders the layout WITHOUT
  the nav or any logged-in information.
- footer.php: closes layout; renders flash messages via flash_get() (full-width
  on mobile, readable text size).
Define and document (in a comment block at the top of header.php) the shared
mobile-first Tailwind class strings for: primary button (w-full sm:w-auto,
min-h-11), secondary button, danger button, text input (text-base, min-h-11),
label, card, the five status badges, the page container, the table-to-cards
responsive pattern (card list md:hidden + table hidden md:table), and the
single-to-two-column form grid (grid grid-cols-1 md:grid-cols-2 gap-4).
Create a temporary test page reports/dashboard.php that just includes the layout
and prints "Dashboard placeholder" plus one sample card list and one sample
table using the responsive pattern. Test it at 360px and 1280px and report
what you checked. Stop and report.
```

**3c — Authentication**
```
PHASE 3c — AUTHENTICATION

Task: Implement login/logout and route protection.
- includes/auth_check.php: set session.use_strict_mode=1 and cookie params
  (httponly, samesite=Lax, secure when HTTPS) BEFORE session_start(); enforce a
  30-minute idle timeout using $_SESSION['last_activity'] (log out and redirect
  to login with a "session expired" message); on every request re-load the user
  and force logout if the user no longer exists or is_active = 0; redirect to
  auth/change_password.php when must_change_password = 1; provide the role check
  helper and require_admin().
- auth/login.php: mobile-first form (username, password) matching the Stitch login
  screen (full-width card on mobile, centered max-w-sm from sm:, 44px inputs and button);
  add autocomplete="username" / "current-password", autocapitalize="none" on the
  username, and an optional show-password toggle (vanilla JS).
  Login logic: look up the user by username with a prepared statement; if
  locked_until is in the future, refuse with the same generic error; verify with
  password_verify; on failure increment failed_attempts and, at 5, set
  locked_until = NOW() + 15 minutes; on success reset failed_attempts and
  locked_until, set last_login_at, regenerate the session ID, and store user id,
  name, and role plus last_activity in $_SESSION. Use ONE generic error message
  for wrong username, wrong password, locked, or deactivated accounts. After
  login redirect to home_url() (admin -> dashboard, staff -> orders list); an
  already logged-in user opening login.php is redirected to home_url(). Add a
  friendly "session expired" message when the idle timeout logged the user out.
- auth/logout.php: destroy the session and redirect to login.
- index.php: redirect to reports/dashboard.php if logged in, otherwise to login.
- Protect reports/dashboard.php with auth_check.php.
Give me manual tests: correct login, wrong password, five wrong passwords then
the correct one during the lock, direct URL while logged out, idle timeout (tell
me the one constant to change to 1 minute for testing), cookie flags in browser
dev tools, session ID change after login, logout, staff vs admin nav
differences, and the role-based landing pages (admin -> dashboard, staff -> orders
list); also open every admin URL as staff (expect the 403 page). Stop and report.
```

### Phase 3d — Password change

```
PHASE 3d — PASSWORD CHANGE

Task: Build auth/change_password.php (prototype screen 11), MOBILE-FIRST.
- Available to every logged-in user (and the only page allowed while
  must_change_password = 1, besides logout).
- Fields: current password, new password (minimum 8 characters), confirm; show/hide
  toggles with vanilla JS; correct autocomplete values (current-password,
  new-password).
- Verify the current password with password_verify; reject weak or mismatched
  passwords with field-level errors; store password_hash; set
  must_change_password = 0; regenerate the session ID; flash success and redirect.
- Full-width button on mobile, 44px inputs.
Standard report format, including tests: wrong current password, short password,
mismatch, forced change for a new account.
```

### Phase 3e — User management (admin only)

```
PHASE 3e — USER MANAGEMENT

Task: Build the users module (prototype screen 12), MOBILE-FIRST, admin only
(require_admin()).
Files: users/index.php, create.php, edit.php, reset_password.php.
- index: accounts as cards on mobile and a table from md: (name, username, role,
  active badge, last login); Add User button.
- create: full name, unique username (validate format and uniqueness), role
  (admin or staff), initial password (min 8); stores password_hash and sets
  must_change_password = 1.
- edit: full name, role, is_active. Rules enforced on the SERVER: an admin cannot
  deactivate or demote themselves, and the last active admin can never be
  deactivated or demoted.
- reset_password.php: POST only; set a temporary password shown once on screen,
  set must_change_password = 1, and clear failed_attempts and locked_until.
- Never delete users. All state changes are POST with prepared statements.
Standard report format, including tests: staff opening users/ by URL, duplicate
username, demoting yourself, deactivating the last admin, deactivated user trying
to log in, unlocking a locked account via reset.
```

---

### Phase 4A — Services & Pricing (Amate)

```
PHASE 4A — SERVICES & PRICING MODULE

Task: Build the services module following prototype screen 8 and the module
rules in ROADMAP.md.
Files: services/index.php, services/create.php, services/edit.php, services/toggle.php.
Requirements:
- Admin only (require_admin()).
- index: table with name, pricing type, unit price, active status, actions.
- create/edit: validate name (required, max 100), pricing_type (only per_kg or
  per_piece), unit_price (numeric > 0, max 2 decimals). Show field-level errors
  and keep entered values on error.
- toggle: POST only; flips is_active; never delete services.
- Use prepared statements, e(), flash messages, Post/Redirect/Get.
MOBILE-FIRST: services list as stacked cards on mobile and a table from md:;
single-column form with a full-width Save button on mobile; toggle button at
least 44px tall.
Follow the standard report format and include tests for invalid input and a
check at 360px.
```

---

### Phase 4B — Customers & Order Intake (Tejada)

**4B-1 Customers**
```
PHASE 4B-1 — CUSTOMERS

Task: Build the customers module (prototype screen 4).
Files: customers/index.php, create.php, edit.php, delete.php.
- index: searchable (GET ?q=) by name or phone, with order count per customer
  (LEFT JOIN + GROUP BY).
- create/edit: validate name (required), phone (optional, digits/+/-/spaces only),
  address (optional).
- delete: POST only; refuse with a flash message if the customer has orders.
- Available to admin and staff.
- MOBILE-FIRST: customers as stacked cards on mobile (name, phone, order count,
  large Edit button) and a table from md:; search input full-width with
  type="search"; phone input type="tel"; Add Customer button full-width on mobile.
- On save store phone_digits = normalize_phone(phone); paginate 15 per page (?page=).
Standard report format.
```

**4B-2 Order creation**
```
PHASE 4B-2 — ORDER INTAKE

Task: Build order creation (prototype screen 3).
Files: orders/create.php, assets/js/order_form.js.
- Customer select (with a link to customers/create.php).
- One or more service lines (only is_active services): service, quantity
  (weight in kg or piece count depending on pricing_type), live subtotal and
  grand total via vanilla JS (preview only).
- On POST: validate everything again on the server, RE-FETCH each service's
  price from the database (never trust prices sent by the browser), compute
  subtotals and total in PHP, generate ticket_no with generate_ticket_no(),
  and save orders + order_items in ONE database transaction with
  unit_price_snapshot stored per item. Roll back on any error.
- Optional "Amount paid now" field (default 0, inputmode="decimal"). If greater
  than 0 and not more than the computed total, insert a payments row (method
  cash by default) in the SAME transaction and set orders.payment_status
  (partial or paid); reject an amount above the total.
- On success redirect to orders/ticket.php?id=... with a flash message.
- MOBILE-FIRST: each service line is a stacked card (service select, quantity
  with inputmode="decimal", subtotal) with a large Add Line and Remove button;
  a sticky bottom bar (fixed on mobile, static from md:) shows the live grand
  total and a full-width Save button; page has bottom padding so nothing is
  hidden; fields are single column on mobile.
Standard report format, including tests: tampered price in the POST, zero
quantity, no items, inactive service id, and a full order entry at 360px.
```

**4B-3 Claim ticket**
```
PHASE 4B-3 — CLAIM TICKET

Task: Build orders/ticket.php (prototype screen 7).
- Loads an order by id (prepared statement), shows ticket number, date,
  customer, item lines, total, amount paid, balance due, payment status, and the
  customer tracking address (track.php) printed as plain text with the ticket
  number (no QR code library).
- Print-friendly with Tailwind print: variants; a Print button using
  window.print(); hide nav and buttons when printing.
- Handle "order not found" gracefully.
- MOBILE-FIRST: the ticket fills the screen width on mobile (receipt style,
  max-w-sm centered from sm:), Print button full-width at the bottom and
  hidden when printing.
Standard report format.
```

### Phase 4B-4 — Edit order while Pending (COULD — only after all MUST and SHOULD items pass)

```
PHASE 4B-4 — EDIT ORDER (COULD)

Task: Build orders/edit.php, MOBILE-FIRST.
- Allowed ONLY when the order status is Pending and the order is not voided;
  otherwise show a friendly message and redirect.
- Same form as order creation, pre-filled. On POST (CSRF verified) recompute all
  subtotals and the total on the server in a transaction: unchanged lines keep
  their unit_price_snapshot, new or changed lines use the current service price;
  reject the edit if the new total would be LESS than the amount already paid
  (non-voided payments); recompute orders.payment_status afterwards.
Standard report format, including tests: editing a Washing order by URL, lowering
the total below the amount paid, tampered prices, removing all items.
```

---

### Phase 4C — Order Status Tracking (De Asis)

```
PHASE 4C — ORDER STATUS TRACKING

Task: Build the status module (prototype screens 5 and 6).
Files: orders/index.php, orders/view.php, orders/update_status.php.
- index (this page is the STAFF HOME, see ROADMAP 2.4/2.5): a prominent New Order
  button; quick search (GET ?q=) by ticket number, customer name, or phone; a
  "Today at a glance" row with the number of orders per status (NO revenue
  figures); filter by status (GET ?status=) with pills; by default show active
  (not Claimed) orders newest first with color-coded status badges and a View
  button; paginate; friendly empty state with a New Order button.
- view: order details, items, totals, payment status, a status stepper, and an
  "Advance to [next status]" button (POST form) shown only if a next status exists.
- update_status.php: POST only; load the order; compute the allowed next status
  with next_status(); reject anything else (skipped, backward, or already
  claimed) with a flash error; set claimed_at = NOW() when moving to claimed;
  moving to claimed is ONLY allowed when the balance due is 0; otherwise reject
  with a flash message that links to the payment form.
- Do NOT change the orders table structure. This page only updates status and
  claimed_at (payment_status is changed only by the payments module).
- MOBILE-FIRST: status filter as a horizontally scrollable row of pills on
  mobile; orders as stacked cards (ticket, customer, status badge, total,
  date, View button) and a table from md:; on the detail page the stepper is
  vertical on mobile and horizontal from md:, and the "Advance to [next status]"
  button is full-width and easy to reach with the thumb (sticky bottom bar
  allowed); when a balance is due, show the balance and a large Record Payment
  button instead of the Claim button.
Standard report format, including tests for crafted POST requests that try to
skip a step, and a status update done at 360px.
```

### Phase 4C-2 — Payments & Receipts (De Asis)

```
PHASE 4C-2 — PAYMENTS & RECEIPTS

Task: Build the payments module (prototype screens 13 and 14), MOBILE-FIRST.
Files: payments/create.php, payments/receipt.php (and payment history on
orders/view.php).
- create.php: shows the order summary with BALANCE DUE (total minus SUM of
  payments) highlighted; form with amount (defaults to balance, inputmode="decimal"),
  method (cash, gcash, other), optional reference number. On POST: start a
  transaction, lock the order row with SELECT ... FOR UPDATE, recompute the balance,
  reject amount <= 0 or amount > balance, insert the payment (received_by =
  logged-in user), recompute orders.payment_status (unpaid, partial, paid),
  commit, then redirect to the receipt.
- Payments are append-only: no edit or delete pages.
- receipt.php?id=: receipt number, date/time, ticket number, customer, amount,
  method, reference, balance after this payment (total minus payments up to and
  including this one), received by; print-friendly, hidden nav/buttons when
  printing; handle "not found".
- Order detail shows payment history, amount paid, and balance due.
- Mobile: balance card at the top, numeric keypad for amount, full-width Record
  Payment button, receipt fills the screen width.
Standard report format, including tests: zero, negative, and over-balance amounts;
two rapid submissions of the same payment; paying an already fully paid order;
crafted POST with a different order id or tampered amount; receipt for a
payment that doesn't exist.
```

### Phase 4C-3 — Void / corrections (SHOULD)

```
PHASE 4C-3 — VOID PAYMENTS AND ORDERS

Task: Build admin-only corrections, MOBILE-FIRST. Files: payments/void.php,
orders/void.php, plus the buttons and the "Show voided" filter on the lists.
- Both are POST only, CSRF verified, admin only (require_admin()).
- A reason is required (min 5 characters, max 255).
- payments/void.php: refuse if the order is Claimed or the payment is already
  void; set is_void = 1, voided_at = NOW(), voided_by, void_reason; then
  recompute orders.payment_status.
- orders/void.php: refuse if the order is Claimed or already void; in ONE
  transaction set is_void on the order AND on all of its payments (same reason,
  voided_by, voided_at).
- Lists hide voided orders by default; admins get a "Show voided" filter; voided
  rows show a gray "Void" badge and struck-through amounts.
- Update every existing query that computes totals, balances, or lists to
  exclude is_void = 1 (unless "Show voided" is on).
Standard report format, including tests: staff calling the void URLs, missing
reason, voiding a Claimed order, voiding twice, and dashboard/report numbers
before and after a void.
```

---

### Phase 4D — Reports & Dashboard (Parreño)

**4D-1 Dashboard and daily sales**
```
PHASE 4D-1 — DASHBOARD & DAILY SALES

Task: Build reports/dashboard.php and reports/daily_sales.php
(prototype screens 2 and 10). Admin only.
- Dashboard: today's total sales (SUM of payments.amount received today), total outstanding
  balance (sum of balances of all orders), orders
  today, number of orders in status ready, count per status, and the 5 most
  recent orders. Tag the page "Admin". Add a "Needs attention" panel: orders that
  have been Ready for more than 3 days, orders with an unpaid balance older than 7
  days, and orders voided today, each row linking to the order; plus quick
  actions (New Order, Record Payment, Add Service).
- Daily sales: date filter (GET ?date=YYYY-MM-DD, default today, validate the
  format), summary cards, and a table of that day's payments (time, ticket, customer, method, amount)
  joined to orders and customers, plus the number of new orders that day. Show "No records" when empty.
- Prepared statements for all date filters.
- MOBILE-FIRST: summary cards stacked in one column on mobile (2 columns from
  sm:, 3 to 4 from lg:); recent orders and daily sales rows as cards on mobile
  and a table from md:; the date picker is type="date", full-width on mobile.
- Exclude voided orders and voided payments in every query (is_void = 0); paginate
  the tables.
Give me the SQL queries I can run manually to verify each number against
the seed data. Standard report format.
```

**4D-2 Pending pickup**
```
PHASE 4D-2 — PENDING PICKUP

Task: Build reports/pending_pickup.php (prototype screen 9). Admin only.
List orders with status 'ready', oldest first, showing ticket, customer name,
phone, total, and days waiting (DATEDIFF). Each row links to orders/view.php.
MOBILE-FIRST: rows as cards on mobile with a tap-to-call link on the phone
number (tel:) and a large View button; table from md:.
Standard report format.
```

**4D-3 Outstanding balances**
```
PHASE 4D-3 — OUTSTANDING BALANCES

Task: Build reports/outstanding_balances.php (prototype screen 15). Admin only.
List orders with a balance due (payment_status unpaid or partial), oldest first:
ticket, customer, phone (tel: link on mobile), total, paid, balance, days since the
order (DATEDIFF), and a Record Payment button linking to payments/create.php. Show
the total outstanding amount in a summary card. Balance = total_amount minus
SUM(payments.amount) over non-voided payments, computed with a LEFT JOIN and
GROUP BY (prepared statements); exclude voided orders; paginate.
MOBILE-FIRST: cards on mobile, table from md:.
Give me a SQL query to verify the total against the seed data. Standard report format.
```

**4D-4 Public order tracking page**
```
PHASE 4D-4 — PUBLIC ORDER TRACKING (track.php)

Task: Build track.php at the project root (prototype screen 16). It is the ONLY
page in the project that does NOT include auth_check.php. MOBILE-FIRST, very
simple.
- Form (POST): ticket number + last 4 digits of the phone number (type="tel",
  inputmode="numeric", maxlength 4).
- Lookup with prepared statements: match orders.ticket_no and compare the last 4
  digits of the customer's phone (digits only). On a match show ONLY: ticket
  number, customer FIRST NAME, order date, status stepper (vertical on mobile),
  a friendly status message (e.g. "Your laundry is ready for pickup"), service
  names, total, amount paid, balance due. NEVER show the full name, phone, address,
  other orders, staff names, or internal ids.
- On ANY failure (unknown ticket, wrong digits, customer without a phone, bad
  input) show the SAME generic message "No matching order found."
- Throttle stored in the DATABASE (table tracking_attempts), not the session: log
  every attempt (IP, ticket, success or not); after 5 failed lookups from the same
  IP in 15 minutes, OR 5 failed lookups against the same ticket number in 15
  minutes, refuse further lookups with the same generic message. Compare
  RIGHT(customers.phone_digits, 4) with the submitted digits. Include a CSRF token
  in the form.
- Read-only for orders and customers (its only database write is the
  tracking_attempts log). Layout via header.php with $public_page = true (no nav,
  no logged-in info). Escape all output with e().
- FRIENDLY DESIGN (ROADMAP 2.5): short friendly heading; two large fields with
  helper text ("Printed on your claim ticket", "Last 4 digits of the phone number
  you gave us"); one big Track button that is disabled after clicking; text at
  least 18px; results in plain words ("We're washing your clothes", "Your laundry
  is ready for pickup") with a vertical stepper and what to do next (bring your
  ticket, balance due); one friendly error message: "We couldn't find that order.
  Please check your ticket number and the last 4 digits of your phone number.";
  shop name, phone (tel: link), and opening hours at the bottom from SHOP_NAME,
  SHOP_PHONE, SHOP_HOURS.
Standard report format, including tests: logged out access, wrong ticket, wrong
digits, customer without a phone, SQL injection strings, throttle after 5 fails (must still work after clearing
cookies or using a private window),
and a check that no other customer's data can appear.
```

**4D-5 Analytics (STRETCH — only if all core modules pass)**
```
PHASE 4D-5 — ANALYTICS (STRETCH)

Task: Build reports/analytics.php (prototype screen 17). Admin only.
- Best-selling services: SUM(quantity) and SUM(subtotal) per service using
  order_items JOIN services, GROUP BY service.
- Revenue per day for the last 7 days.
- Draw bars with plain HTML + Tailwind width/height utility classes computed
  in PHP as percentages of the max value. ABSOLUTELY NO chart libraries and no
  canvas/JS charting.
- MOBILE-FIRST: charts are full-width and readable at 360px (horizontal bars
  work best on mobile; labels above bars, values at the end); the two charts
  stack in one column on mobile and sit side by side from lg:.
Standard report format.
```

---

### Phase 5 — Integration

```
PHASE 5 — INTEGRATION REVIEW

Task: Review the whole project as a code reviewer and fix integration problems.
1. Walk the full flow by reading the code: login → create service → create
   customer → create order with a partial payment → print ticket → look it up on
   track.php while logged out → record the remaining payment → print the receipt →
   advance status to claimed → dashboard/report numbers (sales and balances).
   Also trace: lockout, idle timeout, password change, user deactivation.
2. Check every page: includes auth_check.php, uses correct role check, uses
   the shared header/nav/footer, uses prepared statements, escapes output.
3. Check every nav link points to an existing page.
4. Find duplicated code that should live in includes/functions.php.
5. Compare page layouts to docs/prototype/ (mobile AND desktop versions) and list
   visual differences.
6. Audit mobile-first: viewport meta present; unprefixed classes are the mobile
   layout and breakpoints only enhance (sm:/md:/lg:); no fixed pixel widths that
   cause horizontal scroll at 360px; tap targets at least 44px; every table/list
   has a mobile card presentation; menu toggle works; inputs use correct types
   and 16px text; sticky bars don't hide content.
7. Audit hardening: every POST form has csrf_field() and every POST handler calls
   csrf_verify(); every link, redirect, and asset uses url(); lists paginate;
   voided rows are excluded from all totals and balances; config/config.php is in
   .gitignore; display_errors is off unless DEBUG; timezone is consistent; page
   focus outlines and labels exist.
8. Audit roles and UX: compare every page with the access matrix (ROADMAP 2.4),
   confirm home_url() landing pages, and check empty states, inline errors,
   confirmations for risky actions, double-submit protection, plain-language
   labels, and the customer page's wording and text size (18px+).
Output a table of issues (file, problem, severity, proposed fix) FIRST and wait
for my approval before changing any code. Do not add new features.
```

---

### Phase 6 — Testing & Hardening

```
PHASE 6 — SECURITY & QA

Task: Audit the project against the Phase 6 checklist in docs/ROADMAP.md.
For each item, (a) inspect the code, (b) tell me PASS or FAIL with the file and
line, and (c) for FAIL propose the minimal fix.
Specifically verify: login lockout after 5 failed attempts for 15 minutes stored in
the database; idle timeout of 30 minutes; session cookie flags (httponly,
samesite, secure on HTTPS) and strict mode; session ID regenerated on login;
deactivated users cannot log in or keep a session; password change requires the
current password; an admin cannot deactivate or demote themselves or the last
active admin; payments cannot exceed the balance and are locked with FOR UPDATE;
Claimed requires balance 0; payment_status always matches the payments; track.php
is the only page without auth_check and reveals no other customer's data and gives
one generic failure message; the access matrix in ROADMAP 2.4 (for every admin page a staff session gets the
403 page, a logged-out visitor gets the login page, and each role lands on its own
home page; staff never see revenue totals); CSRF tokens on every POST form and
verified in every POST handler (test with a
missing and a wrong token); the tracking throttle is stored in the database and
still works after clearing cookies; voiding rules (admin only, reason required,
blocked on Claimed orders, voided rows excluded from all totals); timezone
consistency between PHP and MySQL; pagination on every list including invalid
?page= values; no credentials committed; display_errors off unless DEBUG; friendly
403/404/500 pages; basic accessibility (focus visible, contrast, labels); no SQL built by string concatenation anywhere; every
echoed variable is escaped; all state-changing actions are POST-only; admin
pages reject staff by direct URL; status transitions are enforced server-side;
order totals are recomputed server-side; price snapshots are used in reports;
no forbidden libraries or CDNs appear anywhere (grep for <script src, <link,
composer.json, package.json, vendor/, node_modules/).
Also verify mobile-first: viewport meta on every page; grep for desktop-first
patterns (e.g. hidden on mobile without a mobile alternative, fixed widths like
w-[...px], missing md:/lg: enhancements); tables have card alternatives;
touch targets, input types, and 16px input text.
Then write docs/TEST-PLAN.md with manual test cases (steps + expected result)
for each module that my teammates can run, including a responsive test per
page at 360px, 768px, and 1280px (no horizontal scroll, readable cards/tables,
tappable buttons, menu works, forms usable on a phone keyboard). Wait for
approval before applying fixes.
```

---

### Phase 7 — Documentation & Defense Prep

```
PHASE 7 — DOCUMENTATION & DEFENSE

Task: Prepare the final documentation.
1. docs/TECH-ALIGNMENT.md: a table mapping each Tech Alignment Check
   requirement (HTML5 semantic + Tailwind, PHP CRUD, MySQL 2+ related tables,
   no external tools) to concrete evidence: file paths, table names, foreign
   keys, and a one-line proof that nothing outside the stack is used.
   For CRUD, use the customers module as the full Create/Read/Update/Delete
   example and explain that services and users are deactivated, and orders and
   payments are voided, instead of deleted by design.
2. docs/USER-GUIDE.md: short guide for shop staff and admin (login, create an
   order, print a ticket, update status, claim, read reports).
3. docs/DEFENSE-QA.md: 20 likely instructor questions with short, accurate
   answers based on OUR code (e.g., why prepared statements, why price
   snapshot, how transitions are enforced, why transactions, how passwords
   are stored, how mobile-first responsiveness works with Tailwind breakpoints, why lockout is
   stored in the database, how balances are computed from payments, how the public
   tracking page avoids exposing customer data).
4. docs/DEMO-SCRIPT.md: a 5–7 minute demo script splitting the flow across
   the five modules with speaker names: Tejada, De Asis, Parreño, Amate, Navarro.
Only describe what actually exists in the code; do not invent features.
```

---

## 3. Utility Prompts

**Fix a bug**
```
Bug in [file/page]. Expected: [what should happen]. Actual: [what happens].
Error message (exact): [paste].
Relevant code: [paste or say which files to read].
Find the root cause first and explain it in 2–3 lines, then apply the smallest
fix. Do not refactor unrelated code. Stay inside the strict stack.
```

**Match the prototype**
```
Compare [page] with docs/prototype/[mobile image] AND docs/prototype/[desktop image].
List visual differences (layout, spacing, colors, components) for each width, then
fix them using Tailwind utility classes only, staying mobile-first (unprefixed =
mobile, enhance with md:/lg:). Keep semantic HTML and don't change PHP logic.
```

**Fix responsiveness**
```
[Page] is broken on mobile: [describe, e.g. horizontal scroll at 360px, table cut
off, buttons too small, menu overlaps content]. Find the cause (fixed widths,
desktop-first classes, missing wrappers, missing viewport meta) and fix it with
Tailwind utilities, mobile-first. Confirm it at 360px, 768px, and 1280px, and
make sure the desktop layout does not regress. Don't change PHP logic.
```

**Drift check (run whenever the agent seems to go off-track)**
```
Stop. Re-read the STRICT TECH STACK and CODING RULES in the master prompt.
Check the files you just wrote for violations (external libraries, string-built
SQL, unescaped output, missing auth_check, prices computed only in JS, desktop-first
styling, missing viewport meta, tiny tap targets, tables without a mobile layout). List
any violations and fix them. Confirm the stack is still HTML5 + Tailwind,
plain PHP, MySQL only.
```

**Usability test script**
```
Write docs/USABILITY-TEST.md for WashFlow. Three testers who are NOT on the team:
(1) a "staff" tester, (2) an "admin" tester, (3) a "customer" tester using their
own phone. For each tester give: a 2-minute walkthrough script, 4 to 6 tasks
written as a scenario without telling them where to click, the success measure
(e.g. create an order and print the ticket in under 90 seconds; find an order by
ticket number in under 15 seconds; a customer tracks an order on the first try),
and a table to record time taken, number of mistakes, and confusing words. Staff
tasks: create an order, print the ticket, find an order by ticket number, record a
partial payment, advance a status. Admin tasks: see what needs attention, add a
service, void a payment with a reason. Customer task: track an order from a claim
ticket. End with a short template for listing the top 5 issues and the fix for each.
```

**Re-plan the schedule**
```
Today is [date]. Our deadline is [date]. Read section 7 (Schedule) of
docs/ROADMAP.md. Done so far: [list]. Not done: [list]. Team availability this
week: [notes]. Produce a revised week-by-week plan that (1) protects the core
flow (login, order intake, status tracking, payments, reports), (2) drops or
postpones stretch items first (analytics, then track.php polish), (3) keeps one
full week for integration, security QA, and defense prep, and (4) lists exactly
which tasks each member does each week. Do not add features.
```

**Explain code for defense**
```
Explain [file] to me like I'm presenting it to my instructor: what each part
does, why it was written that way, and 3 questions the instructor might ask
with good answers. Keep it simple and accurate to this code.
```

**Ownership-safe change (team use)**
```
I am working on the [module] module (branch [branch name]). Only edit files in
[folders]. If you need a change in a shared file (includes/, config/,
sql/schema.sql) or another module, tell me what and why instead of editing it.
```
