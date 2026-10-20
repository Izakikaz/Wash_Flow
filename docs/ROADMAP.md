# WashFlow — Development Roadmap

> Order & Service Tracking System for Laundry Shops
> IPT 1 Project · Team: The Pentagram · Holy Child Central Colleges, Inc. — College of Computer Studies

This file is the single source of truth for building WashFlow. It is written so that a human team **and** an AI coding agent can work from it. Read sections 1–3 fully before doing any task.

---

## 1. Hard Constraints (NON-NEGOTIABLE)

The project must pass the instructor's **Tech Alignment Check**:

| Layer | Requirement |
|---|---|
| Frontend | HTML5 semantic structure + Tailwind CSS |
| Backend | PHP-driven logic (CRUD operations) |
| Persistence | MySQL relational database (2+ related tables) |
| Tooling | No tools outside the course stack (no external frameworks) |

### 1.1 Allowed
- HTML5 semantic elements (`header`, `nav`, `main`, `section`, `article`, `aside`, `footer`, `form`, `table`, etc.)
- Tailwind CSS (loaded the way the course teaches it; use the same method everywhere)
- Plain PHP (PHP 8.x) using `mysqli` **or** `PDO` (pick one and use it everywhere)
- MySQL / MariaDB via XAMPP
- Minimal vanilla JavaScript only where truly needed (e.g. live total preview, confirm dialogs, print button, mobile menu toggle)
- Tailwind's built-in responsive prefixes (`sm:`, `md:`, `lg:`, `xl:`) — used **mobile-first** (see 1.3)

### 1.2 Forbidden
- Any PHP framework (Laravel, CodeIgniter, Symfony, Slim, etc.)
- Composer packages or any dependency manager for PHP
- Bootstrap, jQuery, Alpine, React, Vue, Svelte, or any JS framework/library
- Chart libraries (Chart.js, D3, etc.) — charts must be plain HTML/CSS with Tailwind classes
- Icon packs or font libraries beyond what the course allows (default: use inline text or simple inline SVG written by hand)
- ORMs, query builders, template engines
- Google Stitch output pasted in as-is (Stitch is a **design reference only**)

> **Agent rule:** If a task seems to need something forbidden, do NOT add it. Solve it with plain PHP/HTML/Tailwind/vanilla JS, or stop and ask the human.

### 1.3 Mobile-First Requirements (apply to EVERY page)

Laundry staff often work from a phone or tablet at the counter, so the app is designed and coded for small screens first and enhanced for larger ones.

- **Mobile-first Tailwind:** unprefixed utility classes define the **mobile layout (~360px)**. Larger screens are enhanced only by adding `sm:` (640px), `md:` (768px), `lg:` (1024px), `xl:` (1280px). Never write desktop styles first and then "undo" them for mobile.
- **Viewport:** `header.php` must include `<meta name="viewport" content="width=device-width, initial-scale=1">`.
- **No horizontal page scroll** at 360px. Avoid fixed pixel widths (`w-[600px]`); use `w-full`, `max-w-*`, flex, and grid.
- **Tables:** on mobile, show each row as a stacked card (a `md:hidden` card list plus a `hidden md:table` table, or equivalent markup). `overflow-x-auto` on a wrapper is only an acceptable fallback for wide report tables.
- **Navigation:** top bar with a menu button on mobile that opens the nav (small vanilla JS toggle or `<details>`; no library). Persistent left sidebar from `lg:` upward. Menu button needs `aria-expanded` / `aria-label`.
- **Touch targets:** every button, link, and input at least 44×44px (`min-h-11`), with enough spacing between tappable items. Primary actions are full-width on mobile (`w-full sm:w-auto`).
- **Forms:** single column on mobile, labels above inputs, input text at least 16px (`text-base`) so iOS doesn't zoom, and the right input types (`type="tel"`, `type="number" inputmode="decimal"`, `type="date"`, `type="search"`).
- **Key counter actions** (Save Order, Advance Status) must be reachable one-handed; an optional sticky bottom action bar on mobile (`fixed inset-x-0 bottom-0 ... md:static`) is allowed. Add bottom padding so it never covers content.
- **Status stepper:** vertical on mobile, horizontal from `md:`.
- **Typography and spacing:** readable base size, comfortable line height; increase padding and columns progressively with breakpoints.
- **Test widths:** 360px, 390px, 768px, 1024px, 1280px.

---

## 2. Project Overview

**Problem:** Small laundry shops track orders on handwritten claim stubs, causing lost tickets, no status visibility, and inconsistent pricing.

**Solution:** A web app where staff log orders, generate claim tickets, track status through the wash cycle, auto-compute prices, and view sales reports.

### 2.1 Core Features
1. **Order Intake & Customer Management** — log orders, select service type, weight or piece count, generate a claim ticket.
2. **Order Status Tracking** — Pending → Washing → Drying → Ready → Claimed, with automatic price computation.
3. **Sales & Order Reports** — daily sales summary and pending-pickup list for the admin.
4. **Payments & Receipts** — record full or partial payments per order, show the balance due, print a payment receipt. (Extension beyond the pitch deck; same stack.)
5. **Customer Order Tracking Page** — a public, read-only page where customers check their order status using their ticket number + the last 4 digits of their phone number. (Extension beyond the pitch deck; same stack.)
6. **Account management** — admin manages staff accounts; every user can change their own password.
7. **Stretch (optional):** simple analytics — best-selling services and revenue trends (CSS-only bars).

### 2.2 Module Ownership (feature-vertical split)

| Module | Owner | Owns table(s) | Notes |
|---|---|---|---|
| Order Intake / Customers | TEJADA | `customers`, `orders`, `order_items` | Creates orders and their items (one transaction); `orders` structure is owned here |
| Order Status Tracking & Payments | DE ASIS | `payments` | Only **updates** `orders.status`, `orders.claimed_at`, and `orders.payment_status`; records payments and receipts |
| Reports / Dashboard / Public Tracking | PARREÑO | reads all tables | Read-only queries and joins; `track.php` is read-only |
| Service / Pricing Mgmt | AMATE | `services` | Deactivate instead of delete |
| Auth, Accounts & Integration | NAVARRO | `users` | Login security, password change, user management, session guard, shared includes, merging |

### 2.3 Scope Priority (Must / Should / Could)

The pitch deck promises three core features plus one stretch goal. Everything else is an extension. Build in this order and cut from the bottom if the schedule slips.

| Tier | Items |
|---|---|
| **MUST** (the pitch + a working, safe app) | Login with session guard and lockout; services; customers (full CRUD); order intake + claim ticket; status tracking (Pending → Claimed); daily sales summary; pending-pickup list; prepared statements, output escaping, CSRF tokens; mobile-first layout |
| **SHOULD** | Payments and receipts; outstanding balances report; password change; user management; idle timeout and cookie flags; void (corrections) for orders and payments; pagination |
| **COULD** | Public `track.php`; edit an order while Pending; analytics (stretch); show-password toggle |

**Cut order when behind (cut first → last):** analytics → order editing → `track.php` → user management (keep the seeded accounts and reset passwords through phpMyAdmin) → void → outstanding balances. Never cut the MUST tier.

**Update the pitch deck:** slides 3 (features) and 5 (module ownership) should show this tier list and the final ownership so your instructor sees the same scope as your code.

### 2.4 Access Matrix & Role Landing Pages

WashFlow has three audiences: the **public** (customers), **staff**, and **admin**. Access is enforced **on the server** for every page and every POST handler; hiding a menu link is never enough.

| Page / action | Public (no login) | Staff | Admin |
|---|---|---|---|
| `track.php` (order tracking) | Yes | Yes | Yes |
| `auth/login.php` | Yes (when logged out) | – | – |
| `auth/change_password.php` | No | Yes | Yes |
| Orders: list, view, create, claim ticket, edit while Pending | No | Yes | Yes |
| Advance order status | No | Yes | Yes |
| Customers: list, create, edit, delete | No | Yes | Yes |
| Payments: record payment, receipt | No | Yes | Yes |
| Services & pricing management | No | **No (403)** | Yes |
| Reports: dashboard, daily sales, pending pickup, outstanding balances, analytics | No | **No (403)** | Yes |
| User management (`users/`) | No | **No (403)** | Yes |
| Void order / void payment, "Show voided" filter | No | **No (403)** | Yes |

**Landing pages (home):** `home_url()` returns `reports/dashboard.php` for admins and `orders/index.php` for staff. It is used by the login redirect, `index.php`, and the logo/Home link, so staff never land on a page they cannot open.

**Behavior rules**
- Logged out → redirect to login. Logged in without permission → the friendly `errors/403.php` page (never a redirect loop or a blank page).
- Staff never see revenue totals, sales reports, user management, service management, or void actions (they do see the order counts per status and each order's own balance due).
- The navigation is grouped: daily-work links first (Orders, Customers, My Account), then an **Admin** section heading with the admin-only links; admin pages show a small "Admin" tag in the header so people know which area they are in.
- Destructive or sensitive admin actions (void, deactivate, reset password) are styled as danger buttons, placed apart from normal actions, and always confirmed.

### 2.5 UX & Usability Requirements

**Staff and admin (the counter app)**
- **Plain language:** everyday words and consistent terms ("Ready for pickup", "Claimed", "Balance due"); no codes or jargon.
- **One clear primary action per screen**, in the same place and style everywhere (for example a large "New Order" button on the orders list).
- **Fast lookup:** a quick search (ticket number, customer name, or phone) is always reachable from the top bar or the orders list.
- **Sensible defaults:** today's date, the payment amount pre-filled with the balance due, most-used services first, first field auto-focused.
- **Helpful feedback:** field-level error messages next to the field with the entered values kept; success messages say what happened and offer the next step ("Order saved. Print ticket / New order").
- **Friendly empty states:** a short explanation and a button (for example "No orders yet. Create the first order").
- **Confirm risky actions** (void, deactivate, reset password, claim) with a dialog that explains the effect in one sentence.
- **Double-submit protection:** disable a submit button after it is clicked (tiny vanilla JS), in addition to the server-side checks.
- **Helper text** under fields that are not obvious (for example "Weight in kilograms, e.g. 3.5"); correct input types and keypads on phones.
- **Admin home** is a "what needs attention" screen, not just numbers: Ready orders waiting more than 3 days, balances unpaid for more than 7 days, plus quick actions.
- **Readable:** at least 16px text, generous spacing, high contrast, never color alone.

**Customer (the public tracking page)**
- The simplest screen in the project: a short friendly heading, two large fields with helper text, one big button. No menu, no login button, no jargon.
- Helper text under the fields: "Your ticket number is printed on your claim ticket" and "Last 4 digits of the phone number you gave us".
- Text at least 18px, very high contrast, large tap targets, works one-handed on a small phone.
- Results in plain words: "We're washing your clothes", "Your laundry is ready for pickup", plus the stepper, and what to do next (bring your ticket, balance due).
- Friendly, non-technical error: "We couldn't find that order. Please check your ticket number and the last 4 digits of your phone number." (same message for every failure).
- Shop name, phone number (tap to call), and opening hours shown at the bottom, read from `SHOP_NAME`, `SHOP_PHONE`, and `SHOP_HOURS` in `config.php`; the same constants appear on the claim ticket and receipt.

**Usability targets (test them in Phase 6)**
- A person who is not on the team can create an order and print the ticket within 90 seconds after a 2-minute walkthrough.
- Finding an order by ticket number takes under 15 seconds.
- New Order, Record Payment, and Advance Status are each reachable in at most 3 taps from the staff home.
- A customer can track an order on a phone on the first try, without help.

---

## 3. Conventions (for humans and agents)

### 3.1 Folder Structure
```
washflow/
├── index.php                  # redirects to login or dashboard
├── config/
│   ├── config.php             # BASE_URL, timezone, DEBUG, SHOP_NAME/PHONE/HOURS, DB credentials (GITIGNORED)
│   ├── config.sample.php      # committed template without real credentials
│   └── db.php                 # single DB connection (reads config.php)
├── includes/
│   ├── header.php             # <head>, Tailwind, opening layout
│   ├── nav.php                # sidebar/top nav (role-aware)
│   ├── footer.php
│   ├── auth_check.php         # session guard, include at top of protected pages
│   └── functions.php          # helpers: e(), redirect(), flash(), compute_total()
├── track.php                  # PUBLIC customer order tracking page (no login)
├── auth/                      # login.php, logout.php, change_password.php
├── users/                     # admin only: index.php, create.php, edit.php, reset_password.php
├── customers/                 # index.php, create.php, edit.php, delete.php
├── orders/                    # index.php, create.php, view.php, ticket.php, update_status.php
├── payments/                  # create.php (record payment), receipt.php
├── services/                  # index.php, create.php, edit.php, toggle.php
├── reports/                   # dashboard.php, daily_sales.php, pending_pickup.php, outstanding_balances.php, analytics.php
├── assets/
│   ├── css/                   # only if the course Tailwind method needs a built file
│   └── js/                    # small vanilla scripts (order_form.js, etc.)
├── errors/                    # 403.php, 404.php, 500.php (friendly error pages)
├── logs/                      # error.log (GITIGNORED)
├── sql/
│   ├── schema.sql
│   └── seed.sql
└── docs/
    ├── prototype/             # Google Stitch screenshots/exports (reference only)
    ├── ERD.png
    └── ROADMAP.md             # this file
```

### 3.2 Coding Rules
- **SQL:** always prepared statements with bound parameters. Never concatenate user input into SQL.
- **Output:** escape every echoed value with `e()` (a wrapper around `htmlspecialchars($v, ENT_QUOTES, 'UTF-8')`).
- **Passwords:** `password_hash()` / `password_verify()` only; minimum 8 characters; never log or echo passwords. New accounts and admin-reset passwords set `must_change_password = 1`.
- **Sessions & login security:** `session_start()` in `auth_check.php`; every protected page includes `auth_check.php` first (the only public page is `track.php`). Before starting the session set: `session.use_strict_mode=1`, cookie flags `httponly`, `samesite=Lax`, and `secure` when running on HTTPS. Regenerate the session ID on login. **Idle timeout: 30 minutes** (store `$_SESSION['last_activity']`, log the user out and redirect with a message when exceeded). **Lockout:** after **5 failed logins** the account is locked for **15 minutes** (stored in the database: `failed_attempts`, `locked_until`); reset the counter on success; show one generic error message for wrong username, wrong password, and locked/deactivated accounts. Deactivated users (`is_active = 0`) cannot log in and existing sessions are rejected on the next request.
- **Validation:** validate on the server for every form (required, numeric ranges, allowed enum values). JS validation is optional and never a replacement.
- **Forms:** POST for changes, GET for filters/search. Redirect after a successful POST (Post/Redirect/Get) and show a flash message.
- **Money:** store as `DECIMAL(10,2)`; compute totals in PHP on the server. The **balance due** is always computed as `orders.total_amount − SUM(payments.amount)` over **non-voided** payments; never trust an amount sent by the browser.
- **Naming:** `snake_case` for DB columns and PHP variables/functions; lowercase file names.
- **HTML:** use semantic elements; every input has a `<label>`; tables use `<thead>`/`<tbody>`; one `<h1>` per page.
- **Tailwind:** utility classes directly in markup, **mobile-first** (unprefixed = mobile; add `sm:`/`md:`/`lg:` to enhance, see 1.3); reuse the same class patterns for buttons, inputs, tables, and status badges across all pages (see 3.3).
- **Comments:** short comment above each function/section explaining purpose. Keep code readable for a student defense.
- **CSRF protection:** every POST form includes `csrf_field()` (a hidden input holding a random token from `random_bytes(32)`, stored in the session); every POST handler calls `csrf_verify()` first (compare with `hash_equals`) and stops with a 403 on failure. Also check `$_SERVER['REQUEST_METHOD'] === 'POST'`. Applies to login, logout (use a POST form), status changes, payments, voids, and user actions. Regenerate the token on login.
- **Time zone:** `date_default_timezone_set('Asia/Manila')` in `config.php`, and `SET time_zone = '+08:00'` right after connecting in `db.php`, so "today" means the same thing in PHP and MySQL.
- **URLs:** `config.php` defines `BASE_URL` (for example `/washflow`). Every link, form action, redirect, and asset path uses `url('path')`; never hard-code `../` paths (they break in subfolders).
- **Errors:** `display_errors` is off unless `DEBUG` is true; errors are logged to `logs/error.log`; users see friendly `errors/403.php`, `404.php`, `500.php` pages, never raw PHP errors. During development (`DEBUG = true`) errors may be shown.
- **Git hygiene:** `.gitignore` lists `config/config.php` and `logs/`. Only `config/config.sample.php` (no real credentials) is committed.
- **Pagination:** every list (orders, customers, payments, users, report tables) shows 15 rows per page using `?page=`; validate `page` as a positive integer and use bound integers for `LIMIT`/`OFFSET`; show "Page X of Y" with large Previous/Next buttons on mobile.
- **Phone numbers:** on save, `normalize_phone()` stores digits only in `customers.phone_digits`; the tracking lookup compares `RIGHT(phone_digits, 4)`.
- **Ticket numbers:** `WF-YYYYMMDD-` plus 4 random characters from `ABCDEFGHJKMNPQRSTUVWXYZ23456789` (no look-alike characters), `UNIQUE` in the database; on a duplicate-key error, generate a new one (retry up to 5 times).
- **Voids instead of deletes:** orders and payments are never edited or deleted. An **admin** can void one with a required reason (min 5 characters). Voided rows stay in the database, are marked with a "Void" badge, and are **excluded from every total, report, and balance** (`is_void = 0`).
- **Accessibility basics:** `<html lang="en">`; visible focus outlines (never remove them without a replacement); text contrast at least 4.5:1; every input has a `<label>`; status is shown with text as well as color; icon-only buttons have `aria-label`.
- **Roles and landing pages:** follow the access matrix in 2.4. Admin-only pages call `require_role('admin')` right after `auth_check.php` (staff get `errors/403.php`); `home_url()` gives each role its landing page; every POST handler re-checks the role.
- **Shop info constants:** `SHOP_NAME`, `SHOP_PHONE`, `SHOP_HOURS` are defined in `config.php` and used on the claim ticket, receipt, and tracking page (no shop-settings table needed).
- **Double-submit protection:** every form's submit button is disabled after the first click (vanilla JS); the server still guards against duplicates.
- **User-friendly UI:** follow 2.5 (plain language, empty states, inline errors, confirmations, sensible defaults).

### 3.3 Shared UI Patterns (define once, reuse everywhere)
- **Status badge colors:** Pending = gray, Washing = blue, Drying = amber, Ready = green, Claimed = slate/dark.
- **Primary button, secondary button, danger button, form field, table, card, flash message** — define the class strings during Phase 3 and document them in `includes/functions.php` comments or a short `docs/ui-patterns.md`.
- **Responsive patterns (mobile-first):** page container, responsive table-to-cards pattern, mobile nav/menu toggle, full-width-on-mobile button, single-to-multi-column form grid (`grid grid-cols-1 md:grid-cols-2 gap-4`), vertical-to-horizontal status stepper, optional sticky bottom action bar.

### 3.4 Git Workflow
- `main` is always runnable. One branch per module: `feat/customers-orders`, `feat/status-tracking`, `feat/reports`, `feat/services`, `feat/auth-foundation`.
- Small commits with clear messages (`feat: add customer create form`).
- Merge to `main` only after the module's acceptance checks (Section 5) pass.

---

## 4. Roadmap

Legend: `[ ]` = todo. Each phase lists **Tasks** and **Acceptance criteria**. Do not start a phase until the previous phase's acceptance criteria are met (Phases 4's modules can run in parallel once Phase 3 is done).

---

### Phase 1 — Prototype in Google Stitch (HUMAN step, do this FIRST)

**Goal:** Agree on how every screen looks before any code is written. AI coding agents cannot use Stitch; a human team member does this and drops the results into `docs/prototype/`.

**Tasks**
- [ ] Choose design direction and put it in every prompt: palette (mint / teal / charcoal from the pitch deck), one font, clean minimal admin layout, **mobile-first** (design the phone layout first, then tablet and desktop).
- [ ] Design **two experiences** with the same brand: (1) the staff/admin counter app (efficient, quick search, admin areas labeled "Admin") and (2) the public tracking page (extremely simple, friendly, extra-large text, no menu). Follow the UX requirements in 2.5, including empty states, inline errors, and confirmation dialogs.
- [ ] Generate these screens (one prompt each, then refine with follow-up prompts):

| # | Screen | Module |
|---|---|---|
| 1 | Login | Auth |
| 2 | Dashboard (today's sales, orders by status, pending pickups) | Reports |
| 3 | New order form (customer, service, weight/pieces, live computed total) | Orders |
| 4 | Customer list + add/edit form | Customers |
| 5 | Order list with status filter and ticket-number search | Status |
| 6 | Order detail with status update buttons | Status |
| 7 | Printable claim ticket | Orders |
| 8 | Services & pricing management | Services |
| 9 | Pending-pickup list | Reports |
| 10 | Daily sales / date-range report | Reports |
| 11 | Change password | Auth |
| 12 | Users management (list, add/edit, reset password) — admin | Auth |
| 13 | Record payment (balance due, amount, method, reference) | Payments |
| 14 | Printable payment receipt | Payments |
| 15 | Outstanding balances report | Reports |
| 16 | Public order tracking page (customer-facing, no login) | Tracking |
| 17 | (Stretch) Analytics page | Reports |

- [ ] Example prompt to reuse and adapt:
  > "Laundry shop admin web app, desktop-first, mint and teal palette, charcoal text, clean and minimal. Screen: New Order form. Fields: customer (search or select), service type dropdown, weight in kg or piece count, computed total shown live, Save & Print Ticket button. Include a sidebar with: Dashboard, Orders, Customers, Services, Reports."
- [ ] For every screen, generate the **mobile version first (about 390px wide)**, approve it, then generate the tablet/desktop adaptation. Mobile nav is a top bar with a menu button; desktop nav is a sidebar.
- [ ] Check every screen at mobile, tablet, and desktop widths, including that tables become cards on mobile and that primary buttons are thumb-reachable.
- [ ] Team walkthrough of the main flow: login → new order → update status → claim → report. Fix gaps in Stitch now.
- [ ] Export screenshots (and HTML/Figma if available) to `docs/prototype/` with both versions per screen: `01-login-mobile.png`, `01-login-desktop.png`, `02-dashboard-mobile.png`, etc.
- [ ] Write a short `docs/prototype/README.md` listing: color palette (hex values), font, spacing style, and the shared components spotted (nav, table, form field, status badge, buttons).

**Acceptance criteria**
- All screens 1–16 exist as **mobile and desktop** images in `docs/prototype/`, approved by all five members.
- The design tokens (colors, font) are written down in `docs/prototype/README.md`.

**Agent guidance:** Treat `docs/prototype/` as the visual spec. Recreate layouts with semantic HTML + Tailwind classes. Never copy exported markup that includes external CDNs, icon fonts, or JS libraries.

---

### Phase 2 — Database Design

**Goal:** A relational schema that supports all screens, with real foreign keys (not a token join).

**Relationship chain:** `customers → orders → order_items → services`, plus `orders → payments`, `users` (authentication, `created_by`, `received_by`, `voided_by`), and `tracking_attempts` (throttle log for the public page).

**Tasks**
- [ ] Draw the ERD and save as `docs/ERD.png`.
- [ ] Write `sql/schema.sql` (reference version below; adjust only with team agreement).
- [ ] Write `sql/seed.sql` (also fill `customers.phone_digits`, and include one voided order and one voided payment with reasons so reports can be tested for exclusion): 1 admin user, 1 staff user, 5–8 services, 8–10 customers (**every customer has a phone number with at least 4 digits**, needed for the tracking page), 15–20 orders across all statuses and several dates, and payments so that some orders are unpaid, some partially paid, and some fully paid (`orders.payment_status` must match the sum of its payments).
- [ ] Verify a clean import works on a fresh XAMPP MySQL.

**Reference schema**
```sql
CREATE DATABASE IF NOT EXISTS washflow CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE washflow;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  username VARCHAR(50) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','staff') NOT NULL DEFAULT 'staff',
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  must_change_password TINYINT(1) NOT NULL DEFAULT 0,
  failed_attempts INT NOT NULL DEFAULT 0,
  locked_until DATETIME NULL,
  last_login_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  phone VARCHAR(20),
  phone_digits VARCHAR(20),                  -- digits only, filled by normalize_phone()
  address VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_customers_name (name),
  INDEX idx_customers_phone_digits (phone_digits)
);

CREATE TABLE services (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  pricing_type ENUM('per_kg','per_piece') NOT NULL,
  unit_price DECIMAL(10,2) NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 1
);

CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ticket_no VARCHAR(20) NOT NULL UNIQUE,
  customer_id INT NOT NULL,
  status ENUM('pending','washing','drying','ready','claimed') NOT NULL DEFAULT 'pending',
  total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  payment_status ENUM('unpaid','partial','paid') NOT NULL DEFAULT 'unpaid',  -- derived from payments
  created_by INT NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  claimed_at DATETIME NULL,
  is_void TINYINT(1) NOT NULL DEFAULT 0,
  voided_at DATETIME NULL,
  void_reason VARCHAR(255) NULL,
  voided_by INT NULL,
  INDEX idx_orders_status (status),
  INDEX idx_orders_created_at (created_at),
  INDEX idx_orders_customer (customer_id),
  FOREIGN KEY (customer_id) REFERENCES customers(id),
  FOREIGN KEY (created_by) REFERENCES users(id),
  FOREIGN KEY (voided_by) REFERENCES users(id)
);

CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  service_id INT NOT NULL,
  quantity DECIMAL(8,2) NOT NULL,            -- kg or pieces depending on service
  unit_price_snapshot DECIMAL(10,2) NOT NULL, -- price at time of order
  subtotal DECIMAL(10,2) NOT NULL,
  FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
  FOREIGN KEY (service_id) REFERENCES services(id)
);

CREATE TABLE payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL,
  amount DECIMAL(10,2) NOT NULL,              -- must be > 0 and <= balance due
  method ENUM('cash','gcash','other') NOT NULL DEFAULT 'cash',
  reference_no VARCHAR(50) NULL,              -- e.g. e-wallet reference
  received_by INT NOT NULL,
  paid_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  is_void TINYINT(1) NOT NULL DEFAULT 0,
  voided_at DATETIME NULL,
  void_reason VARCHAR(255) NULL,
  voided_by INT NULL,
  INDEX idx_payments_order (order_id),
  INDEX idx_payments_paid_at (paid_at),
  FOREIGN KEY (order_id) REFERENCES orders(id),
  FOREIGN KEY (received_by) REFERENCES users(id),
  FOREIGN KEY (voided_by) REFERENCES users(id)
);

-- Public tracking page throttle (stored in the DB so clearing cookies doesn't bypass it)
CREATE TABLE tracking_attempts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(45) NOT NULL,
  ticket_no VARCHAR(30) NULL,
  was_success TINYINT(1) NOT NULL DEFAULT 0,
  attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_tracking_ip_time (ip_address, attempted_at),
  INDEX idx_tracking_ticket_time (ticket_no, attempted_at)
);
```

**Design decisions to keep**
- `unit_price_snapshot` is stored per item so later price edits never change past orders.
- Services are **deactivated** (`is_active = 0`), never deleted, so old orders keep their references.
- `orders.status` is the only status field; allowed transitions are enforced in PHP.
- **Payments are never edited or deleted.** Mistakes are corrected by an admin **voiding** the payment (`is_void = 1`, with a reason). Balance due = `orders.total_amount − SUM(payments.amount WHERE is_void = 0)`. After every payment or void, recompute `orders.payment_status` (`unpaid` if sum = 0, `partial` if 0 < sum < total, `paid` if sum = total). An order can only move to **Claimed** when its balance is 0.
- **Voiding an order** (admin only, reason required, not allowed once Claimed) sets `is_void = 1` on the order **and on all of its payments** in one transaction. Voided orders and payments are excluded from every list total, report, and balance.
- **The tracking throttle lives in the database** (`tracking_attempts`), by IP address and by ticket number, so it can't be bypassed by clearing cookies.
- **Sales figures are cash-basis:** "sales for a day" = sum of payments received that day (`payments.paid_at`).
- **Lockout is stored in the database** (`failed_attempts`, `locked_until`), not in the session, so it can't be bypassed by clearing cookies.
- Users are **deactivated**, never deleted, so `created_by` / `received_by` references stay valid.

**Acceptance criteria**
- `schema.sql` then `seed.sql` import with no errors on a fresh install.
- Foreign keys exist for every relationship in the chain.
- Seed data covers every status value and every payment status (unpaid, partial, paid).

---

### Phase 3 — Project Foundation (Auth + shared layout)

**Owner:** NAVARRO (others wait on this before Phase 4 pages)

**Tasks**
- [ ] Create the folder structure from 3.1.
- [ ] `config/db.php`: single connection; on failure show a friendly error and stop.
- [ ] `includes/functions.php`: `e()`, `redirect()`, `flash_set()` / `flash_get()`, `is_admin()`, `compute_total()` helper, `next_status()` helper.
- [ ] `includes/header.php`, `nav.php`, `footer.php` matching the Stitch layout, **mobile-first** with Tailwind: viewport meta tag, top bar + menu button on mobile, sidebar from `lg:`. Nav highlights the current page. Admin-only links (Services, Reports, Users) are hidden for staff and grouped under an "Admin" heading; the logo/Home link uses `home_url()`; a quick search (ticket, name, phone) is reachable from the top bar.
- [ ] `config/config.php` + committed `config/config.sample.php` + `.gitignore`: `BASE_URL`, `DEBUG`, DB credentials, `date_default_timezone_set('Asia/Manila')`; `config/db.php` runs `SET time_zone = '+08:00'` after connecting.
- [ ] `includes/functions.php` helpers: `e()`, `url()`, `csrf_field()`, `csrf_verify()`, `normalize_phone()`, `paginate()`, `generate_ticket_no()`, flash helpers, and balance/status helpers.
- [ ] `errors/403.php`, `404.php`, `500.php` and global error settings (`display_errors` off unless `DEBUG`; log to `logs/error.log`).
- [ ] `includes/auth_check.php`: sets secure session settings (strict mode, `httponly`, `samesite=Lax`, `secure` on HTTPS), starts the session, enforces the **30-minute idle timeout**, re-checks that the user still exists and `is_active = 1`, redirects to login when not authenticated, redirects to `auth/change_password.php` when `must_change_password = 1`, and provides the role check.
- [ ] `auth/login.php`: `password_verify`, **lockout after 5 failed attempts for 15 minutes (stored in the DB)**, generic error message, session ID regeneration, reset counters and set `last_login_at` on success. Mobile-friendly: `autocomplete="username"` / `autocomplete="current-password"`, `autocapitalize="none"` on the username, optional show-password toggle (vanilla JS).
- [ ] `auth/logout.php`: clear session data, expire the cookie, destroy the session.
- [ ] **Role landing (option A):** `home_url()` (admin → `reports/dashboard.php`, staff → `orders/index.php`) used by the login redirect, `index.php`, an already-logged-in visit to `login.php`, and the logo/Home link; `require_role('admin')` shows `errors/403.php` (no redirect loop) when a logged-in user lacks access.
- [ ] `index.php` redirects to dashboard or login.
- [ ] Document shared UI class patterns (3.3).
- [ ] Write a `docs/SETUP.md` with the exact steps to run the project on XAMPP.

**Acceptance criteria**
- Logging in with the seeded admin works; wrong credentials show one generic error; logout ends the session.
- Five wrong passwords lock the account for 15 minutes (even the correct password is refused during the lock); the lock clears afterward.
- After 30 minutes without activity the next request redirects to login with an "session expired" message.
- Session cookie shows `HttpOnly` and `SameSite=Lax` in browser dev tools.
- Visiting any protected URL while logged out redirects to login.
- Submitting any POST form without a valid CSRF token is rejected with a 403 page.
- After login an admin lands on the dashboard and staff land on the orders list; a staff member opening any admin URL directly sees the friendly 403 page; a logged-out visitor is sent to login.
- All links and assets work from every subfolder (via `url()`); moving the project folder only requires changing `BASE_URL`.
- Raw PHP errors never appear when `DEBUG` is false; `config/config.php` is not tracked by Git.
- The shared layout renders identically on every page and matches the prototype at mobile (360/390px) and desktop widths; the mobile menu opens and closes correctly; no horizontal scroll at 360px.

#### 3d — Password change & user management (NAVARRO) — `feat/auth-foundation`

**Tasks**
- [ ] `auth/change_password.php` (all users): current password, new password (min 8 characters), confirm; verify the current password with `password_verify`; store a new `password_hash`; clear `must_change_password`; regenerate the session ID.
- [ ] `users/index.php` (admin only): list of accounts (name, username, role, active, last login) as cards on mobile and a table from `md:`.
- [ ] `users/create.php` (admin only): full name, unique username, role, initial password; sets `must_change_password = 1`.
- [ ] `users/edit.php` (admin only): edit name, role, and active status. **Rules:** an admin cannot deactivate or demote themselves, and the last active admin can never be deactivated or demoted.
- [ ] `users/reset_password.php` (admin only, POST): sets a temporary password, sets `must_change_password = 1`, clears `failed_attempts` and `locked_until` (this is also how a locked account is unlocked early).
- [ ] Never delete users; deactivate them.

**Acceptance criteria**
- Staff cannot open any `users/` page (server-side check).
- A user can change their own password only with the correct current password; weak (under 8 characters) passwords are rejected.
- A new or reset account is forced to change its password at the next login.
- A deactivated user cannot log in, and an already-logged-in deactivated user is logged out on the next request.
- The last active admin cannot be deactivated or demoted.

---

### Phase 4 — Feature Modules (parallel, one branch each)

Each module = page layout (HTML5 + Tailwind) + PHP CRUD logic + its SQL. Follow the prototype screens.

**Mobile-first applies to every module (see 1.3):** build the mobile layout first, then enhance with `md:`/`lg:`. Lists and tables become stacked cards on mobile, forms are single-column with 44px touch targets, and primary actions are full-width or in a sticky bottom bar. Each module's acceptance check includes viewing it at 360px and at desktop width.

**Also required in every module:** CSRF token on every POST form, `url()` for all links, pagination on lists, voided rows excluded from totals, the access matrix (2.4) and UX requirements (2.5: plain language, empty states, inline errors, confirmations, double-submit protection), and build in tier order (MUST → SHOULD → COULD, see 2.3).

#### 4A. Services & Pricing (AMATE) — `feat/services`
- [ ] `services/index.php`: table of services with type, price, active status.
- [ ] `services/create.php` and `edit.php`: validate name, pricing type (enum), price (> 0).
- [ ] `services/toggle.php`: activate/deactivate (POST only, admin only).
- **Acceptance:** admin can add, edit, deactivate services; deactivated services no longer appear in the order form; invalid input is rejected with messages.

#### 4B. Customers & Order Intake (TEJADA) — `feat/customers-orders`
- [ ] Customers: list with search, create, edit, delete (block delete if the customer has orders, show a message).
- [ ] `orders/create.php`: select or add customer, add one or more service lines (service, weight/pieces), live total preview (vanilla JS) and **server-side** recomputation on save.
- [ ] Generate a unique, non-guessable `ticket_no` (`WF-YYYYMMDD-` + 4 random characters; see conventions), retrying on a duplicate-key error.
- [ ] Save `orders` + `order_items` inside a **database transaction**; snapshot `unit_price` into each item.
- [ ] Optional **"Amount paid now"** field (default 0). If greater than 0 (and not more than the total), insert a `payments` row in the same transaction and set `orders.payment_status` accordingly.
- [ ] `orders/ticket.php`: printable claim ticket (ticket number, customer, items, total, amount paid, balance due, date, and the customer tracking address `track.php` printed as plain text), print-friendly with a "Print" button and Tailwind `print:` variants.
- [ ] (COULD) `orders/edit.php`: staff can correct an order's items **only while its status is Pending**. Recompute totals on the server; unchanged lines keep their `unit_price_snapshot`, new or changed lines use the current service price; reject the edit if the new total would be **less than the amount already paid**. Voided orders cannot be edited.
- **Acceptance:** creating an order writes one `orders` row and matching `order_items` rows atomically; totals match the server computation; ticket prints cleanly; two orders created at the same moment never share a ticket number; a customer's phone is stored normalized (digits only) in `phone_digits`.

#### 4C. Order Status Tracking & Payments (DE ASIS) — `feat/status-tracking`
- [ ] `orders/index.php`: list with status filter, ticket/customer search, color-coded badges, sorted newest first.
- [ ] `orders/view.php`: order details, items, totals, status history display (current status is enough; history table is optional).
- [ ] `orders/update_status.php`: POST only; allow **only** the next status in sequence (Pending → Washing → Drying → Ready → Claimed); set `claimed_at` when moving to Claimed; **moving to Claimed is refused unless the balance due is 0** (show a message with a link to record payment).
- [ ] Reject skipped or backward transitions on the server.
- [ ] **Staff home = `orders/index.php`:** a prominent **New Order** button, quick search (ticket, customer name, or phone; also in the top bar), a small **"Today at a glance"** row with the number of orders per status (no revenue figures), status filter pills, and by default the active orders (not Claimed) newest first; friendly empty state with a New Order button. Staff must be able to reach New Order, Record Payment, and Advance Status in at most 3 taps from here.
- [ ] `payments/create.php` (POST, admin and staff): record a payment for an order — amount (numeric > 0 and **not more than the balance due**, re-checked on the server inside a transaction that locks the order row with `SELECT ... FOR UPDATE`), method (`cash`/`gcash`/`other`), optional reference number. After inserting, recompute `orders.payment_status`.
- [ ] Order detail shows payment history, amount paid, and balance due; a large **Record Payment** button appears when a balance exists.
- [ ] `payments/receipt.php?id=`: printable payment receipt (receipt number, date/time, ticket number, customer, amount, method, reference, balance after this payment, received by), print-friendly, mobile-first.
- [ ] (SHOULD) `payments/void.php` (admin only, POST + CSRF): void a payment with a required reason (min 5 characters); not allowed if the order is Claimed or already void; recompute `orders.payment_status`.
- [ ] (SHOULD) `orders/void.php` (admin only, POST + CSRF): void an order with a required reason; not allowed if the order is Claimed; in one transaction set `is_void` on the order and all its payments. Lists hide voided orders by default; admins get a "Show voided" filter; voided rows display a gray "Void" badge.
- **Acceptance:** status can only advance one step at a time; direct URL/POST tampering cannot skip steps; claimed orders can no longer change status; an order with a balance cannot be claimed; overpayment and zero/negative amounts are rejected; `payment_status` always matches the sum of non-voided payments; the receipt prints cleanly; only admins can void; voiding needs a reason; voided rows disappear from every total and balance; staff cannot reach the void pages by URL.

#### 4D. Reports, Dashboard & Public Tracking (PARREÑO) — `feat/reports`
- [ ] `reports/dashboard.php` (admin home, tagged "Admin"): today's total sales (sum of payments received today), total outstanding balance, count of orders per status, number pending pickup; a **"Needs attention"** panel (orders Ready for more than 3 days, orders with an unpaid balance older than 7 days, orders voided today) with a link on every row; quick actions (New Order, Record Payment, Add Service).
- [ ] `reports/daily_sales.php`: date filter (default today), list of that day's payments (time, ticket, customer, method, amount), sum of payments received, number of new orders.
- [ ] `reports/pending_pickup.php`: orders with status `ready`, oldest first, with customer contact and balance due.
- [ ] `reports/outstanding_balances.php`: orders with a balance due (unpaid or partial), oldest first, with customer contact, total, paid, and balance; sum of all balances.
- [ ] `track.php` **friendly design (customer-facing):** follow the customer requirements in 2.5: short friendly heading, two large fields with helper text ("printed on your claim ticket", "last 4 digits of the phone number you gave us"), one big Track button, text at least 18px, plain-words status messages ("We're washing your clothes"), a friendly single error message, and the shop name, phone (tap to call) and opening hours from `SHOP_NAME`, `SHOP_PHONE`, `SHOP_HOURS`. No menu and no login button.
- [ ] `track.php` (**PUBLIC, no login**): the customer enters their **ticket number + last 4 digits of their phone number**. On a match show only: ticket number, status stepper (vertical on mobile), a friendly status message (e.g. "Ready for pickup"), order date, service names, total, amount paid, balance due, and the customer's first name. **Never** show phone, address, other orders, or staff info. Rules: prepared statements; read-only for orders and customers (its only write is the `tracking_attempts` log); POST form; one generic "no match" message for any failure; throttle using the `tracking_attempts` table: log every attempt (IP, ticket, result); after 5 failed lookups from the same IP in 15 minutes, or 5 failed lookups against the same ticket number in 15 minutes, refuse further lookups with the same generic message (compare `RIGHT(customers.phone_digits, 4)`; the throttle must still work after clearing cookies or using a private window); uses the shared layout without the sidebar (`$public_page = true`); mobile-first and very simple (this page will be opened on customers' phones).
- [ ] Use joins across `orders`, `order_items`, `services`, `customers`; prepared statements for date filters.
- [ ] (Stretch, only after everything else passes) `reports/analytics.php`: best-selling services (`GROUP BY service`) and revenue by day, drawn with **CSS-only bars** (Tailwind width classes computed in PHP). No chart library.
- **Acceptance:** numbers on the dashboard equal the sum of the seeded data (verify manually with SQL); empty dates show "no records" instead of errors; outstanding balances equal totals minus payments; `track.php` works without logging in, reveals nothing beyond the fields listed above, and gives the same message for a wrong ticket, a wrong phone suffix, or a missing phone number.

---

### Phase 5 — Integration

**Owner:** NAVARRO with all members

**Tasks**
- [ ] Merge all module branches into `main` one at a time; fix conflicts in shared includes.
- [ ] Walk the full flow end to end: login → add service → add customer → create order with a partial payment → print ticket → look it up on `track.php` → record the remaining payment → print the receipt → advance status to Claimed → see it in reports (sales and balances).
- [ ] Confirm navigation links all work and role restrictions apply (staff cannot open admin pages by URL).
- [ ] Compare each page with `docs/prototype/` (mobile and desktop versions); fix visual drift.
- [ ] Open every page at 360px, 390px, 768px, and 1280px; fix horizontal scroll, overlapping elements, and tiny tap targets.
- [ ] Remove dead code, unused files, and debug output.

**Acceptance criteria**
- The full flow works with no PHP warnings or notices (set `display_errors` on during development and check).
- Staff role is blocked from admin-only pages both in the nav and by direct URL.

---

### Phase 6 — Testing & Hardening

**Checklist**
- [ ] SQL injection attempts (`' OR 1=1 --`) in every text field and URL parameter do nothing.
- [ ] XSS attempt (`<script>alert(1)</script>` as a customer name) is displayed as text, not executed.
- [ ] Empty, zero, negative, and non-numeric weight/price values are rejected.
- [ ] Duplicate usernames are rejected; passwords stored only as hashes.
- [ ] Skipping status steps via a crafted POST is rejected.
- [ ] Editing a service price does not change totals on existing orders.
- [ ] Deleting a customer with orders is blocked cleanly.
- [ ] Every protected page redirects to login when logged out.
- [ ] Five failed logins lock the account for 15 minutes; the correct password is refused during the lock; admin reset unlocks it.
- [ ] Idle timeout logs the user out after 30 minutes of inactivity (temporarily set the timeout to 1 minute to test, then restore it).
- [ ] Session cookie has `HttpOnly` and `SameSite`; the session ID changes after login.
- [ ] Deactivated user cannot log in and is logged out on the next request; admin cannot deactivate or demote themselves or the last active admin.
- [ ] Password change requires the correct current password; forced change works for new and reset accounts.
- [ ] Payments: zero, negative, and over-balance amounts rejected; two rapid payments cannot overpay; `payment_status` always matches the payments; Claimed is blocked while a balance exists; receipt prints correctly.
- [ ] `track.php`: works logged out; wrong ticket, wrong phone suffix, and missing phone give the same message; nothing beyond the allowed fields is shown; throttling works; the page is usable on a phone.
- [ ] `track.php` is the only page reachable without login.
- [ ] CSRF: submitting each POST form without a token, or with a wrong one, is rejected (test at least login, status change, payment, void, and user actions); logout works only via POST.
- [ ] `track.php` throttle still blocks after clearing cookies / using a private window (it is stored in `tracking_attempts`).
- [ ] Voiding: only admins; reason required; blocked on Claimed orders; voided orders and payments are excluded from dashboard, daily sales, balances, and lists (unless "Show voided").
- [ ] Time zone: a payment recorded at 11:30 PM appears under the same date in PHP and MySQL reports.
- [ ] Pagination works on every list (page 1, last page, invalid `?page=` values such as 0, -1, abc, 9999).
- [ ] No credentials in Git (`config/config.php` ignored); raw PHP errors never appear when `DEBUG` is false; 403/404/500 pages show.
- [ ] Accessibility basics: visible focus outlines, contrast, labels on all inputs, status not shown by color alone.
- [ ] **Access matrix (2.4):** log in as staff and open every admin URL directly (services, reports, users, void pages) → each shows the 403 page; logged out → login; admin can open everything; staff land on the orders list and admin on the dashboard; staff never see revenue totals.
- [ ] **Usability test (2.5):** ask 3 people outside the team (one acting as staff, one as admin, one as a customer on their own phone) to do the tasks below without help. Record time, mistakes, and confusing words, then fix the top issues. Staff tasks: create an order and print the ticket (target 90 seconds), find an order by ticket number (15 seconds), record a partial payment, advance a status. Admin tasks: find what needs attention, add a service, void a payment. Customer task: track an order from a claim ticket.
- [ ] Empty states, inline errors, confirmations for risky actions, and double-submit protection exist on every module's pages.
- [ ] Mobile-first check at 360px, 390px, 768px, 1024px, and 1280px (browser dev tools device mode, plus one real phone if possible): no horizontal scroll, tables readable as cards on mobile, tap targets at least 44px, forms usable with the on-screen keyboard (correct input types), menu works, ticket prints correctly, sticky action bar never hides content.
- [ ] Fresh XAMPP install + `schema.sql` + `seed.sql` + `docs/SETUP.md` steps = working app.

**Acceptance criteria:** every checklist item passes and is ticked by a member other than the author.

---

### Phase 7 — Documentation & Defense Prep

- [ ] **Tech Alignment map:** one page or slide showing exactly where each requirement is met (HTML5 + Tailwind, PHP CRUD, MySQL related tables, no external tools).
- [ ] Final ERD, screenshots, and a short user guide.
- [ ] Update the pitch deck (slides 3 and 5) with the final Must/Should/Could scope and module ownership.
- [ ] For the CRUD requirement, point to the **customers** module as the full Create/Read/Update/Delete example; explain that services and users are deactivated, and orders and payments are voided, instead of deleted by design.
- [ ] Demo script: one order from intake to claim, then the reports. Each member presents their own module and can explain their SQL and PHP.
- [ ] Backup: database export (`washflow.sql`) and a zipped copy of the project.
- [ ] Optional: brief "future improvements" list (SMS notification, customer-facing tracking page, analytics).

---

## 5. Definition of Done (per module)

A module is done only when all of these are true:
1. Matches its Stitch prototype screen(s) in **both mobile and desktop** versions, built mobile-first and verified at 360px and 1280px.
2. Uses only the allowed stack (Section 1).
3. Full CRUD (or the read-only queries for reports) works with server-side validation.
4. All queries use prepared statements; all output is escaped.
5. Protected by `auth_check.php` with the right role; every POST form has a CSRF token verified server-side; links use `url()`; lists are paginated; voided rows are excluded from totals; the page follows the access matrix (2.4) and the UX requirements (2.5).
6. Its acceptance criteria in Phase 4 pass.
7. Merged to `main` without breaking other modules.

---

## 6. Instructions for the AI Coding Agent

Copy this block into the agent's system/project instructions:

```
You are helping build WashFlow, a laundry order-tracking web app for a school project.

STRICT STACK: HTML5 semantic markup + Tailwind CSS, plain PHP 8 (mysqli or PDO), MySQL.
FORBIDDEN: PHP frameworks, Composer packages, JS frameworks/libraries, chart libraries,
ORMs, Bootstrap, jQuery, or anything outside the stack. If something seems to require
them, solve it with the allowed stack or ask me first.

Rules: prepared statements always; escape all output with e(); password_hash/verify;
server-side validation on every form; POST/Redirect/GET; money as DECIMAL computed on
the server; snake_case names; short comments; readable student-level code.

MOBILE-FIRST: design and code for ~360px first. Unprefixed Tailwind classes = mobile
layout; enhance only with sm:/md:/lg:/xl:. Viewport meta tag required. No horizontal
scroll at 360px, tables become stacked cards on mobile, touch targets >= 44px, single-column
forms with 16px inputs and correct input types, top-bar menu on mobile and sidebar from lg:.

AUTH & MONEY: track.php is the ONLY public page; every other page includes auth_check.php.
Login lockout (5 attempts / 15 min) is stored in the database; idle timeout is 30 minutes;
session cookies use httponly + samesite (+ secure on HTTPS). Balance due is always
orders.total_amount minus SUM(payments.amount), computed on the server; payments are
never edited or deleted (admins VOID them with a reason; voided rows are excluded from all
totals); an order cannot be Claimed with a balance. Users are deactivated, never deleted.
EVERY POST form has a CSRF token verified server-side (csrf_field()/csrf_verify()). Use url()
for all links, redirects, and assets (BASE_URL). Timezone Asia/Manila in PHP and MySQL.
display_errors off unless DEBUG. Paginate lists (15 per page). config/config.php is gitignored.
Build in tier order: MUST, then SHOULD, then COULD (ROADMAP section 2.3); ask before starting a COULD item.
ROLES: follow the access matrix (ROADMAP 2.4). home_url(): admin -> reports/dashboard.php, staff ->
orders/index.php. Admin-only pages call require_role('admin') (staff get errors/403.php). Enforce roles
on the server in every page and POST handler; hiding a link is not enough.
UX: follow ROADMAP 2.5: plain language, one primary action per screen, quick search, sensible defaults,
inline errors, empty states, confirmations for risky actions, disabled submit after click, 16px+ text
(18px+ on the customer page track.php), friendly wording.

Read ROADMAP.md first. Work on ONE task at a time from the current phase, follow the
folder structure in section 3.1, follow the design in docs/prototype/, and stop after
each task so I can review. Never modify the schema without telling me. After each task,
list which acceptance criteria it satisfies and what I should test manually.
```

**Suggested workflow with the agent**
1. Give it this file and the Stitch screenshots.
2. Ask for one task at a time (e.g. "Do Phase 3: `config/db.php` and `includes/functions.php` only").
3. Run and test it yourself in XAMPP before moving on.
4. Commit after each working task.
5. When something breaks, paste the exact error message and the relevant file back to the agent.

---

## 7. Schedule (week by week)

Dates are placeholders. Replace `[start date]` and the deadlines with your real ones, then adjust the number of weeks. If you have fewer weeks, merge weeks 5–6 and drop the stretch goal first.

| Week | Dates | Focus | Checkpoint (team review at end of week) |
|---|---|---|---|
| 1 | [start date] – [+6 days] | **Phase 1:** Stitch prototype (mobile first, then desktop) for screens 1–16; repo and XAMPP setup; agree on `mysqli` vs `PDO` and how Tailwind is loaded; draft ERD | Prototype approved by all five members; repo created; everyone can run XAMPP |
| 2 | [ ] | **Phase 2:** finalize `schema.sql` + `seed.sql` (including payments); **Phase 3a–3b:** config, helpers, shared mobile-first layout | Fresh import works; layout renders at 360px and 1280px |
| 3 | [ ] | **Phase 3c–3e:** secure login (lockout, idle timeout, cookie flags), password change, user management; start **4A** Services (Amate) | Login, lockout, timeout and user pages pass their acceptance criteria; branches can now merge into `main` |
| 4 | [ ] | **4A** finish; **4B** customers + order intake with optional first payment (Tejada); **4C** orders list and detail (De Asis); **4D** dashboard skeleton (Parreño) | Create an order end to end and see it in the orders list |
| 5 | [ ] | **4B** claim ticket; **4C** status advance + **payments and receipt**; **4D** daily sales, pending pickup, outstanding balances | Partial payment → receipt → claim blocked until paid |
| 6 | [ ] | **4D** public `track.php`; merge everything (Phase 5 integration starts); fix layout drift on mobile and desktop | Full flow demo: order → track → pay → claim → reports |
| 7 | [ ] | **Phase 5** finish; **Phase 6** security and QA checklist; bug fixing; stretch analytics only if everything else is green | Phase 6 checklist signed off by someone other than the author |
| 8 | [ ] | **Phase 7:** Tech Alignment map, user guide, defense Q&A, demo script, rehearsal, database backup and zip | Two full rehearsals; final zip and SQL export saved |

**Weekly rhythm**
- Start of week: 15-minute planning (who does which task, blockers).
- Mid-week: merge `main` into each branch to avoid big conflicts.
- End of week: 30-minute checkpoint using the last column above; do not start the next week's tasks if the checkpoint is not met.
- Use the "Re-plan the schedule" prompt in `WashFlow-PROMPTS.md` if you fall behind, and cut in the order given in section 2.3 ("Cut order").

**Per-member focus**

| Member | Weeks 1–2 | Weeks 3–4 | Weeks 5–6 | Weeks 7–8 |
|---|---|---|---|---|
| Tejada | Screens 3, 4, 7 in Stitch | Customers + order intake | Claim ticket, payment at intake | QA of intake, defense: intake module |
| De Asis | Screens 5, 6, 13, 14 | Orders list + detail | Status flow, payments, receipts | QA of status/payments, defense: payments |
| Parreño | Screens 2, 9, 10, 15, 16 | Dashboard | Reports, balances, `track.php` | QA of reports/tracking, defense: reports |
| Amate | Screen 8 | Services and pricing (done early) | Help integrate; write seed data and test cases | Test plan, docs, defense: pricing |
| Navarro | Screens 1, 11, 12; foundation | Auth, security, users | Integration and merges | Security audit, defense: auth and integration |
