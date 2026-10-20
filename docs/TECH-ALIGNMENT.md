# WashFlow Tech Alignment

This map describes the implemented WashFlow application and its supporting SQL. The Stitch exports under `docs/prototype/` are visual references, not pages loaded by the application.

| Instructor requirement | Concrete evidence | Proof of alignment |
|---|---|---|
| HTML5 semantic structure + Tailwind CSS | Shared layout: `includes/header.php`, `includes/nav.php`, `includes/footer.php`; examples: `orders/index.php`, `orders/view.php`, `track.php` | Pages use semantic elements such as `header`, `nav`, `main`, `section`, `form`, and `table`. Tailwind utility classes provide styling and mobile-first layouts, with the shared header loading the project's Tailwind CDN script. |
| PHP CRUD operations | Customer CRUD: `customers/create.php`, `customers/index.php`, `customers/edit.php`, `customers/delete.php`; order intake: `orders/create.php`; service management: `services/create.php`, `services/edit.php`, `services/toggle.php`; user management: `users/create.php`, `users/edit.php`, `users/reset_password.php` | The customer module is the full Create/Read/Update/Delete example. Services and users are deactivated rather than deleted; orders and payments are voided rather than edited or deleted so their histories are retained. PHP uses `mysqli` prepared statements for database operations. |
| MySQL relational persistence with at least two related tables | Schema: `sql/schema.sql`; seed data: `sql/seed.sql`; setup: `docs/SETUP.md` | The InnoDB schema defines seven tables and multiple foreign-key relationships. An order connects a customer and creator; order items connect orders and services; payments connect orders and receiving/voiding users. |
| No tools outside the course stack | Runtime PHP pages and shared includes; `assets/js/order_form.js`; `includes/nav.php`; `orders/ticket.php`; `includes/header.php` | Runtime code is plain PHP 8 with `mysqli`, MySQL, semantic HTML, Tailwind CSS, and small vanilla JavaScript behaviors for the order form, menu, confirmation, and printing. There are no runtime PHP or JavaScript frameworks, package-managed libraries, ORMs, or chart libraries. `docs/prototype/` contains design references only. |

## Relational schema evidence

All tables below are declared in `sql/schema.sql` using InnoDB.

| Relationship | Foreign key | Why it is related |
|---|---|---|
| Customers → orders | `orders.customer_id` → `customers.id` | Each order belongs to a customer. |
| Users → orders | `orders.created_by` → `users.id`; `orders.voided_by` → `users.id` | Records who created or voided an order. |
| Orders → order items | `order_items.order_id` → `orders.id` (`ON DELETE CASCADE`) | Stores each service line belonging to an order. Orders are voided rather than deleted in normal application use. |
| Services → order items | `order_items.service_id` → `services.id` | Identifies the service whose price snapshot was used for the order line. |
| Orders → payments | `payments.order_id` → `orders.id` | Stores append-only payment records belonging to an order. |
| Users → payments | `payments.received_by` → `users.id`; `payments.voided_by` → `users.id` | Records who received or voided a payment. |

`tracking_attempts` stores public tracking attempt IP, ticket, success flag, and timestamp for database-backed throttling. It is intentionally not a foreign-key child of customers or orders, so failed attempts do not require a matching customer or order.

## Stack scope

The project uses MySQL through WAMP/phpMyAdmin and plain PHP `mysqli`; setup and seed instructions are in `docs/SETUP.md`. The application uses the Tailwind loading method configured in `includes/header.php`. The prototype HTML exports are references and are not included by the application layout or navigation.
