# WashFlow Defense Q&A

These answers describe the current implementation, not planned features.

1. **What technologies does WashFlow use?**  
   Semantic HTML, Tailwind CSS, plain PHP 8, `mysqli`, and a MySQL/MariaDB database. Small interface behaviors use vanilla JavaScript.

2. **Why use prepared statements?**  
   User-provided values are bound as parameters instead of being joined into SQL strings. This keeps values separate from query structure and helps prevent SQL injection.

3. **How does the database meet the relational requirement?**  
   `sql/schema.sql` defines InnoDB tables with foreign keys. For example, `orders.customer_id` references `customers.id`, `order_items.order_id` references `orders.id`, and `payments.order_id` references `orders.id`.

4. **Which module demonstrates full CRUD?**  
   Customers: create and delete are in `customers/create.php` and `customers/delete.php`; read/list is in `customers/index.php`; update is in `customers/edit.php`.

5. **Why are services and users deactivated instead of deleted?**  
   Existing orders, payments, and audit history may refer to them. Deactivation stops future use while preserving those references.

6. **Why are orders and payments voided instead of edited or deleted?**  
   They are transaction records. Voiding records a correction reason and actor while retaining the original history. Voided values are excluded from applicable balances and reports.

7. **How is an order total calculated?**  
   `orders/create.php` reloads active service prices from the database, validates quantities, calculates line subtotals and total in PHP, and stores them as decimal values. A browser-submitted total is not trusted.

8. **What is a price snapshot?**  
   `order_items.unit_price_snapshot` stores the service unit price used when that order was created. Editing a service price later does not rewrite the historical order line.

9. **How is a balance due computed?**  
   The application subtracts the sum of non-voided payments from the order total. Shared balance logic is in `includes/functions.php`; report queries also exclude voided orders and payments.

10. **Why use transactions?**  
    Order creation writes an order, its items, and possibly an initial payment. Payment recording writes a payment and updates payment status. Transactions ensure related changes commit together or roll back together.

11. **How are rapid or duplicate overpayments prevented?**  
    `payments/create.php` starts a transaction, locks the order row with `SELECT ... FOR UPDATE`, recomputes the non-voided balance, validates the submitted amount, then inserts the payment and recalculates payment status before committing.

12. **How are order status transitions enforced?**  
    `orders/update_status.php` checks POST and CSRF, locks the order, derives the only allowed next status with `next_status()`, and rejects a skipped or invalid transition. Claiming is blocked if any balance remains.

13. **How are passwords stored?**  
    Passwords are hashed with PHP's `password_hash()` and checked with `password_verify()`. Passwords are not stored in plaintext. New and reset accounts are required to change the temporary password.

14. **How does login lockout work?**  
    Failed login count and lock expiry are stored on the user row (`failed_attempts`, `locked_until`). After five failures, the account is locked for 15 minutes. Storing this in MySQL means it is not cleared by browser cookies.

15. **What session protections are used?**  
    `includes/auth_check.php` sets strict session mode and cookie flags before `session_start()`, and the login regenerates the session ID. Protected requests also check the idle timeout and whether the user is still active.

16. **How do admin-only pages stay protected?**  
    Admin pages call `require_admin()` or `require_role('admin')` server-side. The navigation hides admin links from staff, but access control does not rely on that hiding.

17. **How does the public tracking page protect customer privacy?**  
    A lookup must match both a ticket number and the last four digits of the customer's normalized phone number. The result returns only the first name and limited order details for that match; it does not show a full name, address, phone, staff identity, or other orders.

18. **How is public tracking throttled?**  
    Attempts are logged in `tracking_attempts`. The page counts failed attempts by IP and by ticket over 15 minutes and applies a five-failure threshold. The limit is database-backed rather than cookie-only.

19. **How does the interface work on phones and larger screens?**  
    Tailwind's unprefixed classes define the small-screen layout. `sm:`, `md:`, and `lg:` classes progressively add wider layouts. Lists provide card presentations on mobile and tables from `md:` where implemented; navigation switches from a mobile menu to a desktop sidebar at `lg:`.

20. **Does the tracking page only read the database?**  
    It reads matching order/customer/service/payment data for the response, but it also writes each attempt to `tracking_attempts` to support throttling. It does not update customer or order records.
